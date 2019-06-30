<?php declare(strict_types = 1);

namespace SilverStripe\Forms\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\PasswordField;

class PasswordFieldTest extends SapphireTest
{
    public function boolDataProvider()
    {
        return [
            [false],
            [true]
        ];
    }

    /**
     * @dataProvider boolDataProvider
     * @param bool $bool
     */
    public function testAutocomplete($bool)
    {
        Config::modify()->set(PasswordField::class, 'autocomplete', $bool);
        $field = new PasswordField('test');
        $attrs = $field->getAttributes();

        $this->assertArrayHasKey('autocomplete', $attrs);
        $this->assertEquals($bool ? 'on' : 'off', $attrs['autocomplete']);
    }

    /**
     * @dataProvider boolDataProvider
     * @param bool $bool
     */
    public function testValuePostback($bool)
    {
        $field = (new PasswordField('test', 'test', 'password'))
            ->setAllowValuePostback($bool);
        $attrs = $field->getAttributes();

        $this->assertArrayHasKey('value', $attrs);
        $this->assertEquals($bool ? 'password' : '', $attrs['value']);
    }
}
