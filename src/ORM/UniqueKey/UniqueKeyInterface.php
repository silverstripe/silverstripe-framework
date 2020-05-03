<?php

namespace SilverStripe\ORM\UniqueKey;

use SilverStripe\ORM\DataObject;

/**
 * Interface UniqueKeyInterface
 *
 * Useful when you want to implement your own custom service and use it instead of the default one (@see UniqueKeyService)
 * your custom service needs to implement this interface
 */
interface UniqueKeyInterface
{
    /**
     * Generate a unique key for data object
     *
     * @param DataObject $object
     * @param array $keyComponents
     * @return string
     */
    public function generateKey(DataObject $object, array $keyComponents = []): string;
}
