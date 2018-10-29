<?php

namespace SilverStripe\ORM;

use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use LogicException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Resettable;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\FormScaffolder;
use SilverStripe\i18n\i18n;
use SilverStripe\i18n\i18nEntityProvider;
use SilverStripe\ORM\Connect\MySQLSchemaManager;
use SilverStripe\ORM\FieldType\DBClassName;
use SilverStripe\ORM\FieldType\DBComposite;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\Filters\SearchFilter;
use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\ORM\Queries\SQLInsert;
use SilverStripe\ORM\Search\SearchContext;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ViewableData;
use stdClass;

/**
 * A single database record & abstract class for the data-access-model.
 *
 * <h2>Extensions</h2>
 *
 * See {@link Extension} and {@link DataExtension}.
 *
 * <h2>Permission Control</h2>
 *
 * Object-level access control by {@link Permission}. Permission codes are arbitrary
 * strings which can be selected on a group-by-group basis.
 *
 * <code>
 * class Article extends DataObject implements PermissionProvider {
 *  static $api_access = true;
 *
 *  function canView($member = false) {
 *    return Permission::check('ARTICLE_VIEW');
 *  }
 *  function canEdit($member = false) {
 *    return Permission::check('ARTICLE_EDIT');
 *  }
 *  function canDelete() {
 *    return Permission::check('ARTICLE_DELETE');
 *  }
 *  function canCreate() {
 *    return Permission::check('ARTICLE_CREATE');
 *  }
 *  function providePermissions() {
 *    return array(
 *      'ARTICLE_VIEW' => 'Read an article object',
 *      'ARTICLE_EDIT' => 'Edit an article object',
 *      'ARTICLE_DELETE' => 'Delete an article object',
 *      'ARTICLE_CREATE' => 'Create an article object',
 *    );
 *  }
 * }
 * </code>
 *
 * Object-level access control by {@link Group} membership:
 * <code>
 * class Article extends DataObject {
 *   static $api_access = true;
 *
 *   function canView($member = false) {
 *     if(!$member) $member = Security::getCurrentUser();
 *     return $member->inGroup('Subscribers');
 *   }
 *   function canEdit($member = false) {
 *     if(!$member) $member = Security::getCurrentUser();
 *     return $member->inGroup('Editors');
 *   }
 *
 *   // ...
 * }
 * </code>
 *
 * If any public method on this class is prefixed with an underscore,
 * the results are cached in memory through {@link cachedCall()}.
 *
 *
 * @todo Add instance specific removeExtension() which undos loadExtraStatics()
 *  and defineMethods()
 *
 * @property int $ID ID of the DataObject, 0 if the DataObject doesn't exist in database.
 * @property int $OldID ID of object, if deleted
 * @property string $Title
 * @property string $ClassName Class name of the DataObject
 * @property string $LastEdited Date and time of DataObject's last modification.
 * @property string $Created Date and time of DataObject creation.
 * @property string $ObsoleteClassName If ClassName no longer exists this will be set to the legacy value
 */
class DataObject extends ViewableData implements DataObjectInterface, i18nEntityProvider, Resettable
{

    /**
     * Human-readable singular name.
     * @var string
     * @config
     */
    private static $singular_name = null;

    /**
     * Human-readable plural name
     * @var string
     * @config
     */
    private static $plural_name = null;

    /**
     * Allow API access to this object?
     * @todo Define the options that can be set here
     * @config
     */
    private static $api_access = false;

    /**
     * Allows specification of a default value for the ClassName field.
     * Configure this value only in subclasses of DataObject.
     *
     * @config
     * @var string
     */
    private static $default_classname = null;

    /**
     * @deprecated 4.0.0:5.0.0
     * @var bool
     */
    public $destroyed = false;

    /**
     * Data stored in this objects database record. An array indexed by fieldname.
     *
     * Use {@link toMap()} if you want an array representation
     * of this object, as the $record array might contain lazy loaded field aliases.
     *
     * @var array
     */
    protected $record;

    /**
     * If selected through a many_many through relation, this is the instance of the through record
     *
     * @var DataObject
     */
    protected $joinRecord;

    /**
     * Represents a field that hasn't changed (before === after, thus before == after)
     */
    const CHANGE_NONE = 0;

    /**
     * Represents a field that has changed type, although not the loosely defined value.
     * (before !== after && before == after)
     * E.g. change 1 to true or "true" to true, but not true to 0.
     * Value changes are by nature also considered strict changes.
     */
    const CHANGE_STRICT = 1;

    /**
     * Represents a field that has changed the loosely defined value
     * (before != after, thus, before !== after))
     * E.g. change false to true, but not false to 0
     */
    const CHANGE_VALUE = 2;

    /**
     * An array indexed by fieldname, true if the field has been changed.
     * Use {@link getChangedFields()} and {@link isChanged()} to inspect
     * the changed state.
     *
     * @var array
     */
    private $changed;

    /**
     * The database record (in the same format as $record), before
     * any changes.
     * @var array
     */
    protected $original;

    /**
     * Used by onBeforeDelete() to ensure child classes call parent::onBeforeDelete()
     * @var boolean
     */
    protected $brokenOnDelete = false;

    /**
     * Used by onBeforeWrite() to ensure child classes call parent::onBeforeWrite()
     * @var boolean
     */
    protected $brokenOnWrite = false;

    /**
     * Should dataobjects be validated before they are written?
     *
     * Caution: Validation can contain safeguards against invalid/malicious data,
     * and check permission levels (e.g. on {@link Group}). Therefore it is recommended
     * to only disable validation for very specific use cases.
     *
     * @config
     * @var boolean
     */
    private static $validation_enabled = true;

    /**
     * Static caches used by relevant functions.
     *
     * @var array
     */
    protected static $_cache_get_one;

    /**
     * Cache of field labels
     *
     * @var array
     */
    protected static $_cache_field_labels = array();

    /**
     * Base fields which are not defined in static $db
     *
     * @config
     * @var array
     */
    private static $fixed_fields = array(
        'ID' => 'PrimaryKey',
        'ClassName' => 'DBClassName',
        'LastEdited' => 'DBDatetime',
        'Created' => 'DBDatetime',
    );

    /**
     * Override table name for this class. If ignored will default to FQN of class.
     * This option is not inheritable, and must be set on each class.
     * If left blank naming will default to the legacy (3.x) behaviour.
     *
     * @var string
     */
    private static $table_name = null;

    /**
     * Non-static relationship cache, indexed by component name.
     *
     * @var DataObject[]
     */
    protected $components = [];

    /**
     * Non-static cache of has_many and many_many relations that can't be written until this object is saved.
     *
     * @var UnsavedRelationList[]
     */
    protected $unsavedRelations;

    /**
     * List of relations that should be cascade deleted, similar to `owns`
     * Note: This will trigger delete on many_many objects, not only the mapping table.
     * For many_many through you can specify the components you want to delete separately
     * (many_many or has_many sub-component)
     *
     * @config
     * @var array
     */
    private static $cascade_deletes = [];

    /**
     * List of relations that should be cascade duplicate.
     * many_many duplications are shallow only.
     *
     * Note: If duplicating a many_many through you should refer to the
     * has_many intermediary relation instead, otherwise extra fields
     * will be omitted from the duplicated relation.
     *
     * @var array
     */
    private static $cascade_duplicates = [];

    /**
     * Get schema object
     *
     * @return DataObjectSchema
     */
    public static function getSchema()
    {
        return Injector::inst()->get(DataObjectSchema::class);
    }

    /**
     * Construct a new DataObject.
     *
     * @param array|null $record Used internally for rehydrating an object from database content.
     *                           Bypasses setters on this class, and hence should not be used
     *                           for populating data on new records.
     * @param boolean $isSingleton This this to true if this is a singleton() object, a stub for calling methods.
     *                             Singletons don't have their defaults set.
     * @param array $queryParams List of DataQuery params necessary to lazy load, or load related objects.
     */
    public function __construct($record = null, $isSingleton = false, $queryParams = array())
    {
        parent::__construct();

        // Set query params on the DataObject to tell the lazy loading mechanism the context the object creation context
        $this->setSourceQueryParams($queryParams);

        // Set the fields data.
        if (!$record) {
            $record = array(
                'ID' => 0,
                'ClassName' => static::class,
                'RecordClassName' => static::class
            );
        }

        if ($record instanceof stdClass) {
            $record = (array)$record;
        }

        if (!is_array($record)) {
            if (is_object($record)) {
                $passed = "an object of type '" . get_class($record) . "'";
            } else {
                $passed = "The value '$record'";
            }

            user_error(
                "DataObject::__construct passed $passed.  It's supposed to be passed an array,"
                . " taken straight from the database.  Perhaps you should use DataList::create()->First(); instead?",
                E_USER_WARNING
            );
            $record = null;
        }

        // Set $this->record to $record, but ignore NULLs
        $this->record = array();
        foreach ($record as $k => $v) {
            // Ensure that ID is stored as a number and not a string
            // To do: this kind of clean-up should be done on all numeric fields, in some relatively
            // performant manner
            if ($v !== null) {
                if ($k == 'ID' && is_numeric($v)) {
                    $this->record[$k] = (int)$v;
                } else {
                    $this->record[$k] = $v;
                }
            }
        }

        // Identify fields that should be lazy loaded, but only on existing records
        if (!empty($record['ID'])) {
            // Get all field specs scoped to class for later lazy loading
            $fields = static::getSchema()->fieldSpecs(
                static::class,
                DataObjectSchema::INCLUDE_CLASS | DataObjectSchema::DB_ONLY
            );
            foreach ($fields as $field => $fieldSpec) {
                $fieldClass = strtok($fieldSpec, ".");
                if (!array_key_exists($field, $record)) {
                    $this->record[$field . '_Lazy'] = $fieldClass;
                }
            }
        }

        $this->original = $this->record;

        // Must be called after parent constructor
        if (!$isSingleton && (!isset($this->record['ID']) || !$this->record['ID'])) {
            $this->populateDefaults();
        }

        // prevent populateDefaults() and setField() from marking overwritten defaults as changed
        $this->changed = array();
    }

    /**
     * Destroy all of this objects dependant objects and local caches.
     * You'll need to call this to get the memory of an object that has components or extensions freed.
     */
    public function destroy()
    {
        $this->flushCache(false);
    }

    /**
     * Create a duplicate of this node. Can duplicate many_many relations
     *
     * @param bool $doWrite Perform a write() operation before returning the object.
     * If this is true, it will create the duplicate in the database.
     * @param array|null|false $relations List of relations to duplicate.
     * Will default to `cascade_duplicates` if null.
     * Set to 'false' to force none.
     * Set to specific array of names to duplicate to override these.
     * Note: If using versioned, this will additionally failover to `owns` config.
     * @return static A duplicate of this node. The exact type will be the type of this node.
     */
    public function duplicate($doWrite = true, $relations = null)
    {
        // Handle legacy behaviour
        if (is_string($relations) || $relations === true) {
            if ($relations === true) {
                $relations = 'many_many';
            }
            Deprecation::notice('5.0', 'Use cascade_duplicates config instead of providing a string to duplicate()');
            $relations = array_keys($this->config()->get($relations)) ?: [];
        }

        // Get duplicates
        if ($relations === null) {
            $relations = $this->config()->get('cascade_duplicates');
            // Remove any duplicate entries before duplicating them
            if (is_array($relations)) {
                $relations = array_unique($relations);
            }
        }

        // Create unsaved raw duplicate
        $map = $this->toMap();
        unset($map['Created']);
        /** @var static $clone */
        $clone = Injector::inst()->create(static::class, $map, false, $this->getSourceQueryParams());
        $clone->ID = 0;

        // Note: Extensions such as versioned may update $relations here
        $clone->invokeWithExtensions('onBeforeDuplicate', $this, $doWrite, $relations);
        if ($relations) {
            $this->duplicateRelations($this, $clone, $relations);
        }
        if ($doWrite) {
            $clone->write();
        }
        $clone->invokeWithExtensions('onAfterDuplicate', $this, $doWrite, $relations);

        return $clone;
    }

    /**
     * Copies the given relations from this object to the destination
     *
     * @param DataObject $sourceObject the source object to duplicate from
     * @param DataObject $destinationObject the destination object to populate with the duplicated relations
     * @param array $relations List of relations
     */
    protected function duplicateRelations($sourceObject, $destinationObject, $relations)
    {
        // Get list of duplicable relation types
        $manyMany = $sourceObject->manyMany();
        $hasMany = $sourceObject->hasMany();
        $hasOne = $sourceObject->hasOne();
        $belongsTo = $sourceObject->belongsTo();

        // Duplicate each relation based on type
        foreach ($relations as $relation) {
            switch (true) {
                case array_key_exists($relation, $manyMany): {
                    $this->duplicateManyManyRelation($sourceObject, $destinationObject, $relation);
                    break;
                }
                case array_key_exists($relation, $hasMany): {
                    $this->duplicateHasManyRelation($sourceObject, $destinationObject, $relation);
                    break;
                }
                case array_key_exists($relation, $hasOne): {
                    $this->duplicateHasOneRelation($sourceObject, $destinationObject, $relation);
                    break;
                }
                case array_key_exists($relation, $belongsTo): {
                    $this->duplicateBelongsToRelation($sourceObject, $destinationObject, $relation);
                    break;
                }
                default: {
                    $sourceType = get_class($sourceObject);
                    throw new InvalidArgumentException(
                        "Cannot duplicate unknown relation {$relation} on parent type {$sourceType}"
                    );
                }
            }
        }
    }

    /**
     * Copies the many_many and belongs_many_many relations from one object to another instance of the name of object.
     *
     * @deprecated 4.1.0:5.0.0 Use duplicateRelations() instead
     * @param DataObject $sourceObject the source object to duplicate from
     * @param DataObject $destinationObject the destination object to populate with the duplicated relations
     * @param bool|string $filter
     */
    protected function duplicateManyManyRelations($sourceObject, $destinationObject, $filter)
    {
        Deprecation::notice('5.0', 'Use duplicateRelations() instead');

        // Get list of relations to duplicate
        if ($filter === 'many_many' || $filter === 'belongs_many_many') {
            $relations = $sourceObject->config()->get($filter);
        } elseif ($filter === true) {
            $relations = $sourceObject->manyMany();
        } else {
            throw new InvalidArgumentException("Invalid many_many duplication filter");
        }
        foreach ($relations as $manyManyName => $type) {
            $this->duplicateManyManyRelation($sourceObject, $destinationObject, $manyManyName);
        }
    }

