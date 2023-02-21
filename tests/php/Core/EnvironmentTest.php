<?php

namespace SilverStripe\Core\Tests;

use ReflectionProperty;
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

        // Modify environment
        Environment::putEnv('_ENVTEST_RESTORED=new');
        $this->assertEquals('initial', $vars['env']['_ENVTEST_RESTORED']);
        $this->assertEquals('new', Environment::getEnv('_ENVTEST_RESTORED'));

        // Restore
        Environment::setVariables($vars);
        $this->assertEquals('initial', Environment::getEnv('_ENVTEST_RESTORED'));
    }

    public function testGetVariables()
    {
        $GLOBALS['test'] = 'global';
        $vars = Environment::getVariables();
        $this->assertArrayHasKey('test', $vars);
        $this->assertEquals('global', $vars['test']);
        $this->assertEquals('global', $GLOBALS['test']);

        $vars['test'] = 'fail';
        $this->assertEquals('fail', $vars['test']);
        $this->assertEquals('global', $GLOBALS['test']);
    }

    public function provideHasEnv()
    {
        $setAsOptions = [
            '.env',
            '_ENV',
            '_SERVER',
            'putenv',
        ];
        $valueOptions = [
            true,
            false,
            null,
            0,
            1,
            1.75,
            '',
            '0',
            'some-value',
        ];
        $scenarios = [];
        foreach ($setAsOptions as $setAs) {
            foreach ($valueOptions as $value) {
                $scenarios[] = [
                    'setAs' => $setAs,
                    'value' => $value,
                    'expected' => true,
                ];
            }
        }
        $scenarios[] = [
            'setAs' => null,
            'value' => null,
            'expected' => false,
        ];
        return $scenarios;
    }

    /**
     * @dataProvider provideHasEnv
     */
    public function testHasEnv(?string $setAs, $value, bool $expected)
    {
        $name = '_ENVTEST_HAS_ENV';

        // Set the value
        switch ($setAs) {
            case '.env':
                Environment::setEnv($name, $value);
                break;
            case '_ENV':
                $_ENV[$name] = $value;
                break;
            case '_SERVER':
                $_SERVER[$name] = $value;
                break;
            case 'putenv':
                $val = is_string($value) ? $value : json_encode($value);
                putenv("$name=$val");
                break;
            default:
                // null is no-op, to validate not setting it works as expected.
                if ($setAs !== null) {
                    $this->fail("setAs value $setAs isn't taken into account correctly for this test.");
                }
        }

        $this->assertSame($expected, Environment::hasEnv($name));

        // unset the value
        $reflectionEnv = new ReflectionProperty(Environment::class, 'env');
        $reflectionEnv->setAccessible(true);
        $reflectionEnv->setValue(array_diff($reflectionEnv->getValue(), [$name => $value]));
        unset($_ENV[$name]);
        unset($_SERVER[$name]);
        putenv("$name");
    }
}
