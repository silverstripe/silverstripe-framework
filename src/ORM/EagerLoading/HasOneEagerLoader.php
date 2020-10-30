<?php

namespace SilverStripe\ORM\EagerLoading;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\QueryCache\CachedDataQueryExecutor;
use SilverStripe\ORM\QueryCache\DataQueryStoreInterface;
use InvalidArgumentException;

class HasOneEagerLoader implements RelationEagerLoaderInterface
{
    /**
     * @param DataQuery $query
     * @param string $relation
     * @param DataQueryStoreInterface $store
     * @return DataQuery
     */
    public function eagerLoadRelation(DataQuery $query, string $relation, DataQueryStoreInterface $store): DataQuery
    {
        $parentClass = $query->dataClass();
        $schema = DataObject::getSchema();

        $relationClass = $schema->hasOneComponent($parentClass, $relation);
        $relationField = $relation . 'ID';
        $idMap = array_unique($query->column($relationField));
        if (empty($idMap)) {
            // This ensures that we get back an empty datalist
            $idMap = ['-1'];
        }
        $relatedRecords = DataList::create($relationClass)->byIDs($idMap);
        $lookup = $relatedRecords->map('ID', 'Me')->toArray();
        foreach ($query->execute() as $item) {
            $foreignID = $item[$relationField];
            $query = DataList::create($relationClass)->filter(['ID' => $foreignID]);
            if (isset($lookup[$foreignID])) {
                $record = $lookup[$foreignID];
                $store->persist($query->dataQuery(), $record, CachedDataQueryExecutor::FIRST_ROW);
            } elseif ($foreignID) {
                // broken relation
                $store->persist($query->dataQuery(), $relationClass::singleton(), CachedDataQueryExecutor::FIRST_ROW);
            }
        }

        return $relatedRecords->dataQuery();
    }
}
