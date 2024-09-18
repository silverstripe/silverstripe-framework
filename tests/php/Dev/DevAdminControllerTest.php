<?php

namespace SilverStripe\Dev\Tests;

use Exception;
use ReflectionMethod;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Dev\DevelopmentAdmin;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Dev\Tests\DevAdminControllerTest\Controller1;
use SilverStripe\Dev\Tests\DevAdminControllerTest\ControllerWithPermissions;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Note: the running of this test is handled by the thing it's testing (DevelopmentAdmin controller).
 */
class DevAdminControllerTest extends FunctionalTest
{

    protected function setUp(): void
    {
        parent::setUp();

        DevelopmentAdmin::config()->merge(
            'registered_controllers',
            [
                'x1' => [
                    'controller' => Controller1::class,
                    'links' => [
                        'x1' => 'x1 link description',
                        'x1/y1' => 'x1/y1 link description'
                    ]
                ],
                'x2' => [
                    'controller' => 'DevAdminControllerTest_Controller2', // intentionally not a class that exists
                    'links' => [
                        'x2' => 'x2 link description'
                    ]
                ],
                'x3' => [
                    'controller' => ControllerWithPermissions::class,
                    'links' => [
                        'x3' => 'x3 link description'
                    ]
                ],
            ]
        );
    }

    public function testGoodRegisteredControllerOutput()
    {
        // Check for the controller running from the registered url above
        // (we use contains rather than equals because sometimes you get a warning)
        $this->assertStringContainsString(Controller1::OK_MSG, $this->getCapture('/dev/x1'));
        $this->assertStringContainsString(Controller1::OK_MSG, $this->getCapture('/dev/x1/y1'));
    }

    public function testGoodRegisteredControllerStatus()
    {
        // Check response code is 200/OK
        $this->assertEquals(false, $this->getAndCheckForError('/dev/x1'));
        $this->assertEquals(false, $this->getAndCheckForError('/dev/x1/y1'));

        // Check response code is 500/ some sort of error
        $this->assertEquals(true, $this->getAndCheckForError('/dev/x2'));
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
            $method = new ReflectionMethod($controller, 'getLinks');
            $method->setAccessible(true);
            $links = $method->invoke($controller);

            foreach ($present as $expected) {
                $this->assertArrayHasKey($expected, $links, sprintf('Expected link %s not found in %s', $expected, json_encode($links)));
            }

            foreach ($absent as $unexpected) {
                $this->assertArrayNotHasKey($unexpected, $links, sprintf('Unexpected link %s found in %s', $unexpected, json_encode($links)));
            }
        } finally {
            $kernel->setEnvironment($env);
        }
    }

    public static function getLinksPermissionsProvider() : array
    {
        return [
            ['ADMIN', ['x1', 'x1/y1', 'x3'], ['x2']],
            ['ALL_DEV_ADMIN', ['x1', 'x1/y1', 'x3'], ['x2']],
            ['DEV_ADMIN_TEST_PERMISSION', ['x3'], ['x1', 'x1/y1', 'x2']],
            ['NOTHING', [], ['x1', 'x1/y1', 'x2', 'x3']],
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
