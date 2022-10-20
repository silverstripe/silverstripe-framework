<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Tests\DeprecationTest\TestDeprecation;

class DeprecationTest extends SapphireTest
{
    protected function tearDown(): void
    {
        Deprecation::$notice_level = E_USER_DEPRECATED;
        Deprecation::disable();
        parent::tearDown();
    }

    public function testNotice()
    {
        $message = implode(' ', [
            'SilverStripe\Dev\Tests\DeprecationTest->testNotice is deprecated.',
            'My message.',
            'Called from PHPUnit\Framework\TestCase->runTest.'
        ]);
        $this->expectError();
        $this->expectErrorMessage($message);
        Deprecation::$notice_level = E_USER_NOTICE;
        Deprecation::enable();
        Deprecation::notice('1.2.3', 'My message');
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDisabled()
    {
        Deprecation::$notice_level = E_USER_NOTICE;
        // test that no error error is raised because by default Deprecation is disabled
        Deprecation::notice('4.5.6', 'My message');
    }
}
