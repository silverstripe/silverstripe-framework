<?php

namespace SilverStripe\ORM\Tests\Search\SearchContextFormTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

class TestController extends Controller implements TestOnly
{
    private static $url_segment = 'my-path';
}
