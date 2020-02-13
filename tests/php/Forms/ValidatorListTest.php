<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\Tests\ValidatorTest\TestValidator;
use SilverStripe\Forms\Tests\ValidatorTest\TestValidatorList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ValidatorList;

/**
 * @package framework
 * @subpackage tests
 */
class ValidatorListTest extends SapphireTest
{
    /**
     * Common method for setting up form, since that will always be a dependency for the validator.
     *
     * @param array $fieldNames
     * @return Form
     */
    protected function getForm(array $fieldNames = array()): Form
    {
        // Setup field list now. We're only worried about names right now
        $fieldList = new FieldList();

        foreach ($fieldNames as $name) {
            $fieldList->add(new TextField($name));
        }

        return new Form(Controller::curr(), "testForm", $fieldList, new FieldList([/* no actions */]));
    }

    public function testAddValidator(): void
    {
        $validatorList = new ValidatorList();
        $validatorList->addValidator(new RequiredFields());
        $validatorList->addValidator(new RequiredFields());

        $this->assertCount(2, $validatorList->getValidators());
    }

    public function testSetForm(): void
    {
        $form = $this->getForm();

        $validatorList = new TestValidatorList();
        $validator = new TestValidator();

        $validatorList->addValidator($validator);

        $validatorList->setForm($form);

        $this->assertNotNull($validatorList->getForm());
        $this->assertCount(1, $validatorList->getValidators());

        foreach ($validatorList->getValidators() as $validator) {
            /** @var TestValidator $validator */
            $this->assertNotNull($validator->getForm());
        }
    }

    public function testGetValidatorsByType(): void
    {
        $validatorList = new ValidatorList();
        $validatorList->addValidator(new RequiredFields());
        $validatorList->addValidator(new TestValidator());
        $validatorList->addValidator(new RequiredFields());
        $validatorList->addValidator(new TestValidator());

        $this->assertCount(4, $validatorList->getValidators());
        $this->assertCount(2, $validatorList->getValidatorsByType(RequiredFields::class));
    }

    public function testRemoveValidatorsByType(): void
    {
        $validatorList = new ValidatorList();
        $validatorList->addValidator(new RequiredFields());
        $validatorList->addValidator(new TestValidator());
        $validatorList->addValidator(new RequiredFields());
        $validatorList->addValidator(new TestValidator());

        $this->assertCount(4, $validatorList->getValidators());

        $validatorList->removeValidatorsByType(RequiredFields::class);
        $this->assertCount(2, $validatorList->getValidators());
    }

    public function testCanBeCached(): void
    {
        $validatorList = new ValidatorList();
        $validatorList->addValidator(new RequiredFields());

        $this->assertTrue($validatorList->canBeCached());

        $validatorList = new ValidatorList();
        $validatorList->addValidator(new RequiredFields(['Foor']));

        $this->assertFalse($validatorList->canBeCached());
    }

    public function testFieldIsRequired(): void
    {
        $validatorList = new ValidatorList();

        $fieldNames = [
            'Title',
            'Content',
        ];

        $requiredFieldsFirst = new RequiredFields(
            [
                $fieldNames[0],
            ]
        );
        $requiredFieldsSecond = new RequiredFields(
            [
                $fieldNames[1],
            ]
        );

        $validatorList->addValidator($requiredFieldsFirst);
        $validatorList->addValidator($requiredFieldsSecond);

        foreach ($fieldNames as $field) {
            $this->assertTrue(
                $validatorList->fieldIsRequired($field),
                sprintf('Failed to find "%s" field in required list', $field)
            );
        }
    }

    public function testValidate(): void
    {
        $validatorList = new ValidatorList();
        // Add two separate validators, each with one required field
        $validatorList->addValidator(new RequiredFields(['Foo']));
        $validatorList->addValidator(new RequiredFields(['Bar']));

        // Setup a form with the fields/data we're testing (a form is a dependency for validation right now)
        // We'll add three empty fields, but only two of them should be required
        $data = [
            'Foo' => '',
            'Bar' => '',
            'FooBar' => '',
        ];
        // We only care right now about the fields we've got setup in this array
        $form = $this->getForm(array_keys($data));
        $form->disableSecurityToken();
        // Setup validator now that we've got our form
        $form->setValidator($validatorList);
        // Put data into the form so the validator can pull it back out again
        $form->loadDataFrom($data);

        $result = $form->validationResult();
        $this->assertFalse($result->isValid());
        $this->assertCount(2, $result->getMessages());
    }

    public function testRemoveValidation(): void
    {
        $validatorList = new ValidatorList();
        $validatorList->addValidator(new TestValidator());

        // Setup a form with the fields/data we're testing (a form is a dependency for validation right now)
        $data = [
            'Foo' => '',
        ];
        // We only care right now about the fields we've got setup in this array
        $form = $this->getForm(array_keys($data));
        $form->disableSecurityToken();
        // Setup validator now that we've got our form
        $form->setValidator($validatorList);
        // Put data into the form so the validator can pull it back out again
        $form->loadDataFrom($data);

        $result = $form->validationResult();
        $this->assertFalse($result->isValid());
        $this->assertCount(1, $result->getMessages());

        // Make sure it doesn't fail after removing validation AND has no errors (since calling validate should reset
        // errors)
        $validatorList->removeValidation();
        $result = $form->validationResult();
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getMessages());
    }
}
