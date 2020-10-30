<?php

namespace SilverStripe\ORM;

interface DataQueryExecutorInterface
{
    /**
     * @param DataQuery $dataQuery
     * @param string $modifier
     * @param FutureQueryHints|null $hints
     * @return iterable
     */
    public function execute(
        DataQuery $dataQuery,
        ?string $modifier = null,
        ?FutureQueryHints $hints = null
    ): iterable;

    /**
     * @param DataQuery $dataQuery
     * @param FutureQueryHints|null $hints
     * @return iterable|null
     */
    public function getFirstRow(DataQuery $dataQuery, ?FutureQueryHints $hints = null): ?iterable;

    /**
     * @param DataQuery $dataQuery
     * @param FutureQueryHints|null $hints
     * @return iterable|null
     */
    public function getLastRow(DataQuery $dataQuery, ?FutureQueryHints $hints = null): ?iterable;

    /**
     * @param DataQuery $dataQuery
     * @param FutureQueryHints|null $hints
     * @return string
     */
    public function getCount(DataQuery $dataQuery, ?FutureQueryHints $hints = null): string;
}
