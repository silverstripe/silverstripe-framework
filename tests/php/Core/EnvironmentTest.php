<?php

namespace SilverStripe\Core\Tests;

use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;

class EnvironmentTest extends SapphireTest
{
    public function providerTestPutEnv()
    {
        return [
            ['_ENVTEST_BOOL=true', '_ENVTEST_BOOL', true],
            ['_ENVTEST_BOOL_QUOTED="true"', '_ENVTEST_BOOL_QUOTED', 'true'],
            ['_ENVTEST_NUMBER=1', '_ENVTEST_NUMBER', 1],
            ['_ENVTEST_NUMBER_QUOTED="1"', '_ENVTEST_NUMBER_QUOTED', '1'],
            ['_ENVTEST_NUMBER_SPECIAL="value=4"', '_ENVTEST_NUMBER_SPECIAL', 'value=4'],
            ['_ENVTEST_BLANK', '_ENVTEST_BLANK', false],
        ];
    }

    /**
     * @dataProvider providerTestPutenv
     */
    public function testPutEnv($put, $var, $value)
    {
        Environment::putEnv($put);
        $this->assertEquals($value, Environment::getEnv($var));
    }

    /**
     * @dataProvider providerTestPutEnv
     */
    public function testSetEnv($put, $var, $value)
    {
        Environment::setEnv($var, $value);
        $this->assertEquals($value, Environment::getEnv($var));
    }

    public function testRestoreEnv()
    {
        // Set and backup original vars
        Environment::putEnv('_ENVTEST_RESTORED=initial');
        $vars = Environment::getVariables();
        $this->assertEquals('initial', Environment::getEnv('_ENVTEST_RESTORED'));

        // Modify enironment
        Environment::putEnv('_ENVTEST_RESTORED=new');
        $this->assertEquals('initial', $vars['env']['_ENVTEST_RESTORED']);
        $this->assertEquals('new', Environment::getEnv('_ENVTEST_RESTORED'));

        // Restore
        Environment::setVariables($vars);
        $this->assertEquals('initial', Environment::getEnv('_ENVTEST_RESTORED'));
    }
}
