<?php

namespace SilverStripe\Model\Tests\List\ArrayListTest;

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
        return ['First' => $this->First, 'Second' => $this->Second];
    }
}
