<?php declare(strict_types = 1);

namespace TestNamespace\Testing;

use TestNamespace\{Test1, Test2, Test3};

class MyTest extends Test1 implements Test2
{

    public function MyMethod()
    {
        //We shouldn't see anything in here
        $var = 1;
        $var += 1;
        return $var;
    }

    public function MyNestedMethod()
    {
        $var = 1;
        for ($i = 0; $i < 5; ++$i) {
            if ($i % 2) {
                $var += $i;
            }
        }
    }

}
