<?php

namespace SilverStripe\ORM\Tests\RelatedDataServiceTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class HubExtension extends DataExtension implements TestOnly
{
    private static $has_one = [
        'ExtHO' => Node::class
    ];

    private static $many_many = [
        // does not have belong_many_many on the other end
        'ExtMMtoNoBMM' => Node::class
    ];
}
