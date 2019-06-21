<?php

namespace SilverStripe\ORM\EagerLoading;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\QueryCache\DataQueryStoreInterface;

interface RelationEagerLoaderInterface
{
    /**
     * @param DataList $list
     * @param string $relation
     * @param DataQueryStoreInterface $store
     * @return DataList
     */
    public function eagerLoadRelation(DataList $list, string $relation, DataQueryStoreInterface $store): DataList;

}