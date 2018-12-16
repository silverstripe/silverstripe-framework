<?php

namespace SilverStripe\ORM;

class NaiveDataQueryExecutor implements DataQueryExecutorInterface
{
    /**
     * @param DataQuery $dataQuery
     * @param null $modifier
     * @return \Iterator|Connect\Query
     */
    public function execute(DataQuery $dataQuery, $modifier = null)
    {
        return $dataQuery->query()->execute();
    }

    /**
     * @param DataQuery $dataQuery
     * @return \Iterator|Connect\Query
     */
    public function getFirstRow(DataQuery $dataQuery)
    {
        return $dataQuery->firstRow()->execute();
    }

    /**
     * @param DataQuery $dataQuery
     * @return \Iterator|Connect\Query
     */
    public function getLastRow(DataQuery $dataQuery)
    {
        return $dataQuery->lastRow()->execute();
    }

    /**
     * @param DataQuery $dataQuery
     * @return int|string
     */
    public function getCount(DataQuery $dataQuery)
    {
        return $dataQuery->count();
    }
}