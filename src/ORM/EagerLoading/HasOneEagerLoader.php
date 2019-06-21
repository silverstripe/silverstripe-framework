<?php

namespace SilverStripe\ORM\EagerLoading;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\QueryCache\CachedDataQueryExecutor;
use SilverStripe\ORM\QueryCache\DataQueryStoreInterface;
use InvalidArgumentException;

class HasOneEagerLoader implements RelationEagerLoaderInterface
{
    /**
     * @param DataList $list
     * @param string $relation
     * @param DataQueryStoreInterface $store
     * @return DataList
     */
    public function eagerLoadRelation(DataList $list, string $relation, DataQueryStoreInterface $store): DataList
    {
        if (!$list->getDataQueryExecutor() instanceof DataQueryStoreInterface) {
            throw new InvalidArgumentException(sprintf(
                '%s must be used with a DataList that uses %s',
                __CLASS__,
                DataQueryStoreInterface::class
            ));
        }
        $parentClass = $list->dataClass();
        $schema = DataObject::getSchema();

        $relationClass = $schema->hasOneComponent($parentClass, $relation);
        $relationField = $relation . 'ID';
        $idMap = $list->columnUnique($relationField);
        if (empty($idMap)) {
            // This ensures that we get back an empty datalist
            $idMap = ['-1'];
        }
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