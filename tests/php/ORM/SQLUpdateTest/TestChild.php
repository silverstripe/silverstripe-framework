<?php

namespace SilverStripe\ORM\Tests\SQLUpdateTest;

class TestChild extends TestBase
{
    private static $table_name = 'SQLUpdateChild';

    private static $db = [
        'Details' => 'Varchar(255)'
    ];
}
