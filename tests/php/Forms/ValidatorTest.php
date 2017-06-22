<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\Tests\ValidatorTest\TestValidator;
use SilverStripe\Forms\TextField;

/**
 * @package framework
 * @subpackage tests
 */
class ValidatorTest extends SapphireTest
{

    /**
     * Common method for setting up form, since that will always be a dependency for the validator.
     *
     * @param    array $fieldNames
     * @return    Form
     */
    protected function getForm(array $fieldNames = array())
    {
        // Setup field list now. We're only worried about names right now.
        $fieldList = new FieldList();
        foreach ($fieldNames as $name) {
            $fieldList->add(new TextField($name));
        }

        return new Form(Controller::curr(), "testForm", $fieldList, new FieldList([/* no actions */]));
    }


    public function testRemoveValidation()
    {
        $validator = new TestValidator();

        // Setup a form with the fields/data we're testing (a form is a dependency for validation right now).
        $data = array("foobar" => "");
        $form = $this->getForm(array_keys($data)); // We only care right now about the fields we've got setup in this array.
        $form->disableSecurityToken();
        $form->setValidator($validator); // Setup validator now that we've got our form.
        $form->loadDataFrom($data); // Put data into the form so the validator can pull it back out again.

        $result = $form->validationResult();
        $this->assertFalse($result->isValid());
        $this->assertCount(1, $result->getMessages());

        // Make sure it doesn't fail after removing validation AND has no errors (since calling validate should reset errors).
        $validator->removeValidation();
        $result = $form->validationResult();
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getMessages());
    }
}
