<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\BuildTask;

class BuildTaskTest extends SapphireTest
{
    /**
     * Test that the default `$enabled` property is used when the new `is_enabled` config is not used
     * Test that the `is_enabled` config overrides `$enabled` property
     *
     * This test should be removed in CMS 6 as the default $enabled property is now deprecated
     */
    public function testIsEnabled(): void
    {
        // enabledTask
        $enabledTask = new class extends BuildTask
        {
            protected $enabled = true;
            public function run($request)
            {
                // noop
            }
        };
        $this->assertTrue($enabledTask->isEnabled());
        $enabledTask->config()->set('is_enabled', false);
        $this->assertFalse($enabledTask->isEnabled());
        // disabledTask
        $disabledTask = new class extends BuildTask
        {
            protected $enabled = false;
            public function run($request)
            {
                // noop
            }
        };
        $this->assertFalse($disabledTask->isEnabled());
        $disabledTask->config()->set('is_enabled', true);
        $this->assertTrue($disabledTask->isEnabled());
    }
}
