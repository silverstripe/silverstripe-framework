<?php

namespace SilverStripe\Dev\Tests;

use Exception;
use LogicException;
use SilverStripe\Control\Director;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Dev\Command\DevCommand;
use SilverStripe\Dev\DevelopmentAdmin;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Dev\Tests\DevAdminControllerTest\Controller1;
use SilverStripe\Dev\Tests\DevAdminControllerTest\ControllerWithPermissions;
use SilverStripe\Dev\Tests\DevAdminControllerTest\TestCommand;
use SilverStripe\Dev\Tests\DevAdminControllerTest\TestHiddenController;
use PHPUnit\Framework\Attributes\DataProvider;

class DevAdminControllerTest extends FunctionalTest
{
    protected function setUp(): void
    {
        parent::setUp();

        DevelopmentAdmin::config()->merge(
            'commands',
            [
                'c1' => TestCommand::class,
            ]
        );

        DevelopmentAdmin::config()->merge(
            'controllers',
            [
                'x1' => [
                    'class' => Controller1::class,
                    'description' => 'controller1 description',
                ],
                'x3' => [
                    'class' => ControllerWithPermissions::class,
                    'description' => 'permission controller description',
                ],
                'x4' => [
                    'class' => TestHiddenController::class,
                    'skipLink' => true,
                ],
            ]
        );
    }

    public function testGoodRegisteredControllerOutput()
    {
        // Check for the controller or command running from the registered url above
        // Use string contains string because there's a lot of extra HTML markup around the output
        $this->assertStringContainsString(Controller1::OK_MSG, $this->getCapture('/dev/x1'));
        $this->assertStringContainsString(Controller1::OK_MSG . ' y1', $this->getCapture('/dev/x1/y1'));
        $this->assertStringContainsString(TestHiddenController::OK_MSG, $this->getCapture('/dev/x4'));
        $this->assertStringContainsString('<h2>This is a test command</h2>' . TestCommand::OK_MSG, $this->getCapture('/dev/c1'));
    }

    public function testGoodRegisteredControllerStatus()
    {
        // Check response code is 200/OK
        $this->assertEquals(false, $this->getAndCheckForError('/dev/x1'));
        $this->assertEquals(false, $this->getAndCheckForError('/dev/x1/y1'));
        $this->assertEquals(false, $this->getAndCheckForError('/dev/x4'));
        $this->assertEquals(false, $this->getAndCheckForError('/dev/xc1'));
    }

    #[DataProvider('getLinksPermissionsProvider')]
    public function testGetLinks(string $permission, array $present, array $absent): void
    {
        DevelopmentAdmin::config()->set('allow_all_cli', false);
        $kernel = Injector::inst()->get(Kernel::class);
        $env = $kernel->getEnvironment();
        $kernel->setEnvironment(Kernel::LIVE);
        try {
            $this->logInWithPermission($permission);
            $controller = new DevelopmentAdmin();
            $links = $controller->getLinks();

            foreach ($present as $expected) {
                $this->assertArrayHasKey('dev/' . $expected, $links, sprintf('Expected link %s not found in %s', 'dev/' . $expected, json_encode($links)));
            }

            foreach ($absent as $unexpected) {
                $this->assertArrayNotHasKey('dev/' . $unexpected, $links, sprintf('Unexpected link %s found in %s', 'dev/' . $unexpected, json_encode($links)));
            }
        } finally {
            $kernel->setEnvironment($env);
        }
    }

    public static function provideMissingClasses(): array
    {
        return [
            'missing command' => [
                'configKey' => 'commands',
                'configToMerge' => [
                    'c2' => 'DevAdminControllerTest_NonExistentCommand',
                ],
                'expectedMessage' => 'Class \'DevAdminControllerTest_NonExistentCommand\' doesn\'t exist',
            ],
            'missing controller' => [
                'configKey' => 'controllers',
                'configToMerge' => [
                    'x2' => [
                        'class' => 'DevAdminControllerTest_NonExistentController',
                        'description' => 'controller2 description',
                    ],
                ],
                'expectedMessage' => 'Class \'DevAdminControllerTest_NonExistentController\' doesn\'t exist',
            ],
            'wrong class command' => [
                'configKey' => 'commands',
                'configToMerge' => [
                    'c2' => static::class,
                ],
                'expectedMessage' => 'Class \'' . static::class . '\' must be a subclass of ' . DevCommand::class,
            ],
            'wrong class controller' => [
                'configKey' => 'controllers',
                'configToMerge' => [
                    'x2' => [
                        'class' => static::class,
                        'description' => 'controller2 description',
                    ],
                ],
                'expectedMessage' => 'Class \'' . static::class . '\' must be a subclass of ' . RequestHandler::class,
            ],
        ];
    }

    #[DataProvider('provideMissingClasses')]
    public function testMissingClasses(string $configKey, array $configToMerge, string $expectedMessage): void
    {
        DevelopmentAdmin::config()->merge($configKey, $configToMerge);
        $controller = new DevelopmentAdmin();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage($expectedMessage);
        $controller->getLinks();
    }

    public static function getLinksPermissionsProvider() : array
    {
        return [
            'admin access' => ['ADMIN', ['c1', 'x1', 'x3'], ['x4']],
            'all dev access' => ['ALL_DEV_ADMIN', ['c1', 'x1', 'x3'], ['x4']],
            'dev test access' => ['DEV_ADMIN_TEST_PERMISSION', ['x3'], ['c1', 'x1', 'x4']],
            'no access' => ['NOTHING', [], ['c1', 'x1', 'x3', 'x4']],
        ];
    }

    protected function getCapture($url)
    {
        $this->logInWithPermission('ADMIN');

        ob_start();
        $this->get($url);
        $r = ob_get_contents();
        ob_end_clean();

        return $r;
    }

    protected function getAndCheckForError($url)
    {
        $this->logInWithPermission('ADMIN');

        if (Director::is_cli()) {
            // when in CLI the admin controller throws exceptions
            ob_start();
            try {
                $this->get($url);
            } catch (Exception $e) {
                ob_end_clean();
                return true;
            }

            ob_end_clean();
            return false;
        } else {
            // when in http the admin controller sets a response header
            ob_start();
            $resp = $this->get($url);
            ob_end_clean();
            return $resp->isError();
        }
    }
}
