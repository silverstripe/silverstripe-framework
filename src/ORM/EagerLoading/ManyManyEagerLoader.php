<?php

namespace SilverStripe\ORM\EagerLoading;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\QueryCache\DataQueryStoreInterface;

class ManyManyEagerLoader implements RelationEagerLoaderInterface
{
    public function eagerLoadRelation(DataQuery $query, string $relation, DataQueryStoreInterface $store): DataQuery
    {
        $parentClass = $query->dataClass();
        $schema = DataObject::getSchema();
        $mmData = $schema->manyManyComponent($parentClass, $relation);
        if (!$mmData) {
            return $query;
        }
        $joinTable = $mmData['join'];
        $parentField = $mmData['parentField'];
        $childField = $mmData['childField'];
        $childClass = $mmData['childClass'];
        $extraFields = $schema->manyManyExtraFieldsForComponent($parentClass, $relation) ?: [];
        $parentIDs = array_unique($query->column('ID'));
        $placeholders = DB::placeholders($parentIDs);
        $relatedRecordsQuery = new SQLSelect(
            [$parentField, $childField],
            $joinTable,
            [
                ["$parentField IN ($placeholders)" => $parentIDs]
            ]
        );
        $relatedRecordsQuery->setOrderBy("\"$parentField\", \"$childField\" ASC");
        $relatedRecordsQuery->setDistinct(true);

        $result = iterator_to_array($relatedRecordsQuery->execute());
        $childIDs = [];
        foreach ($result as $row) {
            $id = $row[$childField];
            $childIDs[$id] = $id;
        }
        /* @var DataList $childRecords */
        $childRecords = $childClass::get()->byIDs(array_keys($childIDs));
        $childrenMap = $childRecords->map('ID', 'Me')->toArray();
        $map = [];
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

        return $childRecords->dataQuery();
    }
}
