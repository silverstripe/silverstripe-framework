<?php

namespace SilverStripe\ORM\Tests\ManyManyListTest;

/**
 * A data object that is a subclass of the secondary side. The test will create an instance of this,
 * and ensure that the extra fields are available on the instance even though the many many is
 * defined at the parent level.
 */
class SecondarySub extends Secondary
{
    private static $table_name = 'ManyManyListTest_SecondarySub';
}
