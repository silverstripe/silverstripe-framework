<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

class ExtendedTeamComment extends TeamComment
{
    private static $table_name = 'DataObjectTest_ExtendedTeamComment';

    private static $db = array(
        'Comment' => 'HTMLText'
    );
}
