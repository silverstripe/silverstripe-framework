<?php

namespace SilverStripe\Forms;

use Exception;
use InvalidArgumentException;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Storage\AssetContainer;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\RelationList;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\UnsavedRelationList;
use SilverStripe\ORM\ValidationException;

/**
 * Provides operations for reading and writing uploaded files to/from
 * {@see File} dataobject instances.
 * Allows writing to a parent record with the following relation types:
 *   - has_one
 *   - has_many
 *   - many_many
 * Additionally supports writing directly to the File table not attached
 * to any parent record.
 *
 * Note that this trait expects to be applied to a {@see FormField} class
 *
 * @mixin FormField
 */
trait FileUploadReceiver
{
    use UploadReceiver;

    /**
     * Flag to automatically determine and save a has_one-relationship
     * on the saved record (e.g. a "Player" has_one "PlayerImage" would
     * trigger saving the ID of newly created file into "PlayerImageID"
     * on the record).
     *
     * @var boolean
     */
    public $relationAutoSetting = true;

    /**
     * Parent data record. Will be inferred from parent form or controller if blank.
     *
     * @var ?DataObject
     */
    protected $record;

    /**
     * Items loaded into this field. May be a RelationList, or any other SS_List
     *
     * @var SS_List
     */
    protected $items;

    protected function constructFileUploadReceiver()
    {
        $this->constructUploadReceiver();
    }


    /**
     * Force a record to be used as "Parent" for uploaded Files (eg a Page with a has_one to File)
     *
     * @param DataObject $record
     * @return $this
     */
    public function setRecord($record)
    {
        $this->record = $record;
        return $this;
    }
    /**
     * Get the record to use as "Parent" for uploaded Files (eg a Page with a has_one to File) If none is set, it will
     * use Form->getRecord() or Form->Controller()->data()
     *
     * @return ?DataObject
     */
    public function getRecord()
    {
        if ($this->record) {
            return $this->record;
        }
        if (!$this->getForm()) {
            return null;
        }

        // Get record from form
        $record = $this->getForm()->getRecord();
        if ($record && ($record instanceof DataObject)) {
            $this->record = $record;
            return $record;
        }

        // Get record from controller
        $controller = $this->getForm()->getController();
        if ($controller
            && $controller->hasMethod('data')
            && ($record = $controller->data())
            && ($record instanceof DataObject)
        ) {
            $this->record = $record;
            return $record;
        }

        return null;
    }


