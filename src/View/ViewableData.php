<?php

namespace SilverStripe\View;

use ArrayIterator;
use Exception;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\SSViewer;
use Traversable;
use UnexpectedValueException;

/**
 * A ViewableData object is any object that can be rendered into a template/view.
 *
 * A view interrogates the object being currently rendered in order to get data to render into the template. This data
 * is provided and automatically escaped by ViewableData. Any class that needs to be available to a view (controllers,
 * {@link DataObject}s, page controls) should inherit from this class.
 */
class ViewableData implements IteratorAggregate
{
    use Extensible {
        defineMethods as extensibleDefineMethods;
    }
    use Injectable;
    use Configurable;

    /**
     * An array of objects to cast certain fields to. This is set up as an array in the format:
     *
     * <code>
     * public static $casting = array (
     *     'FieldName' => 'ClassToCastTo(Arguments)'
     * );
     * </code>
     *
     * @var array
     * @config
     */
    private static $casting = [
        'CSSClasses' => 'Varchar'
    ];

    /**
     * The default object to cast scalar fields to if casting information is not specified, and casting to an object
     * is required.
     *
     * @var string
     * @config
     */
    private static $default_cast = 'Text';

    /**
     * @var array
     */
    private static $casting_cache = [];

    /**
     * Acts as a PHP 8.2+ compliant replacement for dynamic properties
     */
    private array $dynamicData = [];

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * A failover object to attempt to get data from if it is not present on this object.
     *
     * @var ViewableData
     */
    protected $failover;

    /**
     * @var ViewableData
     */
    protected $customisedObject;

    /**
     * @var array
     */
    private $objCache = [];

    public function __construct()
    {
    }

    // -----------------------------------------------------------------------------------------------------------------

    // FIELD GETTERS & SETTERS -----------------------------------------------------------------------------------------

    /**
     * Check if a field exists on this object or its failover.
     * Note that, unlike the core isset() implementation, this will return true if the property is defined
     * and set to null.
     *
     * @param string $property
     * @return bool
     */
    public function __isset($property)
    {
        // getField() isn't a field-specific getter and shouldn't be treated as such
        if (strtolower($property ?? '') !== 'field' && $this->hasMethod("get$property")) {
            return true;
        }
        if ($this->hasField($property)) {
            return true;
        }
        if ($this->failover) {
            return isset($this->failover->$property);
        }

        return false;
    }

    /**
     * Get the value of a property/field on this object. This will check if a method called get{$property} exists, then
     * check if a field is available using {@link ViewableData::getField()}, then fall back on a failover object.
     *
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        // getField() isn't a field-specific getter and shouldn't be treated as such
        $method = "get$property";
        if (strtolower($property ?? '') !== 'field' && $this->hasMethod($method) && $this->isAccessibleMethod($method)) {
            return $this->$method();
        }
        if ($this->hasField($property)) {
            return $this->getField($property);
        }
        if ($this->failover) {
            return $this->failover->$property;
        }

        return null;
    }

    /**
     * Set a property/field on this object. This will check for the existence of a method called set{$property}, then
     * use the {@link ViewableData::setField()} method.
     *
     * @param string $property
     * @param mixed $value
     */
    public function __set($property, $value)
    {
        $this->objCacheClear();
        $method = "set$property";

        if ($this->hasMethod($method) && $this->isAccessibleMethod($method)) {
            $this->$method($value);
        } else {
            $this->setField($property, $value);
        }
    }

    /**
     * Set a failover object to attempt to get data from if it is not present on this object.
     *
     * @param ViewableData $failover
     */
    public function setFailover(ViewableData $failover)
    {
        // Ensure cached methods from previous failover are removed
        if ($this->failover) {
            $this->removeMethodsFrom('failover');
        }

        $this->failover = $failover;
        $this->defineMethods();
    }

    /**
     * Get the current failover object if set
     *
     * @return ViewableData|null
     */
    public function getFailover()
    {
        return $this->failover;
    }

    /**
     * Check if a field exists on this object. This should be overloaded in child classes.
     *
     * @param string $field
     * @return bool
     */
    public function hasField($field)
    {
        return property_exists($this, $field) || $this->hasDynamicData($field);
    }

    /**
     * Get the value of a field on this object. This should be overloaded in child classes.
     *
     * @param string $field
     * @return mixed
     */
    public function getField($field)
    {
        if ($this->isAccessibleProperty($field)) {
            return $this->$field;
        }
        return $this->getDynamicData($field);
    }

