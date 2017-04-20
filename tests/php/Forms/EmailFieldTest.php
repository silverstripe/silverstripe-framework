<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Forms\EmailField;
use Exception;
use PHPUnit_Framework_AssertionFailedError;
use SilverStripe\Forms\Tests\EmailFieldTest\TestValidator;

/**
 * @skipUpgrade
 */
class EmailFieldTest extends FunctionalTest
{

    protected static $extra_controllers = [
        EmailFieldTest\TestController::class,
    ];

    /**
     * Check the php validator for email addresses. We should be checking against RFC 5322 which defines email address
     * syntax.
     *
     * @TODO
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

        $val = new TestValidator();
        try {
            $field->validate($val);
            // If we expect failure and processing gets here without an exception, the test failed
            $this->assertTrue($expectSuccess, $checkText . " (/$email/ passed validation, but not expected to)");
        } catch (Exception $e) {
            if ($e instanceof PHPUnit_Framework_AssertionFailedError) {
                 // re-throw assertion failure
                throw $e;
            } elseif ($expectSuccess) {
                $this->fail(
                    $checkText . ": " . $e->getMessage() . " (/$email/ did not pass validation, but was expected to)"
                );
            }
        }
    }

    /**
     * Check that input type='email' fields are submitted by SimpleTest
     *
     * @see SimpleTagBuilder::_createInputTag()
     */
    function testEmailFieldPopulation()
    {

        $this->get('EmailFieldTest_Controller');
        $this->submitForm(
            'Form_Form',
            null,
            array(
            'Email' => 'test@test.com'
            )
        );

        $this->assertPartialMatchBySelector(
            'p.good',
            array(
            'Test save was successful'
            )
        );
    }
}
