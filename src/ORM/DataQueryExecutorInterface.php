<?php

namespace SilverStripe\ORM;

use Iterator;

interface DataQueryExecutorInterface
{
    /**
     * @param DataQuery $dataQuery
     * @param string $modifier
     * @return Iterator
     */
    public function execute(DataQuery $dataQuery, $modifier = null);

    /**
     * @param DataQuery $dataQuery
     * @return Iterator
     */
    public function getFirstRow(DataQuery $dataQuery);

    /**
     * @param DataQuery $dataQuery
     * @return Iterator
     */
    public function getLastRow(DataQuery $dataQuery);

    /**
     * @param DataQuery $dataQuery
     * @return string
     */
    public function getCount(DataQuery $dataQuery);
}