<?php

namespace SilverStripe\View\Tests\ViewableDataTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\ViewableData;

class Castable extends ViewableData implements TestOnly
{

    private static $default_cast = Caster::class;

    private static $casting = array(
        'alwaysCasted' => RequiresCasting::class,
        'castedUnsafeXML' => UnescapedCaster::class,
        'test' => 'Text',
    );

    public $test = 'test';

    public $uncastedZeroValue = 0;

    public function alwaysCasted()
    {
        return 'alwaysCasted';
    }

    public function noCastingInformation()
    {
        return 'noCastingInformation';
    }

    public function unsafeXML()
    {
        return '<foo>';
    }

    public function castedUnsafeXML()
    {
        return $this->unsafeXML();
    }

    public function forTemplate()
    {
        return 'castable';
    }
}
