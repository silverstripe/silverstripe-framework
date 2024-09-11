<?php

namespace SilverStripe\Model\Tests\ModelDataTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\ModelData;

class Castable extends ModelData implements TestOnly
{
    private static $default_cast = Caster::class;

    private static $casting = [
        'alwaysCasted' => RequiresCasting::class,
        'castedUnsafeXML' => UnescapedCaster::class,
        'test' => 'Text',
        'arrayOne' => 'Text',
    ];

    public $test = 'test';

    public $uncastedZeroValue = 0;

    public function alwaysCasted()
    {
        return 'alwaysCasted';
    }

    public function arrayOne()
    {
        return ['value1', 'value2'];
    }

    public function arrayTwo()
    {
        return ['value1', 'value2'];
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

    public function forTemplate(): string
    {
        return 'castable';
    }
}
