<?php

namespace SilverStripe\ORM\QueryCache;

use SilverStripe\ORM\DataQuery;

interface DataQueryStoreInterface
{
    /**
     * @param DataQuery $dataQuery
     * @param string $modifier
     * @return array|null
     */
    public function getCachedResult(DataQuery $dataQuery, $modifier = null);

    /**
     * @param DataQuery $dataQuery
     * @param mixed $results
     * @param string $modifier
     * @return $this
     */
    public function persist(DataQuery $dataQuery, $results, $modifier = null);
}
