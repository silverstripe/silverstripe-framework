<?php

namespace SilverStripe\View\Tests\SSViewerCacheBlockTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

class VersionedModel extends DataObject implements TestOnly
{
    private static $table_name = 'SSViewerCacheBlockTest_VersionedModel';

    protected $entropy = 'default';

    private static $extensions = array(
        Versioned::class
    );

    public function setEntropy($entropy)
    {
        $this->entropy = $entropy;
    }

    public function Inspect()
    {
        return $this->entropy . ' ' . Versioned::get_reading_mode();
    }
}
