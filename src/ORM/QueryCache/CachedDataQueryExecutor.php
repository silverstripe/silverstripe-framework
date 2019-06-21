<?php

namespace SilverStripe\ORM\QueryCache;

use SebastianBergmann\CodeCoverage\Node\Iterator;
use SilverStripe\ORM\DataQueryExecutorInterface;
use SilverStripe\ORM\DataQuery;

class CachedDataQueryExecutor implements DataQueryExecutorInterface, DataQueryStoreInterface
{
    const FIRST_ROW = 'first';

    const LAST_ROW = 'last';

    const COUNT = 'count';

    /**
     * @var DataQueryStoreInterface
     */
    protected $queryStore;

    /**
     * @var array
     */
    protected $store = [];

    /**
     * @param DataQuery $dataQuery
     * @param string $modifier
     * @return mixed
     */
    public function execute(DataQuery $dataQuery, ?string $modifier = null)
    {
        $results = $this->getCachedResult($dataQuery, $modifier);
        if ($results !== null) {
            return $results;
        }
        $this->persist($dataQuery, $dataQuery->query()->execute(), $modifier);

        return $this->getCachedResult($dataQuery, $modifier);
    }

    /**
     * @param DataQuery $dataQuery
     * @return iterable
     */
    public function getFirstRow(DataQuery $dataQuery): iterable
    {
        $result = $this->getCachedResult($dataQuery, self::FIRST_ROW);
        if ($result) {
            return $result;
        }
        $this->persist($dataQuery, $dataQuery->firstRow()->execute(), self::FIRST_ROW);

        return $this->getCachedResult($dataQuery, self::FIRST_ROW);
    }

    /**
     * @param DataQuery $dataQuery
     * @return iterable
     */
    public function getLastRow(DataQuery $dataQuery): iterable
    {
        $result = $this->getCachedResult($dataQuery, self::LAST_ROW);
        if ($result) {
            return $result;
        }
        $this->persist($dataQuery, $dataQuery->lastRow()->execute(), self::LAST_ROW);

        return $this->getCachedResult($dataQuery, self::LAST_ROW);
    }

    /**
     * @param DataQuery $dataQuery
     * @return string
     */
    public function getCount(DataQuery $dataQuery): string
    {
        if ($count = $this->getCachedResult($dataQuery, self::COUNT)) {
            return $count;
        }

        // If a list already exists, just count it
        $result = $this->getCachedResult($dataQuery);
        if ($result) {
            return sizeof($result);
        }

        // If there is no list, cache this as a COUNT() query only.
        $this->persist($dataQuery, $dataQuery->count(), self::COUNT);

        return $this->getCachedResult($dataQuery, self::COUNT);
    }

    /**
     * @param DataQuery $dataQuery
     * @param string $modifier
     * @return mixed
     */
    public function getCachedResult(DataQuery $dataQuery, ?string $modifier = null)
    {
        $key = $this->createKey($dataQuery, $modifier);
        if (isset($this->store[$key])) {
            return $this->store[$key];
        }

        return null;
    }

    /**
     * @param DataQuery $dataQuery
     * @param mixed $value
     * @param string $modifier
     * @return $this
     */
    public function persist(DataQuery $dataQuery, $value, $modifier = null): DataQueryStoreInterface
    {
        if ($value instanceof Iterator) {
            $value = iterator_to_array($value);
        }
        $key = $this->createKey($dataQuery, $modifier);
        $this->store[$key] = $value;

        return $this;
    }

    /**
     * @param DataQuery $dataQuery
     * @param string|null $modifier
     * @return string
     */
    protected function createKey(DataQuery $dataQuery, ?string $modifier = null): string
    {
        $prefix = $modifier ? $modifier . '__' : null;

        return $prefix . $dataQuery->getSignature();
    }

}
