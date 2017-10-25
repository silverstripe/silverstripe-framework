<?php

namespace SilverStripe\Core\Tests;

use SilverStripe\Core\Environment;
use SilverStripe\Core\EnvironmentLoader;
use SilverStripe\Dev\SapphireTest;

class EnvironmentLoaderTest extends SapphireTest
{
    public function testStripComments()
    {
        $loader = new EnvironmentLoader();

        // No file
        $this->assertNull($loader->loadFile(__DIR__ . '/EnvironmentTest/nofile.env'));

        // Initial load
        $vars = $loader->loadFile(__DIR__ . '/EnvironmentTest/test.env');
        $this->assertCount(7, $vars);
        $this->assertEquals('first', $vars['TEST_ENV_FIRST']);
        $this->assertEquals('first', Environment::getEnv('TEST_ENV_FIRST'));
        $this->assertEquals('start "#notcomment end', $vars['TEST_ENV_SECOND']);
        $this->assertEquals('start "#notcomment end', Environment::getEnv('TEST_ENV_SECOND'));
        $this->assertEquals(3, $vars['TEST_ENV_THIRD']);
        $this->assertEquals(3, Environment::getEnv('TEST_ENV_THIRD'));
        $this->assertEquals(true, $vars['TEST_ENV_FOURTH']);
        $this->assertEquals(true, Environment::getEnv('TEST_ENV_FOURTH'));
        $this->assertEquals('not#comment', $vars['TEST_ENV_FIFTH']);
        $this->assertEquals('not#comment', Environment::getEnv('TEST_ENV_FIFTH'));
        $this->assertEquals('not#comment', $vars['TEST_ENV_SIXTH']);
        $this->assertEquals('not#comment', Environment::getEnv('TEST_ENV_SIXTH'));
        $this->assertEquals('', $vars['TEST_ENV_SEVENTH']);
        $this->assertEquals('', Environment::getEnv('TEST_ENV_SEVENTH'));
    }

    public function testOverloading()
    {
        $loader = new EnvironmentLoader();

        // No file
        $loader->loadFile(__DIR__ . '/EnvironmentTest/test.env');

        // Ensure default behaviour doesn't overload
        $vars2 = $loader->loadFile(__DIR__ . '/EnvironmentTest/test2.env');
        $this->assertEquals(
            [
                'TEST_ENV_FIRST' => 'first',
                'TEST_ENV_SECOND' => 'start "#notcomment end',
                'TEST_ENV_NEWVAR' => 'first-overloaded',
                'TEST_ENV_NEWVAR2' => 'second-file',
            ],
            $vars2
        );
        $this->assertEquals('first', Environment::getEnv('TEST_ENV_FIRST'));

        // Test overload = true
        $vars2 = $loader->loadFile(__DIR__ . '/EnvironmentTest/test2.env', true);
        $this->assertEquals(
            [
                'TEST_ENV_FIRST' => 'first-overloaded',
                'TEST_ENV_SECOND' => 'first-overloaded',
                'TEST_ENV_NEWVAR' => 'first-overloaded',
                'TEST_ENV_NEWVAR2' => 'second-file',
            ],
            $vars2
        );
        $this->assertEquals('first-overloaded', Environment::getEnv('TEST_ENV_FIRST'));
    }

    public function testInterpolation()
    {
        $loader = new EnvironmentLoader();
        $vars = $loader->loadFile(__DIR__ . '/EnvironmentTest/test3.env');
        $this->assertEquals(
            [
                'TEST_ENV_INT_ONE' => 'some var',
                'TEST_ENV_INT_TWO' => 'some var',
            ],
            $vars
        );
    }
}
