<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\DevelopmentAdmin;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Control\Director;
use Exception;
use SilverStripe\Dev\Tests\DevAdminControllerTest\Controller1;

/**
 * Note: the running of this test is handled by the thing it's testing (DevelopmentAdmin controller).
 */
class DevAdminControllerTest extends FunctionalTest
{

    protected function setUp()
    {
        parent::setUp();

        DevelopmentAdmin::config()->update(
            'registered_controllers',
            array(
            'x1' => array(
                'controller' => Controller1::class,
                'links' => array(
                    'x1' => 'x1 link description',
                    'x1/y1' => 'x1/y1 link description'
                )
            ),
            'x2' => array(
                'controller' => 'DevAdminControllerTest_Controller2', // intentionally not a class that exists
                'links' => array(
                    'x2' => 'x2 link description'
                )
            )
            )
        );
    }


    public function testGoodRegisteredControllerOutput()
    {
        // Check for the controller running from the registered url above
        // (we use contains rather than equals because sometimes you get a warning)
        $this->assertContains(Controller1::OK_MSG, $this->getCapture('/dev/x1'));
        $this->assertContains(Controller1::OK_MSG, $this->getCapture('/dev/x1/y1'));
    }

    public function testGoodRegisteredControllerStatus()
    {
        // Check response code is 200/OK
        $this->assertEquals(false, $this->getAndCheckForError('/dev/x1'));
        $this->assertEquals(false, $this->getAndCheckForError('/dev/x1/y1'));

        // Check response code is 500/ some sort of error
        $this->assertEquals(true, $this->getAndCheckForError('/dev/x2'));
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
