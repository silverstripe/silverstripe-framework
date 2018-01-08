<?php

namespace SilverStripe\Forms;

use SilverStripe\Assets\File;
use SilverStripe\Control\HTTP;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;

/**
 * Represents a file type which can be added to a form.
 * Automatically tries to save has_one-relations on the saved
 * record.
 *
 * Please set a validator on the form-object to get feedback
 * about imposed filesize/extension restrictions.
 *
 * <b>Usage</p>
 *
 * If you want to implement a FileField into a form element, you need to pass it an array of source data.
 *
 * <code>
 * class ExampleFormController extends PageController {
 *
 *  function Form() {
 *      $fields = new FieldList(
 *          new TextField('MyName'),
 *          new FileField('MyFile')
 *      );
 *      $actions = new FieldList(
 *          new FormAction('doUpload', 'Upload file')
 *      );
 *    $validator = new RequiredFields(array('MyName', 'MyFile'));
 *
 *      return new Form($this, 'Form', $fields, $actions, $validator);
 *  }
 *
 *  function doUpload($data, $form) {
 *      $file = $data['MyFile'];
 *      $content = file_get_contents($file['tmp_name']);
 *      // ... process content
 *  }
 * }
 * </code>
 */
class FileField extends FormField implements FileHandleField
{
    use UploadReceiver;

    protected $inputType = 'file';

    /**
     * Flag to automatically determine and save a has_one-relationship
     * on the saved record (e.g. a "Player" has_one "PlayerImage" would
     * trigger saving the ID of newly created file into "PlayerImageID"
     * on the record).
     *
     * @var boolean
     */
    protected $relationAutoSetting = true;

    /**
     * Create a new file field.
     *
     * @param string $name The internal field name, passed to forms.
     * @param string $title The field label.
     * @param int $value The value of the field.
     */
    public function __construct($name, $title = null, $value = null)
    {
        $this->constructUploadReceiver();
        parent::__construct($name, $title, $value);
    }

    /**
     * @param array $properties
     * @return string
     */
    public function Field($properties = array())
    {
        $properties = array_merge($properties, array(
            'MaxFileSize' => $this->getValidator()->getAllowedMaxFileSize()
        ));

        return parent::Field($properties);
    }

    public function getAttributes()
    {
        $attributes = parent::getAttributes();

        $accept = $this->getAcceptFileTypes();
        if ($accept) {
            $attributes = array_merge(['accept' => implode(',', $accept)], $attributes);
        }

        return $attributes;
    }

    /**
     * Returns a list of file extensions (and corresponding mime types) that will be accepted
     *
     * @return array
     */
    protected function getAcceptFileTypes()
    {
        $extensions = $this->getValidator()->getAllowedExtensions();
        if (!$extensions) {
            return [];
        }

        $accept = [];
        $mimeTypes = HTTP::config()->uninherited('MimeTypes');
        foreach ($extensions as $extension) {
            $accept[] = ".{$extension}";
            // Check for corresponding mime type
            if (isset($mimeTypes[$extension])) {
                $accept[] = $mimeTypes[$extension];
            }
        }

        return array_unique($accept);
    }

    /**
     * @param DataObject|DataObjectInterface $record
     */
    public function saveInto(DataObjectInterface $record)
    {
        if (!isset($_FILES[$this->name]['error']) || $_FILES[$this->name]['error'] == UPLOAD_ERR_NO_FILE) {
            return;
        }

        $fileClass = File::get_class_for_file_extension(
            File::get_file_extension($_FILES[$this->name]['name'])
        );

        /** @var File $file */
        if ($this->relationAutoSetting) {
            // assume that the file is connected via a has-one
            $objectClass = DataObject::getSchema()->hasOneComponent(get_class($record), $this->name);
            if ($objectClass === File::class || empty($objectClass)) {
                // Create object of the appropriate file class
                $file = Injector::inst()->create($fileClass);
            } else {
                // try to create a file matching the relation
                $file = Injector::inst()->create($objectClass);
            }
        } elseif ($record instanceof File) {
            $file = $record;
        } else {
            $file = Injector::inst()->create($fileClass);
        }

        $this->upload->loadIntoFile($_FILES[$this->name], $file, $this->getFolderName());

        if ($this->upload->isError()) {
            return;
        }

        if ($this->relationAutoSetting) {
            if (empty($objectClass)) {
                return;
            }

            $file = $this->upload->getFile();

            $record->{$this->name . 'ID'} = $file->ID;
        }
    }

    public function Value()
    {
        return isset($_FILES[$this->getName()]) ? $_FILES[$this->getName()] : null;
    }

    public function validate($validator)
    {
        if (!isset($_FILES[$this->name])) {
            return true;
        }

        $tmpFile = $_FILES[$this->name];

        $valid = $this->upload->validate($tmpFile);
        if (!$valid) {
            $errors = $this->upload->getErrors();
            if ($errors) {
                foreach ($errors as $error) {
                    $validator->validationError($this->name, $error, "validation");
                }
            }
            return false;
        }

        return true;
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
}
