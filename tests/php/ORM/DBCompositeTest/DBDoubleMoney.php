<?php

namespace SilverStripe\ORM\Tests\DBCompositeTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\FieldType\DBMoney;

class DBDoubleMoney extends DBMoney implements TestOnly
{
    public function writeToManipulation(&$manipulation)
    {
        // Duplicate the amount before writing
        $this->setAmount($this->getAmount() * 2);

        parent::writeToManipulation($manipulation);
    }
}
