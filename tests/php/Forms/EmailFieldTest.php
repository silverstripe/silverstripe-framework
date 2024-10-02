<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldsValidator;

class EmailFieldTest extends FunctionalTest
{

    protected static $extra_controllers = [
        EmailFieldTest\TestController::class,
    ];

    /**
     * Check the php validator for email addresses. We should be checking against RFC 5322 which defines email address
     * syntax.
     *   - double quotes around the local part (before @) is not supported
     *   - special chars ! # $ % & ' * + - / = ? ^ _ ` { | } ~ are all valid in local part
     *   - special chars ()[]\;:,<> are valid in the local part if the local part is in double quotes
     *   - "." is valid in the local part as long as its not first or last char
     * @return void
     */
    public function testEmailAddressSyntax()
    {
        $this->internalCheck("blah@blah.com", "Valid, simple", true);
        $this->internalCheck("mr.o'connor+on-toast@blah.com", "Valid, special chars", true);
        $this->internalCheck("", "Empty email", true);
        $this->internalCheck("invalid", "Invalid, simple", false);
        $this->internalCheck("invalid@name@domain.com", "Invalid, two @'s", false);
        $this->internalCheck("invalid@domain", "Invalid, domain too simple", false);
        $this->internalCheck("domain.but.no.user", "Invalid, no user part", false);
    }

    public function internalCheck($email, $checkText, $expectSuccess)
    {
        $field = new EmailField("MyEmail");
        $field->setValue($email);

        if ($expectSuccess) {
            $message = $checkText . " (/$email/ did not pass validation, but was expected to)";
        } else {
            $message = $checkText . " (/$email/ passed validation, but not expected to)";
        }

        $result = $field->validate(new FieldsValidator());
        $this->assertSame($expectSuccess, $result, $message);
    }

    /**
     * Check that input type='email' fields are submitted
     */
    public function testEmailFieldPopulation()
    {
        $this->get('EmailFieldTest_Controller');

        $response = $this->submitForm(
            'Form_Form',
            null,
            ['Email' => 'test@test.com']
        );

        $this->assertStringContainsString('Test save was successful', $response->getBody());
    }
}
