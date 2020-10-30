<?php


namespace SilverStripe\ORM;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;
use Generator;

/**
 * A simple value object that stores information about what queries
 * might be needed in the future
 */
class FutureQueryHints
{
    use Injectable;

    /**
     * @var array
     */
    private $relations = [];

    /**
     * FutureQueryHints constructor.
     * @param array $relations
     */
    public function __construct(array $relations = [])
    {
        $this->addRelations($relations);
    }

    /**
     * @param array $relations
     * @return $this
     */
    public function addRelations(array $relations): self
    {
        $normalised = [];
        foreach ($relations as $key => $val) {
            if (is_numeric($key) && (is_string($val) || $val === true)) {
                $normalised[$val] = true;
            } elseif (is_string($key) && is_array($val)) {
                $normalised[$key] = $val;
            } else {
                throw new InvalidArgumentException(sprintf(
                    'addRelations() must take indexed arrays or keys mapped to arrays'
                ));
            }
        }
        $this->relations = array_merge_recursive($this->relations, $normalised);

        return $this;
    }

    /**
     * @return Generator
     */
    public function getRelations(): Generator
    {
        foreach ($this->relations as $key => $val) {
            if (is_array($val)) {
                yield $key => static::create($val);
            } else {
                yield $key => null;
            }
        }
    }
}
