<?php declare(strict_types = 1);

namespace SilverStripe\Forms\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\ConfirmedPasswordField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Security\Member;
use SilverStripe\Security\PasswordValidator;

class ConfirmedPasswordFieldTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp()
    {
        parent::setUp();

        PasswordValidator::singleton()
            ->setMinLength(0)
            ->setTestNames([]);
    }

    public function testSetValue()
    {
        $field = new ConfirmedPasswordField('Test', 'Testing', 'valueA');
        $this->assertEquals('valueA', $field->Value());
        $this->assertEquals('valueA', $field->children->fieldByName($field->getName() . '[_Password]')->Value());
        $this->assertEquals('valueA', $field->children->fieldByName($field->getName() . '[_ConfirmPassword]')->Value());
        $field->setValue('valueB');
        $this->assertEquals('valueB', $field->Value());
        $this->assertEquals('valueB', $field->children->fieldByName($field->getName() . '[_Password]')->Value());
        $this->assertEquals('valueB', $field->children->fieldByName($field->getName() . '[_ConfirmPassword]')->Value());
    }

    /**
     * @useDatabase true
     */
    public function testHashHidden()
    {
        $field = new ConfirmedPasswordField('Password', 'Password', 'valueA');
        $field->setCanBeEmpty(true);

        $this->assertEquals('valueA', $field->Value());
        $this->assertEquals('valueA', $field->children->fieldByName($field->getName() . '[_Password]')->Value());
        $this->assertEquals('valueA', $field->children->fieldByName($field->getName() . '[_ConfirmPassword]')->Value());

        $member = new Member();
        $member->Password = "valueB";
        $member->write();

        /** @skipUpgrade */
        $form = new Form(Controller::curr(), 'Form', new FieldList($field), new FieldList());
        $form->loadDataFrom($member);

        $this->assertEquals('', $field->Value());
        $this->assertEquals('', $field->children->fieldByName($field->getName() . '[_Password]')->Value());
        $this->assertEquals('', $field->children->fieldByName($field->getName() . '[_ConfirmPassword]')->Value());
    }

    public function testSetShowOnClick()
    {
        //hide by default and display show/hide toggle button
        $field = new ConfirmedPasswordField('Test', 'Testing', 'valueA', null, true);
        $fieldHTML = $field->Field();
        $this->assertContains(
            "showOnClickContainer",
            $fieldHTML,
            "Test class for hiding/showing the form contents is set"
        );
        $this->assertContains(
            "showOnClick",
            $fieldHTML,
            "Test class for hiding/showing the form contents is set"
        );

        //show all by default
        $field = new ConfirmedPasswordField('Test', 'Testing', 'valueA', null, false);
        $fieldHTML = $field->Field();
        $this->assertNotContains(
            "showOnClickContainer",
            $fieldHTML,
            "Test class for hiding/showing the form contents is set"
        );
        $this->assertNotContains(
            "showOnClick",
            $fieldHTML,
            "Test class for hiding/showing the form contents is set"
        );
    }

    public function testValidation()
    {
        $field = new ConfirmedPasswordField(
            'Test',
            'Testing',
            [
                '_Password' => 'abc123',
                '_ConfirmPassword' => 'abc123',
            ]
        );
        $validator = new RequiredFields();
        $this->assertTrue(
            $field->validate($validator),
            'Validates when both passwords are the same'
        );
        $field->setName('TestNew'); //try changing name of field
        $this->assertTrue(
            $field->validate($validator),
            'Validates when field name is changed'
        );
        //non-matching password should make the field invalid
        $field->setValue([
            '_Password' => 'abc123',
            '_ConfirmPassword' => '123abc',
        ]);
        $this->assertFalse(
            $field->validate($validator),
            'Does not validate when passwords differ'
        );

        // Empty passwords should make the field invalid
        $field->setCanBeEmpty(false);
        $field->setValue([
            '_Password' => '',
            '_ConfirmPassword' => '',
        ]);
        $this->assertFalse(
            $field->validate($validator),
            'Empty passwords should not be allowed when canBeEmpty is false'
        );
    }

    public function testFormValidation()
    {
        /** @skipUpgrade */
        $form = new Form(
            Controller::curr(),
            'Form',
            new FieldList($field = new ConfirmedPasswordField('Password')),
            new FieldList()
        );

        $form->loadDataFrom([
            'Password' => [
                '_Password' => '123',
                '_ConfirmPassword' => '',
            ],
        ]);

        $this->assertEquals('123', $field->children->first()->Value());
        $this->assertEmpty($field->children->last()->Value());
        $this->assertNotEquals($field->children->first()->Value(), $field->children->last()->Value());

        $form->loadDataFrom([
            'Password' => [
                '_Password' => '123',
                '_ConfirmPassword' => 'abc',
            ],
        ]);

        $this->assertEquals('123', $field->children->first()->Value());
        $this->assertEquals('abc', $field->children->last()->Value());
        $this->assertNotEquals($field->children->first()->Value(), $field->children->last()->Value());

        $form->loadDataFrom([
            'Password' => [
                '_Password' => '',
                '_ConfirmPassword' => 'abc',
            ],
        ]);

        $this->assertEmpty($field->children->first()->Value());
        $this->assertEquals('abc', $field->children->last()->Value());
        $this->assertNotEquals($field->children->first()->Value(), $field->children->last()->Value());
    }

    /**
     * @param int|null $minLength
     * @param int|null $maxLength
     * @param bool $expectValid
     * @param string $expectedMessage
     * @dataProvider lengthValidationProvider
     */
    public function testLengthValidation($minLength, $maxLength, $expectValid, $expectedMessage = '')
    {
        $field = new ConfirmedPasswordField('Test', 'Testing', [
            '_Password' => 'abc123',
            '_ConfirmPassword' => 'abc123',
        ]);
        $field->setMinLength($minLength)->setMaxLength($maxLength);

        $validator = new RequiredFields();
        $result = $field->validate($validator);

        $this->assertSame($expectValid, $result, 'Validate method should return its result');
        $this->assertSame($expectValid, $validator->getResult()->isValid());
        if ($expectedMessage) {
            $this->assertContains($expectedMessage, $validator->getResult()->serialize());
        }
    }

    /**
     * @return array[]
     */
    public function lengthValidationProvider()
    {
        return [
            'valid: within min and max' => [3, 8, true],
            'invalid: lower than min with max' => [8, 12, false, 'Passwords must be 8 to 12 characters long'],
            'valid: greater than min' => [3, null, true],
            'invalid: lower than min' => [8, null, false, 'Passwords must be at least 8 characters long'],
            'valid: less than max' => [null, 8, true],
            'invalid: greater than max' => [null, 4, false, 'Passwords must be at most 4 characters long'],

        ];
    }

    public function testStrengthValidation()
    {
        $field = new ConfirmedPasswordField('Test', 'Testing', [
            '_Password' => 'abc',
            '_ConfirmPassword' => 'abc',
        ]);
        $field->setRequireStrongPassword(true);

        $validator = new RequiredFields();
        $result = $field->validate($validator);

        $this->assertFalse($result, 'Validate method should return its result');
        $this->assertFalse($validator->getResult()->isValid());
        $this->assertContains(
            'Passwords must have at least one digit and one alphanumeric character',
            $validator->getResult()->serialize()
        );
    }

    public function testCurrentPasswordValidation()
    {
        $field = new ConfirmedPasswordField('Test', 'Testing', [
            '_Password' => 'abc',
            '_ConfirmPassword' => 'abc',
        ]);
        $field->setRequireExistingPassword(true);

        $validator = new RequiredFields();
        $result = $field->validate($validator);

        $this->assertFalse($result, 'Validate method should return its result');
        $this->assertFalse($validator->getResult()->isValid());
        $this->assertContains(
            'You must enter your current password',
            $validator->getResult()->serialize()
        );
    }

    public function testMustBeLoggedInToChangePassword()
    {
        $field = new ConfirmedPasswordField('Test', 'Testing');
        $field->setRequireExistingPassword(true);
        $field->setValue([
            '_CurrentPassword' => 'foo',
            '_Password' => 'abc',
            '_ConfirmPassword' => 'abc',
        ]);

        $validator = new RequiredFields();
        $this->logOut();
        $result = $field->validate($validator);

        $this->assertFalse($result, 'Validate method should return its result');
        $this->assertFalse($validator->getResult()->isValid());
        $this->assertContains(
            'You must be logged in to change your password',
            $validator->getResult()->serialize()
        );
    }

    /**
     * @useDatabase true
     */
    public function testValidateCorrectPassword()
    {
        $this->logInWithPermission('ADMIN');

        $field = new ConfirmedPasswordField('Test', 'Testing');
        $field->setRequireExistingPassword(true);
        $field->setValue([
            '_CurrentPassword' => 'foo-not-going-to-be-the-correct-password',
            '_Password' => 'abc',
            '_ConfirmPassword' => 'abc',
        ]);

        $validator = new RequiredFields();
        $result = $field->validate($validator);

        $this->assertFalse($result, 'Validate method should return its result');
        $this->assertFalse($validator->getResult()->isValid());
        $this->assertContains(
            'The current password you have entered is not correct',
            $validator->getResult()->serialize()
        );
    }

    public function testTitle()
    {
        $this->assertNull(ConfirmedPasswordField::create('Test')->Title(), 'Should not have it\'s own title');
    }

    public function testSetTitlePropagatesToPasswordField()
    {
        /** @var ConfirmedPasswordField $field */
        $field = ConfirmedPasswordField::create('Test')
            ->setTitle('My password');

        $this->assertSame('My password', $field->getPasswordField()->Title());
    }

    public function testSetRightTitlePropagatesToChildren()
    {
        $field = new ConfirmedPasswordField('Test');

        $this->assertCount(2, $field->getChildren());
        foreach ($field->getChildren() as $child) {
            $this->assertEmpty($child->RightTitle());
        }

        $field->setRightTitle('Please confirm');
        foreach ($field->getChildren() as $child) {
            $this->assertSame('Please confirm', $child->RightTitle());
        }
    }

    public function testSetChildrenTitles()
    {
        $field = new ConfirmedPasswordField('Test');
        $field->setRequireExistingPassword(true);
        $field->setChildrenTitles([
            'Current Password',
            'Password',
            'Confirm Password',
        ]);

        $this->assertSame('Current Password', $field->getChildren()->shift()->Title());
        $this->assertSame('Password', $field->getChildren()->shift()->Title());
        $this->assertSame('Confirm Password', $field->getChildren()->shift()->Title());
    }

    public function testPerformReadonlyTransformation()
    {
        $field = new ConfirmedPasswordField('Test', 'Change it');
        $result = $field->performReadonlyTransformation();

        $this->assertInstanceOf(ReadonlyField::class, $result);
        $this->assertSame('Change it', $result->Title());
        $this->assertContains('***', $result->Value());
    }

    public function testPerformDisabledTransformation()
    {
        $field = new ConfirmedPasswordField('Test', 'Change it');
        $result = $field->performDisabledTransformation();

        $this->assertInstanceOf(ReadonlyField::class, $result);
    }

    public function testSetRequireExistingPasswordOnlyRunsOnce()
    {
        $field = new ConfirmedPasswordField('Test', 'Change it');

        $this->assertCount(2, $field->getChildren());

        $field->setRequireExistingPassword(true);
        $this->assertCount(3, $field->getChildren(), 'Current password field was not pushed');

        $field->setRequireExistingPassword(true);
        $this->assertCount(3, $field->getChildren(), 'Current password field should not be pushed again');

        $field->setRequireExistingPassword(false);
        $this->assertCount(2, $field->getChildren(), 'Current password field should not be removed');
    }
}
