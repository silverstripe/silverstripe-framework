<?php

namespace SilverStripe\ORM;

use SilverStripe\ORM\Connect\Query;

interface DataQueryExecutorInterface
{
    /**
     * @param DataQuery $dataQuery
     * @return Iterator
     */
    public function execute(DataQuery $dataQuery);
}