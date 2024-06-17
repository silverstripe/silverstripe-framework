<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ViewableData;

/**
 * A base for bulk loaders of content into the SilverStripe database.
 * Bulk loaders give SilverStripe authors the ability to do large-scale uploads into their SilverStripe databases.
 *
 * You can configure column-handling,
 *
 * @see http://tools.ietf.org/html/rfc4180
 * @author Ingo Schommer, Silverstripe Ltd. (<firstname>@silverstripe.com)
 */
abstract class BulkLoader extends ViewableData
{
    private bool $checkPermissions = false;

    /**
     * Each row in the imported dataset should map to one instance
     * of this class (with optional property translation
     * through {@BulkLoader::$columnMaps}.
     *
     * @var string
     */
    public $objectClass;

    /**
     * Override this on subclasses to give the specific functions names.
     *
     * @var string
     */
    public static $title;

    /**
     * Map columns to DataObject-properties.
     * If not specified, we assume the first row
     * in the file contains the column headers.
     * The order of your array should match the column order.
     *
     * The column count should match the count of array elements,
     * fill with NULL values if you want to skip certain columns.
     *
     * You can also combine {@link $hasHeaderRow} = true and {@link $columnMap}
     * and omit the NULL values in your map.
     *
     * Supports one-level chaining of has_one relations and properties with dot notation
     * (e.g. Team.Title). The first part has to match a has_one relation name
     * (not necessarily the classname of the used relation).
     *
     * <code>
     * <?php
     *  // simple example
     *  [
     *      'Title',
     *      'Birthday'
     *  ]
     *
     * // complex example
     *  [
     *      'first name' => 'FirstName', // custom column name
     *      null, // ignored column
     *      'RegionID', // direct has_one/has_many ID setting
     *      'OrganisationTitle', // create has_one relation to existing record using $relationCallbacks
     *      'street' => 'Organisation.StreetName', // match an existing has_one or create one and write property.
     *  ];
     * ?>
     * </code>
     *
     * @var array
     */
    public $columnMap = [];

    /**
     * Find a has_one relation based on a specific column value.
     *
     * <code>
     * <?php
     * [
     *      'OrganisationTitle' => [
     *          'relationname' => 'Organisation', // relation accessor name
     *          'callback' => 'getOrganisationByTitle',
     *      ];
     * ];
     * ?>
     * </code>
     *
     * @var array
     */
    public $relationCallbacks = [];

    /**
     * Specifies how to determine duplicates based on one or more provided fields
     * in the imported data, matching to properties on the used {@link DataObject} class.
     * Alternatively the array values can contain a callback method (see example for
     * implementation details). The callback method should be defined on the source class.
     *
     * NOTE: If you're trying to get a unique Member record by a particular field that
     * isn't Email, you need to ensure that Member is correctly set to the unique field
     * you want, as it will merge any duplicates during {@link Member::onBeforeWrite()}.
     *
     * {@see Member::$unique_identifier_field}.
     *
     * If multiple checks are specified, the first non-empty field "wins".
     *
     *  <code>
     * <?php
     * [
     *      'customernumber' => 'ID',
     *      'phonenumber' => [
     *          'callback' => 'getByImportedPhoneNumber'
     *      ]
     * ];
     * ?>
     * </code>
     *
     * @var array
     */
    public $duplicateChecks = [];

    /**
     * @var Boolean $clearBeforeImport Delete ALL records before importing.
     */
    public $deleteExistingRecords = false;

    public function __construct($objectClass)
    {
        $this->objectClass = $objectClass;
        parent::__construct();
    }

    /**
     * If true, this bulk loader will respect create/edit/delete permissions.
     */
    public function getCheckPermissions(): bool
    {
        return $this->checkPermissions;
    }

    /**
     * Determine whether this bulk loader should respect create/edit/delete permissions.
     */
    public function setCheckPermissions(bool $value): BulkLoader
    {
        $this->checkPermissions = $value;
        return $this;
    }