    /**
     * Duplicates a single many_many relation from one object to another.
     *
     * @param DataObject $sourceObject
     * @param DataObject $destinationObject
     * @param string $relation
     */
    protected function duplicateManyManyRelation($sourceObject, $destinationObject, $relation)
    {
        // Copy all components from source to destination
        $source = $sourceObject->getManyManyComponents($relation);
        $dest = $destinationObject->getManyManyComponents($relation);

        if ($source instanceof ManyManyList) {
            $extraFieldNames = $source->getExtraFields();
        } else {
            $extraFieldNames = [];
        }

        foreach ($source as $item) {
            // Merge extra fields
            $extraFields = [];
            foreach ($extraFieldNames as $fieldName => $fieldType) {
                $extraFields[$fieldName] = $item->getField($fieldName);
            }
            $dest->add($item, $extraFields);
        }
    }

    /**
     * Duplicates a single many_many relation from one object to another.
     *
     * @param DataObject $sourceObject
     * @param DataObject $destinationObject
     * @param string $relation
     */
    protected function duplicateHasManyRelation($sourceObject, $destinationObject, $relation)
    {
        // Copy all components from source to destination
        $source = $sourceObject->getComponents($relation);
        $dest = $destinationObject->getComponents($relation);

        /** @var DataObject $item */
        foreach ($source as $item) {
            // Don't write on duplicate; Wait until ParentID is available later.
            // writeRelations() will eventually write these records when converting
            // from UnsavedRelationList
            $clonedItem = $item->duplicate(false);
            $dest->add($clonedItem);
        }
    }

    /**
     * Duplicates a single has_one relation from one object to another.
     * Note: Child object will be force written.
     *
     * @param DataObject $sourceObject
     * @param DataObject $destinationObject
     * @param string $relation
     */
    protected function duplicateHasOneRelation($sourceObject, $destinationObject, $relation)
    {
        // Check if original object exists
        $item = $sourceObject->getComponent($relation);
        if (!$item->isInDB()) {
            return;
        }

        $clonedItem = $item->duplicate(false);
        $destinationObject->setComponent($relation, $clonedItem);
    }

    /**
     * Duplicates a single belongs_to relation from one object to another.
     * Note: This will force a write on both parent / child objects.
     *
     * @param DataObject $sourceObject
     * @param DataObject $destinationObject
     * @param string $relation
     */
    protected function duplicateBelongsToRelation($sourceObject, $destinationObject, $relation)
    {
        // Check if original object exists
        $item = $sourceObject->getComponent($relation);
        if (!$item->isInDB()) {
            return;
        }

        $clonedItem = $item->duplicate(false);
        $destinationObject->setComponent($relation, $clonedItem);
        // After $clonedItem is assigned the appropriate FieldID / FieldClass, force write
        // @todo Write this component in onAfterWrite instead, assigning the FieldID then
        // https://github.com/silverstripe/silverstripe-framework/issues/7818
        $clonedItem->write();
    }

    /**
     * Return obsolete class name, if this is no longer a valid class
     *
     * @return string
     */
    public function getObsoleteClassName()
    {
        $className = $this->getField("ClassName");
        if (!ClassInfo::exists($className)) {
            return $className;
        }
        return null;
    }

    /**
     * Gets name of this class
     *
     * @return string
     */
    public function getClassName()
    {
        $className = $this->getField("ClassName");
        if (!ClassInfo::exists($className)) {
            return static::class;
        }
        return $className;
    }

    /**
     * Set the ClassName attribute. {@link $class} is also updated.
     * Warning: This will produce an inconsistent record, as the object
     * instance will not automatically switch to the new subclass.
     * Please use {@link newClassInstance()} for this purpose,
     * or destroy and reinstanciate the record.
     *
     * @param string $className The new ClassName attribute (a subclass of {@link DataObject})
     * @return $this
     */
    public function setClassName($className)
    {
        $className = trim($className);
        if (!$className || !is_subclass_of($className, self::class)) {
            return $this;
        }

        $this->setField("ClassName", $className);
        $this->setField('RecordClassName', $className);
        return $this;
    }

    /**
     * Create a new instance of a different class from this object's record.
     * This is useful when dynamically changing the type of an instance. Specifically,
     * it ensures that the instance of the class is a match for the className of the
     * record. Don't set the {@link DataObject->class} or {@link DataObject->ClassName}
     * property manually before calling this method, as it will confuse change detection.
     *
     * If the new class is different to the original class, defaults are populated again
     * because this will only occur automatically on instantiation of a DataObject if
     * there is no record, or the record has no ID. In this case, we do have an ID but
     * we still need to repopulate the defaults.
     *
     * @param string $newClassName The name of the new class
     *
     * @return DataObject The new instance of the new class, The exact type will be of the class name provided.
     */
    public function newClassInstance($newClassName)
    {
        if (!is_subclass_of($newClassName, self::class)) {
            throw new InvalidArgumentException("$newClassName is not a valid subclass of DataObject");
        }

        $originalClass = $this->ClassName;

        /** @var DataObject $newInstance */
        $newInstance = Injector::inst()->create($newClassName, $this->record, false);

        // Modify ClassName
        if ($newClassName != $originalClass) {
            $newInstance->setClassName($newClassName);
            $newInstance->populateDefaults();
            $newInstance->forceChange();
        }

        return $newInstance;
    }

    /**
     * Adds methods from the extensions.
     * Called by Object::__construct() once per class.
     */
    public function defineMethods()
    {
        parent::defineMethods();

        if (static::class === self::class) {
            return;
        }

        // Set up accessors for joined items
        if ($manyMany = $this->manyMany()) {
            foreach ($manyMany as $relationship => $class) {
                $this->addWrapperMethod($relationship, 'getManyManyComponents');
            }
        }
        if ($hasMany = $this->hasMany()) {
            foreach ($hasMany as $relationship => $class) {
                $this->addWrapperMethod($relationship, 'getComponents');
            }
        }
        if ($hasOne = $this->hasOne()) {
            foreach ($hasOne as $relationship => $class) {
                $this->addWrapperMethod($relationship, 'getComponent');
            }
        }
        if ($belongsTo = $this->belongsTo()) {
            foreach (array_keys($belongsTo) as $relationship) {
                $this->addWrapperMethod($relationship, 'getComponent');
            }
        }
    }

    /**
     * Returns true if this object "exists", i.e., has a sensible value.
     * The default behaviour for a DataObject is to return true if
     * the object exists in the database, you can override this in subclasses.
     *
     * @return boolean true if this object exists
     */
    public function exists()
    {
        return (isset($this->record['ID']) && $this->record['ID'] > 0);
    }

