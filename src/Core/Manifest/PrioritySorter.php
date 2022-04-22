<?php

namespace SilverStripe\Core\Manifest;

use SilverStripe\Core\Injector\Injectable;

/**
 * Sorts an associative array of items given a list of priorities,
 * where priorities are the keys of the items in the order they are desired.
 * Allows user-defined variables, and a "rest" key to symbolise all remaining items.
 * Example:
 *
 * $myItems = [
 *   'product-one' => new Product(...),
 *   'product-two' => new Product(...),
 *   'product-three' => new Product(...),
 *   'product-four' =>  new Product(...),
 * ];
 *
 * $priorities = [
 *   '$featured',
 *   'product-two',
 *   '...rest',
 * ];
 *
 * $sorter = new PrioritySorter($items, $priorities);
 * $sorter->setVariable('$featured', 'product-three');
 * $sorter->getSortedList();
 *
 * [
 *   'product-three' => [object] Product,
 *   'product-two' => [object] Product,
 *   'product-one' => [object] Product,
 *   'product-four' => [object] Product
 * ]
 *
 */
class PrioritySorter
{
    use Injectable;

    /**
     * The key that is used to denote all remaining items that have not
     * been specified in priorities
     * @var string
     */
    protected $restKey = '...rest';

    /**
     * A map of variables to their values
     * @var array
     */
    protected $variables = [];

    /**
     * An associative array of items, whose keys can be used in the $priorities list
     *
     * @var array
     */
    protected $items;

    /**
     * An indexed array of keys in the $items list, reflecting the desired sort
     *
     * @var array
     */
    protected $priorities;

    /**
     * The keys of the $items array
     *
     * @var array
     */
    protected $names;

    /**
     * PrioritySorter constructor.
     * @param array $items
     * @param array $priorities
     */
    public function __construct(array $items = [], array $priorities = [])
    {
        $this->setItems($items);
        $this->priorities = $priorities;
    }

    /**
     * Sorts the items and returns a new version of $this->items
     *
     * @return array
     */
    public function getSortedList()
    {
        $this->addVariables();

        // Find all items that don't have their order specified by the config system
        $unspecified = array_diff($this->names ?? [], $this->priorities);

        if (!empty($unspecified)) {
            $this->includeRest($unspecified);
        }

        $sortedList = [];
        foreach ($this->priorities as $itemName) {
            if (isset($this->items[$itemName])) {
                $sortedList[$itemName] = $this->items[$itemName];
            }
        }

        return $sortedList;
    }

    /**
     * Set the priorities for the items
     *
     * @param array $priorities An array of keys used in $this->items
     * @return $this
     */
    public function setPriorities(array $priorities)
    {
        $this->priorities = $priorities;

        return $this;
    }

    /**
     * Sets the list of all items
     *
     * @param array $items
     * @return $this
     */
    public function setItems(array $items)
    {
        $this->items = $items;
        $this->names = array_keys($items ?? []);

        return $this;
    }

    /**
     * Add a variable for replacination, e.g. addVariable->('$project', 'myproject')
     *
     * @param string $name
     * @param $value
     * @return $this
     */
    public function setVariable($name, $value)
    {
        $this->variables[$name] = $value;

        return $this;
    }

    /**
     * The key used for "all other items"
     *
     * @param $key
     * @return $this
     */
    public function setRestKey($key)
    {
        $this->restKey = $key;

        return $this;
    }

    /**
     * If variables are defined, interpolate their values
     */
    protected function addVariables()
    {
        // Remove variables from the list
        $varValues = array_values($this->variables ?? []);
        $this->names = array_filter($this->names ?? [], function ($name) use ($varValues) {
            return !in_array($name, $varValues ?? []);
        });

        // Replace variables with their values
        $this->priorities = array_map(function ($name) {
            return $this->resolveValue($name);
        }, $this->priorities ?? []);
    }

    /**
     * If the "rest" key exists in the order array,
     * replace it by the unspecified items
     */
    protected function includeRest(array $list)
    {
        $otherItemsIndex = false;
        if ($this->restKey) {
            $otherItemsIndex = array_search($this->restKey, $this->priorities ?? []);
        }
        if ($otherItemsIndex !== false) {
            array_splice($this->priorities, $otherItemsIndex ?? 0, 1, $list);
        } else {
            // Otherwise just jam them on the end
            $this->priorities = array_merge($this->priorities, $list);
        }
    }

    /**
     * Ensure variables get converted to their values
     *
     * @param $name
     * @return mixed
     */
    protected function resolveValue($name)
    {
        return isset($this->variables[$name]) ? $this->variables[$name] : $name;
    }
}
