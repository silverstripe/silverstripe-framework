<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Class FirstTimer
 *
 * @package SilverStripe\ORM\Tests\DataObjectTest
 * @property string ChangeMeToForceWrites
 * @property integer CalledOnBeforeFirstWrite
 * @property integer CalledOnBeforeWrite
 * @property integer CalledOnAfterFirstWrite
 * @property integer CalledOnAfterWrite
 */
class FirstTimer extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectTest_FirstTimer';

    private static $db = [
        'ChangeMeToForceWrites' => 'Varchar',
        'CalledOnBeforeFirstWrite' => 'Int',
        'CalledOnBeforeWrite' => 'Int',
        'CalledOnAfterFirstWrite' => 'Int',
        'CalledOnAfterWrite' => 'Int',
    ];

    public function onBeforeFirstWrite()
    {
        $this->increment('CalledOnBeforeFirstWrite');
        parent::onBeforeFirstWrite();
    }

    public function onBeforeWrite()
    {
        $this->increment('CalledOnBeforeWrite');
        parent::onBeforeWrite();
    }

    public function onAfterFirstWrite()
    {
        $this->increment('CalledOnAfterFirstWrite');
        parent::onAfterFirstWrite();
    }

    public function onAfterWrite()
    {
        $this->increment('CalledOnAfterWrite');
        parent::onAfterWrite();
    }

    private function increment($field)
    {
        $this->setField($field, $this->getField($field) + 1);
    }
}