    /*
     * Load the given file via {@link BulkLoader::processAll()} and {@link BulkLoader::processRecord()}.
     * Optionally truncates (clear) the table before it imports.
     *
     * @return BulkLoader_Result See {@link BulkLoader::processAll()}
     */
    public function load($filepath)
    {
        Environment::increaseTimeLimitTo(3600);
        Environment::increaseMemoryLimitTo('512M');

        //get all instances of the to be imported data object
        if ($this->deleteExistingRecords) {
            if ($this->getCheckPermissions()) {
                // We need to check each record, in case there's some fancy conditional logic in the canDelete method.
                // If we can't delete even a single record, we should bail because otherwise the result would not be
                // what the user expects.
                /** @var DataObject $record */
                foreach (DataObject::get($this->objectClass) as $record) {
                    if (!$record->canDelete()) {
                        $type = $record->i18n_singular_name();
                        throw new HTTPResponse_Exception(
                            _t(__CLASS__ . '.CANNOT_DELETE', "Not allowed to delete '$type' records"),
                            403
                        );
                    }
                }
            }
            DataObject::get($this->objectClass)->removeAll();
        }

        return $this->processAll($filepath);
    }

    /**
     * Preview a file import (don't write anything to the database).
     * Useful to analyze the input and give the users a chance to influence
     * it through a UI.
     *
     * @param string $filepath Absolute path to the file we're importing
     * @return array See {@link BulkLoader::processAll()}
     */
    abstract public function preview($filepath);

    /**
     * Process every record in the file
     *
     * @param string $filepath Absolute path to the file we're importing (with UTF8 content)
     * @param boolean $preview If true, we'll just output a summary of changes but not actually do anything
     * @return BulkLoader_Result A collection of objects which are either created, updated or deleted.
     * 'message': free-text string that can optionally provide some more information about what changes have
     */
    abstract protected function processAll($filepath, $preview = false);


    /**
     * Process a single record from the file.
     *
     * @param array $record An map of the data, keyed by the header field defined in {@link BulkLoader::$columnMap}
     * @param array $columnMap
     * @param $result BulkLoader_Result (passed as reference)
     * @param boolean $preview
     */
    abstract protected function processRecord($record, $columnMap, &$result, $preview = false);

    /**
     * Return a FieldList containing all the options for this form; this
     * doesn't include the actual upload field itself
     */
    public function getOptionFields()
    {
    }

    /**
     * Return a human-readable name for this object.
     * It defaults to the class name can be overridden by setting the static variable $title
     *
     * @return string
     */
    public function Title()
    {
        $title = $this->config()->get('title');
        return $title ?: static::class;
    }

    /**
     * Get a specification of all available columns and relations on the used model.
     * Useful for generation of spec documents for technical end users.
     *
     * Return Format:
     * <code>
     * [
     *   'fields' => ['myFieldName'=>'myDescription'],
     *   'relations' => ['myRelationName'=>'myDescription'],
     * ]
     * </code>
     *
     *
     * @return array
     **/
    public function getImportSpec()
    {
        $singleton = DataObject::singleton($this->objectClass);

        // get database columns (fieldlabels include fieldname as a key)
        // using $$includerelations flag as false, so that it only contain $db fields
        $fields = (array)$singleton->fieldLabels(false);

        // Merge relations
        $relations = array_merge(
            $singleton->hasOne(),
            $singleton->hasMany(),
            $singleton->manyMany()
        );

        // Ensure description is string (e.g. many_many through)
        foreach ($relations as $name => $desc) {
            if (!is_string($desc)) {
                $relations[$name] = $name;
            }
        }

        return [
            'fields' => $fields,
            'relations' => $relations,
        ];
    }

    /**
     * Determines if a specific field is null.
     * Can be useful for unusual "empty" flags in the file,
     * e.g. a "(not set)" value.
     * The usual {@link DBField::isNull()} checks apply when writing the {@link DataObject},
     * so this is mainly a customization method.
     *
     * @param mixed $val
     * @param string $fieldName Name of the field as specified in the array-values for {@link BulkLoader::$columnMap}.
     * @return boolean
     */
    protected function isNullValue($val, $fieldName = null)
    {
        return (empty($val) && $val !== '0');
    }
}
