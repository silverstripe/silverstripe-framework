<?php

namespace SilverStripe\Forms\Tests\UploadFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\UploadField;
use SilverStripe\ORM\DataObject;

class UploadFieldTestForm extends Form implements TestOnly
{

    public function getRecord()
    {
        if (empty($this->record)) {
            $this->record = DataObject::get_one(TestRecord::class, '"Title" = \'Record 1\'');
        }
        return $this->record;
    }

    /**
 * @skipUpgrade
*/
    function __construct($controller = null, $name = 'Form')
    {
        if (empty($controller)) {
            $controller = new TestController();
        }

        $fieldRootFolder = UploadField::create('RootFolderTest')
            ->setFolderName('/');

        $fieldNoRelation = UploadField::create('NoRelationField')
            ->setFolderName('UploadedFiles');

        $fieldHasOne = UploadField::create('HasOneFile')
            ->setFolderName('UploadedFiles');

        $fieldHasOneExtendedFile = UploadField::create('HasOneExtendedFile')
            ->setFolderName('UploadedFiles');

        $fieldHasOneMaxOne = UploadField::create('HasOneFileMaxOne')
            ->setFolderName('UploadedFiles')
            ->setAllowedMaxFileNumber(1);

        $fieldHasOneMaxTwo = UploadField::create('HasOneFileMaxTwo')
            ->setFolderName('UploadedFiles')
            ->setAllowedMaxFileNumber(2);

        $fieldHasMany = UploadField::create('HasManyFiles')
            ->setFolderName('UploadedFiles');

        $fieldHasManyMaxTwo = UploadField::create('HasManyFilesMaxTwo')
            ->setFolderName('UploadedFiles')
            ->setAllowedMaxFileNumber(2);

        $fieldManyMany = UploadField::create('ManyManyFiles')
            ->setFolderName('UploadedFiles');

        $fieldHasManyNoView = UploadField::create('HasManyNoViewFiles')
            ->setFolderName('UploadedFiles');

        $fieldHasManyDisplayFolder = UploadField::create('HasManyDisplayFolder')
            ->setFolderName('UploadedFiles')
            ->setDisplayFolderName('UploadFieldTest');

        /**
 * @skipUpgrade
*/
        $fieldReadonly = UploadField::create('ReadonlyField')
            ->setFolderName('UploadedFiles')
            ->performReadonlyTransformation();

        $fieldDisabled = UploadField::create('DisabledField')
            ->setFolderName('UploadedFiles')
            ->performDisabledTransformation();

        $fieldSubfolder = UploadField::create('SubfolderField')
            ->setFolderName('UploadedFiles/subfolder1');

        $fieldCanUploadFalse = UploadField::create('CanUploadFalseField')
            ->setCanUpload(false);

        $fieldCanAttachExisting = UploadField::create('CanAttachExistingFalseField')
            ->setCanAttachExisting(false);

        $fieldAllowedExtensions = new UploadField('AllowedExtensionsField');
        $fieldAllowedExtensions->getValidator()->setAllowedExtensions(array('txt'));

        $fieldInvalidAllowedExtensions = new UploadField('InvalidAllowedExtensionsField');
        $fieldInvalidAllowedExtensions->getValidator()->setAllowedExtensions(array('txt', 'php'));

        $fields = new FieldList(
            $fieldRootFolder,
            $fieldNoRelation,
            $fieldHasOne,
            $fieldHasOneMaxOne,
            $fieldHasOneMaxTwo,
            $fieldHasOneExtendedFile,
            $fieldHasMany,
            $fieldHasManyMaxTwo,
            $fieldManyMany,
            $fieldHasManyNoView,
            $fieldHasManyDisplayFolder,
            $fieldReadonly,
            $fieldDisabled,
            $fieldSubfolder,
            $fieldCanUploadFalse,
            $fieldCanAttachExisting,
            $fieldAllowedExtensions,
            $fieldInvalidAllowedExtensions
        );
        $actions = new FieldList(
            new FormAction('submit')
        );
        $validator = new RequiredFields();

        parent::__construct($controller, $name, $fields, $actions, $validator);

        $this->loadDataFrom($this->getRecord());
    }

    public function submit($data, Form $form)
    {
        $record = $this->getRecord();
        $form->saveInto($record);
        $record->write();
        return json_encode($record->toMap());
    }
}
