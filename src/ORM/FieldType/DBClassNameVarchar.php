<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\ORM\FieldType\DBVarchar;

/**
 * An alternative to DBClassName that stores the class name as a varchar instead of an enum
 * This will use more disk space, though will prevent issues with long dev/builds on
 * very large database tables when a ALTER TABLE queries are required to update the enum.
 *
 * Use the following config to use this class in your project:
 *
 * <code>
 * SilverStripe\ORM\DataObject:
 *   fixed_fields:
 *     ClassName: DBClassNameVarchar
 *
 * SilverStripe\ORM\FieldType\DBPolymorphicForeignKey:
 *   composite_db:
 *     Class: DBClassNameVarchar('SilverStripe\ORM\DataObject', ['index' => false])
 * </code>
 */
class DBClassNameVarchar extends DBVarchar
{
    use DBClassNameTrait;
}
