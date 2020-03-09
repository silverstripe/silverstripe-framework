<?php

namespace SilverStripe\ORM\UniqueKey;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;

/**
 * Class UniqueKeyService
 *
 * Generate a unique key for data object
 *
 * recommended use:
 * - when you need unique key for caching purposes
 * - when you need unique id on the front end (for example JavaScript needs to target specific element)
 *
 * @package SilverStripe\ORM\UniqueKey
 */
class UniqueKeyService implements UniqueKeyInterface
{
    use Injectable;

    public function generateKey(DataObject $object, array $extraKeys = []): string
    {
        if (!$object->isInDB()) {
            return '';
        }

        $class = ClassInfo::shortName($object);
        $extraKeys = json_encode($extraKeys);

        $hash = md5(sprintf('%s-%s-%d', $extraKeys, $object->ClassName, $object->ID));

        // note: class name and id are added just for readability as the hash already contains all parts
        // needed to create a unique key
        return sprintf('ss-%s-%d-%s', $class, $object->ID, $hash);
    }
}
