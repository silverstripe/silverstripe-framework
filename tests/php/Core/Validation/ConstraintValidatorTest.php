<?php

namespace SilverStripe\Core\Tests\Validation;

use InvalidArgumentException;
use SilverStripe\Core\Validation\ConstraintValidator;
use SilverStripe\Dev\SapphireTest;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\AtLeastOneOf;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\CardScheme;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Cidr;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\CssColor;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\DivisibleBy;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Hostname;
use Symfony\Component\Validator\Constraints\Iban;
use Symfony\Component\Validator\Constraints\IdenticalTo;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Ip;
use Symfony\Component\Validator\Constraints\Isbn;
use Symfony\Component\Validator\Constraints\IsFalse;
use Symfony\Component\Validator\Constraints\Isin;
use Symfony\Component\Validator\Constraints\IsNull;
use Symfony\Component\Validator\Constraints\Issn;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Luhn;
use Symfony\Component\Validator\Constraints\Negative;
use Symfony\Component\Validator\Constraints\NegativeOrZero;
use Symfony\Component\Validator\Constraints\NoSuspiciousCharacters;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotEqualTo;
use Symfony\Component\Validator\Constraints\NotIdenticalTo;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Constraints\Time;
use Symfony\Component\Validator\Constraints\Timezone;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Ulid;
use Symfony\Component\Validator\Constraints\Unique;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraints\Uuid;
use PHPUnit\Framework\Attributes\DataProvider;

class ConstraintValidatorTest extends SapphireTest
{
    protected $usesDatabase = false;

    public static function provideValidate(): array
    {
        $scenarios = [
            // basic
            'NotBlank' => [
                'value' => '',
                'constraint' => new NotBlank(),
            ],
            'Blank' => [
                'value' => 'not blank',
                'constraint' => new Blank(),
            ],
            'NotNull' => [
                'value' => null,
                'constraint' => new NotNull(),
            ],
            'IsNull' => [
                'value' => 'not null',
                'constraint' => new IsNull(),
            ],
            'IsTrue' => [
                'value' => false,
                'constraint' => new IsTrue(),
            ],
            'IsFalse' => [
                'value' => true,
                'constraint' => new IsFalse(),
            ],
            'Type' => [
                'value' => 'not that type',
                'constraint' => new Type(Type::class),
            ],
            // strings
            'Email' => [
                'value' => 'not an email address',
                'constraint' => new Email(),
            ],
            'Length' => [
                'value' => 'not length of 5',
                'constraint' => new Length(exactly: 5),
            ],
            'Url' => [
                'value' => 'not a valid url',
                'constraint' => new Url(),
            ],
            'Regex' => [
                'value' => 'doesnt match that pattern',
                'constraint' => new Regex('/regex/'),
            ],
            'Hostname' => [
                'value' => 'not a valid hostname',
                'constraint' => new Hostname(),
            ],
            'Ip' => [
                'value' => 'not an IP address',
                'constraint' => new Ip(),
            ],
            'Cidr' => [
                'value' => 'not CIDR notation',
                'constraint' => new Cidr(),
            ],
            'Json' => [
                'value' => 'not a JSON string',
                'constraint' => new Json(),
            ],
            'Uuid' => [
                'value' => 'not a UUID',
                'constraint' => new Uuid(),
            ],
            'Ulid' => [
                'value' => 'not a ULID',
                'constraint' => new Ulid(),
            ],
            'CssColor' => [
                'value' => 'not a color',
                'constraint' => new CssColor(),
            ],
            // comparisons
            'EqualTo' => [
                'value' => 'doesnt match that',
                'constraint' => new EqualTo('match this'),
            ],
            'NotEqualTo' => [
                'value' => 'match this',
                'constraint' => new NotEqualTo('match this'),
            ],
            'IdenticalTo' => [
                'value' => 'not exactly the same',
                'constraint' => new IdenticalTo('exactly the same'),
            ],
            'NotIdenticalTo' => [
                'value' => 'exactly the same',
                'constraint' => new NotIdenticalTo('exactly the same'),
            ],
            'LessThan' => [
                'value' => 35,
                'constraint' => new LessThan(1),
            ],
            'LessThanOrEqual' => [
                'value' => 35,
                'constraint' => new LessThanOrEqual(1),
            ],
            'GreaterThan' => [
                'value' => 1,
                'constraint' => new GreaterThan(35),
            ],
            'GreaterThanOrEqual' => [
                'value' => 1,
                'constraint' => new GreaterThanOrEqual(35),
            ],
            'Range' => [
                'value' => 1,
                'constraint' => new Range(min: 30, max: 35),
            ],
            'DivisibleBy' => [
                'value' => 3,
                'constraint' => new DivisibleBy(2),
            ],
            'Unique' => [
                'value' => ['not unique', 'not unique'],
                'constraint' => new Unique(),
            ],
            // numbers
            'Positive' => [
                'value' => -1,
                'constraint' => new Positive(),
            ],
            'PositiveOrZero' => [
                'value' => -1,
                'constraint' => new PositiveOrZero(),
            ],
            'Negative' => [
                'value' => 1,
                'constraint' => new Negative(),
            ],
            'NegativeOrZero' => [
                'value' => 1,
                'constraint' => new NegativeOrZero(),
            ],
            // dates
            'Date' => [
                'value' => 'not a date',
                'constraint' => new Date(),
            ],
            'DateTime' => [
                'value' => 'not a datetime',
                'constraint' => new DateTime(),
            ],
            'Time' => [
                'value' => 'not a time',
                'constraint' => new Time(),
            ],
            'Timezone' => [
                'value' => 'not a timezone',
                'constraint' => new Timezone(),
            ],
            // choices
            'Choice' => [
                'value' => 'not one of those',
                'constraint' => new Choice(['one', 'of', 'these']),
            ],
            // files
            'File' => [
                'value' => 'not a path to a file',
                'constraint' => new File(),
            ],
            'Image' => [
                'value' => 'not a path to an image',
                'constraint' => new Image(),
            ],
            // fincancial
            'CardScheme' => [
                'value' => 'not a credit card number',
                'constraint' => new CardScheme(CardScheme::VISA),
            ],
            'Luhn' => [
                'value' => 'not a credit card number',
                'constraint' => new Luhn(),
            ],
            'Iban' => [
                'value' => 'not a valid IBAN',
                'constraint' => new Iban(),
            ],
            'Isbn' => [
                'value' => 'not a valid ISBN',
                'constraint' => new Isbn(),
            ],
            'Issn' => [
                'value' => 'not a valid ISSN',
                'constraint' => new Issn(),
            ],
            'Isin' => [
                'value' => 'not a valid ISIN',
                'constraint' => new Isin(),
            ],
            // other
            'AtLeastOneOf' => [
                'value' => 'doesnt match any of the constraints',
                'constraint' => new AtLeastOneOf(constraints: [new Regex('/regex/')]),
            ],
            'Sequentially' => [
                'value' => 'doesnt match the constraints in sequence',
                'constraint' => new Sequentially(constraints: [new Regex('/regex/')]),
            ],
            'Callback' => [
                'value' => 'this value doesnt matter',
                'constraint' => new Callback(
                    fn ($_, $context) => $context->buildViolation('always fail the validation')->addViolation()
                ),
            ],
            'All' => [
                'value' => ['all items passed in fail all of the constraints'],
                'constraint' => new All(constraints: [new Regex('/regex/')]),
            ],
            'Collection' => [
                'value' => ['field1' => 'doesnt match the pattern'],
                'constraint' => new Collection(fields: ['field1' => new Regex('/regex/')]),
            ],
            'Count' => [
                'value' => ['less than 30 items'],
                'constraint' => new Count(min:30),
            ],
        ];
        // These classes don't exist until symfony/validator 6.3
        if (class_exists(PasswordStrength::class)) {
            $scenarios['PasswordStrength'] = [
                'value' => 'password',
                'constraint' => new PasswordStrength(minScore: PasswordStrength::STRENGTH_VERY_STRONG),
            ];
        }
        if (class_exists(NoSuspiciousCharacters::class)) {
            $scenarios['NoSuspiciousCharacters'] = [
                'value' => '1234567৪',
                'constraint' => new NoSuspiciousCharacters(),
            ];
        }
        return $scenarios;
    }

