<?php

namespace SilverStripe\Forms\Tests;

use ReflectionMethod;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FileField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\RequiredFields;

class FileFieldTest extends FunctionalTest
{
    /**
     * Test a valid upload of a required file in a form. Error is set to 0, as the upload went well
     *
     * @skipUpgrade
     */
    public function testUploadRequiredFile()
    {
        $form = new Form(
            Controller::curr(),
            'Form',
            new FieldList(
                $fileField = new FileField('cv', 'Upload your CV')
            ),
            new FieldList()
        );
        $fileFieldValue = array(
            'name' => 'aCV.txt',
            'type' => 'application/octet-stream',
            'tmp_name' => '/private/var/tmp/phpzTQbqP',
            'error' => 0,
            'size' => 3471
        );
        $fileField->setValue($fileFieldValue);

        $this->assertTrue($form->validationResult()->isValid());
    }

    /**
     * @skipUpgrade
     */
    public function testGetAcceptFileTypes()
    {
        $field = new FileField('image', 'Image');
        $field->setAllowedExtensions('jpg', 'png');

        $method = new ReflectionMethod($field, 'getAcceptFileTypes');
        $method->setAccessible(true);
        $allowed = $method->invoke($field);

        $expected = ['.jpg', '.png', 'image/jpeg', 'image/png'];
        foreach ($expected as $extensionOrMime) {
            $this->assertContains($extensionOrMime, $allowed);
        }
    }

    /**
     * Test different scenarii for a failed upload : an error occured, no files where provided
     * @skipUpgrade
     */
    public function testUploadMissingRequiredFile()
    {
        $form = new Form(
            Controller::curr(),
            'Form',
            new FieldList(
                $fileField = new FileField('cv', 'Upload your CV')
            ),
            new FieldList(),
            new RequiredFields('cv')
        );
        // All fields are filled but for some reason an error occured when uploading the file => fails
        $fileFieldValue = array(
            'name' => 'aCV.txt',
            'type' => 'application/octet-stream',
            'tmp_name' => '/private/var/tmp/phpzTQbqP',
            'error' => 1,
            'size' => 3471
        );
        $fileField->setValue($fileFieldValue);

        $this->assertFalse(
            $form->validationResult()->isValid(),
            'An error occured when uploading a file, but the validator returned true'
        );

        // We pass an empty set of parameters for the uploaded file => fails
        $fileFieldValue = array();
        $fileField->setValue($fileFieldValue);

        $this->assertFalse(
            $form->validationResult()->isValid(),
            'An empty array was passed as parameter for an uploaded file, but the validator returned true'
        );

        // We pass an null value for the uploaded file => fails
        $fileFieldValue = null;
        $fileField->setValue($fileFieldValue);

        $this->assertFalse(
            $form->validationResult()->isValid(),
            'A null value was passed as parameter for an uploaded file, but the validator returned true'
        );
    }
}
