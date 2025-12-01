<?php

namespace SilverStripe\Forms\Tests;

use DOMDocument;
use DOMXPath;
use DOMElement;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\ConfirmedPasswordField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\Security\Member;
use SilverStripe\View\SSViewer;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Security\Validation\EntropyPasswordValidator;
use SilverStripe\Security\Validation\PasswordValidator;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\Validation\RulesPasswordValidator;
use Symfony\Component\Validator\Constraints\PasswordStrength;

class ConfirmedPasswordFieldTest extends SapphireTest
{
    protected $usesDatabase = true;

    private ?PasswordValidator $origPasswordValidator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->origPasswordValidator = Member::password_validator();
        Member::set_password_validator(null);
    }

    protected function tearDown(): void
    {
        Member::set_password_validator($this->origPasswordValidator);
        parent::tearDown();
    }

    public function testSetValue()
    {
        $field = new ConfirmedPasswordField('Test', 'Testing', 'valueA');
        $this->assertEquals('valueA', $field->getValue());
        $this->assertEquals('valueA', $field->children->fieldByName($field->getName() . '[_Password]')->getValue());
        $this->assertEquals('valueA', $field->children->fieldByName($field->getName() . '[_ConfirmPassword]')->getValue());
        $field->setValue('valueB');
        $this->assertEquals('valueB', $field->getValue());
        $this->assertEquals('valueB', $field->children->fieldByName($field->getName() . '[_Password]')->getValue());
        $this->assertEquals('valueB', $field->children->fieldByName($field->getName() . '[_ConfirmPassword]')->getValue());
    }

    /**
     * @useDatabase true
     */
    public function testHashHidden()
    {
        $field = new ConfirmedPasswordField('Password', 'Password', 'valueA');
        $field->setCanBeEmpty(true);

        $this->assertEquals('valueA', $field->getValue());
        $this->assertEquals('valueA', $field->children->fieldByName($field->getName() . '[_Password]')->getValue());
        $this->assertEquals('valueA', $field->children->fieldByName($field->getName() . '[_ConfirmPassword]')->getValue());

        $member = new Member();
        $member->Password = "valueB";
        $member->write();

        $form = new Form(Controller::curr(), 'Form', new FieldList($field), new FieldList());
        $form->loadDataFrom($member);

        $this->assertEquals('', $field->getValue());
        $this->assertEquals('', $field->children->fieldByName($field->getName() . '[_Password]')->getValue());
        $this->assertEquals('', $field->children->fieldByName($field->getName() . '[_ConfirmPassword]')->getValue());
    }

    public function testSetShowOnClick()
    {
        //hide by default and display show/hide toggle button
        $field = new ConfirmedPasswordField('Test', 'Testing', 'valueA', null, true);
        $fieldHTML = $field->Field();
        $this->assertStringContainsString(
            "showOnClickContainer",
            $fieldHTML,
            "Test class for hiding/showing the form contents is set"
        );
        $this->assertStringContainsString(
            "showOnClick",
            $fieldHTML,
            "Test class for hiding/showing the form contents is set"
        );

        //show all by default
        $field = new ConfirmedPasswordField('Test', 'Testing', 'valueA', null, false);
        $fieldHTML = $field->Field();
        $this->assertStringNotContainsString(
            "showOnClickContainer",
            $fieldHTML,
            "Test class for hiding/showing the form contents is set"
        );
        $this->assertStringNotContainsString(
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
        $this->assertTrue(
            $field->validate()->isValid(),
            'Validates when both passwords are the same'
        );
        $field->setName('TestNew'); //try changing name of field
        $this->assertTrue(
            $field->validate()->isValid(),
            'Validates when field name is changed'
        );
        //non-matching password should make the field invalid
        $field->setValue([
            '_Password' => 'abc123',
            '_ConfirmPassword' => '123abc',
        ]);
        $this->assertFalse(
            $field->validate()->isValid(),
            'Does not validate when passwords differ'
        );

        // Empty passwords should make the field invalid
        $field->setCanBeEmpty(false);
        $field->setValue([
            '_Password' => '',
            '_ConfirmPassword' => '',
        ]);
        $this->assertFalse(
            $field->validate()->isValid(),
            'Empty passwords should not be allowed when canBeEmpty is false'
        );
    }

    public function testFormValidation()
    {
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
        $this->assertSame('123', $field->children->first()->getValue());
        $this->assertSame('', $field->children->last()->getValue());

        $form->loadDataFrom([
            'Password' => [
                '_Password' => '123',
                '_ConfirmPassword' => 'abc',
            ],
        ]);
        $this->assertSame('123', $field->children->first()->getValue());
        $this->assertSame('abc', $field->children->last()->getValue());

        $form->loadDataFrom([
            'Password' => [
                '_Password' => '',
                '_ConfirmPassword' => 'abc',
            ],
        ]);
        $this->assertSame('', $field->children->first()->getValue());
        $this->assertSame('abc', $field->children->last()->getValue());
    }

    /**
     * @param int|null $minLength
     * @param int|null $maxLength
     * @param bool $expectValid
     * @param string $expectedMessage
     */
    #[DataProvider('lengthValidationProvider')]
    public function testLengthValidation($minLength, $maxLength, $expectValid, $expectedMessage = '')
    {
        $field = new ConfirmedPasswordField('Test', 'Testing', [
            '_Password' => 'abc123',
            '_ConfirmPassword' => 'abc123',
        ]);
        $field->setMinLength($minLength)->setMaxLength($maxLength);
        $result = $field->validate();
        $this->assertSame($expectValid, $result->isValid());
        if ($expectedMessage) {
            $this->assertStringContainsString($expectedMessage, json_encode($result->getMessages()));
        }
    }

    /**
     * @return array[]
     */
    public static function lengthValidationProvider()
    {
        return [
            // 'valid: within min and max' => [3, 8, true],
            'invalid: lower than min with max' => [8, 12, false, 'Passwords must be 8 to 12 characters long'],
            // 'valid: greater than min' => [3, 0, true],
            // 'invalid: lower than min' => [8, 0, false, 'Passwords must be at least 8 characters long'],
            // 'valid: less than max' => [0, 8, true],
            // 'invalid: greater than max' => [0, 4, false, 'Passwords must be at most 4 characters long'],

        ];
    }

    public function testStrengthValidation()
    {
        $field = new ConfirmedPasswordField('Test', 'Testing', [
            '_Password' => 'abc',
            '_ConfirmPassword' => 'abc',
        ]);
        $field->setRequireStrongPassword(true);
        $result = $field->validate();
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString(
            'The password strength is too low. Please use a stronger password.',
            json_encode($result->getMessages())
        );
    }

    public function testCurrentPasswordValidation()
    {
        $field = new ConfirmedPasswordField('Test', 'Testing', [
            '_Password' => 'abc',
            '_ConfirmPassword' => 'abc',
        ]);
        $field->setRequireExistingPassword(true);
        $result = $field->validate();
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString(
            'You must enter your current password',
            json_encode($result->getMessages())
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
        $this->logOut();
        $result = $field->validate();
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString(
            'You must be logged in to change your password',
            json_encode($result->getMessages())
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
        $result = $field->validate();
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString(
            'The current password you have entered is not correct',
            json_encode($result->getMessages())
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

        $this->assertCount(3, $field->getChildren());
        foreach ($field->getChildren() as $child) {
            $this->assertEmpty($child->RightTitle());
        }

        $field->setRightTitle('Please confirm');
        foreach ($field->getChildren() as $child) {
            $this->assertSame('Please confirm', $child->RightTitle());
        }
    }

    public static function provideSetChildrenTitles(): array
    {
        return [
            [true],
            [false],
        ];
    }

    #[DataProvider('provideSetChildrenTitles')]
    public function testSetChildrenTitles(bool $requireExistingPassword)
    {
        $field = new ConfirmedPasswordField('Test');
        $field->setRequireExistingPassword($requireExistingPassword);
        $setValues = [
            'New Password field',
            'Confirm Password field',
        ];

        if ($requireExistingPassword) {
            array_unshift($setValues, 'Original Password field');
        }

        $field->setChildrenTitles($setValues);

        $children = $field->getChildren();
        $children->removeByName('Test[_PasswordStrength]');

        if ($requireExistingPassword) {
            $this->assertSame('Original Password field', $children->shift()->Title());
        }
        $this->assertSame('New Password field', $children->shift()->Title());
        $this->assertSame('Confirm Password field', $children->shift()->Title());
    }

    public function testPerformReadonlyTransformation()
    {
        $field = new ConfirmedPasswordField('Test', 'Change it');
        $result = $field->performReadonlyTransformation();

        $this->assertInstanceOf(ReadonlyField::class, $result);
        $this->assertSame('Change it', $result->Title());
        $this->assertStringContainsString('***', $result->getValue());
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

        $this->assertCount(3, $field->getChildren());

        $field->setRequireExistingPassword(true);
        $this->assertCount(4, $field->getChildren(), 'Current password field was not pushed');

        $field->setRequireExistingPassword(true);
        $this->assertCount(4, $field->getChildren(), 'Current password field should not be pushed again');

        $field->setRequireExistingPassword(false);
        $this->assertCount(3, $field->getChildren(), 'Current password field should not be removed');
    }

    #[DataProvider('provideSetCanBeEmptySaveInto')]
    public function testSetCanBeEmptySaveInto(bool $generateRandomPasswordOnEmpty, ?string $expected)
    {
        $field = new ConfirmedPasswordField('Test', 'Change it');
        $field->setCanBeEmpty(true);
        if ($generateRandomPasswordOnEmpty) {
            $field->setRandomPasswordCallback(Closure::fromCallable(function () {
                return 'R4ndom-P4ssw0rd$LOREM^ipsum#12345';
            }));
        }
        $this->assertEmpty($field->getValue());
        $member = new Member();
        $field->saveInto($member);
        $this->assertSame($expected, $field->getValue());
    }

    public static function provideSetCanBeEmptySaveInto(): array
    {
        return [
            [
                'generateRandomPasswordOnEmpty' => true,
                'expected' => 'R4ndom-P4ssw0rd$LOREM^ipsum#12345',
            ],
            [
                'generateRandomPasswordOnEmpty' => false,
                'expected' => null,
            ],
        ];
    }

    public function testSetCanBeEmptyRightTitle()
    {
        $field = new ConfirmedPasswordField('Test', 'Change it');
        $passwordField = $field->getPasswordField();
        $this->assertEmpty($passwordField->RightTitle());
        $field->setCanBeEmpty(true);
        $this->assertEmpty($passwordField->RightTitle());
        $field->setRandomPasswordCallback(Closure::fromCallable(function () {
            return 'R4ndom-P4ssw0rd$LOREM^ipsum#12345';
        }));
        $this->assertNotEmpty($passwordField->RightTitle());
    }

    public static function provideRequired()
    {
        return [
            'can be empty' => [true],
            'cannot be empty' => [false],
        ];
    }

    #[DataProvider('provideRequired')]
    public function testRequired(bool $canBeEmpty)
    {
        $field = new ConfirmedPasswordField('Test');
        $field->setCanBeEmpty($canBeEmpty);
        $this->assertSame(!$canBeEmpty, $field->Required());
    }

    public static function provideChildFieldsAreRequired()
    {
        return [
            'not required' => [
                'canBeEmpty' => true,
                'required' => false,
                'childrenRequired' => false,
                'expectRequired' => false,
            ],
            'required via validator' => [
                'canBeEmpty' => true,
                'required' => true,
                'childrenRequired' => false,
                'expectRequired' => true,
            ],
            'children required directly' => [
                'canBeEmpty' => true,
                'required' => false,
                'childrenRequired' => true,
                'expectRequired' => true,
            ],
            'required because cannot be empty' => [
                'canBeEmpty' => false,
                'required' => false,
                'childrenRequired' => false,
                'expectRequired' => true,
            ],
        ];
    }

    #[DataProvider('provideChildFieldsAreRequired')]
    public function testChildFieldsAreRequired(bool $canBeEmpty, bool $required, bool $childrenRequired, bool $expectRequired)
    {
        // CWP front-end templates break this logic - but there's no easy fix for that.
        // For the most part we are interested in ensuring this works in the CMS with default templates.
        $originalThemes = SSViewer::get_themes();
        if (class_exists(LeftAndMain::class)) {
            SSViewer::set_themes(LeftAndMain::config()->uninherited('admin_themes'));
        }
        try {
            $form = new Form();
            $field = new ConfirmedPasswordField('Test');
            $field->setForm($form);
            $field->setCanBeEmpty($canBeEmpty);
            $requiredFields = [];
            if ($required) {
                $requiredFields[] = 'Test';
            }
            if ($childrenRequired) {
                $requiredFields[] = 'Test[_Password]';
                $requiredFields[] = 'Test[_ConfirmPassword]';
            }
            $form->setValidator(new RequiredFieldsValidator($requiredFields));

            $rendered = $field->Field();
            $fieldOneRegex = '<input\s+type="password"\s+name="Test\[_Password\]"\s[^>]*?required="required"\s+aria-required="true"\s[^>]*\/>';
            $fieldTwoRegex = '<input\s+type="password"\s+name="Test\[_ConfirmPassword\]"\s[^>]*?required="required"\s+aria-required="true"\s[^>]*\/>';
            $regex = '/' . $fieldOneRegex . '.*' . $fieldTwoRegex . '/isu';

            if ($expectRequired) {
                $this->assertMatchesRegularExpression($regex, $rendered);
            } else {
                $this->assertDoesNotMatchRegularExpression($regex, $rendered);
            }
        } finally {
            SSViewer::set_themes($originalThemes);
        }
    }

    public static function provideStrength(): array
    {
        $tooLow = 'The password strength is too low. Please use a stronger password.';
        return [
            'very strong' => [
                'requestBody' => json_encode((object) [
                    'password' => 'the-quick-brown-fox-jumps-over-the-lazy-dog'
                ]),
                'httpMethod' => 'POST',
                'expectedStatusCode' => 200,
                'expectedBody' => json_encode((object) [
                    'strength' => 4,
                    'message' => 'Password strength: Very strong',
                    'tooLow' => $tooLow,
                ]),
            ],
            'strong' => [
                'requestBody' => json_encode((object) [
                    'password' => 'the-quick-brown-fox'
                ]),
                'httpMethod' => 'POST',
                'expectedStatusCode' => 200,
                'expectedBody' => json_encode((object) [
                    'strength' => 3,
                    'message' => 'Password strength: Strong',
                    'tooLow' => $tooLow,
                ]),
            ],
            'medium' => [
                'requestBody' => json_encode((object) [
                    'password' => 'the-quick-brown'
                ]),
                'httpMethod' => 'POST',
                'expectedStatusCode' => 200,
                'expectedBody' => json_encode((object) [
                    'strength' => 2,
                    'message' => 'Password strength: Medium',
                    'tooLow' => $tooLow,
                ]),
            ],
            'weak' => [
                'requestBody' => json_encode((object) [
                    'password' => 'the-quick-br'
                ]),
                'httpMethod' => 'POST',
                'expectedStatusCode' => 200,
                'expectedBody' => json_encode((object) [
                    'strength' => 1,
                    'message' => 'Password strength: Weak',
                    'tooLow' => $tooLow,
                ]),
            ],
            'very-weak' => [
                'requestBody' => json_encode((object) [
                    'password' => 'the'
                ]),
                'httpMethod' => 'POST',
                'expectedStatusCode' => 200,
                'expectedBody' => json_encode((object) [
                    'strength' => 0,
                    'message' => 'Password strength: Very weak',
                    'tooLow' => $tooLow,
                ]),
            ],
            'http-get' => [
                'requestBody' => json_encode((object) [
                    'password' => 'the-quick-brown'
                ]),
                'httpMethod' => 'GET',
                'expectedStatusCode' => 400,
                'expectedBody' => null,
            ],
            'invalid-request-json' => [
                'requestBody' => json_encode((object) [
                    'wordpass' => 'the-quick-brown'
                ]),
                'httpMethod' => 'POST',
                'expectedStatusCode' => 400,
                'expectedBody' => null,
            ],
        ];
    }

    #[DataProvider('provideStrength')]
    public function testStrength(
        string $requestBody,
        string $httpMethod,
        int $expectedStatusCode,
        ?string $expectedBody,
    ): void {
        $field = new ConfirmedPasswordField('Test');
        $passwordValidator = new EntropyPasswordValidator();
        Member::set_password_validator($passwordValidator);
        $request = new HTTPRequest($httpMethod, 'url', [], [], $requestBody);
        $response = $field->strength($request);
        $this->assertSame($expectedStatusCode, $response->getStatusCode());
        $this->assertSame($expectedBody, $response->getBody());
        $defaultContentType = (new HTTPResponse)->getHeader('Content-Type');
        $expectedContentType = $expectedBody ? 'application/json' : $defaultContentType;
        $this->assertSame($expectedContentType, $response->getHeader('Content-Type'));
    }

    public static function provideDataAttributes(): array
    {
        return [
            'default' => [
                'isOnMemberForm' => false,
                'requireStrongPassowrd' => false,
                'validatorClass' => EntropyPasswordValidator::class,
                'passwordStrengthCall' => null,
                'passwordStrengthConfig' => PasswordStrength::STRENGTH_MEDIUM,
                'expectedDataStrengthUrl' => '',
                'expectedMinStrength' => '',
            ],
            'on-member-form-entropy' => [
                'isOnMemberForm' => true,
                'requireStrongPassowrd' => false,
                'validatorClass' => EntropyPasswordValidator::class,
                'passwordStrengthCall' => null,
                'passwordStrengthConfig' => PasswordStrength::STRENGTH_MEDIUM,
                'expectedDataStrengthUrl' => 'field/Test/strength',
                'expectedMinStrength' => '2',
            ],
            'on-member-form-rules' => [
                'isOnMemberForm' => true,
                'requireStrongPassowrd' => false,
                'validatorClass' => RulesPasswordValidator::class,
                'passwordStrengthCall' => null,
                'passwordStrengthConfig' => PasswordStrength::STRENGTH_MEDIUM,
                'expectedDataStrengthUrl' => '',
                'expectedMinStrength' => '',
            ],
            'require-strong-password-entropy' => [
                'isOnMemberForm' => false,
                'requireStrongPassowrd' => true,
                'validatorClass' => EntropyPasswordValidator::class,
                'passwordStrengthCall' => null,
                'passwordStrengthConfig' => PasswordStrength::STRENGTH_MEDIUM,
                'expectedDataStrengthUrl' => 'field/Test/strength',
                'expectedMinStrength' => '2',
            ],
            'require-strong-password-rules' => [
                'isOnMemberForm' => false,
                'requireStrongPassowrd' => true,
                'validatorClass' => RulesPasswordValidator::class,
                'passwordStrengthCall' => null,
                'passwordStrengthConfig' => PasswordStrength::STRENGTH_MEDIUM,
                'expectedDataStrengthUrl' => 'field/Test/strength',
                'expectedMinStrength' => '2',
            ],
            'min-strength-strong' => [
                'isOnMemberForm' => true,
                'requireStrongPassowrd' => false,
                'validatorClass' => EntropyPasswordValidator::class,
                'passwordStrengthCall' => null,
                'passwordStrengthConfig' => PasswordStrength::STRENGTH_STRONG,
                'expectedDataStrengthUrl' => 'field/Test/strength',
                'expectedMinStrength' => '3',
            ],
            'min-strength-weak' => [
                'isOnMemberForm' => true,
                'requireStrongPassowrd' => false,
                'validatorClass' => EntropyPasswordValidator::class,
                'passwordStrengthCall' => null,
                'passwordStrengthConfig' => PasswordStrength::STRENGTH_WEAK,
                'expectedDataStrengthUrl' => 'field/Test/strength',
                'expectedMinStrength' => '1',
            ],
            'min-strength-weak-explicit-very-strong' => [
                'isOnMemberForm' => false,
                'requireStrongPassowrd' => true,
                'validatorClass' => EntropyPasswordValidator::class,
                'passwordStrengthCall' => PasswordStrength::STRENGTH_VERY_STRONG,
                'passwordStrengthConfig' => PasswordStrength::STRENGTH_WEAK,
                'expectedDataStrengthUrl' => 'field/Test/strength',
                'expectedMinStrength' => '4',
            ],
            'member-form-ignores-call' => [
                'isOnMemberForm' => true,
                'requireStrongPassowrd' => false,
                'validatorClass' => EntropyPasswordValidator::class,
                'passwordStrengthCall' => PasswordStrength::STRENGTH_VERY_STRONG,
                'passwordStrengthConfig' => PasswordStrength::STRENGTH_WEAK,
                'expectedDataStrengthUrl' => 'field/Test/strength',
                'expectedMinStrength' => '1',
            ],
            'member-form-and-require-strong' => [
                'isOnMemberForm' => true,
                'requireStrongPassowrd' => true,
                'validatorClass' => EntropyPasswordValidator::class,
                'passwordStrengthCall' => PasswordStrength::STRENGTH_VERY_STRONG,
                'passwordStrengthConfig' => PasswordStrength::STRENGTH_MEDIUM,
                'expectedDataStrengthUrl' => 'field/Test/strength',
                'expectedMinStrength' => '4',
            ],
        ];
    }

    #[DataProvider('provideDataAttributes')]
    public function testDataAttributes(
        bool $isOnMemberForm,
        bool $requireStrongPassowrd,
        string $validatorClass,
        ?int $passwordStrengthCall,
        int $passwordStrengthConfig,
        string $expectedDataStrengthUrl,
        string $expectedMinStrength,
    ): void {
        // Create field
        $form = new Form();
        $field = new ConfirmedPasswordField('Test');
        $field->setForm($form);
        $field->setIsOnMemberForm($isOnMemberForm);
        $field->setRequireStrongPassword($requireStrongPassowrd);
        if ($passwordStrengthCall !== null) {
            $field->setMinPasswordStrength($passwordStrengthCall);
        }
        // PasswordValidator
        $validator = $validatorClass ? Injector::inst()->create($validatorClass) : null;
        Member::set_password_validator($validator);
        Config::modify()->set(EntropyPasswordValidator::class, 'password_strength', $passwordStrengthConfig);
        // Render HTML
        $html = $field->Field();
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $input = $xpath->query('//input[@name="Test[_Password]"]')->item(0);
        /** @var DOMElement $input */
        // Assert
        $this->assertSame($expectedDataStrengthUrl, $input->getAttribute('data-strength-url'));
        $this->assertSame($expectedMinStrength, $input->getAttribute('data-min-strength'));
    }

    public static function provideGetMinPasswordStrengthForEvaluation(): array
    {
        return [
            'entropy-none' => [
                'entropyValidator' => true,
                'requireStrong' => false,
                'onMemberForm' => false,
                'expected' => -1,
            ],
            'entropy-require-strong' => [
                'entropyValidator' => true,
                'requireStrong' => true,
                'onMemberForm' => false,
                'expected' => 4,
            ],
            'entropy-on-member-form' => [
                'entropyValidator' => true,
                'requireStrong' => false,
                'onMemberForm' => true,
                'expected' => 1,
            ],
            'entropy-both' => [
                'entropyValidator' => true,
                'requireStrong' => true,
                'onMemberForm' => true,
                'expected' => 4,
            ],
            'rules-none' => [
                'entropyValidator' => false,
                'requireStrong' => false,
                'onMemberForm' => false,
                'expected' => -1,
            ],
            'rules-require-strong' => [
                'entropyValidator' => false,
                'requireStrong' => true,
                'onMemberForm' => false,
                'expected' => 4,
            ],
            'rules-on-member-form' => [
                'entropyValidator' => false,
                'requireStrong' => false,
                'onMemberForm' => true,
                'expected' => -1,
            ],
            'rules-both' => [
                'entropyValidator' => false,
                'requireStrong' => true,
                'onMemberForm' => true,
                'expected' => 4,
            ],
        ];
    }

    #[DataProvider('provideGetMinPasswordStrengthForEvaluation')]
    public function testGetMinPasswordStrengthForEvaluation(
        bool $entropyValidator,
        bool $requireStrong,
        bool $onMemberForm,
        int $expected,
    ): void {
        $class = $entropyValidator ? EntropyPasswordValidator::class : RulesPasswordValidator::class;
        $validator = Injector::inst()->create($class);
        Injector::inst()->registerService($validator, PasswordValidator::class);
        $field = new ConfirmedPasswordField('Test');
        // used by requireStrongPassword = true
        $field->setMinPasswordStrength(PasswordStrength::STRENGTH_VERY_STRONG);
        // used by onMemberForm = true
        Config::modify()->set(EntropyPasswordValidator::class, 'password_strength', PasswordStrength::STRENGTH_WEAK);
        $field->setRequireStrongPassword($requireStrong);
        $field->setIsOnMemberForm($onMemberForm);
        $refl = new ReflectionMethod($field, 'getMinPasswordStrengthForEvaluation');
        $refl->setAccessible(true);
        $actual = $refl->invoke($field);
        $this->assertSame($expected, $actual);
    }

    public static function provideGetStrengthLabel(): array
    {
        return [
            'very-weak' => [
                'strength' => PasswordStrength::STRENGTH_VERY_WEAK,
                'expected' => 'Very weak',
            ],
            'weak' => [
                'strength' => PasswordStrength::STRENGTH_WEAK,
                'expected' => 'Weak',
            ],
            'medium' => [
                'strength' => PasswordStrength::STRENGTH_MEDIUM,
                'expected' => 'Medium',
            ],
            'strong' => [
                'strength' => PasswordStrength::STRENGTH_STRONG,
                'expected' => 'Strong',
            ],
            'very-strong' => [
                'strength' => PasswordStrength::STRENGTH_VERY_STRONG,
                'expected' => 'Very strong',
            ],
            'unknown' => [
                'strength' => 999,
                'expected' => '',
            ],
        ];
    }

    #[DataProvider('provideGetStrengthLabel')]
    public function testGetStrengthLabel(int $strength, string $expected): void
    {
        $field = new ConfirmedPasswordField('Test');
        $refl = new ReflectionMethod($field, 'getStrengthLabel');
        $refl->setAccessible(true);
        $actual = $refl->invoke($field, $strength);
        $this->assertSame($expected, $actual);
    }
}
