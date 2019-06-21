<?php

namespace SilverStripe\ORM;

class NaiveDataQueryExecutor implements DataQueryExecutorInterface
{
    /**
     * @param DataQuery $dataQuery
     * @param string|null $modifier
     * @return iterable
     */
    public function execute(DataQuery $dataQuery, ?string $modifier = null): iterable
    {
        return $dataQuery->query()->execute();
    }

    /**
     * @param DataQuery $dataQuery
     * @return iterable
     */
    public function getFirstRow(DataQuery $dataQuery): iterable
    {
        return $dataQuery->firstRow()->execute();
    }

    /**
     * @param DataQuery $dataQuery
     * @return iterable
     */
    public function getLastRow(DataQuery $dataQuery): iterable
    {
        return $dataQuery->lastRow()->execute();
    }

    /**
     * @param DataQuery $dataQuery
     * @return string
     */
    public function getCount(DataQuery $dataQuery): string
    {
        return $dataQuery->count();
    }
}