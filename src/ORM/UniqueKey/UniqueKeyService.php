<?php

namespace SilverStripe\ORM\UniqueKey;

use Exception;
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
 */
class UniqueKeyService implements UniqueKeyInterface
{
    use Injectable;

    /**
     * @param DataObject $object
     * @param array $keyComponents key components are expected to be strings (or at least scalar values)
     * @return string
     * @throws Exception
     */
    public function generateKey(DataObject $object, array $keyComponents = []): string
    {
        $id = $object->isInDB() ? (string) $object->ID : bin2hex(random_bytes(16));
        $class = ClassInfo::shortName($object);
        $keyComponents = json_encode($keyComponents);
        $hash = md5($keyComponents . $object->ClassName . $id);

        // note: class name and id are added just for readability as the hash already contains all parts
        // needed to create a unique key
        return sprintf('%s-%s-%s', $class, $id, $hash);
    }
}
