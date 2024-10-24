<?php

namespace SilverStripe\Model;

use InvalidArgumentException;
use LogicException;
use ReflectionMethod;
use ReflectionProperty;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\Debug;
use SilverStripe\Core\ArrayLib;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Model\ArrayData;
use SilverStripe\View\CastingService;
use SilverStripe\View\SSViewer;
use UnexpectedValueException;

/**
 * A ModelData object is any object that can be rendered into a template/view.
 *
 * A view interrogates the object being currently rendered in order to get data to render into the template. This data
 * is provided and automatically escaped by ModelData. Any class that needs to be available to a view (controllers,
 * {@link DataObject}s, page controls) should inherit from this class.
 */
class ModelData
{
    use Extensible {
        defineMethods as extensibleDefineMethods;
    }
    use Injectable;
    use Configurable;

    /**
     * An array of DBField classes to cast certain fields to. This is set up as an array in the format:
     *
     * <code>
     * public static $casting = array (
     *     'FieldName' => 'ClassToCastTo(Arguments)'
     * );
     * </code>
     */
    private static array $casting = [
        'CSSClasses' => 'Varchar',
        'forTemplate' => 'HTMLText',
    ];

    /**
     * The default class to cast scalar fields to if casting information is not specified, and casting to an object
     * is required.
     * This can be any injectable service name but must resolve to a DBField subclass.
     *
     * If null, casting will be determined based on the type of value (e.g. integers will be cast to DBInt)
     */
    private static ?string $default_cast = null;

    /**
     * Acts as a PHP 8.2+ compliant replacement for dynamic properties
     */
    private array $dynamicData = [];

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * A failover object to attempt to get data from if it is not present on this object.
     */
    protected ?ModelData $failover = null;

    protected ?ModelData $customisedObject = null;

    private array $objCache = [];

    public function __construct()
    {
        // no-op
    }

    // -----------------------------------------------------------------------------------------------------------------

    // FIELD GETTERS & SETTERS -----------------------------------------------------------------------------------------

    /**
     * Check if a field exists on this object or its failover.
     * Note that, unlike the core isset() implementation, this will return true if the property is defined
     * and set to null.
     */
    public function __isset(string $property): bool
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
     * check if a field is available using {@link ModelData::getField()}, then fall back on a failover object.
     */
    public function __get(string $property): mixed
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
     * use the {@link ModelData::setField()} method.
     */
    public function __set(string $property, mixed $value): void
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
     */
    public function setFailover(ModelData $failover): void
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
     */
    public function getFailover(): ?ModelData
    {
        return $this->failover;
    }

    /**
     * Check if a field exists on this object. This should be overloaded in child classes.
     */
    public function hasField(string $fieldName): bool
    {
        return property_exists($this, $fieldName) || $this->hasDynamicData($fieldName);
    }

    /**
     * Get the value of a field on this object. This should be overloaded in child classes.
     */
    public function getField(string $fieldName): mixed
    {
        if ($this->isAccessibleProperty($fieldName)) {
            return $this->$fieldName;
        }
        return $this->getDynamicData($fieldName);
    }

    /**
     * Set a field on this object. This should be overloaded in child classes.
     */
    public function setField(string $fieldName, mixed $value): static
    {
        $this->objCacheClear();
        // prior to PHP 8.2 support ModelData::setField() simply used `$this->field = $value;`
        // so the following logic essentially mimics this behaviour, though without the use
        // of now deprecated dynamic properties
        if ($this->isAccessibleProperty($fieldName)) {
            $this->$fieldName = $value;
        }
        return $this->setDynamicData($fieldName, $value);
    }

    public function getDynamicData(string $field): mixed
    {
        return $this->hasDynamicData($field) ? $this->dynamicData[$field] : null;
    }

    public function setDynamicData(string $field, mixed $value): static
    {
        $this->objCacheClear();
        $this->dynamicData[$field] = $value;
        return $this;
    }

    public function hasDynamicData(string $field): bool
    {
        return array_key_exists($field, $this->dynamicData);
    }

