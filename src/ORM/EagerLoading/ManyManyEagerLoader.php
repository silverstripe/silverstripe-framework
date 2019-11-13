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
    public function eagerLoadRelation(DataList $list, string $relation, DataQueryStoreInterface $store): DataList
    {
        $parentClass = $list->dataClass();
        $schema = DataObject::getSchema();
        $mmData = $schema->manyManyComponent($parentClass, $relation);
        if (!$mmData) {
            return $list;
        }
        $joinTable = $mmData['join'];
        $parentField = $mmData['parentField'];
        $childField = $mmData['childField'];
        $childClass = $mmData['childClass'];
        $extraFields = $schema->manyManyExtraFieldsForComponent($parentClass, $relation) ?: [];
        $parentIDs = $list->columnUnique('ID');
        $placeholders = DB::placeholders($parentIDs);
        $relatedRecordsQuery = new SQLSelect(
            [$parentField, $childField],
            $joinTable,
            [
                ["$parentField IN ($placeholders)" => $parentIDs]
            ]
        );

        $result = $relatedRecordsQuery->execute();
        $childIDs = [];
        foreach ($result as $row) {
            $id = $row[$childField];
            $childIDs[$id] = $id;
        }
        $childRecords = $childClass::get()->byIDs(array_keys($childIDs));
        $childrenMap = $childRecords->map('ID', 'Me')->toArray();
        $map = [];
        foreach ($parentIDs as $parentID) {
            $map[$parentID] = [];
        }
        foreach ($result as $row) {
            $parentID = $row[$parentField];
            $childID = $row[$childField];
            if (!isset($map[$parentID])) {
                $map[$parentID] = [];
            }
            $map[$parentID][] = $childrenMap[$childID];
        }

        foreach ($map as $parentID => $children) {
            $query = ManyManyList::create($childClass, $joinTable, $childField, $parentField, $extraFields)
                ->forForeignID($parentID);
            $store->persist($query->dataQuery(), $children);
        }

        return $childRecords;
    }
}