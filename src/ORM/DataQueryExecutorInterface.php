<?php

namespace SilverStripe\ORM;

use Iterator;

interface DataQueryExecutorInterface
{
    /**
     * @param DataQuery $dataQuery
     * @param string $modifier
     * @return mixed
     */
    public function execute(DataQuery $dataQuery, ?string $modifier = null);

    /**
     * @param DataQuery $dataQuery
     * @return iterable
     */
    public function getFirstRow(DataQuery $dataQuery): iterable;

    /**
     * @param DataQuery $dataQuery
     * @return iterable
     */
    public function getLastRow(DataQuery $dataQuery): iterable;

    /**
     * @param DataQuery $dataQuery
     * @return string
     */
    public function getCount(DataQuery $dataQuery): string;
}