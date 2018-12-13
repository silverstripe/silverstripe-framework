<?php

namespace SilverStripe\ORM\EagerLoading;

use SilverStripe\ORM\DataQueryExecutorInterface;
use SilverStripe\ORM\DataQuery;

class CachedDataQueryExecutor implements DataQueryExecutorInterface, DataQueryStoreInterface
{
    /**
     * @var DataQueryStoreInterface
     */
    protected $queryStore;

    /**
     * @var array
     */
    protected $store;

    /**
     * @param DataQuery $dataQuery
     * @return Iterator|void
     */
    public function execute(DataQuery $dataQuery)
    {
        $results = $this->getResults($dataQuery);
        if ($results) {
            return $results;
        }

        $results = $dataQuery->query()->execute();
    }

    /**
     * @param DataQuery $dataQuery
     * @return array
     */
    public function getResults(DataQuery $dataQuery)
    {
        $key = $dataQuery->getSignature();
        if (isset($this->store[$key])) {
            return $this->store[$key];
        }

        return null;
    }

    /**
     * @param DataQuery $dataQuery
     * @param array $results
     * @return $this
     */
    public function persist(DataQuery $dataQuery, array $results)
    {
        $this->store[$dataQuery->getSignature()] = $results;

        return $this;
    }

}
