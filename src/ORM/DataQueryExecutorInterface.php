<?php

namespace SilverStripe\ORM;

use Iterator;

interface DataQueryExecutorInterface
{
    /**
     * @param DataQuery $dataQuery
     * @param string $modifier
     * @return iterable
     */
    public function execute(DataQuery $dataQuery, ?string $modifier = null): iterable;

    /**
     * @param DataQuery $dataQuery
     * @return iterable|null
     */
    public function getFirstRow(DataQuery $dataQuery): ?iterable;

    /**
     * @param DataQuery $dataQuery
     * @return iterable|null
     */
    public function getLastRow(DataQuery $dataQuery): ?iterable;

    /**
     * @param DataQuery $dataQuery
     * @return string
     */
    public function getCount(DataQuery $dataQuery): string;
}