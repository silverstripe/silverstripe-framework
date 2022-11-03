<?php

namespace SilverStripe\Dev\Tests;

use PHPUnit\Framework\Error\Deprecated;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Tests\DeprecationTest\DeprecationTestObject;

class DeprecationTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        DeprecationTestObject::class,
    ];

    private $oldHandler = null;

    protected function setup(): void
    {
        // Use custom error handler for two reasons:
        // - Filter out errors for deprecated class properities unrelated to this unit test
        // - Allow the use of expectDeprecation(), which doesn't work with E_USER_DEPRECATION by default
        //   https://github.com/laminas/laminas-di/pull/30#issuecomment-927585210
        parent::setup();
        $this->oldHandler = set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
            if ($errno === E_USER_DEPRECATED) {
                if (str_contains($errstr, 'SilverStripe\\Dev\\Tests\\DeprecationTest')) {
                    throw new Deprecated($errstr, $errno, '', 1);
                } else {
                    // Surpress any E_USER_DEPRECATED unrelated to this unit-test
                    return true;
                }
            }
            if (is_callable($this->oldHandler)) {
                return call_user_func($this->oldHandler, $errno, $errstr, $errfile, $errline);
            }
            // Fallback to default PHP error handler
            return false;
        });
    }

    protected function tearDown(): void
    {
        Deprecation::disable();
        restore_error_handler();
        $this->oldHandler = null;
        parent::tearDown();
    }

    public function testNotice()
    {
        $message = implode(' ', [
            'SilverStripe\Dev\Tests\DeprecationTest->testNotice is deprecated.',
            'My message.',
            'Called from PHPUnit\Framework\TestCase->runTest.'
        ]);
        $this->expectDeprecation();
        $this->expectDeprecationMessage($message);
        Deprecation::enable();
        Deprecation::notice('1.2.3', 'My message');
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDisabled()
    {
        // test that no error error is raised because by default Deprecation is disabled
        Deprecation::notice('4.5.6', 'My message');
    }

    // The following tests would be better to put in the silverstripe/config module, however this is not
    // possible to do in a clean way as the config for the DeprecationTestObject will not load if it
    // is inside the silverstripe/config directory, as there is no _config.php file or _config folder.
    // Adding a _config.php file will break existing unit-tests within silverstripe/config
    // It is possible to put DeprecationTestObject in framework and the unit tests in config, however
    // that's probably messier then just having everything within framework

    public function testConfigGetFirst()
    {
        $message = implode(' ', [
            'Config SilverStripe\Dev\Tests\DeprecationTest\DeprecationTestObject.first_config is deprecated.',
            'My first config message.'
        ]);
        $this->expectDeprecation();
        $this->expectDeprecationMessage($message);
        Deprecation::enable();
        Config::inst()->get(DeprecationTestObject::class, 'first_config');
    }

    public function testConfigGetSecond()
    {
        $message = implode(' ', [
            'Config SilverStripe\Dev\Tests\DeprecationTest\DeprecationTestObject.second_config is deprecated.',
            'My second config message.'
        ]);
        $this->expectDeprecation();
        $this->expectDeprecationMessage($message);
        Deprecation::enable();
        Config::inst()->get(DeprecationTestObject::class, 'second_config');
    }

    public function testConfigGetThird()
    {
        $message = 'Config SilverStripe\Dev\Tests\DeprecationTest\DeprecationTestObject.third_config is deprecated.';
        $this->expectDeprecation();
        $this->expectDeprecationMessage($message);
        Deprecation::enable();
        Config::inst()->get(DeprecationTestObject::class, 'third_config');
    }

    public function testConfigSet()
    {
        $message = implode(' ', [
            'Config SilverStripe\Dev\Tests\DeprecationTest\DeprecationTestObject.first_config is deprecated.',
            'My first config message.'
        ]);
        $this->expectDeprecation();
        $this->expectDeprecationMessage($message);
        Deprecation::enable();
        Config::modify()->set(DeprecationTestObject::class, 'first_config', 'abc');
    }

    public function testConfigMerge()
    {
        $message = implode(' ', [
            'Config SilverStripe\Dev\Tests\DeprecationTest\DeprecationTestObject.array_config is deprecated.',
            'My array config message.'
        ]);
        $this->expectDeprecation();
        $this->expectDeprecationMessage($message);
        Deprecation::enable();
        Config::modify()->merge(DeprecationTestObject::class, 'array_config', ['abc']);
    }
}
