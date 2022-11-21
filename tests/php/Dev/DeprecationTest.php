<?php

namespace SilverStripe\Dev\Tests;

use PHPUnit\Framework\Error\Deprecated;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Tests\DeprecationTest\DeprecationTestObject;
use SilverStripe\Core\Injector\Injector;

class DeprecationTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        DeprecationTestObject::class,
    ];

    private $oldHandler = null;

    private bool $noticesWereEnabled = false;

    protected function setup(): void
    {
        // Use custom error handler for two reasons:
        // - Filter out errors for deprecated class properities unrelated to this unit test
        // - Allow the use of expectDeprecation(), which doesn't work with E_USER_DEPRECATION by default
        //   https://github.com/laminas/laminas-di/pull/30#issuecomment-927585210
        parent::setup();
        $this->noticesWereEnabled = Deprecation::isEnabled();
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
        if (!$this->noticesWereEnabled) {
            Deprecation::disable();
        }
        restore_error_handler();
        $this->oldHandler = null;
        parent::tearDown();
    }

    private function myDeprecatedMethod(): string
    {
        Deprecation::notice('1.2.3', 'My message');
        return 'abc';
    }

    public function testNotice()
    {
        $message = implode(' ', [
            'SilverStripe\Dev\Tests\DeprecationTest->myDeprecatedMethod is deprecated.',
            'My message.',
            'Called from SilverStripe\Dev\Tests\DeprecationTest->testNotice.'
        ]);
        $this->expectDeprecation();
        $this->expectDeprecationMessage($message);
        Deprecation::enable();
        $ret = $this->myDeprecatedMethod();
        $this->assertSame('abc', $ret);
        // call outputNotices() directly because the regular shutdown function that emits
        // the notices within Deprecation won't be called until after this unit-test has finished
        Deprecation::outputNotices();
    }

    public function testCallUserFunc()
    {
        $message = implode(' ', [
            'SilverStripe\Dev\Tests\DeprecationTest->myDeprecatedMethod is deprecated.',
            'My message.',
            'Called from SilverStripe\Dev\Tests\DeprecationTest->testCallUserFunc.'
        ]);
        $this->expectDeprecation();
        $this->expectDeprecationMessage($message);
        Deprecation::enable();
        $ret = call_user_func([$this, 'myDeprecatedMethod']);
        $this->assertSame('abc', $ret);
        Deprecation::outputNotices();
    }

    public function testCallUserFuncArray()
    {
        $message = implode(' ', [
            'SilverStripe\Dev\Tests\DeprecationTest->myDeprecatedMethod is deprecated.',
            'My message.',
            'Called from SilverStripe\Dev\Tests\DeprecationTest->testCallUserFuncArray.'
        ]);
        $this->expectDeprecation();
        $this->expectDeprecationMessage($message);
        Deprecation::enable();
        $ret = call_user_func_array([$this, 'myDeprecatedMethod'], []);
        $this->assertSame('abc', $ret);
        Deprecation::outputNotices();
    }

    public function testWithNoReplacementDefault()
    {
        Deprecation::enable();
        $ret = Deprecation::withNoReplacement(function () {
            return $this->myDeprecatedMethod();
        });
        $this->assertSame('abc', $ret);
        Deprecation::outputNotices();
    }

    public function testWithNoReplacementTrue()
    {
        $message = implode(' ', [
            'SilverStripe\Dev\Tests\DeprecationTest->myDeprecatedMethod is deprecated.',
            'My message.',
            'Called from SilverStripe\Dev\Tests\DeprecationTest->testWithNoReplacementTrue.'
        ]);
        $this->expectDeprecation();
        $this->expectDeprecationMessage($message);
        Deprecation::enable(true);
        $ret = Deprecation::withNoReplacement(function () {
            return $this->myDeprecatedMethod();
        });
        $this->assertSame('abc', $ret);
        Deprecation::outputNotices();
    }

    public function testWithNoReplacementTrueCallUserFunc()
    {
        $message = implode(' ', [
            'SilverStripe\Dev\Tests\DeprecationTest->myDeprecatedMethod is deprecated.',
            'My message.',
            'Called from SilverStripe\Dev\Tests\DeprecationTest->testWithNoReplacementTrueCallUserFunc.'
        ]);
        $this->expectDeprecation();
        $this->expectDeprecationMessage($message);
        Deprecation::enable(true);
        $ret = Deprecation::withNoReplacement(function () {
            return call_user_func([$this, 'myDeprecatedMethod']);
        });
        $this->assertSame('abc', $ret);
        Deprecation::outputNotices();
    }

    public function testClassWithNoReplacement()
    {
        $message = implode(' ', [
            'SilverStripe\Dev\Tests\DeprecationTest\DeprecationTestObject is deprecated.',
            'Some class message.',
            'Called from SilverStripe\Dev\Tests\DeprecationTest->testClassWithNoReplacement.'
        ]);
        $this->expectDeprecation();
        $this->expectDeprecationMessage($message);
        Deprecation::enable(true);
        // using this syntax because my IDE was complaining about DeprecationTestObject not existing
        // when trying to use `new DeprecationTestObject();`
        $class = DeprecationTestObject::class;
        new $class();
        Deprecation::outputNotices();
    }

    public function testClassWithInjectorWithNoReplacement()
    {
        $message = implode(' ', [
            'SilverStripe\Dev\Tests\DeprecationTest\DeprecationTestObject is deprecated.',
            'Some class message.',
            'Called from SilverStripe\Dev\Tests\DeprecationTest->testClassWithInjectorWithNoReplacement.'
        ]);
        $this->expectDeprecation();
        $this->expectDeprecationMessage($message);
        Deprecation::enable(true);
        Injector::inst()->get(DeprecationTestObject::class);
        Deprecation::outputNotices();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDisabled()
    {
        if ($this->noticesWereEnabled) {
            $this->markTestSkipped('Notices are enabled for this project outside of this unit test');
        }
        // test that no error error is raised because by default Deprecation is disabled
        $this->myDeprecatedMethod();
        Deprecation::outputNotices();
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
        Deprecation::outputNotices();
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
        Deprecation::outputNotices();
    }

    public function testConfigGetThird()
    {
        $message = 'Config SilverStripe\Dev\Tests\DeprecationTest\DeprecationTestObject.third_config is deprecated.';
        $this->expectDeprecation();
        $this->expectDeprecationMessage($message);
        Deprecation::enable();
        Config::inst()->get(DeprecationTestObject::class, 'third_config');
        Deprecation::outputNotices();
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
        Deprecation::outputNotices();
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
        Deprecation::outputNotices();
    }
}
