<?php declare(strict_types = 1);

namespace SilverStripe\Core\Tests\ObjectTest;

class ExtendTest4 extends ExtendTest3
{
    public function extendableMethod($argument = null)
    {
        return "ExtendTest4($argument)";
    }
}
