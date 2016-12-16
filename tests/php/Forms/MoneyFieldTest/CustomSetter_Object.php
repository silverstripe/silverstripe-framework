<?php

namespace SilverStripe\Forms\Tests\MoneyFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Customised class, implementing custom getter and setter methods for
 * MyMoney.
 */
class CustomSetter_Object extends DataObject implements TestOnly
{

    private static $table_name = 'MoneyFieldTest_CustomSetter_Object';

    private static $db = array(
        'MyMoney' => 'Money',
    );

    public function getCustomMoney()
    {
        return $this->MyMoney->getValue();
    }

    public function setCustomMoney($value)
    {

        $newAmount = $value->getAmount() * 2;
        $this->MyMoney->setAmount($newAmount);

        $newAmount = $value->getAmount() * 2;
        $this->MyMoney->setCurrency($value->getCurrency());
    }
}
