<?php

namespace SilverStripe\View\Tests\ViewableDataTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\ViewableData;

class Caster extends ViewableData implements TestOnly
{

    public function forTemplate(): string
    {
        return 'casted';
    }

    public function setValue()
    {
    }
}
