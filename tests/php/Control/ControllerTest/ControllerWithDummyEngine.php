<?php

namespace SilverStripe\Control\Tests\ControllerTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\View\TemplateEngine;

class ControllerWithDummyEngine extends Controller implements TestOnly
{
    protected function getTemplateEngine(): TemplateEngine
    {
        return new DummyTemplateEngine();
    }
}
