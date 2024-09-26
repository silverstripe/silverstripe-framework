<?php

namespace SilverStripe\View\Tests\SSTemplateEngineTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'SSTemplateEngineTest_Object';

    public $number = null;

    private static $casting = [
        'Link' => 'Text',
    ];


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

    public function getMyProperty(mixed $someArg = null): string
    {
        if ($someArg) {
            return "Was passed in: $someArg";
        }
        return 'Nothing passed in';
    }
}
