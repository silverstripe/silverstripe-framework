<?php

namespace SilverStripe\UniqueKey;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;

class Service
{
    use Injectable;

    /**
     * Generate a unique key for data object
     *
     * recommended use:
     * - when you need unique key for caching purposes
     * - when you need unique id on the front end (for example JavaScript needs to target specific element)
     *
     * @param DataObject $object
     * @param array $extraKeys
     * @return string
     */
    public function generateKey(DataObject $object, array $extraKeys = []): string
    {
        if (!$object->isInDB()) {
            return '';
        }

        // extract class name (remove namespaces)
        $classSegments = explode('\\', $object->ClassName);

        if (count($classSegments) === 0) {
            return '';
        }

        $class = array_pop($classSegments);
        $extraKeys = json_encode($extraKeys);

        $hash = md5(sprintf('%s-%s-%d', $extraKeys, $object->ClassName, $object->ID));

        // note: class name and id are added just for readability as the hash already contains all parts
        // needed to create a unique key
        return sprintf('ss-%s-%d-%s', $class, $object->ID, $hash);
    }
}
