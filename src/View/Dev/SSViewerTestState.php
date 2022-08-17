<?php

namespace SilverStripe\View\Dev;

use SilverStripe\Control\ContentNegotiator;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\State\TestState;
use SilverStripe\View\SSViewer;

class SSViewerTestState implements TestState
{
    public function setUp(SapphireTest $test): void
    {
        SSViewer::set_themes(null);
        SSViewer::setRewriteHashLinksDefault(null);
        ContentNegotiator::setEnabled(null);
    }

    public function tearDown(SapphireTest $test): void
    {
    }

    public function setUpOnce(string $class): void
    {
    }

    public function tearDownOnce(string $class): void
    {
    }
}
