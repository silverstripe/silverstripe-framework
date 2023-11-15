<?php

use SilverStripe\Core\DatabaselessKernel;

$kernel = new class(BASE_PATH) extends DatabaselessKernel {
    public function getIncludeTests()
    {
        return true;
    }
};

try {
    $kernel->boot();
} catch (\Throwable) {
}
