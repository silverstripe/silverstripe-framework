<?php declare(strict_types = 1);

namespace SilverStripe\View\Tests\ViewableDataTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\ViewableData;

class Cached extends ViewableData implements TestOnly
{
    public $Test;
}
