<?php

namespace SilverStripe\ORM\Tests\ArrayListTest;

class TestObject
{

    public $First;
    public $Second;

    public function __construct($first, $second)
    {
        $this->First = $first;
        $this->Second = $second;
    }

    public function toMap()
    {
        return array('First' => $this->First, 'Second' => $this->Second);
    }
}