    /**
     * Loads the related record values into this field. This can be uploaded
     * in one of three ways:
     *
     *  - By passing in a list of file IDs in the $value parameter (an array with a single
     *    key 'Files', with the value being the actual array of IDs).
     *  - By passing in an explicit list of File objects in the $record parameter, and
     *    leaving $value blank.
     *  - By passing in a dataobject in the $record parameter, from which file objects
     *    will be extracting using the field name as the relation field.
     *
     * Each of these methods will update both the items (list of File objects) and the
     * field value (list of file ID values).
     *
     * @param array $value Array of submitted form data, if submitting from a form
     * @param array|DataObject|SS_List $record Full source record, either as a DataObject,
     * SS_List of items, or an array of submitted form data
     * @return $this Self reference
     * @throws ValidationException
     */
    public function setValue($value, $record = null)
    {

        // If we're not passed a value directly, we can attempt to infer the field
        // value from the second parameter by inspecting its relations
        $items = new ArrayList();

        // Determine format of presented data
        if ($value instanceof File) {
            $items = ArrayList::create([$value]);
            $value = null;
        } elseif ($value instanceof SS_List) {
            $items = $value;
            $value = null;
        } elseif (empty($value) && $record) {
            // If a record is given as a second parameter, but no submitted values,
            // then we should inspect this instead for the form values

            if (($record instanceof DataObject) && $record->hasMethod($this->getName())) {
                // If given a dataobject use reflection to extract details

                $data = $record->{$this->getName()}();
                if ($data instanceof DataObject) {
                    // If has_one, add sole item to default list
                    $items->push($data);
                } elseif ($data instanceof SS_List) {
                    // For many_many and has_many relations we can use the relation list directly
                    $items = $data;
                }
            } elseif ($record instanceof SS_List) {
                // If directly passing a list then save the items directly
                $items = $record;
            }
        } elseif (is_array($value) && !empty($value['Files'])) {
            // If value is given as an array (such as a posted form), extract File IDs from this
            $class = $this->getRelationAutosetClass();
            $items = DataObject::get($class)->byIDs($value['Files']);
        }

        // If javascript is disabled, direct file upload (non-html5 style) can
        // trigger a single or multiple file submission. Note that this may be
        // included in addition to re-submitted File IDs as above, so these
        // should be added to the list instead of operated on independently.
        if ($uploadedFiles = $this->extractUploadedFileData($value)) {
            foreach ($uploadedFiles as $tempFile) {
                $file = $this->saveTemporaryFile($tempFile, $error);
                if ($file) {
                    $items->add($file);
                } else {
                    throw new ValidationException($error);
                }
            }
        }

        // Filter items by what's allowed to be viewed
        $filteredItems = new ArrayList();
        $fileIDs = [];
        /** @var File $file */
        foreach ($items as $file) {
            if ($file->isInDB() && $file->canView()) {
                $filteredItems->push($file);
                $fileIDs[] = $file->ID;
            }
        }

        // Filter and cache updated item list
        $this->items = $filteredItems;
        // Same format as posted form values for this field. Also ensures that
        // $this->setValue($this->getValue()); is non-destructive
        $value = $fileIDs ? ['Files' => $fileIDs] : null;

        // Set value using parent
        parent::setValue($value, $record);
        return $this;
    }

    /**
     * Sets the items assigned to this field as an SS_List of File objects.
     * Calling setItems will also update the value of this field, as well as
     * updating the internal list of File items.
     *
     * @param SS_List $items
     * @return $this self reference
     */
    public function setItems(SS_List $items)
    {
        return $this->setValue(null, $items);
    }

    /**
     * Retrieves the current list of files
     *
     * @return SS_List|File[]
     */
    public function getItems()
    {
        return $this->items ? $this->items : new ArrayList();
    }

    /**
     * Retrieves the list of selected file IDs
     *
     * @return array
     */
    public function getItemIDs()
    {
        $value = $this->Value();
        return empty($value['Files']) ? [] : $value['Files'];
    }

    public function Value()
    {
        // Re-override FileField Value to use data value
        return $this->dataValue();
    }

    /**
     * @param DataObject|DataObjectInterface $record
     * @return $this
     */
    public function saveInto(DataObjectInterface $record)
    {
        // Check required relation details are available
        $fieldname = $this->getName();
        if (!$fieldname) {
            return $this;
        }

        // Get details to save
        $idList = $this->getItemIDs();

        // Check type of relation
        $relation = $record->hasMethod($fieldname) ? $record->$fieldname() : null;
        if ($relation && ($relation instanceof RelationList || $relation instanceof UnsavedRelationList)) {
            // has_many or many_many
            $relation->setByIDList($idList);
        } elseif ($class = DataObject::getSchema()->hasOneComponent(get_class($record), $fieldname)) {
            // Assign has_one ID
            $id = $idList ? reset($idList) : 0;
            $record->{"{$fieldname}ID"} = $id;

            // Polymorphic assignment
            if ($class === DataObject::class) {
                $file = $id ? File::get()->byID($id) : null;
                $fileClass = $file ? get_class($file) : File::class;
                $record->{"{$fieldname}Class"} = $id ? $fileClass : null;
            }
        }
        return $this;
    }

