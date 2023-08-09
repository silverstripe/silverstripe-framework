<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FieldsValidator;
use SilverStripe\Forms\Form;

class FieldsValidatorTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function provideValidation()
    {
        return [
            'missing values arent invalid' => [
                'values' => [],
                'isValid' => true,
            ],
            'empty values arent invalid' => [
                'values' => [
                    'EmailField1' => '',
                    'EmailField2' => null,
                ],
                'isValid' => true,
            ],
            'any invalid is invalid' => [
                'values' => [
                    'EmailField1' => 'email@example.com',
                    'EmailField2' => 'not email',
                ],
                'isValid' => false,
            ],
            'all invalid is invalid' => [
                'values' => [
                    'EmailField1' => 'not email',
                    'EmailField2' => 'not email',
                ],
                'isValid' => false,
            ],
            'all valid is valid' => [
                'values' => [
                    'EmailField1' => 'email@example.com',
                    'EmailField2' => 'email@example.com',
                ],
                'isValid' => true,
            ],
        ];
    }

    /**
     * @dataProvider provideValidation
     */
    public function testValidation(array $values, bool $isValid)
    {
        $fieldList = new FieldList([
            $field1 = new EmailField('EmailField1'),
            $field2 = new EmailField('EmailField2'),
        ]);
        if (array_key_exists('EmailField1', $values)) {
            $field1->setValue($values['EmailField1']);
        }
        if (array_key_exists('EmailField2', $values)) {
            $field2->setValue($values['EmailField2']);
        }
        $form = new Form(null, 'testForm', $fieldList, new FieldList([/* no actions */]), new FieldsValidator());

        $result = $form->validationResult();
        $this->assertSame($isValid, $result->isValid());
        $messages = $result->getMessages();
        if ($isValid) {
            $this->assertEmpty($messages);
        } else {
            $this->assertNotEmpty($messages);
        }
    }
}
