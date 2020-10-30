<?php

namespace SilverStripe\ORM;

class NaiveDataQueryExecutor implements DataQueryExecutorInterface
{
    /**
     * @param DataQuery $dataQuery
     * @param string|null $modifier
     * @param FutureQueryHints|null $hints
     * @return iterable
     */
    public function execute(
        DataQuery $dataQuery,
        ?string $modifier = null,
        ?FutureQueryHints $hints = null
    ): iterable {
        return $dataQuery->query()->execute();
    }

    /**
     * @param DataQuery $dataQuery
     * @param FutureQueryHints|null $hints
     * @return iterable
     */
    public function getFirstRow(DataQuery $dataQuery, ?FutureQueryHints $hints = null): iterable
    {
        return $dataQuery->firstRow()->execute();
    }

    /**
     * @param DataQuery $dataQuery
     * @param FutureQueryHints|null $hints
     * @return iterable
     */
    public function getLastRow(DataQuery $dataQuery, ?FutureQueryHints $hints = null): iterable
    {
        return $dataQuery->lastRow()->execute();
    }

    /**
     * @param DataQuery $dataQuery
     * @param FutureQueryHints|null $hints
     * @return string
     */
    public function getCount(DataQuery $dataQuery, ?FutureQueryHints $hints = null): string
    {
        return $dataQuery->count();
    }
}
