<?php

namespace SilverStripe\View\Tests\SSTemplateEngineCacheBlockTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

class VersionedModel extends DataObject implements TestOnly
{
    private static $table_name = 'SSTemplateEngineCacheBlockTest_VersionedModel';

    protected $entropy = 'default';

    private static $extensions = [
        Versioned::class
    ];

    public function setEntropy($entropy)
    {
        $this->entropy = $entropy;
    }

    public function Inspect()
    {
        return $this->entropy . ' ' . Versioned::get_reading_mode();
    }
}
