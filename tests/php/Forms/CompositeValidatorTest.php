<?php

namespace SilverStripe\Forms\Tests;

use ReflectionClass;
use ReflectionException;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\Tests\ValidatorTest\TestValidator;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\CompositeValidator;

/**
 * @package framework
 * @subpackage tests
 */
class CompositeValidatorTest extends SapphireTest
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
        $compositeValidator = new CompositeValidator();
        $compositeValidator->addValidator(new RequiredFields());
        $compositeValidator->addValidator(new RequiredFields());

        $this->assertCount(2, $compositeValidator->getValidators());
    }

    /**
     * @throws ReflectionException
     */
    public function testSetForm(): void
    {
        $form = $this->getForm();

        $reflectionClass = new ReflectionClass(CompositeValidator::class);
        $property = $reflectionClass->getProperty('form');
        $property->setAccessible(true);

        $compositeValidator = new CompositeValidator();
        $validator = new TestValidator();

        $compositeValidator->addValidator($validator);

        $compositeValidator->setForm($form);

        $this->assertNotNull($property->getValue($compositeValidator));
        $this->assertCount(1, $compositeValidator->getValidators());

        foreach ($compositeValidator->getValidators() as $validator) {
            /** @var TestValidator $validator */
            $this->assertNotNull($property->getValue($validator));
        }
    }

    public function testGetValidatorsByType(): void
    {
        $compositeValidator = new CompositeValidator();
        $compositeValidator->addValidator(new RequiredFields());
        $compositeValidator->addValidator(new TestValidator());
        $compositeValidator->addValidator(new RequiredFields());
        $compositeValidator->addValidator(new TestValidator());

        $this->assertCount(4, $compositeValidator->getValidators());
        $this->assertCount(2, $compositeValidator->getValidatorsByType(RequiredFields::class));
    }

    public function testRemoveValidatorsByType(): void
    {
        $compositeValidator = new CompositeValidator();
        $compositeValidator->addValidator(new RequiredFields());
        $compositeValidator->addValidator(new TestValidator());
        $compositeValidator->addValidator(new RequiredFields());
        $compositeValidator->addValidator(new TestValidator());

        $this->assertCount(4, $compositeValidator->getValidators());

        $compositeValidator->removeValidatorsByType(RequiredFields::class);
        $this->assertCount(2, $compositeValidator->getValidators());
    }

    public function testCanBeCached(): void
    {
        $compositeValidator = new CompositeValidator();
        $compositeValidator->addValidator(new RequiredFields());

        $this->assertTrue($compositeValidator->canBeCached());

        $compositeValidator = new CompositeValidator();
        $compositeValidator->addValidator(new RequiredFields(['Foor']));

        $this->assertFalse($compositeValidator->canBeCached());
    }

    public function testFieldIsRequired(): void
    {
        $compositeValidator = new CompositeValidator();

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

        $compositeValidator->addValidator($requiredFieldsFirst);
        $compositeValidator->addValidator($requiredFieldsSecond);

        foreach ($fieldNames as $field) {
            $this->assertTrue(
                $compositeValidator->fieldIsRequired($field),
                sprintf('Failed to find "%s" field in required list', $field)
            );
        }
    }

    public function testValidate(): void
    {
        $compositeValidator = new CompositeValidator();
        // Add two separate validators, each with one required field
        $compositeValidator->addValidator(new RequiredFields(['Foo']));
        $compositeValidator->addValidator(new RequiredFields(['Bar']));

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
        $form->setValidator($compositeValidator);
        // Put data into the form so the validator can pull it back out again
        $form->loadDataFrom($data);

        $result = $form->validationResult();
        $this->assertFalse($result->isValid());
        $this->assertCount(2, $result->getMessages());
    }

    public function testRemoveValidation(): void
    {
        $compositeValidator = new CompositeValidator();
        $compositeValidator->addValidator(new TestValidator());

        // Setup a form with the fields/data we're testing (a form is a dependency for validation right now)
        $data = [
            'Foo' => '',
        ];
        // We only care right now about the fields we've got setup in this array
        $form = $this->getForm(array_keys($data));
        $form->disableSecurityToken();
        // Setup validator now that we've got our form
        $form->setValidator($compositeValidator);
        // Put data into the form so the validator can pull it back out again
        $form->loadDataFrom($data);

        $result = $form->validationResult();
        $this->assertFalse($result->isValid());
        $this->assertCount(1, $result->getMessages());

        // Make sure it doesn't fail after removing validation AND has no errors (since calling validate should
        // reset errors)
        $compositeValidator->removeValidation();
        $result = $form->validationResult();
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getMessages());
    }
}