    /**
     * Tests that all of the currently supported constraints work without throwing exceptions.
     *
     * We're not actually testing the validation logic per se - just testing that the validators
     * all do some validating (hence why they are all set to fail) without exceptions being thrown.
     */
    #[DataProvider('provideValidate')]
    public function testValidate(mixed $value, Constraint $constraint): void
    {
        $this->assertFalse(ConstraintValidator::validate($value, $constraint)->isValid());
    }

    public static function provideValidateResults(): array
    {
        return [
            'single constraint, no field' => [
                'value' => 'some value',
                'constraints' => new Blank(),
                'fieldName' => '',
            ],
            'single constraint, with field' => [
                'value' => 'some value',
                'constraints' => new Blank(),
                'fieldName' => 'MyField',
            ],
            'array, no field' => [
                'value' => 'some value',
                'constraints' => [new Blank(), new Date()],
                'fieldName' => '',
            ],
            'array, with field' => [
                'value' => 'some value',
                'constraints' => [new Blank(), new Date()],
                'fieldName' => 'MyField',
            ],
        ];
    }

    #[DataProvider('provideValidateResults')]
    public function testValidateResults(mixed $value, Constraint|array $constraints, string $fieldName): void
    {
        $result = ConstraintValidator::validate($value, $constraints, $fieldName);
        $violations = $result->getMessages();
        $countViolations = is_array($constraints) ? count($constraints) : 1;

        $this->assertCount($countViolations, $violations);
        foreach ($violations as $violation) {
            $this->assertSame($fieldName ?: null, $violation['fieldName']);
        }
    }

    public function testValidateNoConstraints(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ConstraintValidator::validate('some value', []);
    }
}