    /**
     * Returns TRUE if all values (other than "ID") are
     * considered empty (by weak boolean comparison).
     *
     * @return boolean
     */
    public function isEmpty()
    {
        $fixed = DataObject::config()->uninherited('fixed_fields');
        foreach ($this->toMap() as $field => $value) {
            // only look at custom fields
            if (isset($fixed[$field])) {
                continue;
            }

            $dbObject = $this->dbObject($field);
            if (!$dbObject) {
                continue;
            }
            if ($dbObject->exists()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Pluralise this item given a specific count.
     *
     * E.g. "0 Pages", "1 File", "3 Images"
     *
     * @param string $count
     * @return string
     */
    public function i18n_pluralise($count)
    {
        $default = 'one ' . $this->i18n_singular_name() . '|{count} ' . $this->i18n_plural_name();
        return i18n::_t(
            static::class . '.PLURALS',
            $default,
            ['count' => $count]
        );
    }

    /**
     * Get the user friendly singular name of this DataObject.
     * If the name is not defined (by redefining $singular_name in the subclass),
     * this returns the class name.
     *
     * @return string User friendly singular name of this DataObject
     */
    public function singular_name()
    {
        $name = $this->config()->get('singular_name');
        if ($name) {
            return $name;
        }
        return ucwords(trim(strtolower(preg_replace(
            '/_?([A-Z])/',
            ' $1',
            ClassInfo::shortName($this)
        ))));
    }

    /**
     * Get the translated user friendly singular name of this DataObject
     * same as singular_name() but runs it through the translating function
     *
     * Translating string is in the form:
     *     $this->class.SINGULARNAME
     * Example:
     *     Page.SINGULARNAME
     *
     * @return string User friendly translated singular name of this DataObject
     */
    public function i18n_singular_name()
    {
        return _t(static::class . '.SINGULARNAME', $this->singular_name());
    }

    /**
     * Get the user friendly plural name of this DataObject
     * If the name is not defined (by renaming $plural_name in the subclass),
     * this returns a pluralised version of the class name.
     *
     * @return string User friendly plural name of this DataObject
     */
    public function plural_name()
    {
        if ($name = $this->config()->get('plural_name')) {
            return $name;
        }
        $name = $this->singular_name();
        //if the penultimate character is not a vowel, replace "y" with "ies"
        if (preg_match('/[^aeiou]y$/i', $name)) {
            $name = substr($name, 0, -1) . 'ie';
        }
        return ucfirst($name . 's');
    }

    /**
     * Get the translated user friendly plural name of this DataObject
     * Same as plural_name but runs it through the translation function
     * Translation string is in the form:
     *      $this->class.PLURALNAME
     * Example:
     *      Page.PLURALNAME
     *
     * @return string User friendly translated plural name of this DataObject
     */
    public function i18n_plural_name()
    {
        return _t(static::class . '.PLURALNAME', $this->plural_name());
    }

    /**
     * Standard implementation of a title/label for a specific
     * record. Tries to find properties 'Title' or 'Name',
     * and falls back to the 'ID'. Useful to provide
     * user-friendly identification of a record, e.g. in errormessages
     * or UI-selections.
     *
     * Overload this method to have a more specialized implementation,
     * e.g. for an Address record this could be:
     * <code>
     * function getTitle() {
     *   return "{$this->StreetNumber} {$this->StreetName} {$this->City}";
     * }
     * </code>
     *
     * @return string
     */
    public function getTitle()
    {
        $schema = static::getSchema();
        if ($schema->fieldSpec($this, 'Title')) {
            return $this->getField('Title');
        }
        if ($schema->fieldSpec($this, 'Name')) {
            return $this->getField('Name');
        }

        return "#{$this->ID}";
    }

    /**
     * Returns the associated database record - in this case, the object itself.
     * This is included so that you can call $dataOrController->data() and get a DataObject all the time.
     *
     * @return DataObject Associated database record
     */
    public function data()
    {
        return $this;
    }

    /**
     * Convert this object to a map.
     *
     * @return array The data as a map.
     */
    public function toMap()
    {
        $this->loadLazyFields();
        return $this->record;
    }

    /**
     * Return all currently fetched database fields.
     *
     * This function is similar to toMap() but doesn't trigger the lazy-loading of all unfetched fields.
     * Obviously, this makes it a lot faster.
     *
     * @return array The data as a map.
     */
    public function getQueriedDatabaseFields()
    {
        return $this->record;
    }

    /**
     * Update a number of fields on this object, given a map of the desired changes.
     *
     * The field names can be simple names, or you can use a dot syntax to access $has_one relations.
     * For example, array("Author.FirstName" => "Jim") will set $this->Author()->FirstName to "Jim".
     *
     * update() doesn't write the main object, but if you use the dot syntax, it will write()
     * the related objects that it alters.
     *
     * @param array $data A map of field name to data values to update.
     * @return DataObject $this
     */
    public function update($data)
    {
        foreach ($data as $key => $value) {
            // Implement dot syntax for updates
            if (strpos($key, '.') !== false) {
                $relations = explode('.', $key);
                $fieldName = array_pop($relations);
                /** @var static $relObj */
                $relObj = $this;
                $relation = null;
                foreach ($relations as $i => $relation) {
                    // no support for has_many or many_many relationships,
                    // as the updater wouldn't know which object to write to (or create)
                    if ($relObj->$relation() instanceof DataObject) {
                        $parentObj = $relObj;
                        $relObj = $relObj->$relation();
                        // If the intermediate relationship objects haven't been created, then write them
                        if ($i < sizeof($relations) - 1 && !$relObj->ID || (!$relObj->ID && $parentObj !== $this)) {
                            $relObj->write();
                            $relatedFieldName = $relation . "ID";
                            $parentObj->$relatedFieldName = $relObj->ID;
                            $parentObj->write();
                        }
                    } else {
                        user_error(
                            "DataObject::update(): Can't traverse relationship '$relation'," .
                            "it has to be a has_one relationship or return a single DataObject",
                            E_USER_NOTICE
                        );
                        // unset relation object so we don't write properties to the wrong object
                        $relObj = null;
                        break;
                    }
                }

                if ($relObj) {
                    $relObj->$fieldName = $value;
                    $relObj->write();
                    $relatedFieldName = $relation . "ID";
                    $this->$relatedFieldName = $relObj->ID;
                    $relObj->flushCache();
                } else {
                    $class = static::class;
                    user_error("Couldn't follow dot syntax '{$key}' on '{$class}' object", E_USER_WARNING);
                }
            } else {
                $this->$key = $value;
            }
        }
        return $this;
    }

    /**
     * Pass changes as a map, and try to
     * get automatic casting for these fields.
     * Doesn't write to the database. To write the data,
     * use the write() method.
     *
     * @param array $data A map of field name to data values to update.
     * @return DataObject $this
     */
    public function castedUpdate($data)
    {
        foreach ($data as $k => $v) {
            $this->setCastedField($k, $v);
        }
        return $this;
    }

    /**
     * Merges data and relations from another object of same class,
     * without conflict resolution. Allows to specify which
     * dataset takes priority in case its not empty.
     * has_one-relations are just transferred with priority 'right'.
     * has_many and many_many-relations are added regardless of priority.
     *
     * Caution: has_many/many_many relations are moved rather than duplicated,
     * meaning they are not connected to the merged object any longer.
     * Caution: Just saves updated has_many/many_many relations to the database,
     * doesn't write the updated object itself (just writes the object-properties).
     * Caution: Does not delete the merged object.
     * Caution: Does now overwrite Created date on the original object.
     *
     * @param DataObject $rightObj
     * @param string $priority left|right Determines who wins in case of a conflict (optional)
     * @param bool $includeRelations Merge any existing relations (optional)
     * @param bool $overwriteWithEmpty Overwrite existing left values with empty right values.
     *                            Only applicable with $priority='right'. (optional)
     * @return Boolean
     */
    public function merge($rightObj, $priority = 'right', $includeRelations = true, $overwriteWithEmpty = false)
    {
        $leftObj = $this;

        if ($leftObj->ClassName != $rightObj->ClassName) {
            // we can't merge similiar subclasses because they might have additional relations
            user_error("DataObject->merge(): Invalid object class '{$rightObj->ClassName}'
			(expected '{$leftObj->ClassName}').", E_USER_WARNING);
            return false;
        }

        if (!$rightObj->ID) {
            user_error("DataObject->merge(): Please write your merged-in object to the database before merging,
				to make sure all relations are transferred properly.').", E_USER_WARNING);
            return false;
        }

        // makes sure we don't merge data like ID or ClassName
        $rightData = DataObject::getSchema()->fieldSpecs(get_class($rightObj));
        foreach ($rightData as $key => $rightSpec) {
            // Don't merge ID
            if ($key === 'ID') {
                continue;
            }

            // Only merge relations if allowed
            if ($rightSpec === 'ForeignKey' && !$includeRelations) {
                continue;
            }

            // don't merge conflicting values if priority is 'left'
            if ($priority == 'left' && $leftObj->{$key} !== $rightObj->{$key}) {
                continue;
            }

            // don't overwrite existing left values with empty right values (if $overwriteWithEmpty is set)
            if ($priority == 'right' && !$overwriteWithEmpty && empty($rightObj->{$key})) {
                continue;
            }

            // TODO remove redundant merge of has_one fields
            $leftObj->{$key} = $rightObj->{$key};
        }

        // merge relations
        if ($includeRelations) {
            if ($manyMany = $this->manyMany()) {
                foreach ($manyMany as $relationship => $class) {
                    /** @var DataObject $leftComponents */
                    $leftComponents = $leftObj->getManyManyComponents($relationship);
                    $rightComponents = $rightObj->getManyManyComponents($relationship);
                    if ($rightComponents && $rightComponents->exists()) {
                        $leftComponents->addMany($rightComponents->column('ID'));
                    }
                    $leftComponents->write();
                }
            }

            if ($hasMany = $this->hasMany()) {
                foreach ($hasMany as $relationship => $class) {
                    $leftComponents = $leftObj->getComponents($relationship);
                    $rightComponents = $rightObj->getComponents($relationship);
                    if ($rightComponents && $rightComponents->exists()) {
                        $leftComponents->addMany($rightComponents->column('ID'));
                    }
                    $leftComponents->write();
                }
            }
        }

        return true;
    }

    /**
     * Forces the record to think that all its data has changed.
     * Doesn't write to the database. Only sets fields as changed
     * if they are not already marked as changed.
     *
     * @return $this
     */
    public function forceChange()
    {
        // Ensure lazy fields loaded
        $this->loadLazyFields();
        $fields = static::getSchema()->fieldSpecs(static::class);

        // $this->record might not contain the blank values so we loop on $this->inheritedDatabaseFields() as well
        $fieldNames = array_unique(array_merge(
            array_keys($this->record),
            array_keys($fields)
        ));

        foreach ($fieldNames as $fieldName) {
            if (!isset($this->changed[$fieldName])) {
                $this->changed[$fieldName] = self::CHANGE_STRICT;
            }
            // Populate the null values in record so that they actually get written
            if (!isset($this->record[$fieldName])) {
                $this->record[$fieldName] = null;
            }
        }

        // @todo Find better way to allow versioned to write a new version after forceChange
        if ($this->isChanged('Version')) {
            unset($this->changed['Version']);
        }
        return $this;
    }

    /**
     * Validate the current object.
     *
     * By default, there is no validation - objects are always valid!  However, you can overload this method in your
     * DataObject sub-classes to specify custom validation, or use the hook through DataExtension.
     *
     * Invalid objects won't be able to be written - a warning will be thrown and no write will occur.  onBeforeWrite()
     * and onAfterWrite() won't get called either.
     *
     * It is expected that you call validate() in your own application to test that an object is valid before
     * attempting a write, and respond appropriately if it isn't.
     *
     * @see {@link ValidationResult}
     * @return ValidationResult
     */
    public function validate()
    {
        $result = ValidationResult::create();
        $this->extend('validate', $result);
        return $result;
    }

    /**
     * Public accessor for {@see DataObject::validate()}
     *
     * @return ValidationResult
     */
    public function doValidate()
    {
        Deprecation::notice('5.0', 'Use validate');
        return $this->validate();
    }

    /**
     * Event handler called before writing to the database.
     * You can overload this to clean up or otherwise process data before writing it to the
     * database.  Don't forget to call parent::onBeforeWrite(), though!
     *
     * This called after {@link $this->validate()}, so you can be sure that your data is valid.
     *
     * @uses DataExtension->onBeforeWrite()
     */
    protected function onBeforeWrite()
    {
        $this->brokenOnWrite = false;

        $dummy = null;
        $this->extend('onBeforeWrite', $dummy);
    }

    /**
     * Event handler called after writing to the database.
     * You can overload this to act upon changes made to the data after it is written.
     * $this->changed will have a record
     * database.  Don't forget to call parent::onAfterWrite(), though!
     *
     * @uses DataExtension->onAfterWrite()
     */
    protected function onAfterWrite()
    {
        $dummy = null;
        $this->extend('onAfterWrite', $dummy);
    }

    /**
     * Find all objects that will be cascade deleted if this object is deleted
     *
     * Notes:
     *   - If this object is versioned, objects will only be searched in the same stage as the given record.
     *   - This will only be useful prior to deletion, as post-deletion this record will no longer exist.
     *
     * @param bool $recursive True if recursive
     * @param ArrayList $list Optional list to add items to
     * @return ArrayList list of objects
     */
    public function findCascadeDeletes($recursive = true, $list = null)
    {
        // Find objects in these relationships
        return $this->findRelatedObjects('cascade_deletes', $recursive, $list);
    }

    /**
     * Event handler called before deleting from the database.
     * You can overload this to clean up or otherwise process data before delete this
     * record.  Don't forget to call parent::onBeforeDelete(), though!
     *
     * @uses DataExtension->onBeforeDelete()
     */
    protected function onBeforeDelete()
    {
        $this->brokenOnDelete = false;

        $dummy = null;
        $this->extend('onBeforeDelete', $dummy);

        // Cascade deletes
        $deletes = $this->findCascadeDeletes(false);
        foreach ($deletes as $delete) {
            $delete->delete();
        }
    }

    protected function onAfterDelete()
    {
        $this->extend('onAfterDelete');
    }

    /**
     * Load the default values in from the self::$defaults array.
     * Will traverse the defaults of the current class and all its parent classes.
     * Called by the constructor when creating new records.
     *
     * @uses DataExtension->populateDefaults()
     * @return DataObject $this
     */
    public function populateDefaults()
    {
        $classes = array_reverse(ClassInfo::ancestry($this));

        foreach ($classes as $class) {
            $defaults = Config::inst()->get($class, 'defaults', Config::UNINHERITED);

            if ($defaults && !is_array($defaults)) {
                user_error(
                    "Bad '" . static::class . "' defaults given: " . var_export($defaults, true),
                    E_USER_WARNING
                );
                $defaults = null;
            }

            if ($defaults) {
                foreach ($defaults as $fieldName => $fieldValue) {
                    // SRM 2007-03-06: Stricter check
                    if (!isset($this->$fieldName) || $this->$fieldName === null) {
                        $this->$fieldName = $fieldValue;
                    }
                    // Set many-many defaults with an array of ids
                    if (is_array($fieldValue) && $this->getSchema()->manyManyComponent(static::class, $fieldName)) {
                        /** @var ManyManyList $manyManyJoin */
                        $manyManyJoin = $this->$fieldName();
                        $manyManyJoin->setByIDList($fieldValue);
                    }
                }
            }
            if ($class == self::class) {
                break;
            }
        }

        $this->extend('populateDefaults');
        return $this;
    }

    /**
     * Determine validation of this object prior to write
     *
     * @return ValidationException Exception generated by this write, or null if valid
     */
    protected function validateWrite()
    {
        if ($this->ObsoleteClassName) {
            return new ValidationException(
                "Object is of class '{$this->ObsoleteClassName}' which doesn't exist - " .
                "you need to change the ClassName before you can write it"
            );
        }

        // Note: Validation can only be disabled at the global level, not per-model
        if (DataObject::config()->uninherited('validation_enabled')) {
            $result = $this->validate();
            if (!$result->isValid()) {
                return new ValidationException($result);
            }
        }
        return null;
    }

    /**
     * Prepare an object prior to write
     *
     * @throws ValidationException
     */
    protected function preWrite()
    {
        // Validate this object
        if ($writeException = $this->validateWrite()) {
            // Used by DODs to clean up after themselves, eg, Versioned
            $this->invokeWithExtensions('onAfterSkippedWrite');
            throw $writeException;
        }

        // Check onBeforeWrite
        $this->brokenOnWrite = true;
        $this->onBeforeWrite();
        if ($this->brokenOnWrite) {
            user_error(static::class . " has a broken onBeforeWrite() function."
                . " Make sure that you call parent::onBeforeWrite().", E_USER_ERROR);
        }
    }

    /**
     * Detects and updates all changes made to this object
     *
     * @param bool $forceChanges If set to true, force all fields to be treated as changed
     * @return bool True if any changes are detected
     */
    protected function updateChanges($forceChanges = false)
    {
        if ($forceChanges) {
            // Force changes, but only for loaded fields
            foreach ($this->record as $field => $value) {
                $this->changed[$field] = static::CHANGE_VALUE;
            }
            return true;
        }
        return $this->isChanged();
    }

    /**
     * Writes a subset of changes for a specific table to the given manipulation
     *
     * @param string $baseTable Base table
     * @param string $now Timestamp to use for the current time
     * @param bool $isNewRecord Whether this should be treated as a new record write
     * @param array $manipulation Manipulation to write to
     * @param string $class Class of table to manipulate
     */
    protected function prepareManipulationTable($baseTable, $now, $isNewRecord, &$manipulation, $class)
    {
        $schema = $this->getSchema();
        $table = $schema->tableName($class);
        $manipulation[$table] = array();

        // Extract records for this table
        foreach ($this->record as $fieldName => $fieldValue) {
            // we're not attempting to reset the BaseTable->ID
            // Ignore unchanged fields or attempts to reset the BaseTable->ID
            if (empty($this->changed[$fieldName]) || ($table === $baseTable && $fieldName === 'ID')) {
                continue;
            }

            // Ensure this field pertains to this table
            $specification = $schema->fieldSpec(
                $class,
                $fieldName,
                DataObjectSchema::DB_ONLY | DataObjectSchema::UNINHERITED
            );
            if (!$specification) {
                continue;
            }

            // if database column doesn't correlate to a DBField instance...
            $fieldObj = $this->dbObject($fieldName);
            if (!$fieldObj) {
                $fieldObj = DBField::create_field('Varchar', $fieldValue, $fieldName);
            }

            // Write to manipulation
            $fieldObj->writeToManipulation($manipulation[$table]);
        }

        // Ensure update of Created and LastEdited columns
        if ($baseTable === $table) {
            $manipulation[$table]['fields']['LastEdited'] = $now;
            if ($isNewRecord) {
                $manipulation[$table]['fields']['Created'] = empty($this->record['Created'])
                    ? $now
                    : $this->record['Created'];
                $manipulation[$table]['fields']['ClassName'] = static::class;
            }
        }

        // Inserts done one the base table are performed in another step, so the manipulation should instead
        // attempt an update, as though it were a normal update.
        $manipulation[$table]['command'] = $isNewRecord ? 'insert' : 'update';
        $manipulation[$table]['id'] = $this->record['ID'];
        $manipulation[$table]['class'] = $class;
    }

    /**
     * Ensures that a blank base record exists with the basic fixed fields for this dataobject
     *
     * Does nothing if an ID is already assigned for this record
     *
     * @param string $baseTable Base table
     * @param string $now Timestamp to use for the current time
     */
    protected function writeBaseRecord($baseTable, $now)
    {
        // Generate new ID if not specified
        if ($this->isInDB()) {
            return;
        }

        // Perform an insert on the base table
        $insert = new SQLInsert('"' . $baseTable . '"');
        $insert
            ->assign('"Created"', $now)
            ->execute();
        $this->changed['ID'] = self::CHANGE_VALUE;
        $this->record['ID'] = DB::get_generated_id($baseTable);
    }

    /**
     * Generate and write the database manipulation for all changed fields
     *
     * @param string $baseTable Base table
     * @param string $now Timestamp to use for the current time
     * @param bool $isNewRecord If this is a new record
     */
    protected function writeManipulation($baseTable, $now, $isNewRecord)
    {
        // Generate database manipulations for each class
        $manipulation = array();
        foreach (ClassInfo::ancestry(static::class, true) as $class) {
            $this->prepareManipulationTable($baseTable, $now, $isNewRecord, $manipulation, $class);
        }

        // Allow extensions to extend this manipulation
        $this->extend('augmentWrite', $manipulation);

        // New records have their insert into the base data table done first, so that they can pass the
        // generated ID on to the rest of the manipulation
        if ($isNewRecord) {
            $manipulation[$baseTable]['command'] = 'update';
        }

        // Perform the manipulation
        DB::manipulate($manipulation);
    }

    /**
     * Writes all changes to this object to the database.
     *  - It will insert a record whenever ID isn't set, otherwise update.
     *  - All relevant tables will be updated.
     *  - $this->onBeforeWrite() gets called beforehand.
     *  - Extensions such as Versioned will ammend the database-write to ensure that a version is saved.
     *
     * @uses DataExtension->augmentWrite()
     *
     * @param boolean $showDebug Show debugging information
     * @param boolean $forceInsert Run INSERT command rather than UPDATE, even if record already exists
     * @param boolean $forceWrite Write to database even if there are no changes
     * @param boolean $writeComponents Call write() on all associated component instances which were previously
     *                                 retrieved through {@link getComponent()}, {@link getComponents()} or
     *                                 {@link getManyManyComponents()} (Default: false)
     * @return int The ID of the record
     * @throws ValidationException Exception that can be caught and handled by the calling function
     */
    public function write($showDebug = false, $forceInsert = false, $forceWrite = false, $writeComponents = false)
    {
        $now = DBDatetime::now()->Rfc2822();

        // Execute pre-write tasks
        $this->preWrite();

        // Check if we are doing an update or an insert
        $isNewRecord = !$this->isInDB() || $forceInsert;

        // Check changes exist, abort if there are none
        $hasChanges = $this->updateChanges($isNewRecord);
        if ($hasChanges || $forceWrite || $isNewRecord) {
            // Ensure Created and LastEdited are populated
            if (!isset($this->record['Created'])) {
                $this->record['Created'] = $now;
            }
            $this->record['LastEdited'] = $now;

            // New records have their insert into the base data table done first, so that they can pass the
            // generated primary key on to the rest of the manipulation
            $baseTable = $this->baseTable();
            $this->writeBaseRecord($baseTable, $now);

            // Write the DB manipulation for all changed fields
            $this->writeManipulation($baseTable, $now, $isNewRecord);

            // If there's any relations that couldn't be saved before, save them now (we have an ID here)
            $this->writeRelations();
            $this->onAfterWrite();
            $this->changed = array();
        } else {
            if ($showDebug) {
                Debug::message("no changes for DataObject");
            }

            // Used by DODs to clean up after themselves, eg, Versioned
            $this->invokeWithExtensions('onAfterSkippedWrite');
        }

        // Write relations as necessary
        if ($writeComponents) {
            $this->writeComponents(true);
        }

        // Clears the cache for this object so get_one returns the correct object.
        $this->flushCache();

        return $this->record['ID'];
    }

    /**
     * Writes cached relation lists to the database, if possible
     */
    public function writeRelations()
    {
        if (!$this->isInDB()) {
            return;
        }

        // If there's any relations that couldn't be saved before, save them now (we have an ID here)
        if ($this->unsavedRelations) {
            foreach ($this->unsavedRelations as $name => $list) {
                $list->changeToList($this->$name());
            }
            $this->unsavedRelations = array();
        }
    }

    /**
     * Write the cached components to the database. Cached components could refer to two different instances of the
     * same record.
     *
     * @param bool $recursive Recursively write components
     * @return DataObject $this
     */
    public function writeComponents($recursive = false)
    {
        foreach ($this->components as $component) {
            $component->write(false, false, false, $recursive);
        }

        if ($join = $this->getJoin()) {
            $join->write(false, false, false, $recursive);
        }

        return $this;
    }

    /**
     * Delete this data object.
     * $this->onBeforeDelete() gets called.
     * Note that in Versioned objects, both Stage and Live will be deleted.
     * @uses DataExtension->augmentSQL()
     */
    public function delete()
    {
        $this->brokenOnDelete = true;
        $this->onBeforeDelete();
        if ($this->brokenOnDelete) {
            user_error(static::class . " has a broken onBeforeDelete() function."
                . " Make sure that you call parent::onBeforeDelete().", E_USER_ERROR);
        }

        // Deleting a record without an ID shouldn't do anything
        if (!$this->ID) {
            throw new LogicException("DataObject::delete() called on a DataObject without an ID");
        }

        // TODO: This is quite ugly.  To improve:
        //  - move the details of the delete code in the DataQuery system
        //  - update the code to just delete the base table, and rely on cascading deletes in the DB to do the rest
        //    obviously, that means getting requireTable() to configure cascading deletes ;-)
        $srcQuery = DataList::create(static::class)
            ->filter('ID', $this->ID)
            ->dataQuery()
            ->query();
        $queriedTables = $srcQuery->queriedTables();
        $this->extend('updateDeleteTables', $queriedTables, $srcQuery);
        foreach ($queriedTables as $table) {
            $delete = SQLDelete::create("\"$table\"", array('"ID"' => $this->ID));
            $this->extend('updateDeleteTable', $delete, $table, $queriedTables, $srcQuery);
            $delete->execute();
        }
        // Remove this item out of any caches
        $this->flushCache();

        $this->onAfterDelete();

        $this->OldID = $this->ID;
        $this->ID = 0;
    }

    /**
     * Delete the record with the given ID.
     *
     * @param string $className The class name of the record to be deleted
     * @param int $id ID of record to be deleted
     */
    public static function delete_by_id($className, $id)
    {
        $obj = DataObject::get_by_id($className, $id);
        if ($obj) {
            $obj->delete();
        } else {
            user_error("$className object #$id wasn't found when calling DataObject::delete_by_id", E_USER_WARNING);
        }
    }

    /**
     * Get the class ancestry, including the current class name.
     * The ancestry will be returned as an array of class names, where the 0th element
     * will be the class that inherits directly from DataObject, and the last element
     * will be the current class.
     *
     * @return array Class ancestry
     */
    public function getClassAncestry()
    {
        return ClassInfo::ancestry(static::class);
    }

    /**
     * Return a unary component object from a one to one relationship, as a DataObject.
     * If no component is available, an 'empty component' will be returned for
     * non-polymorphic relations, or for polymorphic relations with a class set.
     *
     * @param string $componentName Name of the component
     * @return DataObject The component object. It's exact type will be that of the component.
     * @throws Exception
     */
    public function getComponent($componentName)
    {
        if (isset($this->components[$componentName])) {
            return $this->components[$componentName];
        }

        $schema = static::getSchema();
        if ($class = $schema->hasOneComponent(static::class, $componentName)) {
            $joinField = $componentName . 'ID';
            $joinID = $this->getField($joinField);

            // Extract class name for polymorphic relations
            if ($class === self::class) {
                $class = $this->getField($componentName . 'Class');
                if (empty($class)) {
                    return null;
                }
            }

            if ($joinID) {
                // Ensure that the selected object originates from the same stage, subsite, etc
                $component = DataObject::get($class)
                    ->filter('ID', $joinID)
                    ->setDataQueryParam($this->getInheritableQueryParams())
                    ->first();
            }

            if (empty($component)) {
                $component = Injector::inst()->create($class);
            }
        } elseif ($class = $schema->belongsToComponent(static::class, $componentName)) {
            $joinField = $schema->getRemoteJoinField(static::class, $componentName, 'belongs_to', $polymorphic);
            $joinID = $this->ID;

            if ($joinID) {
                // Prepare filter for appropriate join type
                if ($polymorphic) {
                    $filter = array(
                        "{$joinField}ID" => $joinID,
                        "{$joinField}Class" => static::class,
                    );
                } else {
                    $filter = array(
                        $joinField => $joinID
                    );
                }

                // Ensure that the selected object originates from the same stage, subsite, etc
                $component = DataObject::get($class)
                    ->filter($filter)
                    ->setDataQueryParam($this->getInheritableQueryParams())
                    ->first();
            }

            if (empty($component)) {
                $component = Injector::inst()->create($class);
                if ($polymorphic) {
                    $component->{$joinField . 'ID'} = $this->ID;
                    $component->{$joinField . 'Class'} = static::class;
                } else {
                    $component->$joinField = $this->ID;
                }
            }
        } else {
            throw new InvalidArgumentException(
                "DataObject->getComponent(): Could not find component '$componentName'."
            );
        }

        $this->components[$componentName] = $component;
        return $component;
    }

    /**
     * Assign an item to the given component
     *
     * @param string $componentName
     * @param DataObject|null $item
     * @return $this
     */
    public function setComponent($componentName, $item)
    {
        // Validate component
        $schema = static::getSchema();
        if ($class = $schema->hasOneComponent(static::class, $componentName)) {
            // Force item to be written if not by this point
            // @todo This could be lazy-written in a beforeWrite hook, but force write here for simplicity
            // https://github.com/silverstripe/silverstripe-framework/issues/7818
            if ($item && !$item->isInDB()) {
                $item->write();
            }

            // Update local ID
            $joinField = $componentName . 'ID';
            $this->setField($joinField, $item ? $item->ID : null);
            // Update Class (Polymorphic has_one)
            // Extract class name for polymorphic relations
            if ($class === self::class) {
                $this->setField($componentName . 'Class', $item ? get_class($item) : null);
            }
        } elseif ($class = $schema->belongsToComponent(static::class, $componentName)) {
            if ($item) {
                // For belongs_to, add to has_one on other component
                $joinField = $schema->getRemoteJoinField(static::class, $componentName, 'belongs_to', $polymorphic);
                if (!$polymorphic) {
                    $joinField = substr($joinField, 0, -2);
                }
                $item->setComponent($joinField, $this);
            }
        } else {
            throw new InvalidArgumentException(
                "DataObject->setComponent(): Could not find component '$componentName'."
            );
        }

        $this->components[$componentName] = $item;
        return $this;
    }

    /**
     * Returns a one-to-many relation as a HasManyList
     *
     * @param string $componentName Name of the component
     * @param int|array $id Optional ID(s) for parent of this relation, if not the current record
     * @return HasManyList|UnsavedRelationList The components of the one-to-many relationship.
     */
    public function getComponents($componentName, $id = null)
    {
        if (!isset($id)) {
            $id = $this->ID;
        }
        $result = null;

        $schema = $this->getSchema();
        $componentClass = $schema->hasManyComponent(static::class, $componentName);
        if (!$componentClass) {
            throw new InvalidArgumentException(sprintf(
                "DataObject::getComponents(): Unknown 1-to-many component '%s' on class '%s'",
                $componentName,
                static::class
            ));
        }

        // If we haven't been written yet, we can't save these relations, so use a list that handles this case
        if (!$id) {
            if (!isset($this->unsavedRelations[$componentName])) {
                $this->unsavedRelations[$componentName] =
                    new UnsavedRelationList(static::class, $componentName, $componentClass);
            }
            return $this->unsavedRelations[$componentName];
        }

        // Determine type and nature of foreign relation
        $joinField = $schema->getRemoteJoinField(static::class, $componentName, 'has_many', $polymorphic);
        /** @var HasManyList $result */
        if ($polymorphic) {
            $result = PolymorphicHasManyList::create($componentClass, $joinField, static::class);
        } else {
            $result = HasManyList::create($componentClass, $joinField);
        }

        return $result
            ->setDataQueryParam($this->getInheritableQueryParams())
            ->forForeignID($id);
    }

    /**
     * Find the foreign class of a relation on this DataObject, regardless of the relation type.
     *
     * @param string $relationName Relation name.
     * @return string Class name, or null if not found.
     */
    public function getRelationClass($relationName)
    {
        // Parse many_many
        $manyManyComponent = $this->getSchema()->manyManyComponent(static::class, $relationName);
        if ($manyManyComponent) {
            return $manyManyComponent['childClass'];
        }

        // Go through all relationship configuration fields.
        $config = $this->config();
        $candidates = array_merge(
            ($relations = $config->get('has_one')) ? $relations : array(),
            ($relations = $config->get('has_many')) ? $relations : array(),
            ($relations = $config->get('belongs_to')) ? $relations : array()
        );

        if (isset($candidates[$relationName])) {
            $remoteClass = $candidates[$relationName];

            // If dot notation is present, extract just the first part that contains the class.
            if (($fieldPos = strpos($remoteClass, '.')) !== false) {
                return substr($remoteClass, 0, $fieldPos);
            }

            // Otherwise just return the class
            return $remoteClass;
        }

        return null;
    }

    /**
     * Given a relation name, determine the relation type
     *
     * @param string $component Name of component
     * @return string has_one, has_many, many_many, belongs_many_many or belongs_to
     */
    public function getRelationType($component)
    {
        $types = array('has_one', 'has_many', 'many_many', 'belongs_many_many', 'belongs_to');
        $config = $this->config();
        foreach ($types as $type) {
            $relations = $config->get($type);
            if ($relations && isset($relations[$component])) {
                return $type;
            }
        }
        return null;
    }

    /**
     * Given a relation declared on a remote class, generate a substitute component for the opposite
     * side of the relation.
     *
     * Notes on behaviour:
     *  - This can still be used on components that are defined on both sides, but do not need to be.
     *  - All has_ones on remote class will be treated as local has_many, even if they are belongs_to
     *  - Polymorphic relationships do not have two natural endpoints (only on one side)
     *   and thus attempting to infer them will return nothing.
     *  - Cannot be used on unsaved objects.
     *
     * @param string $remoteClass
     * @param string $remoteRelation
     * @return DataList|DataObject The component, either as a list or single object
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     */
    public function inferReciprocalComponent($remoteClass, $remoteRelation)
    {
        $remote = DataObject::singleton($remoteClass);
        $class = $remote->getRelationClass($remoteRelation);
        $schema = static::getSchema();

        // Validate arguments
        if (!$this->isInDB()) {
            throw new BadMethodCallException(__METHOD__ . " cannot be called on unsaved objects");
        }
        if (empty($class)) {
            throw new InvalidArgumentException(sprintf(
                "%s invoked with invalid relation %s.%s",
                __METHOD__,
                $remoteClass,
                $remoteRelation
            ));
        }
        // If relation is polymorphic, do not infer recriprocal relationship
        if ($class === self::class) {
            return null;
        }
        if (!is_a($this, $class, true)) {
            throw new InvalidArgumentException(sprintf(
                "Relation %s on %s does not refer to objects of type %s",
                $remoteRelation,
                $remoteClass,
                static::class
            ));
        }

        // Check the relation type to mock
        $relationType = $remote->getRelationType($remoteRelation);
        switch ($relationType) {
            case 'has_one': {
                // Mock has_many
                $joinField = "{$remoteRelation}ID";
                $componentClass = $schema->classForField($remoteClass, $joinField);
                $result = HasManyList::create($componentClass, $joinField);
                return $result
                    ->setDataQueryParam($this->getInheritableQueryParams())
                    ->forForeignID($this->ID);
            }
            case 'belongs_to':
            case 'has_many': {
                // These relations must have a has_one on the other end, so find it
                $joinField = $schema->getRemoteJoinField(
                    $remoteClass,
                    $remoteRelation,
                    $relationType,
                    $polymorphic
                );
                // If relation is polymorphic, do not infer recriprocal relationship automatically
                if ($polymorphic) {
                    return null;
                }
                $joinID = $this->getField($joinField);
                if (empty($joinID)) {
                    return null;
                }
                // Get object by joined ID
                return DataObject::get($remoteClass)
                    ->filter('ID', $joinID)
                    ->setDataQueryParam($this->getInheritableQueryParams())
                    ->first();
            }
            case 'many_many':
            case 'belongs_many_many': {
                // Get components and extra fields from parent
                $manyMany = $remote->getSchema()->manyManyComponent($remoteClass, $remoteRelation);
                $extraFields = $schema->manyManyExtraFieldsForComponent($remoteClass, $remoteRelation) ?: array();

                // Reverse parent and component fields and create an inverse ManyManyList
                /** @var RelationList $result */
                $result = Injector::inst()->create(
                    $manyMany['relationClass'],
                    $manyMany['parentClass'], // Substitute parent class for dataClass
                    $manyMany['join'],
                    $manyMany['parentField'], // Reversed parent / child field
                    $manyMany['childField'], // Reversed parent / child field
                    $extraFields,
                    $manyMany['childClass'], // substitute child class for parentClass
                    $remoteClass // In case ManyManyThroughList needs to use PolymorphicHasManyList internally
                );
                $this->extend('updateManyManyComponents', $result);

                // If this is called on a singleton, then we return an 'orphaned relation' that can have the
                // foreignID set elsewhere.
                return $result
                    ->setDataQueryParam($this->getInheritableQueryParams())
                    ->forForeignID($this->ID);
            }
            default: {
                return null;
            }
        }
    }

    /**
     * Returns a many-to-many component, as a ManyManyList.
     * @param string $componentName Name of the many-many component
     * @param int|array $id Optional ID for parent of this relation, if not the current record
     * @return ManyManyList|UnsavedRelationList The set of components
     */
    public function getManyManyComponents($componentName, $id = null)
    {
        if (!isset($id)) {
            $id = $this->ID;
        }
        $schema = static::getSchema();
        $manyManyComponent = $schema->manyManyComponent(static::class, $componentName);
        if (!$manyManyComponent) {
            throw new InvalidArgumentException(sprintf(
                "DataObject::getComponents(): Unknown many-to-many component '%s' on class '%s'",
                $componentName,
                static::class
            ));
        }

        // If we haven't been written yet, we can't save these relations, so use a list that handles this case
        if (!$id) {
            if (!isset($this->unsavedRelations[$componentName])) {
                $this->unsavedRelations[$componentName] =
                    new UnsavedRelationList(
                        $manyManyComponent['parentClass'],
                        $componentName,
                        $manyManyComponent['childClass']
                    );
            }
            return $this->unsavedRelations[$componentName];
        }

        $extraFields = $schema->manyManyExtraFieldsForComponent(static::class, $componentName) ?: array();
        /** @var RelationList $result */
        $result = Injector::inst()->create(
            $manyManyComponent['relationClass'],
            $manyManyComponent['childClass'],
            $manyManyComponent['join'],
            $manyManyComponent['childField'],
            $manyManyComponent['parentField'],
            $extraFields,
            $manyManyComponent['parentClass'],
            static::class // In case ManyManyThroughList needs to use PolymorphicHasManyList internally
        );

        // Store component data in query meta-data
        $result = $result->alterDataQuery(function ($query) use ($extraFields) {
            /** @var DataQuery $query */
            $query->setQueryParam('Component.ExtraFields', $extraFields);
        });

        // If we have a default sort set for our "join" then we should overwrite any default already set.
        $joinSort = Config::inst()->get($manyManyComponent['join'], 'default_sort');
        if (!empty($joinSort)) {
            $result = $result->sort($joinSort);
        }

        $this->extend('updateManyManyComponents', $result);

        // If this is called on a singleton, then we return an 'orphaned relation' that can have the
        // foreignID set elsewhere.
        return $result
            ->setDataQueryParam($this->getInheritableQueryParams())
            ->forForeignID($id);
    }

    /**
     * Return the class of a one-to-one component.  If $component is null, return all of the one-to-one components and
     * their classes. If the selected has_one is a polymorphic field then 'DataObject' will be returned for the type.
     *
     * @return string|array The class of the one-to-one component, or an array of all one-to-one components and
     *                          their classes.
     */
    public function hasOne()
    {
        return (array)$this->config()->get('has_one');
    }

    /**
     * Returns the class of a remote belongs_to relationship. If no component is specified a map of all components and
     * their class name will be returned.
     *
     * @param bool $classOnly If this is TRUE, than any has_many relationships in the form "ClassName.Field" will have
     *        the field data stripped off. It defaults to TRUE.
     * @return string|array
     */
    public function belongsTo($classOnly = true)
    {
        $belongsTo = (array)$this->config()->get('belongs_to');
        if ($belongsTo && $classOnly) {
            return preg_replace('/(.+)?\..+/', '$1', $belongsTo);
        } else {
            return $belongsTo ? $belongsTo : array();
        }
    }

    /**
     * Gets the class of a one-to-many relationship. If no $component is specified then an array of all the one-to-many
     * relationships and their classes will be returned.
     *
     * @param bool $classOnly If this is TRUE, than any has_many relationships in the form "ClassName.Field" will have
     *        the field data stripped off. It defaults to TRUE.
     * @return string|array|false
     */
    public function hasMany($classOnly = true)
    {
        $hasMany = (array)$this->config()->get('has_many');
        if ($hasMany && $classOnly) {
            return preg_replace('/(.+)?\..+/', '$1', $hasMany);
        } else {
            return $hasMany ? $hasMany : array();
        }
    }

    /**
     * Return the many-to-many extra fields specification.
     *
     * If you don't specify a component name, it returns all
     * extra fields for all components available.
     *
     * @return array|null
     */
    public function manyManyExtraFields()
    {
        return $this->config()->get('many_many_extraFields');
    }

    /**
     * Return information about a many-to-many component.
     * The return value is an array of (parentclass, childclass).  If $component is null, then all many-many
     * components are returned.
     *
     * @see DataObjectSchema::manyManyComponent()
     * @return array|null An array of (parentclass, childclass), or an array of all many-many components
     */
    public function manyMany()
    {
        $config = $this->config();
        $manyManys = (array)$config->get('many_many');
        $belongsManyManys = (array)$config->get('belongs_many_many');
        $items = array_merge($manyManys, $belongsManyManys);
        return $items;
    }

    /**
     * This returns an array (if it exists) describing the database extensions that are required, or false if none
     *
     * This is experimental, and is currently only a Postgres-specific enhancement.
     *
     * @param string $class
     * @return array|false
     */
    public function database_extensions($class)
    {
        $extensions = Config::inst()->get($class, 'database_extensions', Config::UNINHERITED);
        if ($extensions) {
            return $extensions;
        } else {
            return false;
        }
    }

    /**
     * Generates a SearchContext to be used for building and processing
     * a generic search form for properties on this object.
     *
     * @return SearchContext
     */
    public function getDefaultSearchContext()
    {
        return new SearchContext(
            static::class,
            $this->scaffoldSearchFields(),
            $this->defaultSearchFilters()
        );
    }

    /**
     * Determine which properties on the DataObject are
     * searchable, and map them to their default {@link FormField}
     * representations. Used for scaffolding a searchform for {@link ModelAdmin}.
     *
     * Some additional logic is included for switching field labels, based on
     * how generic or specific the field type is.
     *
     * Used by {@link SearchContext}.
     *
     * @param array $_params
     *   'fieldClasses': Associative array of field names as keys and FormField classes as values
     *   'restrictFields': Numeric array of a field name whitelist
     * @return FieldList
     */
    public function scaffoldSearchFields($_params = null)
    {
        $params = array_merge(
            array(
                'fieldClasses' => false,
                'restrictFields' => false
            ),
            (array)$_params
        );
        $fields = new FieldList();
        foreach ($this->searchableFields() as $fieldName => $spec) {
            if ($params['restrictFields'] && !in_array($fieldName, $params['restrictFields'])) {
                continue;
            }

            // If a custom fieldclass is provided as a string, use it
            $field = null;
            if ($params['fieldClasses'] && isset($params['fieldClasses'][$fieldName])) {
                $fieldClass = $params['fieldClasses'][$fieldName];
                $field = new $fieldClass($fieldName);
            // If we explicitly set a field, then construct that
            } elseif (isset($spec['field'])) {
                // If it's a string, use it as a class name and construct
                if (is_string($spec['field'])) {
                    $fieldClass = $spec['field'];
                    $field = new $fieldClass($fieldName);

                // If it's a FormField object, then just use that object directly.
                } elseif ($spec['field'] instanceof FormField) {
                    $field = $spec['field'];

                // Otherwise we have a bug
                } else {
                    user_error("Bad value for searchable_fields, 'field' value: "
                        . var_export($spec['field'], true), E_USER_WARNING);
                }

            // Otherwise, use the database field's scaffolder
            } elseif ($object = $this->relObject($fieldName)) {
                $field = $object->scaffoldSearchField();
            }

            // Allow fields to opt out of search
            if (!$field) {
                continue;
            }

            if (strstr($fieldName, '.')) {
                $field->setName(str_replace('.', '__', $fieldName));
            }
            $field->setTitle($spec['title']);

            $fields->push($field);
        }
        return $fields;
    }

    /**
     * Scaffold a simple edit form for all properties on this dataobject,
     * based on default {@link FormField} mapping in {@link DBField::scaffoldFormField()}.
     * Field labels/titles will be auto generated from {@link DataObject::fieldLabels()}.
     *
     * @uses FormScaffolder
     *
     * @param array $_params Associative array passing through properties to {@link FormScaffolder}.
     * @return FieldList
     */
    public function scaffoldFormFields($_params = null)
    {
        $params = array_merge(
            array(
                'tabbed' => false,
                'includeRelations' => false,
                'restrictFields' => false,
                'fieldClasses' => false,
                'ajaxSafe' => false
            ),
            (array)$_params
        );

        $fs = FormScaffolder::create($this);
        $fs->tabbed = $params['tabbed'];
        $fs->includeRelations = $params['includeRelations'];
        $fs->restrictFields = $params['restrictFields'];
        $fs->fieldClasses = $params['fieldClasses'];
        $fs->ajaxSafe = $params['ajaxSafe'];

        return $fs->getFieldList();
    }

    /**
     * Allows user code to hook into DataObject::getCMSFields prior to updateCMSFields
     * being called on extensions
     *
     * @param callable $callback The callback to execute
     */
    protected function beforeUpdateCMSFields($callback)
    {
        $this->beforeExtending('updateCMSFields', $callback);
    }

    /**
     * Centerpiece of every data administration interface in Silverstripe,
     * which returns a {@link FieldList} suitable for a {@link Form} object.
     * If not overloaded, we're using {@link scaffoldFormFields()} to automatically
     * generate this set. To customize, overload this method in a subclass
     * or extended onto it by using {@link DataExtension->updateCMSFields()}.
     *
     * <code>
     * class MyCustomClass extends DataObject {
     *  static $db = array('CustomProperty'=>'Boolean');
     *
     *  function getCMSFields() {
     *    $fields = parent::getCMSFields();
     *    $fields->addFieldToTab('Root.Content',new CheckboxField('CustomProperty'));
     *    return $fields;
     *  }
     * }
     * </code>
     *
     * @see Good example of complex FormField building: SiteTree::getCMSFields()
     *
     * @return FieldList Returns a TabSet for usage within the CMS - don't use for frontend forms.
     */
    public function getCMSFields()
    {
        $tabbedFields = $this->scaffoldFormFields(array(
            // Don't allow has_many/many_many relationship editing before the record is first saved
            'includeRelations' => ($this->ID > 0),
            'tabbed' => true,
            'ajaxSafe' => true
        ));

        $this->extend('updateCMSFields', $tabbedFields);

        return $tabbedFields;
    }

    /**
     * need to be overload by solid dataobject, so that the customised actions of that dataobject,
     * including that dataobject's extensions customised actions could be added to the EditForm.
     *
     * @return FieldList an Empty FieldList(); need to be overload by solid subclass
     */
    public function getCMSActions()
    {
        $actions = new FieldList();
        $this->extend('updateCMSActions', $actions);
        return $actions;
    }


    /**
     * Used for simple frontend forms without relation editing
     * or {@link TabSet} behaviour. Uses {@link scaffoldFormFields()}
     * by default. To customize, either overload this method in your
     * subclass, or extend it by {@link DataExtension->updateFrontEndFields()}.
     *
     * @todo Decide on naming for "website|frontend|site|page" and stick with it in the API
     *
     * @param array $params See {@link scaffoldFormFields()}
     * @return FieldList Always returns a simple field collection without TabSet.
     */
    public function getFrontEndFields($params = null)
    {
        $untabbedFields = $this->scaffoldFormFields($params);
        $this->extend('updateFrontEndFields', $untabbedFields);

        return $untabbedFields;
    }

    public function getViewerTemplates($suffix = '')
    {
        return SSViewer::get_templates_by_class(static::class, $suffix, $this->baseClass());
    }

    /**
     * Gets the value of a field.
     * Called by {@link __get()} and any getFieldName() methods you might create.
     *
     * @param string $field The name of the field
     * @return mixed The field value
     */
    public function getField($field)
    {
        // If we already have a value in $this->record, then we should just return that
        if (isset($this->record[$field])) {
            return $this->record[$field];
        }

        // Do we have a field that needs to be lazy loaded?
        if (isset($this->record[$field . '_Lazy'])) {
            $tableClass = $this->record[$field . '_Lazy'];
            $this->loadLazyFields($tableClass);
        }
        $schema = static::getSchema();

        // Support unary relations as fields
        if ($schema->unaryComponent(static::class, $field)) {
            return $this->getComponent($field);
        }

        // In case of complex fields, return the DBField object
        if ($schema->compositeField(static::class, $field)) {
            $this->record[$field] = $this->dbObject($field);
        }

        return isset($this->record[$field]) ? $this->record[$field] : null;
    }

    /**
     * Loads all the stub fields that an initial lazy load didn't load fully.
     *
     * @param string $class Class to load the values from. Others are joined as required.
     * Not specifying a tableClass will load all lazy fields from all tables.
     * @return bool Flag if lazy loading succeeded
     */
    protected function loadLazyFields($class = null)
    {
        if (!$this->isInDB() || !is_numeric($this->ID)) {
            return false;
        }

        if (!$class) {
            $loaded = array();

            foreach ($this->record as $key => $value) {
                if (strlen($key) > 5 && substr($key, -5) == '_Lazy' && !array_key_exists($value, $loaded)) {
                    $this->loadLazyFields($value);
                    $loaded[$value] = $value;
                }
            }

            return false;
        }

        $dataQuery = new DataQuery($class);

        // Reset query parameter context to that of this DataObject
        if ($params = $this->getSourceQueryParams()) {
            foreach ($params as $key => $value) {
                $dataQuery->setQueryParam($key, $value);
            }
        }

        // Limit query to the current record, unless it has the Versioned extension,
        // in which case it requires special handling through augmentLoadLazyFields()
        $schema = static::getSchema();
        $baseIDColumn = $schema->sqlColumnForField($this, 'ID');
        $dataQuery->where([
            $baseIDColumn => $this->record['ID']
        ])->limit(1);

        $columns = array();

        // Add SQL for fields, both simple & multi-value
        // TODO: This is copy & pasted from buildSQL(), it could be moved into a method
        $databaseFields = $schema->databaseFields($class, false);
        foreach ($databaseFields as $k => $v) {
            if (!isset($this->record[$k]) || $this->record[$k] === null) {
                $columns[] = $k;
            }
        }

        if ($columns) {
            $query = $dataQuery->query();
            $this->extend('augmentLoadLazyFields', $query, $dataQuery, $this);
            $this->extend('augmentSQL', $query, $dataQuery);

            $dataQuery->setQueriedColumns($columns);
            $newData = $dataQuery->execute()->record();

            // Load the data into record
            if ($newData) {
                foreach ($newData as $k => $v) {
                    if (in_array($k, $columns)) {
                        $this->record[$k] = $v;
                        $this->original[$k] = $v;
                        unset($this->record[$k . '_Lazy']);
                    }
                }

            // No data means that the query returned nothing; assign 'null' to all the requested fields
            } else {
                foreach ($columns as $k) {
                    $this->record[$k] = null;
                    $this->original[$k] = null;
                    unset($this->record[$k . '_Lazy']);
                }
            }
        }
        return true;
    }

    /**
     * Return the fields that have changed.
     *
     * The change level affects what the functions defines as "changed":
     * - Level CHANGE_STRICT (integer 1) will return strict changes, even !== ones.
     * - Level CHANGE_VALUE (integer 2) is more lenient, it will only return real data changes,
     *   for example a change from 0 to null would not be included.
     *
     * Example return:
     * <code>
     * array(
     *   'Title' = array('before' => 'Home', 'after' => 'Home-Changed', 'level' => DataObject::CHANGE_VALUE)
     * )
     * </code>
     *
     * @param boolean|array $databaseFieldsOnly Filter to determine which fields to return. Set to true
     * to return all database fields, or an array for an explicit filter. false returns all fields.
     * @param int $changeLevel The strictness of what is defined as change. Defaults to strict
     * @return array
     */
    public function getChangedFields($databaseFieldsOnly = false, $changeLevel = self::CHANGE_STRICT)
    {
        $changedFields = array();

        // Update the changed array with references to changed obj-fields
        foreach ($this->record as $k => $v) {
            // Prevents DBComposite infinite looping on isChanged
            if (is_array($databaseFieldsOnly) && !in_array($k, $databaseFieldsOnly)) {
                continue;
            }
            if (is_object($v) && method_exists($v, 'isChanged') && $v->isChanged()) {
                $this->changed[$k] = self::CHANGE_VALUE;
            }
        }

        if (is_array($databaseFieldsOnly)) {
            $fields = array_intersect_key((array)$this->changed, array_flip($databaseFieldsOnly));
        } elseif ($databaseFieldsOnly) {
            $fieldsSpecs = static::getSchema()->fieldSpecs(static::class);
            $fields = array_intersect_key((array)$this->changed, $fieldsSpecs);
        } else {
            $fields = $this->changed;
        }

        // Filter the list to those of a certain change level
        if ($changeLevel > self::CHANGE_STRICT) {
            if ($fields) {
                foreach ($fields as $name => $level) {
                    if ($level < $changeLevel) {
                        unset($fields[$name]);
                    }
                }
            }
        }

        if ($fields) {
            foreach ($fields as $name => $level) {
                $changedFields[$name] = array(
                    'before' => array_key_exists($name, $this->original) ? $this->original[$name] : null,
                    'after' => array_key_exists($name, $this->record) ? $this->record[$name] : null,
                    'level' => $level
                );
            }
        }

        return $changedFields;
    }

    /**
     * Uses {@link getChangedFields()} to determine if fields have been changed
     * since loading them from the database.
     *
     * @param string $fieldName Name of the database field to check, will check for any if not given
     * @param int $changeLevel See {@link getChangedFields()}
     * @return boolean
     */
    public function isChanged($fieldName = null, $changeLevel = self::CHANGE_STRICT)
    {
        $fields = $fieldName ? array($fieldName) : true;
        $changed = $this->getChangedFields($fields, $changeLevel);
        if (!isset($fieldName)) {
            return !empty($changed);
        } else {
            return array_key_exists($fieldName, $changed);
        }
    }

    /**
     * Set the value of the field
     * Called by {@link __set()} and any setFieldName() methods you might create.
     *
     * @param string $fieldName Name of the field
     * @param mixed $val New field value
     * @return $this
     */
    public function setField($fieldName, $val)
    {
        $this->objCacheClear();
        //if it's a has_one component, destroy the cache
        if (substr($fieldName, -2) == 'ID') {
            unset($this->components[substr($fieldName, 0, -2)]);
        }

        // If we've just lazy-loaded the column, then we need to populate the $original array
        if (isset($this->record[$fieldName . '_Lazy'])) {
            $tableClass = $this->record[$fieldName . '_Lazy'];
            $this->loadLazyFields($tableClass);
        }

        // Support component assignent via field setter
        $schema = static::getSchema();
        if ($schema->unaryComponent(static::class, $fieldName)) {
            unset($this->components[$fieldName]);
            // Assign component directly
            if (is_null($val) || $val instanceof DataObject) {
                return $this->setComponent($fieldName, $val);
            }
            // Assign by ID instead of object
            if (is_numeric($val)) {
                $fieldName .= 'ID';
            }
        }

        // Situation 1: Passing an DBField
        if ($val instanceof DBField) {
            $val->setName($fieldName);
            $val->saveInto($this);

            // Situation 1a: Composite fields should remain bound in case they are
            // later referenced to update the parent dataobject
            if ($val instanceof DBComposite) {
                $val->bindTo($this);
                $this->record[$fieldName] = $val;
            }
        // Situation 2: Passing a literal or non-DBField object
        } else {
            // If this is a proper database field, we shouldn't be getting non-DBField objects
            if (is_object($val) && $schema->fieldSpec(static::class, $fieldName)) {
                throw new InvalidArgumentException('DataObject::setField: passed an object that is not a DBField');
            }

            // if a field is not existing or has strictly changed
            if (!isset($this->record[$fieldName]) || $this->record[$fieldName] !== $val) {
                // TODO Add check for php-level defaults which are not set in the db
                // TODO Add check for hidden input-fields (readonly) which are not set in the db
                // At the very least, the type has changed
                $this->changed[$fieldName] = self::CHANGE_STRICT;

                if ((!isset($this->record[$fieldName]) && $val)
                    || (isset($this->record[$fieldName]) && $this->record[$fieldName] != $val)
                ) {
                    // Value has changed as well, not just the type
                    $this->changed[$fieldName] = self::CHANGE_VALUE;
                }

                // Value is always saved back when strict check succeeds.
                $this->record[$fieldName] = $val;
            }
        }
        return $this;
    }

    /**
     * Set the value of the field, using a casting object.
     * This is useful when you aren't sure that a date is in SQL format, for example.
     * setCastedField() can also be used, by forms, to set related data.  For example, uploaded images
     * can be saved into the Image table.
     *
     * @param string $fieldName Name of the field
     * @param mixed $value New field value
     * @return $this
     */
    public function setCastedField($fieldName, $value)
    {
        if (!$fieldName) {
            user_error("DataObject::setCastedField: Called without a fieldName", E_USER_ERROR);
        }
        $fieldObj = $this->dbObject($fieldName);
        if ($fieldObj) {
            $fieldObj->setValue($value);
            $fieldObj->saveInto($this);
        } else {
            $this->$fieldName = $value;
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function castingHelper($field)
    {
        $fieldSpec = static::getSchema()->fieldSpec(static::class, $field);
        if ($fieldSpec) {
            return $fieldSpec;
        }

        // many_many_extraFields aren't presented by db(), so we check if the source query params
        // provide us with meta-data for a many_many relation we can inspect for extra fields.
        $queryParams = $this->getSourceQueryParams();
        if (!empty($queryParams['Component.ExtraFields'])) {
            $extraFields = $queryParams['Component.ExtraFields'];

            if (isset($extraFields[$field])) {
                return $extraFields[$field];
            }
        }

        return parent::castingHelper($field);
    }

    /**
     * Returns true if the given field exists in a database column on any of
     * the objects tables and optionally look up a dynamic getter with
     * get<fieldName>().
     *
     * @param string $field Name of the field
     * @return boolean True if the given field exists
     */
    public function hasField($field)
    {
        $schema = static::getSchema();
        return (
            array_key_exists($field, $this->record)
            || array_key_exists($field, $this->components)
            || $schema->fieldSpec(static::class, $field)
            || $schema->unaryComponent(static::class, $field)
            || $this->hasMethod("get{$field}")
        );
    }

    /**
     * Returns true if the given field exists as a database column
     *
     * @param string $field Name of the field
     *
     * @return boolean
     */
    public function hasDatabaseField($field)
    {
        $spec = static::getSchema()->fieldSpec(static::class, $field, DataObjectSchema::DB_ONLY);
        return !empty($spec);
    }

    /**
     * Returns true if the member is allowed to do the given action.
     * See {@link extendedCan()} for a more versatile tri-state permission control.
     *
     * @param string $perm The permission to be checked, such as 'View'.
     * @param Member $member The member whose permissions need checking.  Defaults to the currently logged
     * in user.
     * @param array $context Additional $context to pass to extendedCan()
     *
     * @return boolean True if the the member is allowed to do the given action
     */
    public function can($perm, $member = null, $context = array())
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        if ($member && Permission::checkMember($member, "ADMIN")) {
            return true;
        }

        if (is_string($perm) && method_exists($this, 'can' . ucfirst($perm))) {
            $method = 'can' . ucfirst($perm);
            return $this->$method($member);
        }

        $results = $this->extendedCan('can', $member);
        if (isset($results)) {
            return $results;
        }

        return ($member && Permission::checkMember($member, $perm));
    }

    /**
     * Process tri-state responses from permission-alterting extensions.  The extensions are
     * expected to return one of three values:
     *
     *  - false: Disallow this permission, regardless of what other extensions say
     *  - true: Allow this permission, as long as no other extensions return false
     *  - NULL: Don't affect the outcome
     *
     * This method itself returns a tri-state value, and is designed to be used like this:
     *
     * <code>
     * $extended = $this->extendedCan('canDoSomething', $member);
     * if($extended !== null) return $extended;
     * else return $normalValue;
     * </code>
     *
     * @param string $methodName Method on the same object, e.g. {@link canEdit()}
     * @param Member|int $member
     * @param array $context Optional context
     * @return boolean|null
     */
    public function extendedCan($methodName, $member, $context = array())
    {
        $results = $this->extend($methodName, $member, $context);
        if ($results && is_array($results)) {
            // Remove NULLs
            $results = array_filter($results, function ($v) {
                return !is_null($v);
            });
            // If there are any non-NULL responses, then return the lowest one of them.
            // If any explicitly deny the permission, then we don't get access
            if ($results) {
                return min($results);
            }
        }
        return null;
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canView($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return Permission::check('ADMIN', 'any', $member);
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canEdit($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return Permission::check('ADMIN', 'any', $member);
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canDelete($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return Permission::check('ADMIN', 'any', $member);
    }

    /**
     * @param Member $member
     * @param array $context Additional context-specific data which might
     * affect whether (or where) this object could be created.
     * @return boolean
     */
    public function canCreate($member = null, $context = array())
    {
        $extended = $this->extendedCan(__FUNCTION__, $member, $context);
        if ($extended !== null) {
            return $extended;
        }
        return Permission::check('ADMIN', 'any', $member);
    }

    /**
     * Debugging used by Debug::show()
     *
     * @return string HTML data representing this object
     */
    public function debug()
    {
        $class = static::class;
        $val = "<h3>Database record: {$class}</h3>\n<ul>\n";
        if ($this->record) {
            foreach ($this->record as $fieldName => $fieldVal) {
                $val .= "\t<li>$fieldName: " . Debug::text($fieldVal) . "</li>\n";
            }
        }
        $val .= "</ul>\n";
        return $val;
    }

    /**
     * Return the DBField object that represents the given field.
     * This works similarly to obj() with 2 key differences:
     *   - it still returns an object even when the field has no value.
     *   - it only matches fields and not methods
     *   - it matches foreign keys generated by has_one relationships, eg, "ParentID"
     *
     * @param string $fieldName Name of the field
     * @return DBField The field as a DBField object
     */
    public function dbObject($fieldName)
    {
        // Check for field in DB
        $schema = static::getSchema();
        $helper = $schema->fieldSpec(static::class, $fieldName, DataObjectSchema::INCLUDE_CLASS);
        if (!$helper) {
            return null;
        }

        if (!isset($this->record[$fieldName]) && isset($this->record[$fieldName . '_Lazy'])) {
            $tableClass = $this->record[$fieldName . '_Lazy'];
            $this->loadLazyFields($tableClass);
        }

        $value = isset($this->record[$fieldName])
            ? $this->record[$fieldName]
            : null;

        // If we have a DBField object in $this->record, then return that
        if ($value instanceof DBField) {
            return $value;
        }

        list($class, $spec) = explode('.', $helper);
        /** @var DBField $obj */
        $table = $schema->tableName($class);
        $obj = Injector::inst()->create($spec, $fieldName);
        $obj->setTable($table);
        $obj->setValue($value, $this, false);
        return $obj;
    }

    /**
     * Traverses to a DBField referenced by relationships between data objects.
     *
     * The path to the related field is specified with dot separated syntax
     * (eg: Parent.Child.Child.FieldName).
     *
     * If a relation is blank, this will return null instead.
     * If a relation name is invalid (e.g. non-relation on a parent) this
     * can throw a LogicException.
     *
     * @param string $fieldPath List of paths on this object. All items in this path
     * must be ViewableData implementors
     *
     * @return mixed DBField of the field on the object or a DataList instance.
     * @throws LogicException If accessing invalid relations
     */
    public function relObject($fieldPath)
    {
        $object = null;
        $component = $this;

        // Parse all relations
        foreach (explode('.', $fieldPath) as $relation) {
            if (!$component) {
                return null;
            }

            // Inspect relation type
            if (ClassInfo::hasMethod($component, $relation)) {
                $component = $component->$relation();
            } elseif ($component instanceof Relation || $component instanceof DataList) {
                // $relation could either be a field (aggregate), or another relation
                $singleton = DataObject::singleton($component->dataClass());
                $component = $singleton->dbObject($relation) ?: $component->relation($relation);
            } elseif ($component instanceof DataObject && ($dbObject = $component->dbObject($relation))) {
                $component = $dbObject;
            } elseif ($component instanceof ViewableData && $component->hasField($relation)) {
                $component = $component->obj($relation);
            } else {
                throw new LogicException(
                    "$relation is not a relation/field on " . get_class($component)
                );
            }
        }
        return $component;
    }

    /**
     * Traverses to a field referenced by relationships between data objects, returning the value
     * The path to the related field is specified with dot separated syntax (eg: Parent.Child.Child.FieldName)
     *
     * @param string $fieldName string
     * @return mixed Will return null on a missing value
     */
    public function relField($fieldName)
    {
        // Navigate to relative parent using relObject() if needed
        $component = $this;
        if (($pos = strrpos($fieldName, '.')) !== false) {
            $relation = substr($fieldName, 0, $pos);
            $fieldName = substr($fieldName, $pos + 1);
            $component = $this->relObject($relation);
        }

        // Bail if the component is null
        if (!$component) {
            return null;
        }
        if (ClassInfo::hasMethod($component, $fieldName)) {
            return $component->$fieldName();
        }
        return $component->$fieldName;
    }

    /**
     * Temporary hack to return an association name, based on class, to get around the mangle
     * of having to deal with reverse lookup of relationships to determine autogenerated foreign keys.
     *
     * @param string $className
     * @return string
     */
    public function getReverseAssociation($className)
    {
        if (is_array($this->manyMany())) {
            $many_many = array_flip($this->manyMany());
            if (array_key_exists($className, $many_many)) {
                return $many_many[$className];
            }
        }
        if (is_array($this->hasMany())) {
            $has_many = array_flip($this->hasMany());
            if (array_key_exists($className, $has_many)) {
                return $has_many[$className];
            }
        }
        if (is_array($this->hasOne())) {
            $has_one = array_flip($this->hasOne());
            if (array_key_exists($className, $has_one)) {
                return $has_one[$className];
            }
        }

        return false;
    }

    /**
     * Return all objects matching the filter
     * sub-classes are automatically selected and included
     *
     * @param string $callerClass The class of objects to be returned
     * @param string|array $filter A filter to be inserted into the WHERE clause.
     * Supports parameterised queries. See SQLSelect::addWhere() for syntax examples.
     * @param string|array $sort A sort expression to be inserted into the ORDER
     * BY clause.  If omitted, self::$default_sort will be used.
     * @param string $join Deprecated 3.0 Join clause. Use leftJoin($table, $joinClause) instead.
     * @param string|array $limit A limit expression to be inserted into the LIMIT clause.
     * @param string $containerClass The container class to return the results in.
     *
     * @todo $containerClass is Ignored, why?
     *
     * @return DataList The objects matching the filter, in the class specified by $containerClass
     */
    public static function get(
        $callerClass = null,
        $filter = "",
        $sort = "",
        $join = "",
        $limit = null,
        $containerClass = DataList::class
    ) {
        // Validate arguments
        if ($callerClass == null) {
            $callerClass = get_called_class();
            if ($callerClass === self::class) {
                throw new InvalidArgumentException('Call <classname>::get() instead of DataObject::get()');
            }
            if ($filter || $sort || $join || $limit || ($containerClass !== DataList::class)) {
                throw new InvalidArgumentException('If calling <classname>::get() then you shouldn\'t pass any other'
                    . ' arguments');
            }
        } elseif ($callerClass === self::class) {
            throw new InvalidArgumentException('DataObject::get() cannot query non-subclass DataObject directly');
        }
        if ($join) {
            throw new InvalidArgumentException(
                'The $join argument has been removed. Use leftJoin($table, $joinClause) instead.'
            );
        }

        // Build and decorate with args
        $result = DataList::create($callerClass);
        if ($filter) {
            $result = $result->where($filter);
        }
        if ($sort) {
            $result = $result->sort($sort);
        }
        if ($limit && strpos($limit, ',') !== false) {
            $limitArguments = explode(',', $limit);
            $result = $result->limit($limitArguments[1], $limitArguments[0]);
        } elseif ($limit) {
            $result = $result->limit($limit);
        }

        return $result;
    }


    /**
     * Return the first item matching the given query.
     * All calls to get_one() are cached.
     *
     * @param string $callerClass The class of objects to be returned
     * @param string|array $filter A filter to be inserted into the WHERE clause.
     * Supports parameterised queries. See SQLSelect::addWhere() for syntax examples.
     * @param boolean $cache Use caching
     * @param string $orderby A sort expression to be inserted into the ORDER BY clause.
     *
     * @return DataObject|null The first item matching the query
     */
    public static function get_one($callerClass, $filter = "", $cache = true, $orderby = "")
    {
        $SNG = singleton($callerClass);

        $cacheComponents = array($filter, $orderby, $SNG->extend('cacheKeyComponent'));
        $cacheKey = md5(serialize($cacheComponents));

        $item = null;
        if (!$cache || !isset(self::$_cache_get_one[$callerClass][$cacheKey])) {
            $dl = DataObject::get($callerClass)->where($filter)->sort($orderby);
            $item = $dl->first();

            if ($cache) {
                self::$_cache_get_one[$callerClass][$cacheKey] = $item;
                if (!self::$_cache_get_one[$callerClass][$cacheKey]) {
                    self::$_cache_get_one[$callerClass][$cacheKey] = false;
                }
            }
        }

        if ($cache) {
            return self::$_cache_get_one[$callerClass][$cacheKey] ?: null;
        }

        return $item;
    }

    /**
     * Flush the cached results for all relations (has_one, has_many, many_many)
     * Also clears any cached aggregate data.
     *
     * @param boolean $persistent When true will also clear persistent data stored in the Cache system.
     *                            When false will just clear session-local cached data
     * @return DataObject $this
     */
    public function flushCache($persistent = true)
    {
        if (static::class == self::class) {
            self::$_cache_get_one = array();
            return $this;
        }

        $classes = ClassInfo::ancestry(static::class);
        foreach ($classes as $class) {
            if (isset(self::$_cache_get_one[$class])) {
                unset(self::$_cache_get_one[$class]);
            }
        }

        $this->extend('flushCache');

        $this->components = array();
        return $this;
    }

    /**
     * Flush the get_one global cache and destroy associated objects.
     */
    public static function flush_and_destroy_cache()
    {
        if (self::$_cache_get_one) {
            foreach (self::$_cache_get_one as $class => $items) {
                if (is_array($items)) {
                    foreach ($items as $item) {
                        if ($item) {
                            $item->destroy();
                        }
                    }
                }
            }
        }
        self::$_cache_get_one = array();
    }

    /**
     * Reset all global caches associated with DataObject.
     */
    public static function reset()
    {
        // @todo Decouple these
        DBClassName::clear_classname_cache();
        ClassInfo::reset_db_cache();
        static::getSchema()->reset();
        self::$_cache_get_one = array();
        self::$_cache_field_labels = array();
    }

    /**
     * Return the given element, searching by ID.
     *
     * This can be called either via `DataObject::get_by_id(MyClass::class, $id)`
     * or `MyClass::get_by_id($id)`
     *
     * @param string|int $classOrID The class of the object to be returned, or id if called on target class
     * @param int|bool $idOrCache The id of the element, or cache if called on target class
     * @param boolean $cache See {@link get_one()}
     *
     * @return static The element
     */
    public static function get_by_id($classOrID, $idOrCache = null, $cache = true)
    {
        // Shift arguments if passing id in first or second argument
        list ($class, $id, $cached) = is_numeric($classOrID)
            ? [get_called_class(), $classOrID, isset($idOrCache) ? $idOrCache : $cache]
            : [$classOrID, $idOrCache, $cache];

        // Validate class
        if ($class === self::class) {
            throw new InvalidArgumentException('DataObject::get_by_id() cannot query non-subclass DataObject directly');
        }

        // Pass to get_one
        $column = static::getSchema()->sqlColumnForField($class, 'ID');
        return DataObject::get_one($class, [$column => $id], $cached);
    }

    /**
     * Get the name of the base table for this object
     *
     * @return string
     */
    public function baseTable()
    {
        return static::getSchema()->baseDataTable($this);
    }

    /**
     * Get the base class for this object
     *
     * @return string
     */
    public function baseClass()
    {
        return static::getSchema()->baseDataClass($this);
    }

    /**
     * @var array Parameters used in the query that built this object.
     * This can be used by decorators (e.g. lazy loading) to
     * run additional queries using the same context.
     */
    protected $sourceQueryParams;

    /**
     * @see $sourceQueryParams
     * @return array
     */
    public function getSourceQueryParams()
    {
        return $this->sourceQueryParams;
    }

    /**
     * Get list of parameters that should be inherited to relations on this object
     *
     * @return array
     */
    public function getInheritableQueryParams()
    {
        $params = $this->getSourceQueryParams();
        $this->extend('updateInheritableQueryParams', $params);
        return $params;
    }

    /**
     * @see $sourceQueryParams
     * @param array
     */
    public function setSourceQueryParams($array)
    {
        $this->sourceQueryParams = $array;
    }

    /**
     * @see $sourceQueryParams
     * @param string $key
     * @param string $value
     */
    public function setSourceQueryParam($key, $value)
    {
        $this->sourceQueryParams[$key] = $value;
    }

    /**
     * @see $sourceQueryParams
     * @param string $key
     * @return string
     */
    public function getSourceQueryParam($key)
    {
        if (isset($this->sourceQueryParams[$key])) {
            return $this->sourceQueryParams[$key];
        }
        return null;
    }

    //-------------------------------------------------------------------------------------------//

    /**
     * Check the database schema and update it as necessary.
     *
     * @uses DataExtension->augmentDatabase()
     */
    public function requireTable()
    {
        // Only build the table if we've actually got fields
        $schema = static::getSchema();
        $table = $schema->tableName(static::class);
        $fields = $schema->databaseFields(static::class, false);
        $indexes = $schema->databaseIndexes(static::class, false);
        $extensions = self::database_extensions(static::class);

        if (empty($table)) {
            throw new LogicException(
                "Class " . static::class . " not loaded by manifest, or no database table configured"
            );
        }

        if ($fields) {
            $hasAutoIncPK = get_parent_class($this) === self::class;
            DB::require_table(
                $table,
                $fields,
                $indexes,
                $hasAutoIncPK,
                $this->config()->get('create_table_options'),
                $extensions
            );
        } else {
            DB::dont_require_table($table);
        }

        // Build any child tables for many_many items
        if ($manyMany = $this->uninherited('many_many')) {
            $extras = $this->uninherited('many_many_extraFields');
            foreach ($manyMany as $component => $spec) {
                // Get many_many spec
                $manyManyComponent = $schema->manyManyComponent(static::class, $component);
                $parentField = $manyManyComponent['parentField'];
                $childField = $manyManyComponent['childField'];
                $tableOrClass = $manyManyComponent['join'];

                // Skip if backed by actual class
                if (class_exists($tableOrClass)) {
                    continue;
                }

                // Build fields
                $manymanyFields = array(
                    $parentField => "Int",
                    $childField => "Int",
                );
                if (isset($extras[$component])) {
                    $manymanyFields = array_merge($manymanyFields, $extras[$component]);
                }

                // Build index list
                $manymanyIndexes = [
                    $parentField => [
                        'type' => 'index',
                        'name' => $parentField,
                        'columns' => [$parentField],
                    ],
                    $childField => [
                        'type' => 'index',
                        'name' => $childField,
                        'columns' => [$childField],
                    ],
                ];
                DB::require_table($tableOrClass, $manymanyFields, $manymanyIndexes, true, null, $extensions);
            }
        }

        // Let any extentions make their own database fields
        $this->extend('augmentDatabase', $dummy);
    }

    /**
     * Add default records to database. This function is called whenever the
     * database is built, after the database tables have all been created. Overload
     * this to add default records when the database is built, but make sure you
     * call parent::requireDefaultRecords().
     *
     * @uses DataExtension->requireDefaultRecords()
     */
    public function requireDefaultRecords()
    {
        $defaultRecords = $this->config()->uninherited('default_records');

        if (!empty($defaultRecords)) {
            $hasData = DataObject::get_one(static::class);
            if (!$hasData) {
                $className = static::class;
                foreach ($defaultRecords as $record) {
                    $obj = Injector::inst()->create($className, $record);
                    $obj->write();
                }
                DB::alteration_message("Added default records to $className table", "created");
            }
        }

        // Let any extentions make their own database default data
        $this->extend('requireDefaultRecords', $dummy);
    }

    /**
     * Get the default searchable fields for this object, as defined in the
     * $searchable_fields list. If searchable fields are not defined on the
     * data object, uses a default selection of summary fields.
     *
     * @return array
     */
    public function searchableFields()
    {
        // can have mixed format, need to make consistent in most verbose form
        $fields = $this->config()->get('searchable_fields');
        $labels = $this->fieldLabels();

        // fallback to summary fields (unless empty array is explicitly specified)
        if (!$fields && !is_array($fields)) {
            $summaryFields = array_keys($this->summaryFields());
            $fields = array();

            // remove the custom getters as the search should not include them
            $schema = static::getSchema();
            if ($summaryFields) {
                foreach ($summaryFields as $key => $name) {
                    $spec = $name;

                    // Extract field name in case this is a method called on a field (e.g. "Date.Nice")
                    if (($fieldPos = strpos($name, '.')) !== false) {
                        $name = substr($name, 0, $fieldPos);
                    }

                    if ($schema->fieldSpec($this, $name)) {
                        $fields[] = $name;
                    } elseif ($this->relObject($spec)) {
                        $fields[] = $spec;
                    }
                }
            }
        }

        // we need to make sure the format is unified before
        // augmenting fields, so extensions can apply consistent checks
        // but also after augmenting fields, because the extension
        // might use the shorthand notation as well

        // rewrite array, if it is using shorthand syntax
        $rewrite = array();
        foreach ($fields as $name => $specOrName) {
            $identifer = (is_int($name)) ? $specOrName : $name;

            if (is_int($name)) {
                // Format: array('MyFieldName')
                $rewrite[$identifer] = array();
            } elseif (is_array($specOrName) && ($relObject = $this->relObject($identifer))) {
                // Format: array('MyFieldName' => array(
                //   'filter => 'ExactMatchFilter',
                //   'field' => 'NumericField', // optional
                //   'title' => 'My Title', // optional
                // ))
                $rewrite[$identifer] = array_merge(
                    array('filter' => $relObject->config()->get('default_search_filter_class')),
                    (array)$specOrName
                );
            } else {
                // Format: array('MyFieldName' => 'ExactMatchFilter')
                $rewrite[$identifer] = array(
                    'filter' => $specOrName,
                );
            }
            if (!isset($rewrite[$identifer]['title'])) {
                $rewrite[$identifer]['title'] = (isset($labels[$identifer]))
                    ? $labels[$identifer] : FormField::name_to_label($identifer);
            }
            if (!isset($rewrite[$identifer]['filter'])) {
                /** @skipUpgrade */
                $rewrite[$identifer]['filter'] = 'PartialMatchFilter';
            }
        }

        $fields = $rewrite;

        // apply DataExtensions if present
        $this->extend('updateSearchableFields', $fields);

        return $fields;
    }

    /**
     * Get any user defined searchable fields labels that
     * exist. Allows overriding of default field names in the form
     * interface actually presented to the user.
     *
     * The reason for keeping this separate from searchable_fields,
     * which would be a logical place for this functionality, is to
     * avoid bloating and complicating the configuration array. Currently
     * much of this system is based on sensible defaults, and this property
     * would generally only be set in the case of more complex relationships
     * between data object being required in the search interface.
     *
     * Generates labels based on name of the field itself, if no static property
     * {@link self::field_labels} exists.
     *
     * @uses $field_labels
     * @uses FormField::name_to_label()
     *
     * @param boolean $includerelations a boolean value to indicate if the labels returned include relation fields
     *
     * @return array Array of all element labels
     */
    public function fieldLabels($includerelations = true)
    {
        $cacheKey = static::class . '_' . $includerelations;

        if (!isset(self::$_cache_field_labels[$cacheKey])) {
            $customLabels = $this->config()->get('field_labels');
            $autoLabels = array();

            // get all translated static properties as defined in i18nCollectStatics()
            $ancestry = ClassInfo::ancestry(static::class);
            $ancestry = array_reverse($ancestry);
            if ($ancestry) {
                foreach ($ancestry as $ancestorClass) {
                    if ($ancestorClass === ViewableData::class) {
                        break;
                    }
                    $types = [
                        'db' => (array)Config::inst()->get($ancestorClass, 'db', Config::UNINHERITED)
                    ];
                    if ($includerelations) {
                        $types['has_one'] = (array)Config::inst()->get($ancestorClass, 'has_one', Config::UNINHERITED);
                        $types['has_many'] = (array)Config::inst()->get(
                            $ancestorClass,
                            'has_many',
                            Config::UNINHERITED
                        );
                        $types['many_many'] = (array)Config::inst()->get(
                            $ancestorClass,
                            'many_many',
                            Config::UNINHERITED
                        );
                        $types['belongs_many_many'] = (array)Config::inst()->get(
                            $ancestorClass,
                            'belongs_many_many',
                            Config::UNINHERITED
                        );
                    }
                    foreach ($types as $type => $attrs) {
                        foreach ($attrs as $name => $spec) {
                            $autoLabels[$name] = _t(
                                "{$ancestorClass}.{$type}_{$name}",
                                FormField::name_to_label($name)
                            );
                        }
                    }
                }
            }

            $labels = array_merge((array)$autoLabels, (array)$customLabels);
            $this->extend('updateFieldLabels', $labels);
            self::$_cache_field_labels[$cacheKey] = $labels;
        }

        return self::$_cache_field_labels[$cacheKey];
    }

    /**
     * Get a human-readable label for a single field,
     * see {@link fieldLabels()} for more details.
     *
     * @uses fieldLabels()
     * @uses FormField::name_to_label()
     *
     * @param string $name Name of the field
     * @return string Label of the field
     */
    public function fieldLabel($name)
    {
        $labels = $this->fieldLabels();
        return (isset($labels[$name])) ? $labels[$name] : FormField::name_to_label($name);
    }

    /**
     * Get the default summary fields for this object.
     *
     * @todo use the translation apparatus to return a default field selection for the language
     *
     * @return array
     */
    public function summaryFields()
    {
        $rawFields = $this->config()->get('summary_fields');

        // Merge associative / numeric keys
        $fields = [];
        foreach ($rawFields as $key => $value) {
            if (is_int($key)) {
                $key = $value;
            }
            $fields[$key] = $value;
        }

        if (!$fields) {
            $fields = array();
            // try to scaffold a couple of usual suspects
            if ($this->hasField('Name')) {
                $fields['Name'] = 'Name';
            }
            if (static::getSchema()->fieldSpec($this, 'Title')) {
                $fields['Title'] = 'Title';
            }
            if ($this->hasField('Description')) {
                $fields['Description'] = 'Description';
            }
            if ($this->hasField('FirstName')) {
                $fields['FirstName'] = 'First Name';
            }
        }
        $this->extend("updateSummaryFields", $fields);

        // Final fail-over, just list ID field
        if (!$fields) {
            $fields['ID'] = 'ID';
        }

        // Localize fields (if possible)
        foreach ($this->fieldLabels(false) as $name => $label) {
            // only attempt to localize if the label definition is the same as the field name.
            // this will preserve any custom labels set in the summary_fields configuration
            if (isset($fields[$name]) && $name === $fields[$name]) {
                $fields[$name] = $label;
            }
        }

        return $fields;
    }

    /**
     * Defines a default list of filters for the search context.
     *
     * If a filter class mapping is defined on the data object,
     * it is constructed here. Otherwise, the default filter specified in
     * {@link DBField} is used.
     *
     * @todo error handling/type checking for valid FormField and SearchFilter subclasses?
     *
     * @return array
     */
    public function defaultSearchFilters()
    {
        $filters = array();

        foreach ($this->searchableFields() as $name => $spec) {
            if (empty($spec['filter'])) {
                /** @skipUpgrade */
                $filters[$name] = 'PartialMatchFilter';
            } elseif ($spec['filter'] instanceof SearchFilter) {
                $filters[$name] = $spec['filter'];
            } else {
                $filters[$name] = Injector::inst()->create($spec['filter'], $name);
            }
        }

        return $filters;
    }

    /**
     * @return boolean True if the object is in the database
     */
    public function isInDB()
    {
        return is_numeric($this->ID) && $this->ID > 0;
    }

    /*
     * @ignore
     */
    private static $subclass_access = true;

    /**
     * Temporarily disable subclass access in data object qeur
     */
    public static function disable_subclass_access()
    {
        self::$subclass_access = false;
    }

    public static function enable_subclass_access()
    {
        self::$subclass_access = true;
    }

    //-------------------------------------------------------------------------------------------//

    /**
     * Database field definitions.
     * This is a map from field names to field type. The field
     * type should be a class that extends .
     * @var array
     * @config
     */
    private static $db = [];

    /**
     * Use a casting object for a field. This is a map from
     * field name to class name of the casting object.
     *
     * @var array
     */
    private static $casting = array(
        "Title" => 'Text',
    );

    /**
     * Specify custom options for a CREATE TABLE call.
     * Can be used to specify a custom storage engine for specific database table.
     * All options have to be keyed for a specific database implementation,
     * identified by their class name (extending from {@link SS_Database}).
     *
     * <code>
     * array(
     *  'MySQLDatabase' => 'ENGINE=MyISAM'
     * )
     * </code>
     *
     * Caution: This API is experimental, and might not be
     * included in the next major release. Please use with care.
     *
     * @var array
     * @config
     */
    private static $create_table_options = array(
        MySQLSchemaManager::ID => 'ENGINE=InnoDB'
    );

    /**
     * If a field is in this array, then create a database index
     * on that field. This is a map from fieldname to index type.
     * See {@link SS_Database->requireIndex()} and custom subclasses for details on the array notation.
     *
     * @var array
     * @config
     */
    private static $indexes = null;

    /**
     * Inserts standard column-values when a DataObject
     * is instantiated. Does not insert default records {@see $default_records}.
     * This is a map from fieldname to default value.
     *
     *  - If you would like to change a default value in a sub-class, just specify it.
     *  - If you would like to disable the default value given by a parent class, set the default value to 0,'',
     *    or false in your subclass.  Setting it to null won't work.
     *
     * @var array
     * @config
     */
    private static $defaults = [];

    /**
     * Multidimensional array which inserts default data into the database
     * on a db/build-call as long as the database-table is empty. Please use this only
     * for simple constructs, not for SiteTree-Objects etc. which need special
     * behaviour such as publishing and ParentNodes.
     *
     * Example:
     * array(
     *  array('Title' => "DefaultPage1", 'PageTitle' => 'page1'),
     *  array('Title' => "DefaultPage2")
     * ).
     *
     * @var array
     * @config
     */
    private static $default_records = null;

    /**
     * One-to-zero relationship defintion. This is a map of component name to data type. In order to turn this into a
     * true one-to-one relationship you can add a {@link DataObject::$belongs_to} relationship on the child class.
     *
     * Note that you cannot have a has_one and belongs_to relationship with the same name.
     *
     * @var array
     * @config
     */
    private static $has_one = [];

    /**
     * A meta-relationship that allows you to define the reverse side of a {@link DataObject::$has_one}.
     *
     * This does not actually create any data structures, but allows you to query the other object in a one-to-one
     * relationship from the child object. If you have multiple belongs_to links to another object you can use the
     * syntax "ClassName.HasOneName" to specify which foreign has_one key on the other object to use.
     *
     * Note that you cannot have a has_one and belongs_to relationship with the same name.
     *
     * @var array
     * @config
     */
    private static $belongs_to = [];

    /**
     * This defines a one-to-many relationship. It is a map of component name to the remote data class.
     *
     * This relationship type does not actually create a data structure itself - you need to define a matching $has_one
     * relationship on the child class. Also, if the $has_one relationship on the child class has multiple links to this
     * class you can use the syntax "ClassName.HasOneRelationshipName" in the remote data class definition to show
     * which foreign key to use.
     *
     * @var array
     * @config
     */
    private static $has_many = [];

    /**
     * many-many relationship definitions.
     * This is a map from component name to data type.
     * @var array
     * @config
     */
    private static $many_many = [];

    /**
     * Extra fields to include on the connecting many-many table.
     * This is a map from field name to field type.
     *
     * Example code:
     * <code>
     * public static $many_many_extraFields = array(
     *  'Members' => array(
     *          'Role' => 'Varchar(100)'
     *      )
     * );
     * </code>
     *
     * @var array
     * @config
     */
    private static $many_many_extraFields = [];

    /**
     * The inverse side of a many-many relationship.
     * This is a map from component name to data type.
     * @var array
     * @config
     */
    private static $belongs_many_many = [];

    /**
     * The default sort expression. This will be inserted in the ORDER BY
     * clause of a SQL query if no other sort expression is provided.
     * @var string
     * @config
     */
    private static $default_sort = null;

    /**
     * Default list of fields that can be scaffolded by the ModelAdmin
     * search interface.
     *
     * Overriding the default filter, with a custom defined filter:
     * <code>
     *  static $searchable_fields = array(
     *     "Name" => "PartialMatchFilter"
     *  );
     * </code>
     *
     * Overriding the default form fields, with a custom defined field.
     * The 'filter' parameter will be generated from {@link DBField::$default_search_filter_class}.
     * The 'title' parameter will be generated from {@link DataObject->fieldLabels()}.
     * <code>
     *  static $searchable_fields = array(
     *    "Name" => array(
     *      "field" => "TextField"
     *    )
     *  );
     * </code>
     *
     * Overriding the default form field, filter and title:
     * <code>
     *  static $searchable_fields = array(
     *    "Organisation.ZipCode" => array(
     *      "field" => "TextField",
     *      "filter" => "PartialMatchFilter",
     *      "title" => 'Organisation ZIP'
     *    )
     *  );
     * </code>
     * @config
     * @var array
     */
    private static $searchable_fields = null;

    /**
     * User defined labels for searchable_fields, used to override
     * default display in the search form.
     * @config
     * @var array
     */
    private static $field_labels = [];

    /**
     * Provides a default list of fields to be used by a 'summary'
     * view of this object.
     * @config
     * @var array
     */
    private static $summary_fields = [];

    public function provideI18nEntities()
    {
        // Note: see http://guides.rubyonrails.org/i18n.html#pluralization for rules
        // Best guess for a/an rule. Better guesses require overriding in subclasses
        $pluralName = $this->plural_name();
        $singularName = $this->singular_name();
        $conjunction = preg_match('/^[aeiou]/i', $singularName) ? 'An ' : 'A ';
        return [
            static::class . '.SINGULARNAME' => $this->singular_name(),
            static::class . '.PLURALNAME' => $pluralName,
            static::class . '.PLURALS' => [
                'one' => $conjunction . $singularName,
                'other' => '{count} ' . $pluralName
            ]
        ];
    }

    /**
     * Returns true if the given method/parameter has a value
     * (Uses the DBField::hasValue if the parameter is a database field)
     *
     * @param string $field The field name
     * @param array $arguments
     * @param bool $cache
     * @return boolean
     */
    public function hasValue($field, $arguments = null, $cache = true)
    {
        // has_one fields should not use dbObject to check if a value is given
        $hasOne = static::getSchema()->hasOneComponent(static::class, $field);
        if (!$hasOne && ($obj = $this->dbObject($field))) {
            return $obj->exists();
        } else {
            return parent::hasValue($field, $arguments, $cache);
        }
    }

    /**
     * If selected through a many_many through relation, this is the instance of the joined record
     *
     * @return DataObject
     */
    public function getJoin()
    {
        return $this->joinRecord;
    }

    /**
     * Set joining object
     *
     * @param DataObject $object
     * @param string $alias Alias
     * @return $this
     */
    public function setJoin(DataObject $object, $alias = null)
    {
        $this->joinRecord = $object;
        if ($alias) {
            if (static::getSchema()->fieldSpec(static::class, $alias)) {
                throw new InvalidArgumentException(
                    "Joined record $alias cannot also be a db field"
                );
            }
            $this->record[$alias] = $object;
        }
        return $this;
    }

    /**
     * Find objects in the given relationships, merging them into the given list
     *
     * @param string $source Config property to extract relationships from
     * @param bool $recursive True if recursive
     * @param ArrayList $list If specified, items will be added to this list. If not, a new
     * instance of ArrayList will be constructed and returned
     * @return ArrayList The list of related objects
     */
    public function findRelatedObjects($source, $recursive = true, $list = null)
    {
        if (!$list) {
            $list = new ArrayList();
        }

        // Skip search for unsaved records
        if (!$this->isInDB()) {
            return $list;
        }

        $relationships = $this->config()->get($source) ?: [];
        foreach ($relationships as $relationship) {
            // Warn if invalid config
            if (!$this->hasMethod($relationship)) {
                trigger_error(sprintf(
                    "Invalid %s config value \"%s\" on object on class \"%s\"",
                    $source,
                    $relationship,
                    get_class($this)
                ), E_USER_WARNING);
                continue;
            }

            // Inspect value of this relationship
            $items = $this->{$relationship}();

            // Merge any new item
            $newItems = $this->mergeRelatedObjects($list, $items);

            // Recurse if necessary
            if ($recursive) {
                foreach ($newItems as $item) {
                    /** @var DataObject $item */
                    $item->findRelatedObjects($source, true, $list);
                }
            }
        }
        return $list;
    }

    /**
     * Helper method to merge owned/owning items into a list.
     * Items already present in the list will be skipped.
     *
     * @param ArrayList $list Items to merge into
     * @param mixed $items List of new items to merge
     * @return ArrayList List of all newly added items that did not already exist in $list
     */
    public function mergeRelatedObjects($list, $items)
    {
        $added = new ArrayList();
        if (!$items) {
            return $added;
        }
        if ($items instanceof DataObject) {
            $items = [$items];
        }

        /** @var DataObject $item */
        foreach ($items as $item) {
            $this->mergeRelatedObject($list, $added, $item);
        }
        return $added;
    }

    /**
     * Merge single object into a list, but ensures that existing objects are not
     * re-added.
     *
     * @param ArrayList $list Global list
     * @param ArrayList $added Additional list to insert into
     * @param DataObject $item Item to add
     */
    protected function mergeRelatedObject($list, $added, $item)
    {
        // Identify item
        $itemKey = get_class($item) . '/' . $item->ID;

        // Write if saved, versioned, and not already added
        if ($item->isInDB() && !isset($list[$itemKey])) {
            $list[$itemKey] = $item;
            $added[$itemKey] = $item;
        }

        // Add joined record (from many_many through) automatically
        $joined = $item->getJoin();
        if ($joined) {
            $this->mergeRelatedObject($list, $added, $joined);
        }
    }
}
