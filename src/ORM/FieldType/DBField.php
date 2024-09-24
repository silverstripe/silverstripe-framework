<?php

namespace SilverStripe\ORM\FieldType;

/**
 * This interface is exists basically so that $object instanceof DBField checks work
 */
interface DBField extends DBIndexable
{
}
