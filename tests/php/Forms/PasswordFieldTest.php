<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\PasswordField;
use PHPUnit\Framework\Attributes\DataProvider;

class PasswordFieldTest extends SapphireTest
{
    public static function boolDataProvider()
    {
        return [
            [false],
            [true]
        ];
    }

    /**
     * @param bool $bool
     */
    #[DataProvider('boolDataProvider')]
    public function testAutocomplete($bool)
    {
        Config::modify()->set(PasswordField::class, 'autocomplete', $bool);
        $field = new PasswordField('test');
        $attrs = $field->getAttributes();

        $this->assertArrayHasKey('autocomplete', $attrs);
        $this->assertEquals($bool ? 'on' : 'off', $attrs['autocomplete']);
    }

    /**
     * @param bool $bool
     */
    #[DataProvider('boolDataProvider')]
    public function testValuePostback($bool)
    {
        $field = (new PasswordField('test', 'test', 'password'))
            ->setAllowValuePostback($bool);
        $attrs = $field->getAttributes();

        $this->assertArrayHasKey('value', $attrs);
        $this->assertEquals($bool ? 'password' : '', $attrs['value']);
    }
}
