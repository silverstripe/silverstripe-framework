<?php

namespace SilverStripe\ORM\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\QueryCache\DataQueryStoreInterface;
use SilverStripe\ORM\DataList;

class ManyManyEagerLoader implements RelationEagerLoaderInterface
{
    public function eagerLoadRelation(DataList $list, $relation, DataQueryStoreInterface $store)
    {
//        $parentClass = $list->dataClass();
//        $schema = DataObject::getSchema();
//        $joinField = $schema->getRemoteJoinField($parentClass, $relation, 'has_many');
//        $relatedClass = $schema->hasManyComponent($parentClass, $relation);
//        $ids = $list->map('ID', 'ID')->toArray();
//        $relatedRecords = DataList::create($relatedClass)->filter([
//            $joinField => array_values($ids)
//        ]);
//        $map = [];
//
//        foreach ($relatedRecords as $item) {
//            $parentID = $item->$joinField;
//            if (!isset($map[$parentID])) {
//                $map[$parentID] = [];
//            }
//            $map[$parentID][] = $item;
//        }
//
//        foreach ($map as $parentID => $records) {
//            $query = HasManyList::create($relatedClass, $joinField)
//                ->forForeignID($parentID);
//            $store->persist($query->dataQuery(), $records);
//        }
//
//        return $relatedRecords;
    }
}