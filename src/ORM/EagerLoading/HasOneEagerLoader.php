<?php

namespace SilverStripe\ORM\EagerLoading;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\QueryCache\CachedDataQueryExecutor;
use SilverStripe\ORM\QueryCache\DataQueryStoreInterface;

class HasOneEagerLoader implements RelationEagerLoaderInterface
{
    /**
     * @param DataList $list
     * @param string $relation
     * @param DataQueryStoreInterface $store
     * @return DataList
     */
    public function eagerLoadRelation(DataList $list, $relation, DataQueryStoreInterface $store)
    {
        $parentClass = $list->dataClass();
        $schema = DataObject::getSchema();

        $relationClass = $schema->hasOneComponent($parentClass, $relation);
        $relationField = $relation . 'ID';
        $idMap = $list->map($relationField, $relationField)->toArray();
        $relatedRecords = DataList::create($relationClass)->byIDs($idMap);
        foreach ($relatedRecords as $record) {
            $query = DataList::create($relationClass)->filter(['ID' => $record->ID]);

            $store->persist($query->dataQuery(), $record, CachedDataQueryExecutor::FIRST_ROW);
        }

        return $relatedRecords;
    }
}