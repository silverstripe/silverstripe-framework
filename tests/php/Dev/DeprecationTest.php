<?php

namespace SilverStripe\Dev\Tests;

use PHPUnit\Framework\Error\Deprecated;
use ReflectionMethod;
use ReflectionProperty;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Tests\DeprecationTest\DeprecationTestObject;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;

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
        // - Filter out errors for deprecated class properties unrelated to this unit test
        // - Allow the use of expectDeprecation(), which doesn't work with E_USER_DEPRECATION by default
        //   https://github.com/laminas/laminas-di/pull/30#issuecomment-927585210
        parent::setup();
        $this->noticesWereEnabled = Deprecation::isEnabled();
        $this->oldHandler = set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
            if ($errno === E_USER_DEPRECATED) {
                if (str_contains($errstr, 'SilverStripe\\Dev\\Tests\\DeprecationTest')) {
                    throw new Deprecated($errstr, $errno, '', 1);
                } else {
                    // Suppress any E_USER_DEPRECATED unrelated to this unit-test
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
        if ($this->noticesWereEnabled) {
            Deprecation::enable();
        } else {
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

    public function testwithSuppressedNoticeDefault()
    {
        Deprecation::enable();
        $ret = Deprecation::withSuppressedNotice(function () {
            return $this->myDeprecatedMethod();
        });
        $this->assertSame('abc', $ret);
        Deprecation::outputNotices();
    }

    public function testwithSuppressedNoticeTrue()
    {
        $message = implode(' ', [
            'SilverStripe\Dev\Tests\DeprecationTest->myDeprecatedMethod is deprecated.',
            'My message.',
            'Called from SilverStripe\Dev\Tests\DeprecationTest->testwithSuppressedNoticeTrue.'
        ]);
        $this->expectDeprecation();
        $this->expectDeprecationMessage($message);
        Deprecation::enable(true);
        $ret = Deprecation::withSuppressedNotice(function () {
            return $this->myDeprecatedMethod();
        });
        $this->assertSame('abc', $ret);
        Deprecation::outputNotices();
    }

    public function testwithSuppressedNoticeTrueCallUserFunc()
    {
        $message = implode(' ', [
            'SilverStripe\Dev\Tests\DeprecationTest->myDeprecatedMethod is deprecated.',
            'My message.',
            'Called from SilverStripe\Dev\Tests\DeprecationTest->testwithSuppressedNoticeTrueCallUserFunc.'
        ]);
        $this->expectDeprecation();
        $this->expectDeprecationMessage($message);
        Deprecation::enable(true);
        $ret = Deprecation::withSuppressedNotice(function () {
            return call_user_func([$this, 'myDeprecatedMethod']);
        });
        $this->assertSame('abc', $ret);
        Deprecation::outputNotices();
    }

    public function testNoticewithSuppressedNoticeTrue()
    {
        $message = implode(' ', [
            'SilverStripe\Dev\Tests\DeprecationTest->testNoticewithSuppressedNoticeTrue is deprecated.',
            'My message.',
            'Called from PHPUnit\Framework\TestCase->runTest.'
        ]);
        $this->expectDeprecation();
        $this->expectDeprecationMessage($message);
        Deprecation::enable(true);
        Deprecation::withSuppressedNotice(function () {
            Deprecation::notice('123', 'My message.');
        });
        Deprecation::outputNotices();
    }

    public function testClasswithSuppressedNotice()
    {
        $message = implode(' ', [
            'SilverStripe\Dev\Tests\DeprecationTest\DeprecationTestObject is deprecated.',
            'Some class message.',
            'Called from SilverStripe\Dev\Tests\DeprecationTest->testClasswithSuppressedNotice.'
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

    public function testClassWithInjectorwithSuppressedNotice()
    {
        $message = implode(' ', [
            'SilverStripe\Dev\Tests\DeprecationTest\DeprecationTestObject is deprecated.',
            'Some class message.',
            'Called from SilverStripe\Dev\Tests\DeprecationTest->testClassWithInjectorwithSuppressedNotice.'
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

    public function provideConfigVsEnv()
    {
        return [
            // env var not set - config setting is respected
            [
                // false is returned when the env isn't set, so this simulates simply not having
                // set the variable in the first place
                'envVal' => 'notset',
                'configVal' => false,
                'expected' => false,
            ],
            [
                'envVal' => 'notset',
                'configVal' => true,
                'expected' => true,
            ],
            // env var is set and truthy, config setting is ignored
            [
                'envVal' => true,
                'configVal' => false,
                'expected' => true,
            ],
            [
                'envVal' => true,
                'configVal' => true,
                'expected' => true,
            ],
            // env var is set and falsy, config setting is ignored
            [
                'envVal' => false,
                'configVal' => false,
                'expected' => false,
            ],
            [
                'envVal' => false,
                'configVal' => true,
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider provideConfigVsEnv
     */
    public function testEnabledConfigVsEnv($envVal, bool $configVal, bool $expected)
    {
        $this->runConfigVsEnvTest('SS_DEPRECATION_ENABLED', $envVal, $configVal, $expected);
    }

    /**
     * @dataProvider provideConfigVsEnv
     */
    public function testShowHttpConfigVsEnv($envVal, bool $configVal, bool $expected)
    {
        $this->runConfigVsEnvTest('SS_DEPRECATION_SHOW_HTTP', $envVal, $configVal, $expected);
    }

    /**
     * @dataProvider provideConfigVsEnv
     */
    public function testShowCliConfigVsEnv($envVal, bool $configVal, bool $expected)
    {
        $this->runConfigVsEnvTest('SS_DEPRECATION_SHOW_CLI', $envVal, $configVal, $expected);
    }

    private function runConfigVsEnvTest(string $varName, $envVal, bool $configVal, bool $expected)
    {
        $oldVars = Environment::getVariables();

        if ($envVal === 'notset') {
            if (Environment::hasEnv($varName)) {
                $this->markTestSkipped("$varName is set, so we can't test behaviour when it's not");
                return;
            }
        } else {
            Environment::setEnv($varName, $envVal);
        }

        switch ($varName) {
            case 'SS_DEPRECATION_ENABLED':
                if ($configVal) {
                    Deprecation::enable();
                } else {
                    Deprecation::disable();
                }
                $result = Deprecation::isEnabled();
                break;
            case 'SS_DEPRECATION_SHOW_HTTP':
                $oldShouldShow = Deprecation::shouldShowForHttp();
                Deprecation::setShouldShowForHttp($configVal);
                $result = Deprecation::shouldShowForHttp();
                Deprecation::setShouldShowForHttp($oldShouldShow);
                break;
            case 'SS_DEPRECATION_SHOW_CLI':
                $oldShouldShow = Deprecation::shouldShowForCli();
                Deprecation::setShouldShowForCli($configVal);
                $result = Deprecation::shouldShowForCli();
                Deprecation::setShouldShowForCli($oldShouldShow);
                break;
        }

        Environment::setVariables($oldVars);

        $this->assertSame($expected, $result);
    }

    public function provideVarAsBoolean()
    {
        return [
            [
                'rawValue' => true,
                'expected' => true,
            ],
            [
                'rawValue' => 'true',
                'expected' => true,
            ],
            [
                'rawValue' => 1,
                'expected' => true,
            ],
            [
                'rawValue' => '1',
                'expected' => true,
            ],
            [
                'rawValue' => 'on',
                'expected' => true,
            ],
            [
                'rawValue' => false,
                'expected' => false,
            ],
            [
                'rawValue' => 'false',
                'expected' => false,
            ],
            [
                'rawValue' => 0,
                'expected' => false,
            ],
            [
                'rawValue' => '0',
                'expected' => false,
            ],
            [
                'rawValue' => 'off',
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider provideVarAsBoolean
     */
    public function testVarAsBoolean($rawValue, bool $expected)
    {
        $reflectionVarAsBoolean = new ReflectionMethod(Deprecation::class, 'varAsBoolean');
        $reflectionVarAsBoolean->setAccessible(true);

        $this->assertSame($expected, $reflectionVarAsBoolean->invoke(null, $rawValue));
    }

    public function provideIsEnabled()
    {
        return [
            'dev, explicitly enabled' => [
                'envMode' => 'dev',
                'envEnabled' => true,
                'staticEnabled' => true,
                'expected' => true,
            ],
            'dev, explicitly enabled override static' => [
                'envMode' => 'dev',
                'envEnabled' => true,
                'staticEnabled' => false,
                'expected' => true,
            ],
            'dev, explicitly disabled override static' => [
                'envMode' => 'dev',
                'envEnabled' => false,
                'staticEnabled' => true,
                'expected' => false,
            ],
            'dev, explicitly disabled' => [
                'envMode' => 'dev',
                'envEnabled' => false,
                'staticEnabled' => false,
                'expected' => false,
            ],
            'dev, statically disabled' => [
                'envMode' => 'dev',
                'envEnabled' => null,
                'staticEnabled' => true,
                'expected' => true,
            ],
            'dev, statically disabled' => [
                'envMode' => 'dev',
                'envEnabled' => null,
                'staticEnabled' => false,
                'expected' => false,
            ],
            'live, explicitly enabled' => [
                'envMode' => 'live',
                'envEnabled' => true,
                'staticEnabled' => true,
                'expected' => false,
            ],
            'live, explicitly disabled' => [
                'envMode' => 'live',
                'envEnabled' => false,
                'staticEnabled' => false,
                'expected' => false,
            ],
            'live, statically disabled' => [
                'envMode' => 'live',
                'envEnabled' => null,
                'staticEnabled' => true,
                'expected' => false,
            ],
            'live, statically disabled' => [
                'envMode' => 'live',
                'envEnabled' => null,
                'staticEnabled' => false,
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider provideIsEnabled
     */
    public function testIsEnabled(string $envMode, ?bool $envEnabled, bool $staticEnabled, bool $expected)
    {
        /** @var Kernel $kernel */
        $kernel = Injector::inst()->get(Kernel::class);
        $origMode = $kernel->getEnvironment();
        $origEnvEnabled = Environment::getEnv('SS_DEPRECATION_ENABLED');
        $reflectionEnabled = new ReflectionProperty(Deprecation::class, 'currentlyEnabled');
        $reflectionEnabled->setAccessible(true);
        $origStaticEnabled = $reflectionEnabled->getValue();

        try {
            $kernel->setEnvironment($envMode);
            Environment::setEnv('SS_DEPRECATION_ENABLED', $envEnabled);
            $this->setEnabledViaStatic($staticEnabled);
            $this->assertSame($expected, Deprecation::isEnabled());
        } finally {
            $kernel->setEnvironment($origMode);
            Environment::setEnv('SS_DEPRECATION_ENABLED', $origEnvEnabled);
            $this->setEnabledViaStatic($origStaticEnabled);
        }
    }

    private function setEnabledViaStatic(bool $enabled): void
    {
        if ($enabled) {
            Deprecation::enable();
        } else {
            Deprecation::disable();
        }
    }
}
