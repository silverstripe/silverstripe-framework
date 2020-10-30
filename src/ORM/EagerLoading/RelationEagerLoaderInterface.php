<?php

namespace SilverStripe\ORM\EagerLoading;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\QueryCache\DataQueryStoreInterface;

interface RelationEagerLoaderInterface
{
    /**
     * @param DataQuery $list
     * @param string $relation
     * @param DataQueryStoreInterface $store
     * @return DataQuery
     */
    public function eagerLoadRelation(DataQuery $list, string $relation, DataQueryStoreInterface $store): DataQuery;
}
