<?php

namespace SilverStripe\ORM\EagerLoading;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\QueryCache\DataQueryStoreInterface;
use SilverStripe\ORM\DataList;

class ManyManyEagerLoader implements RelationEagerLoaderInterface
{
    public function eagerLoadRelation(DataList $list, $relation, DataQueryStoreInterface $store)
    {
        $parentClass = $list->dataClass();
        $schema = DataObject::getSchema();
        $mmData = $schema->manyManyComponent($parentClass, $relation);
        if (!$mmData) {
            return $list;
        }
        $relatedRecords = $list->relation($relation);
        $childrenMap = $relatedRecords->map('ID', 'Me')->toArray();
        $joinTable = $mmData['join'];
        $parentField = $mmData['parentField'];
        $childField = $mmData['childField'];
        $childClass = $mmData['childClass'];
        $extraFields = $schema->manyManyExtraFieldsForComponent($parentClass, $relation) ?: [];
        $parentIDs = $list->map('ID', 'ID')->toArray();

        $placeholders = DB::placeholders($parentIDs);
        $query = new SQLSelect(
            [$parentField, $childField],
            $joinTable,
            [
                ["$parentField IN ($placeholders)" => $parentIDs]
            ]
        );
        $result = $query->execute();
        $map = [];
        foreach ($list as $item) {
            $map[$item->ID] = [];
        }
        while($row = $result->nextRecord()) {
            $parentID = $row[$parentField];
            $childID = $row[$childField];
            $map[$parentID][] = $childrenMap[$childID];
        }

        foreach ($map as $parentID => $children) {
            $query = ManyManyList::create($childClass, $joinTable, $childField, $parentField, $extraFields)
                ->forForeignID($parentID);
            $store->persist($query->dataQuery(), $children);
        }

        return $relatedRecords;
    }
}