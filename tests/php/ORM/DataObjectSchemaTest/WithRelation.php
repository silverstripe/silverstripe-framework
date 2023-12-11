<?php

namespace SilverStripe\ORM\Tests\DataObjectSchemaTest;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;

class WithRelation extends NoFields
{
    private static $table_name = 'DataObjectSchemaTest_WithRelation';

    private static $has_one = [
        'Relation' => HasFields::class,
        'PolymorphicRelation' => DataObject::class,
        'MultiRelationalRelation' => [
            'class' => DataObject::class,
            DataObjectSchema::HAS_ONE_MULTI_RELATIONAL => true,
        ],
        'ArraySyntaxRelation' => [
            'class' => HasFields::class,
        ],
    ];
}
