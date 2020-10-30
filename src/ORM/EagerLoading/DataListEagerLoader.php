<?php

namespace SilverStripe\ORM\EagerLoading;

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DataQueryExecutorInterface;
use SilverStripe\ORM\FutureQueryHints;
use SilverStripe\ORM\QueryCache\CachedDataQueryExecutor;
use SilverStripe\ORM\DataObject;
use InvalidArgumentException;
use SilverStripe\Core\Injector\Injector;
use Psr\Container\NotFoundExceptionInterface;

class DataListEagerLoader implements DataQueryExecutorInterface
{
    /**
     * @var CachedDataQueryExecutor
     */
    private $cacheExecutor;

    /**
     * DataListEagerLoader constructor.
     * @param CachedDataQueryExecutor $cacheExecutor
     */
    public function __construct(CachedDataQueryExecutor $cacheExecutor)
    {
        $this->cacheExecutor = $cacheExecutor;
    }

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
    ): iterable {
        if ($hints) {
            $this->applyEagerLoading($dataQuery, $hints);
        }
        return $this->cacheExecutor->execute($dataQuery);
    }

    /**
     * @param DataQuery $parentQuery
     * @param FutureQueryHints|null $hints
     * @return $this
     * @throws NotFoundExceptionInterface
     */
    protected function applyEagerLoading(DataQuery $parentQuery, FutureQueryHints $hints): self
    {
        $class = $parentQuery->dataClass();
        $dataObject = DataObject::singleton($class);

        foreach ($hints->getRelations() as $relation => $nested) {
            $type = $dataObject->getRelationType($relation);
            $loader = null;
            if (!$type) {
                throw new InvalidArgumentException(sprintf(
                    '%s is not a valid relation on %s',
                    $relation,
                    $class
                ));
            }
            switch ($type) {
                case 'has_one':
                    $loader = Injector::inst()->get(HasOneEagerLoader::class);
                    break;
                case 'belongs_to':
                    $loader = Injector::inst()->get(BelongsToEagerLoader::class);
                    break;
                case 'many_many':
                case 'belongs_many_many':
                    $loader = Injector::inst()->get(ManyManyEagerLoader::class);
                    break;
                case 'has_many':
                    $loader = Injector::inst()->get(HasManyEagerLoader::class);
                    break;
            }
            /* @var RelationEagerLoaderInterface $loader */
            $newParentList = $loader->eagerLoadRelation($parentQuery, $relation, $this->cacheExecutor);
            if ($nested) {
                $this->applyEagerLoading($newParentList, $nested);
            }
        }


        return $this;
    }

    /**
     * @param DataQuery $dataQuery
     * @param FutureQueryHints|null $hints
     * @return string
     */
    public function getCount(DataQuery $dataQuery, ?FutureQueryHints $hints = null): string
    {
        if ($hints) {
            $this->applyEagerLoading($dataQuery, $hints);
        }
        return $this->cacheExecutor->getCount($dataQuery);
    }

    /**
     * @param DataQuery $dataQuery
     * @param FutureQueryHints|null $hints
     * @return iterable|null
     */
    public function getLastRow(DataQuery $dataQuery, ?FutureQueryHints $hints = null): ?iterable
    {
        if ($hints) {
            $this->applyEagerLoading($dataQuery, $hints);
        }
        return $this->cacheExecutor->getLastRow($dataQuery);
    }

    /**
     * @param DataQuery $dataQuery
     * @param FutureQueryHints|null $hints
     * @return iterable|null
     */
    public function getFirstRow(DataQuery $dataQuery, ?FutureQueryHints $hints = null): ?iterable
    {
        if ($hints) {
            $this->applyEagerLoading($dataQuery, $hints);
        }
        return $this->cacheExecutor->getFirstRow($dataQuery);
    }

    /**
     * @return CachedDataQueryExecutor
     */
    public function getCacheExecutor(): CachedDataQueryExecutor
    {
        return $this->cacheExecutor;
    }
}
