<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

/**
 * Class WoodenRoof
 *
 * @property int $WoodType
 * @method House House()
 * @package SilverStripe\ORM\Tests\DataObjectTest
 */
class WoodenRoof extends Roof
{
    /**
     * @var string
     */
    private static $table_name = 'DataObjectTest_WoodenRoof';

    /**
     * @var array
     */
    private static $db = [
        'WoodType' => 'Int',
    ];

    /**
     * @var array
     */
    private static $belongs_to = [
        'House' => House::class.'.WoodenRoof',
    ];
}
