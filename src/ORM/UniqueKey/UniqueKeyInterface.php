<?php

namespace SilverStripe\ORM\UniqueKey;

use SilverStripe\ORM\DataObject;

interface UniqueKeyInterface
{
    /**
     * Generate a unique key for data object
     *
     * @param DataObject $object
     * @param array $extraKeys
     * @return string
     */
    public function generateKey(DataObject $object, array $extraKeys = []): string;
}
