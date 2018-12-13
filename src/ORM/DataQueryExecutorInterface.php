<?php

namespace SilverStripe\ORM;

interface DataQueryExecutorInterface
{
    public function execute(DataQuery $dataQuery);
}