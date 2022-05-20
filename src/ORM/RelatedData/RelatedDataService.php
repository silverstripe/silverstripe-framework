<?php

namespace SilverStripe\ORM\RelatedData;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\SS_List;

/**
 * Interface used to find all other DataObject instances that are related to a DataObject instance
 * in the database
 *
 * @internal
 */
interface RelatedDataService
{

    /**
     * Find all DataObject instances that have a linked relationship with $record
     *
     * @param DataObject $record
     * @param string[] $excludedClasses
     * @return SS_List
     */
    public function findAll(DataObject $record, array $excludedClasses = []): SS_List;
}
