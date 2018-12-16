<?php

namespace SilverStripe\ORM\EagerLoading;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\QueryCache\DataQueryStoreInterface;
use SilverStripe\ORM\DataObject;
use InvalidArgumentException;
use SilverStripe\Core\Injector\Injector;
use Psr\Container\NotFoundExceptionInterface;

class DataListEagerLoader implements QueryEagerLoaderInterface
{
    protected $eagerLoad = [];

    protected $loaded = false;

    public function addRelations($relations)
    {
        $this->eagerLoad = array_merge_recursive($this->eagerLoad, $relations);

        return $this;
    }

    public function getRelations()
    {
        return $this->eagerLoad;
    }

    /**
     * @param DataList $parentList
     * @param DataQueryStoreInterface $store
     * @return $this
     * @throws NotFoundExceptionInterface
     */
    public function execute(DataList $parentList, DataQueryStoreInterface $store)
    {
        if ($this->loaded) {
            return $this;
        }
        $this->loaded = true;

        return $this->applyEagerLoading($parentList, $this->eagerLoad, $store);
    }

    /**
     * @param DataList $parentList
     * @param $relations
     * @param DataQueryStoreInterface $store
     * @return $this
     * @throws NotFoundExceptionInterface
     */
    protected function applyEagerLoading(DataList $parentList, $relations, DataQueryStoreInterface $store)
    {
        $class = $parentList->dataClass();
        $dataObject = DataObject::singleton($class);

        foreach ($relations as $key => $val) {
            $relation = is_numeric($key) ? $val : $key;
            $nestedRelations = is_array($val) ? $val : null;

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

            $newParentList = $loader->eagerLoadRelation($parentList, $relation, $store);

            if ($nestedRelations) {
                $this->applyEagerLoading($newParentList, $nestedRelations, $store);
            }
        }

        return $this;

    }
}