<?php

namespace SilverStripe\Dev\Tests\Install;

use SilverStripe\Dev\Install\InstallRequirements;
use SilverStripe\Dev\SapphireTest;

class InstallRequirementsTest extends SapphireTest
{
    public function testIIS()
    {
        $requirements = new InstallRequirements();
        $_SERVER['SERVER_SIGNATURE'] = 'Microsoft-IIS/10.0';

        // Test server
        $this->assertEquals('Microsoft-IIS/10.0', $requirements->findWebserver());

        // True conditions
        $this->assertTrue($requirements->isIIS());
        $this->assertTrue($requirements->isIIS(10));
        $this->assertTrue($requirements->isIIS('10.0'));
        $this->assertTrue($requirements->isIIS(9));

        // Negative - Based on number
        $this->assertFalse($requirements->isIIS(11));
        $_SERVER['SERVER_SIGNATURE'] = 'Microsoft-IIS/6.0';
        $this->assertFalse($requirements->isIIS());
        $_SERVER['SERVER_SIGNATURE'] = 'Microsoft-IIS/6.5';
        $this->assertFalse($requirements->isIIS());

        // Negative - Based on string
        $_SERVER['SERVER_SOFTWARE'] = 'lighttpd/1.4.33';
        $this->assertFalse($requirements->isIIS());
        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.25 (Unix) PHP/5.6.30 LibreSSL/2.2.7';
        $this->assertFalse($requirements->isIIS());
    }

    public function testApache()
    {
        $requirements = new InstallRequirements();
        $_SERVER['SERVER_SIGNATURE'] = '';
        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.25 (Unix) PHP/5.6.30 LibreSSL/2.2.7';

        // Test server
        $this->assertEquals('Apache/2.4.25 (Unix) PHP/5.6.30 LibreSSL/2.2.7', $requirements->findWebserver());

        // True conditions
        $this->assertTrue($requirements->isApache());

        // False conditions
        $_SERVER['SERVER_SOFTWARE'] = 'lighttpd/1.4.33';
        $this->assertFalse($requirements->isApache());
    }
}