    /**
     * Loads the temporary file data into a File object
     *
     * @param array $tmpFile Temporary file data
     * @param string $error Error message
     * @return AssetContainer File object, or null if error
     */
    protected function saveTemporaryFile($tmpFile, &$error = null)
    {
        // Determine container object
        $error = null;
        $fileObject = null;

        if (empty($tmpFile)) {
            $error = _t('SilverStripe\\Forms\\FileUploadReceiver.FIELDNOTSET', 'File information not found');
            return null;
        }

        if ($tmpFile['error']) {
            $this->getUpload()->validate($tmpFile);
            $error = implode(' ' . PHP_EOL, $this->getUpload()->getErrors());
            return null;
        }

        // Search for relations that can hold the uploaded files, but don't fallback
        // to default if there is no automatic relation
        if ($relationClass = $this->getRelationAutosetClass(null)) {
            // Allow File to be subclassed
            if ($relationClass === File::class && isset($tmpFile['name'])) {
                $relationClass = File::get_class_for_file_extension(
                    File::get_file_extension($tmpFile['name'])
                );
            }
            // Create new object explicitly. Otherwise rely on Upload::load to choose the class.
            $fileObject = Injector::inst()->create($relationClass);
            if (! ($fileObject instanceof DataObject) || !($fileObject instanceof AssetContainer)) {
                throw new InvalidArgumentException("Invalid asset container $relationClass");
            }
        }

        // Get the uploaded file into a new file object.
        try {
            $this->getUpload()->loadIntoFile($tmpFile, $fileObject, $this->getFolderName());
        } catch (Exception $e) {
            // we shouldn't get an error here, but just in case
            $error = $e->getMessage();
            return null;
        }

        // Check if upload field has an error
        if ($this->getUpload()->isError()) {
            $error = implode(' ' . PHP_EOL, $this->getUpload()->getErrors());
            return null;
        }

        // return file
        return $this->getUpload()->getFile();
    }

    /**
     * Gets the foreign class that needs to be created, or 'File' as default if there
     * is no relationship, or it cannot be determined.
     *
     * @param string $default Default value to return if no value could be calculated
     * @return string Foreign class name.
     */
    public function getRelationAutosetClass($default = File::class)
    {
        // Don't autodetermine relation if no relationship between parent record
        if (!$this->getRelationAutoSetting()) {
            return $default;
        }

        // Check record and name
        $name = $this->getName();
        $record = $this->getRecord();
        if (empty($name) || empty($record)) {
            return $default;
        } else {
            $class = $record->getRelationClass($name);
            return empty($class) ? $default : $class;
        }
    }

    /**
     * Set if relation can be automatically assigned to the underlying dataobject
     *
     * @param bool $auto
     * @return $this
     */
    public function setRelationAutoSetting($auto)
    {
        $this->relationAutoSetting = $auto;
        return $this;
    }

    /**
     * Check if relation can be automatically assigned to the underlying dataobject
     *
     * @return bool
     */
    public function getRelationAutoSetting()
    {
        return $this->relationAutoSetting;
    }

    /**
     * Given an array of post variables, extract all temporary file data into an array
     *
     * @param array $postVars Array of posted form data
     * @return array List of temporary file data
     */
    protected function extractUploadedFileData($postVars)
    {
        // Note: Format of posted file parameters in php is a feature of using
        // <input name='{$Name}[Uploads][]' /> for multiple file uploads
        $tmpFiles = [];
        if (!empty($postVars['tmp_name'])
            && is_array($postVars['tmp_name'])
            && !empty($postVars['tmp_name']['Uploads'])
        ) {
            for ($i = 0; $i < count($postVars['tmp_name']['Uploads'] ?? []); $i++) {
                // Skip if "empty" file
                if (empty($postVars['tmp_name']['Uploads'][$i])) {
                    continue;
                }
                $tmpFile = [];
                foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $field) {
                    $tmpFile[$field] = $postVars[$field]['Uploads'][$i];
                }
                $tmpFiles[] = $tmpFile;
            }
        } elseif (!empty($postVars['tmp_name'])) {
            // Fallback to allow single file uploads (method used by AssetUploadField)
            $tmpFiles[] = $postVars;
        }

        return $tmpFiles;
    }
}