    /**
     * Set a field on this object. This should be overloaded in child classes.
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function setField($field, $value)
    {
        $this->objCacheClear();
        // prior to PHP 8.2 support ViewableData::setField() simply used `$this->field = $value;`
        // so the following logic essentially mimics this behaviour, though without the use
        // of now deprecated dynamic properties
        if ($this->isAccessibleProperty($field)) {
            $this->$field = $value;
        }
        return $this->setDynamicData($field, $value);
    }

    public function getDynamicData(string $field): mixed
    {
        return $this->hasDynamicData($field) ? $this->dynamicData[$field] : null;
    }

    public function setDynamicData(string $field, mixed $value): static
    {
        $this->dynamicData[$field] = $value;
        return $this;
    }

    public function hasDynamicData(string $field): bool
    {
        return array_key_exists($field, $this->dynamicData);
    }

    /**
     * Returns true if a method exists for the current class which isn't private.
     * Also returns true for private methods if $this is ViewableData (not a subclass)
     */
    private function isAccessibleMethod(string $method): bool
    {
        if (!method_exists($this, $method)) {
            // Methods added via extensions are accessible
            return $this->hasCustomMethod($method);
        }
        // All methods defined on ViewableData are accessible to ViewableData
        if (static::class === ViewableData::class) {
            return true;
        }
        // Private methods defined on subclasses are not accessible to ViewableData
        $reflectionMethod = new ReflectionMethod($this, $method);
        return !$reflectionMethod->isPrivate();
    }

