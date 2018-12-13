<?php

namespace SilverStripe\ORM\EagerLoading;

use SilverStripe\ORM\DataQuery;

interface DataQueryStoreInterface
{
    /**
     * @param DataQuery $dataQuery
     * @return array|null
     */
    public function getResults(DataQuery $dataQuery);

    /**
     * @param DataQuery $dataQuery
     * @param array $results
     * @return $this
     */
    public function persist(DataQuery $dataQuery, array $results);
}
