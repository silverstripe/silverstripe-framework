<?php

namespace SilverStripe\Forms\Tests;

use InvalidArgumentException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\Tip;

class TipTest extends SapphireTest
{
    /**
     * Ensure the correct defaults are output in the schema
     */
    public function testGeneratesAccurateDefaultSchema()
    {
        $tip = new Tip('message');

        $schema = $tip->getTipSchema();

        $this->assertEquals(
            [
                'content' => 'message',
                'icon' => 'lamp',
                'importance' => 'normal',
            ],
            $schema
        );
    }

    /**
     * Ensure custom settings are output in the schema
     */
    public function testGeneratesAccurateCustomSchema()
    {
        $tip = new Tip(
            'message',
            Tip::IMPORTANCE_LEVELS['HIGH'],
            'page'
        );

        $schema = $tip->getTipSchema();

        $this->assertEquals(
            [
                'content' => 'message',
                'icon' => 'page',
                'importance' => 'high',
            ],
            $schema
        );
    }

    /**
     * Ensure passing an invalid importance level to the constructor fails
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Provided importance level must be defined in Tip::IMPORTANCE_LEVELS
     */
    public function testInvalidImportanceLevelInConstructorCausesException()
    {
        $tip = new Tip('message', 'arbitrary-importance');
    }

    /**
     * Ensure setting an invalid importance level fails
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Provided importance level must be defined in Tip::IMPORTANCE_LEVELS
     */
    public function testInvalidImportanceLevelInSetterCausesException()
    {
        $tip = new Tip('message');

        $tip->setImportanceLevel('arbitrary-importance');
    }
}
