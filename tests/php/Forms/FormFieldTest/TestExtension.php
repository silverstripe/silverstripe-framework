<?php

namespace SilverStripe\Forms\Tests\FormFieldTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class TestExtension extends Extension implements TestOnly
{

    public function updateAttributes(&$attrs)
    {
        $attrs['extended'] = true;
    }
}