    /**
     * Returns true if a property exists for the current class which isn't private.
     * Also returns true for private properties if $this is ViewableData (not a subclass)
     */
    private function isAccessibleProperty(string $property): bool
    {
        if (!property_exists($this, $property)) {
            return false;
        }
        if (static::class === ViewableData::class) {
            return true;
        }
        $reflectionProperty = new ReflectionProperty($this, $property);
        return !$reflectionProperty->isPrivate();
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Add methods from the {@link ViewableData::$failover} object, as well as wrapping any methods prefixed with an
     * underscore into a {@link ViewableData::cachedCall()}.
     *
     * @throws LogicException
     */
    public function defineMethods()
    {
        if ($this->failover && !is_object($this->failover)) {
            throw new LogicException("ViewableData::\$failover set to a non-object");
        }
        if ($this->failover) {
            $this->addMethodsFrom('failover');

            if (isset($_REQUEST['debugfailover'])) {
                $class = static::class;
                $failoverClass = get_class($this->failover);
                Debug::message("$class created with a failover class of {$failoverClass}");
            }
        }
        $this->extensibleDefineMethods();
    }

    /**
     * Merge some arbitrary data in with this object. This method returns a {@link ViewableData_Customised} instance
     * with references to both this and the new custom data.
     *
     * Note that any fields you specify will take precedence over the fields on this object.
     *
     * @param array|ViewableData $data
     * @return ViewableData_Customised
     */
    public function customise($data)
    {
        if (is_array($data) && (empty($data) || ArrayLib::is_associative($data))) {
            $data = new ArrayData($data);
        }

        if ($data instanceof ViewableData) {
            return new ViewableData_Customised($this, $data);
        }

        throw new InvalidArgumentException(
            'ViewableData->customise(): $data must be an associative array or a ViewableData instance'
        );
    }

    /**
     * Return true if this object "exists" i.e. has a sensible value
     *
     * This method should be overridden in subclasses to provide more context about the classes state. For example, a
     * {@link DataObject} class could return false when it is deleted from the database
     *
     * @return bool
     */
    public function exists()
    {
        return true;
    }

    /**
     * @return string the class name
     */
    public function __toString()
    {
        return static::class;
    }

    /**
     * @return ViewableData
     */
    public function getCustomisedObj()
    {
        return $this->customisedObject;
    }

    /**
     * @param ViewableData $object
     */
    public function setCustomisedObj(ViewableData $object)
    {
        $this->customisedObject = $object;
    }

    // CASTING ---------------------------------------------------------------------------------------------------------

    /**
     * Return the "casting helper" (a piece of PHP code that when evaluated creates a casted value object)
     * for a field on this object. This helper will be a subclass of DBField.
     *
     * @param string $field
     * @return string Casting helper As a constructor pattern, and may include arguments.
     * @throws Exception
     */
    public function castingHelper($field)
    {
        // Get casting if it has been configured.
        // DB fields and PHP methods are all case insensitive so we normalise casing before checking.
        $specs = array_change_key_case(static::config()->get('casting'), CASE_LOWER);
        $fieldLower = strtolower($field);
        if (isset($specs[$fieldLower])) {
            return $specs[$fieldLower];
        }

        // If no specific cast is declared, fall back to failover.
        // Note that if there is a failover, the default_cast will always
        // be drawn from this object instead of the top level object.
        $failover = $this->getFailover();
        if ($failover) {
            $cast = $failover->castingHelper($field);
            if ($cast) {
                return $cast;
            }
        }

        // Fall back to default_cast
        $default = $this->config()->get('default_cast');
        if (empty($default)) {
            throw new Exception("No default_cast");
        }
        return $default;
    }

    /**
     * Get the class name a field on this object will be casted to.
     *
     * @param string $field
     * @return string
     */
    public function castingClass($field)
    {
        // Strip arguments
        $spec = $this->castingHelper($field);
        return trim(strtok($spec ?? '', '(') ?? '');
    }

    /**
     * Return the string-format type for the given field.
     *
     * @param string $field
     * @return string 'xml'|'raw'
     */
    public function escapeTypeForField($field)
    {
        $class = $this->castingClass($field) ?: $this->config()->get('default_cast');

        /** @var DBField $type */
        $type = Injector::inst()->get($class, true);
        return $type->config()->get('escape_type');
    }

    // TEMPLATE ACCESS LAYER -------------------------------------------------------------------------------------------

    /**
     * Render this object into the template, and get the result as a string. You can pass one of the following as the
     * $template parameter:
     *  - a template name (e.g. Page)
     *  - an array of possible template names - the first valid one will be used
     *  - an SSViewer instance
     *
     * @param string|array|SSViewer $template the template to render into
     * @param array $customFields fields to customise() the object with before rendering
     * @return DBHTMLText
     */
    public function renderWith($template, $customFields = null)
    {
        if (!is_object($template)) {
            $template = SSViewer::create($template);
        }

        $data = $this->getCustomisedObj() ?: $this;

        if ($customFields instanceof ViewableData) {
            $data = $data->customise($customFields);
        }
        if ($template instanceof SSViewer) {
            return $template->process($data, is_array($customFields) ? $customFields : null);
        }

        throw new UnexpectedValueException(
            "ViewableData::renderWith(): unexpected " . get_class($template) . " object, expected an SSViewer instance"
        );
    }

    /**
     * Generate the cache name for a field
     *
     * @param string $fieldName Name of field
     * @param array $arguments List of optional arguments given
     * @return string
     */
    protected function objCacheName($fieldName, $arguments)
    {
        return $arguments
            ? $fieldName . ":" . var_export($arguments, true)
            : $fieldName;
    }

    /**
     * Get a cached value from the field cache
     *
     * @param string $key Cache key
     * @return mixed
     */
    protected function objCacheGet($key)
    {
        if (isset($this->objCache[$key])) {
            return $this->objCache[$key];
        }
        return null;
    }

    /**
     * Store a value in the field cache
     *
     * @param string $key Cache key
     * @param mixed $value
     * @return $this
     */
    protected function objCacheSet($key, $value)
    {
        $this->objCache[$key] = $value;
        return $this;
    }

    /**
     * Clear object cache
     *
     * @return $this
     */
    protected function objCacheClear()
    {
        $this->objCache = [];
        return $this;
    }

    /**
     * Get the value of a field on this object, automatically inserting the value into any available casting objects
     * that have been specified.
     *
     * @param string $fieldName
     * @param array $arguments
     * @param bool $cache Cache this object
     * @param string $cacheName a custom cache name
     * @return Object|DBField
     */
    public function obj($fieldName, $arguments = [], $cache = false, $cacheName = null)
    {
        if (!$cacheName && $cache) {
            $cacheName = $this->objCacheName($fieldName, $arguments);
        }

        // Check pre-cached value
        $value = $cache ? $this->objCacheGet($cacheName) : null;
        if ($value !== null) {
            return $value;
        }

        // Load value from record
        if ($this->hasMethod($fieldName)) {
            $value = call_user_func_array([$this, $fieldName], $arguments ?: []);
        } else {
            $value = $this->$fieldName;
        }

        // Cast object
        if (!is_object($value)) {
            // Force cast
            $castingHelper = $this->castingHelper($fieldName);
            $valueObject = Injector::inst()->create($castingHelper, $fieldName);
            $valueObject->setValue($value, $this);
            $value = $valueObject;
        }

        // Record in cache
        if ($cache) {
            $this->objCacheSet($cacheName, $value);
        }

        return $value;
    }

    /**
     * A simple wrapper around {@link ViewableData::obj()} that automatically caches the result so it can be used again
     * without re-running the method.
     *
     * @param string $fieldName
     * @param array $arguments
     * @param string $identifier an optional custom cache identifier
     * @return Object|DBField
     */
    public function cachedCall($fieldName, $arguments = [], $identifier = null)
    {
        return $this->obj($fieldName, $arguments, true, $identifier);
    }

    /**
     * Checks if a given method/field has a valid value. If the result is an object, this will return the result of the
     * exists method, otherwise will check if the result is not just an empty paragraph tag.
     *
     * @param string $field
     * @param array $arguments
     * @param bool $cache
     * @return bool
     */
    public function hasValue($field, $arguments = [], $cache = true)
    {
        $result = $this->obj($field, $arguments, $cache);
        return $result->exists();
    }

    /**
     * Get the string value of a field on this object that has been suitable escaped to be inserted directly into a
     * template.
     *
     * @param string $field
     * @param array $arguments
     * @param bool $cache
     * @return string
     */
    public function XML_val($field, $arguments = [], $cache = false)
    {
        $result = $this->obj($field, $arguments, $cache);
        // Might contain additional formatting over ->XML(). E.g. parse shortcodes, nl2br()
        return $result->forTemplate();
    }

    /**
     * Get an array of XML-escaped values by field name
     *
     * @param array $fields an array of field names
     * @return array
     */
    public function getXMLValues($fields)
    {
        $result = [];

        foreach ($fields as $field) {
            $result[$field] = $this->XML_val($field);
        }

        return $result;
    }

    // ITERATOR SUPPORT ------------------------------------------------------------------------------------------------

    /**
     * Return a single-item iterator so you can iterate over the fields of a single record.
     *
     * This is useful so you can use a single record inside a <% control %> block in a template - and then use
     * to access individual fields on this object.
     *
     * @deprecated 5.2.0 Will be removed without equivalent functionality
     *
     * @return ArrayIterator
     */
    public function getIterator(): Traversable
    {
        Deprecation::notice('5.2.0', 'Will be removed without equivalent functionality');
        return new ArrayIterator([$this]);
    }

    // UTILITY METHODS -------------------------------------------------------------------------------------------------

    /**
     * Find appropriate templates for SSViewer to use to render this object
     *
     * @param string $suffix
     * @return array
     */
    public function getViewerTemplates($suffix = '')
    {
        return SSViewer::get_templates_by_class(static::class, $suffix, ViewableData::class);
    }

    /**
     * When rendering some objects it is necessary to iterate over the object being rendered, to do this, you need
     * access to itself.
     *
     * @return ViewableData
     */
    public function Me()
    {
        return $this;
    }

    /**
     * Get part of the current classes ancestry to be used as a CSS class.
     *
     * This method returns an escaped string of CSS classes representing the current classes ancestry until it hits a
     * stop point - e.g. "Page DataObject ViewableData".
     *
     * @param string $stopAtClass the class to stop at (default: ViewableData)
     * @return string
     * @uses ClassInfo
     */
    public function CSSClasses($stopAtClass = ViewableData::class)
    {
        $classes       = [];
        $classAncestry = array_reverse(ClassInfo::ancestry(static::class) ?? []);
        $stopClasses   = ClassInfo::ancestry($stopAtClass);

        foreach ($classAncestry as $class) {
            if (in_array($class, $stopClasses ?? [])) {
                break;
            }
            $classes[] = $class;
        }

        // optionally add template identifier
        if (isset($this->template) && !in_array($this->template, $classes ?? [])) {
            $classes[] = $this->template;
        }

        // Strip out namespaces
        $classes = preg_replace('#.*\\\\#', '', $classes ?? '');

        return Convert::raw2att(implode(' ', $classes));
    }

    /**
     * Return debug information about this object that can be rendered into a template
     *
     * @return ViewableData_Debugger
     */
    public function Debug()
    {
        return new ViewableData_Debugger($this);
    }
}
