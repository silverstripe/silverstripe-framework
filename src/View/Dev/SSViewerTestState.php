<?php

namespace SilverStripe\View\Dev;

use SilverStripe\Control\ContentNegotiator;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\State\TestState;
use SilverStripe\View\SSViewer;

class SSViewerTestState implements TestState
{
    public function setUp(SapphireTest $test)
    {
        SSViewer::set_themes(null);
        SSViewer::setRewriteHashLinksDefault(null);
        ContentNegotiator::setEnabled(null);
    }

    public function tearDown(SapphireTest $test)
    {
    }

    public function setUpOnce($class)
    {
    }

    public function tearDownOnce($class)
    {
    }
}
