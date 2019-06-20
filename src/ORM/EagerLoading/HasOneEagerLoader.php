<?php

namespace SilverStripe\ORM\EagerLoading;

use SilverStripe\Core\Injector\Injector;
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
        $lookup = $relatedRecords->map('ID', 'Me')->toArray();
        foreach ($list as $item) {
            $foreignID = $item->$relationField;
            $query = DataList::create($relationClass)->filter(['ID' => $foreignID]);
            if (isset($lookup[$foreignID])) {
                $record = $lookup[$foreignID];
                $store->persist($query->dataQuery(), $record, CachedDataQueryExecutor::FIRST_ROW);
            } else if($foreignID) {
                // broken relation
                $store->persist($query->dataQuery(), $relationClass::singleton(), CachedDataQueryExecutor::FIRST_ROW);
            }
        }

        return $relatedRecords;
    }
}