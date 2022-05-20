<?php

namespace SilverStripe\Core\Tests\ObjectTest;

class ExtendTest5 extends ExtendTest4
{
    public function extendableMethod($argument = null)
    {
        return "ExtendTest5($argument)";
    }
}
