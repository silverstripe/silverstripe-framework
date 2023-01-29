<?php

namespace SilverStripe\Security\SudoMode\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Session;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\SudoMode\SudoModeService;
use SilverStripe\Security\SudoMode\SudoModeServiceInterface;

class SudoModeServiceTest extends SapphireTest
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var SudoModeService
     */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = new Session([]);
        $this->service = new SudoModeService();

        DBDatetime::set_mock_now('2019-03-01 12:00:00');
        SudoModeService::config()->set('lifetime_minutes', 180);
    }

    public function testCheckWithoutActivation()
    {
        $this->session->clearAll();
        $this->assertFalse($this->service->check($this->session));
    }

    public function testCheckWithLastActivationOutsideLifetimeWindow()
    {
        // 240 minutes ago
        $lastActivated = DBDatetime::now()->getTimestamp() - 240 * 60;
        $this->session->set('sudo-mode-last-activated', $lastActivated);
        $this->assertFalse($this->service->check($this->session));
    }

    public function testCheckWithLastActivationInsideLifetimeWindow()
    {
        // 25 minutes ago
        $lastActivated = DBDatetime::now()->getTimestamp() - 25 * 60;
        $this->session->set('sudo-mode-last-activated', $lastActivated);
        $this->assertTrue($this->service->check($this->session));
    }

    public function testActivateAndCheckImmediately()
    {
        $this->service->activate($this->session);
        $this->assertTrue($this->service->check($this->session));
    }

    public function testSudoModeActivatesOnLogin()
    {
        // Sometimes being logged in carries over from other tests
        $this->logOut();

        /** @var SudoModeServiceInterface $service */
        $service = Injector::inst()->get(SudoModeServiceInterface::class);
        $session = Controller::curr()->getRequest()->getSession();

        // Sudo mode should not be enabled automagically when nobody is logged in
        $this->assertFalse($service->check($session));

        // Ensure sudo mode is activated on login
        $this->logInWithPermission();
        $this->assertTrue($service->check($session));

        // Ensure sudo mode is not active after logging out
        $this->logOut();
        $this->assertFalse($service->check($session));
    }
}
