<?php

namespace SilverStripe\ORM;

use SilverStripe\ORM\Queries\SQLSelect;

/**
 * Allows middleware to modily finalised dataquery on a per-instance basis
 */
interface DataQueryManipulator
{
    /**
     * Invoked prior to getFinalisedQuery()
     *
     * @param DataQuery $dataQuery
     * @param array $queriedColumns
     * @param SQLSelect $sqlSelect
     */
    public function beforeGetFinalisedQuery(DataQuery $dataQuery, $queriedColumns, SQLSelect $sqlSelect);

    /**
     * Invoked after getFinalisedQuery()
     *
     * @param DataQuery $dataQuery
     * @param array $queriedColumns
     * @param SQLSelect $sqlQuery
     */
    public function afterGetFinalisedQuery(DataQuery $dataQuery, $queriedColumns, SQLSelect $sqlQuery);
}
