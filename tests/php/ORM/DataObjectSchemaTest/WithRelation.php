<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\DataObjectSchemaTest;

class WithRelation extends NoFields
{
    private static $table_name = 'DataObjectSchemaTest_WithRelation';

    private static $has_one = array(
        'Relation' => HasFields::Class
    );
}
