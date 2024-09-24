<?php

namespace SilverStripe\Security\Tests\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use SilverStripe\Security\Validation\EntropyPasswordValidator;

/**
 * EntropyPasswordValidator uses a third-party for its validation so we don't need rigorous testing here.
 * Just test that stupid simple passwords don't pass, and complex ones do.
 */
class EntropyPasswordValidatorTest extends SapphireTest
{
    protected $usesDatabase = false;

    public static function provideValidate(): array
    {
        return [
            [
                'password' => '',
                'expected' => false,
            ],
            [
                'password' => 'password123',
                'expected' => false,
            ],
            [
                'password' => 'This is a really long and complex PASSWORD',
                'expected' => true,
            ],
        ];
    }

    #[DataProvider('provideValidate')]
    public function testValidate(string $password, bool $expected): void
    {
        $validator = new EntropyPasswordValidator();
        $this->assertSame($expected, $validator->validate($password, new Member())->isValid());
    }
}
