<?php

namespace SilverStripe\ORM;

class NaiveDataQueryExecutor implements DataQueryExecutorInterface
{
    public function execute(DataQuery $dataQuery)
    {
        return $dataQuery->query()->execute();
    }
}