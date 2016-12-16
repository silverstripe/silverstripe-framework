<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;

class HTMLFragments implements GridField_HTMLProvider, TestOnly
{
    protected $fragments;

    public function __construct($fragments)
    {
        $this->fragments = $fragments;
    }

    public function getHTMLFragments($gridField)
    {
        return $this->fragments;
    }
}
