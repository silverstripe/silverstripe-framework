<?php


class PasswordFieldTest extends SapphireTest
{
    public function boolDataProvider() {
        return array(
            array(false),
            array(true)
        );
    }

    /**
     * @dataProvider boolDataProvider
     * @param bool $bool
     */
    public function testAutocomplete($bool) {
        Config::inst()->update('PasswordField', 'autocomplete', $bool);
        $field = new PasswordField('test');
        $attrs = $field->getAttributes();

        $this->assertArrayHasKey('autocomplete', $attrs);
        $this->assertEquals($bool ? 'on' : 'off', $attrs['autocomplete']);
    }

    /**
     * @dataProvider boolDataProvider
     * @param bool $bool
     */
    public function testValuePostback($bool) {        
        $field = PasswordField::create('test', 'test', 'password')
        	->setAllowValuePostback($bool);
        $attrs = $field->getAttributes();

        $this->assertArrayHasKey('value', $attrs);
        $this->assertEquals($bool ? 'password' : '', $attrs['value']);
    }
}
