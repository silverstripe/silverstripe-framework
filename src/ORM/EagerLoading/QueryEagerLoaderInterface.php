<?php

namespace SilverStripe\ORM\EagerLoading;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\QueryCache\DataQueryStoreInterface;

interface QueryEagerLoaderInterface
{
    /**
     * @param array $relations
     * @return $this
     */
    public function addRelations($relations);

    /**
     * @return array
     */
    public function getRelations();

    /**
     * @param DataList $parentList
     * @param DataQueryStoreInterface $store
     * @return $this
     */
    public function execute(DataList $parentList, DataQueryStoreInterface $store);


}