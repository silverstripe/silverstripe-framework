<?php

namespace SilverStripe\View\Tests\SSViewerTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'SSViewerTest_Object';

    public $number = null;

    private static $casting = array(
        'Link' => 'Text',
    );


    public function __construct($number = null)
    {
        parent::__construct();
        $this->number = $number;
    }

    public function Number()
    {
        return $this->number;
    }

    public function absoluteBaseURL()
    {
        return "testLocalFunctionPriorityCalled";
    }

    public function lotsOfArguments11($a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k)
    {
        return $a . $b . $c . $d . $e . $f . $g . $h . $i . $j . $k;
    }

    public function Link()
    {
        return 'some/url.html';
    }
}
