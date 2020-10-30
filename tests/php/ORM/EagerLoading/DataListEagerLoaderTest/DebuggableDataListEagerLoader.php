<?php


namespace SilverStripe\ORM\Tests\EagerLoading\DataListEagerLoaderTest;


use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\EagerLoading\DataListEagerLoader;
use SilverStripe\ORM\FutureQueryHints;
use SilverStripe\ORM\QueryCache\CachedDataQueryExecutor;

class DebuggableDataListEagerLoader extends DataListEagerLoader
{
    protected $hits = 0;

    protected $queries = 0;

    public function execute(DataQuery $dataQuery, ?string $modifier = null, ?FutureQueryHints $hints = null): iterable
    {
        if ($hints) {
            $this->applyEagerLoading($dataQuery, $hints);
        }
        if ($this->getCacheExecutor()->getCachedResult($dataQuery, $modifier) !== null) {
            $this->hits++;
        } else {
            $this->queries++;
        }
        return parent::execute($dataQuery, $modifier);
    }

    public function reset()
    {
        $this->hits = 0;
        $this->queries = 0;
    }

    public function getHits()
    {
        return $this->hits;
    }

    public function getQueries(): int
    {
        return $this->queries;
    }
}