    /**
     * Returns true if a method exists for the current class which isn't private.
     * Also returns true for private methods if $this is ModelData (not a subclass)
     */
    private function isAccessibleMethod(string $method): bool
    {
        if (!method_exists($this, $method)) {
            // Methods added via extensions are accessible
            return $this->hasCustomMethod($method);
        }
        // All methods defined on ModelData are accessible to ModelData
        if (static::class === ModelData::class) {
            return true;
        }
        // Private methods defined on subclasses are not accessible to ModelData
        $reflectionMethod = new ReflectionMethod($this, $method);
        return !$reflectionMethod->isPrivate();
    }

    /**
     * Returns true if a property exists for the current class which isn't private.
     * Also returns true for private properties if $this is ModelData (not a subclass)
     */
    private function isAccessibleProperty(string $property): bool
    {
        if (!property_exists($this, $property)) {
            return false;
        }
        if (static::class === ModelData::class) {
            return true;
        }
        $reflectionProperty = new ReflectionProperty($this, $property);
        return !$reflectionProperty->isPrivate();
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Add methods from the {@link ModelData::$failover} object
     *
     * @throws LogicException
     */
    public function defineMethods()
    {
        if ($this->failover && !is_object($this->failover)) {
            throw new LogicException("ModelData::\$failover set to a non-object");
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
     * Merge some arbitrary data in with this object. This method returns a {@link ModelDataCustomised} instance
     * with references to both this and the new custom data.
     *
     * Note that any fields you specify will take precedence over the fields on this object.
     */
    public function customise(array|ModelData $data): ModelData
    {
        if (is_array($data) && (empty($data) || ArrayLib::is_associative($data))) {
            $data = new ArrayData($data);
        }

        if ($data instanceof ModelData) {
            return new ModelDataCustomised($this, $data);
        }

        throw new InvalidArgumentException(
            'ModelData->customise(): $data must be an associative array or a ModelData instance'
        );
    }

    /**
     * Return true if this object "exists" i.e. has a sensible value
     *
     * This method should be overridden in subclasses to provide more context about the classes state. For example, a
     * {@link DataObject} class could return false when it is deleted from the database
     */
    public function exists(): bool
    {
        return true;
    }

    /**
     * Return the class name (though subclasses may return something else)
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Return the HTML markup that represents this model when it is directly injected into a template (e.g. using $Me).
     * By default this attempts to render the model using templates based on the class hierarchy.
     */
    public function forTemplate(): string
    {
        return $this->renderWith($this->getViewerTemplates());
    }

    public function getCustomisedObj(): ?ModelData
    {
        return $this->customisedObject;
    }

    public function setCustomisedObj(ModelData $object)
    {
        $this->customisedObject = $object;
    }

    // CASTING ---------------------------------------------------------------------------------------------------------

    /**
     * Return the "casting helper" (an injectable service name)
     * for a field on this object. This helper will be a subclass of DBField.
     */
    public function castingHelper(string $field): ?string
    {
        // Get casting if it has been configured.
        // DB fields and PHP methods are all case insensitive so we normalise casing before checking.
        $specs = array_change_key_case(static::config()->get('casting'), CASE_LOWER);
        $fieldLower = strtolower($field);
        if (isset($specs[$fieldLower])) {
            return $specs[$fieldLower];
        }

        // If no specific cast is declared, fall back to failover.
        $failover = $this->getFailover();
        if ($failover) {
            $cast = $failover->castingHelper($field);
            if ($cast) {
                return $cast;
            }
        }

        return null;
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
     * @param ModelData|array $customFields fields to customise() the object with before rendering
     */
    public function renderWith($template, ModelData|array $customFields = []): DBHTMLText
    {
        if (!is_object($template)) {
            $template = SSViewer::create($template);
        }

        $data = $this->getCustomisedObj() ?: $this;

        if ($customFields instanceof ModelData) {
            $data = $data->customise($customFields);
            $customFields = [];
        }
        if ($template instanceof SSViewer) {
            return $template->process($data, $customFields);
        }

        throw new UnexpectedValueException(
            "ModelData::renderWith(): unexpected " . get_class($template) . " object, expected an SSViewer instance"
        );
    }

    /**
     * Get a cached value from the field cache for a field
     */
    public function objCacheGet(string $fieldName, array $arguments = []): mixed
    {
        $key = $this->objCacheName($fieldName, $arguments);
        if (isset($this->objCache[$key])) {
            return $this->objCache[$key];
        }
        return null;
    }

    /**
     * Store a value in the field cache for a field
     */
    public function objCacheSet(string $fieldName, array $arguments, mixed $value): static
    {
        $key = $this->objCacheName($fieldName, $arguments);
        $this->objCache[$key] = $value;
        return $this;
    }

    /**
     * Clear object cache
     */
    public function objCacheClear(): static
    {
        $this->objCache = [];
        return $this;
    }

    /**
     * Get the value of a field on this object, automatically inserting the value into any available casting objects
     * that have been specified.
     *
     * @return object|DBField|null The specific object representing the field, or null if there is no
     * property, method, or dynamic data available for that field.
     */
    public function obj(
        string $fieldName,
        array $arguments = [],
        bool $cache = false
    ): ?object {
        // Check pre-cached value
        $value = $cache ? $this->objCacheGet($fieldName, $arguments) : null;
        if ($value === null) {
            $hasObj = false;
            // Load value from record
            if ($this->hasMethod($fieldName)) {
                // Try methods first - there's a LOT of logic that assumes this will be checked first.
                $hasObj = true;
                $value = call_user_func_array([$this, $fieldName], $arguments ?: []);
            } else {
                $getter = "get{$fieldName}";
                $hasGetter = $this->hasMethod($getter) && $this->isAccessibleMethod($getter);
                // Try fields and getters if there was no method with that name.
                $hasObj = $this->hasField($fieldName) || $hasGetter;
                if ($hasGetter && !empty($arguments)) {
                    $value = $this->$getter(...$arguments);
                } else {
                    $value = $this->$fieldName;
                }
            }

            // Record in cache
            if ($value !== null && $cache) {
                $this->objCacheSet($fieldName, $arguments, $value);
            }

            // Return null early if there's no backing for this field
            // i.e. no poperty, no method, etc - it just doesn't exist on this model.
            if (!$hasObj && $value === null) {
                return null;
            }
        }

        return CastingService::singleton()->cast($value, $this, $fieldName, true);
    }

    /**
     * Checks if a given method/field has a valid value. If the result is an object, this will return the result of the
     * exists method, otherwise will check if the result is not just an empty paragraph tag.
     */
    public function hasValue(string $field, array $arguments = [], bool $cache = true): bool
    {
        $result = $this->obj($field, $arguments, $cache);
        if ($result instanceof ModelData) {
            return $result->exists();
        }
        return (bool) $result;
    }

    // UTILITY METHODS -------------------------------------------------------------------------------------------------

    /**
     * Find appropriate templates for SSViewer to use to render this object
     */
    public function getViewerTemplates(string $suffix = ''): array
    {
        return SSViewer::get_templates_by_class(static::class, $suffix, ModelData::class);
    }

    /**
     * When rendering some objects it is necessary to iterate over the object being rendered, to do this, you need
     * access to itself.
     */
    public function Me(): static
    {
        return $this;
    }

    /**
     * Get part of the current classes ancestry to be used as a CSS class.
     *
     * This method returns an escaped string of CSS classes representing the current classes ancestry until it hits a
     * stop point - e.g. "Page DataObject ModelData".
     *
     * @param string $stopAtClass the class to stop at (default: ModelData)
     * @uses ClassInfo
     */
    public function CSSClasses(string $stopAtClass = ModelData::class): string
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
     */
    public function Debug(): ModelData|string
    {
        return ModelDataDebugger::create($this);
    }

    /**
     * Generate the cache name for a field
     */
    private function objCacheName(string $fieldName, array $arguments = []): string
    {
        $name = empty($arguments)
            ? $fieldName
            : $fieldName . ":" . var_export($arguments, true);
        return md5($name);
    }
}
