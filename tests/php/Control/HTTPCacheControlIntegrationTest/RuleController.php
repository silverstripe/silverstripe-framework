<?php

namespace SilverStripe\Control\Tests\HTTPCacheControlIntegrationTest;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Dev\TestOnly;

class RuleController extends Controller implements TestOnly
{
    private static $url_segment = 'HTTPCacheControlIntegrationTest_RuleController';

    private static $allowed_actions = [
        'privateaction',
        'publicaction',
        'disabledaction',
    ];

    protected function init()
    {
        parent::init();
        // Prefer public by default
        HTTPCacheControlMiddleware::singleton()->publicCache();
    }

    public function privateaction()
    {
        HTTPCacheControlMiddleware::singleton()->privateCache();
        return 'private content';
    }

    public function publicaction()
    {
        HTTPCacheControlMiddleware::singleton()
            ->publicCache()
            ->setMaxAge(9000);
        return 'public content';
    }

    public function disabledaction()
    {
        HTTPCacheControlMiddleware::singleton()->disableCache();
        return 'uncached content';
    }
}
