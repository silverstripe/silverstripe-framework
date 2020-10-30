<?php

namespace SilverStripe\ORM\EagerLoading;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\QueryCache\DataQueryStoreInterface;
use SilverStripe\ORM\RelationList;

class HasManyEagerLoader implements RelationEagerLoaderInterface
{
    /**
     * @param DataQuery $query
     * @param string $relation
     * @param DataQueryStoreInterface $store
     * @return DataQuery
     * @throws \Exception
     */
    public function eagerLoadRelation(DataQuery $query, string $relation, DataQueryStoreInterface $store): DataQuery
    {
        $parentClass = $query->dataClass();
        $schema = DataObject::getSchema();
        $joinField = $schema->getRemoteJoinField($parentClass, $relation, 'has_many');
        $relatedClass = $schema->hasManyComponent($parentClass, $relation);
        $ids = $query->column('ID');
        $singleton = DataObject::singleton($parentClass);
        /* @var RelationList $relatedRecords */
        $relatedRecords = $singleton->$relation($ids);

        $map = [];
        foreach ($query->execute() as $item) {
            $map[$item['ID']] = [];
        }
        foreach ($relatedRecords as $item) {
            $parentID = $item->$joinField;
            $map[$parentID][] = $item;
        }

        foreach ($map as $parentID => $records) {
            $query = HasManyList::create($relatedClass, $joinField)
                ->forForeignID($parentID);
            $store->persist($query->dataQuery(), $records);
        }

        return $relatedRecords->dataQuery();
    }
}
