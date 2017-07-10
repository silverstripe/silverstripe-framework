<?php

namespace SilverStripe\Core\Manifest;

use SilverStripe\Core\Injector\Injectable;

/**
 * Class PrioritySorter
 * @package SilverStripe\Core\Manifest
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
     * @var array
     */
    protected $items;

    /**
     * @var array
     */
    protected $priorities;

    /**
     * @var array
     */
    protected $names;

    /**
     * If not specified in priorities, put this item at the top (can be a variable)
     *
     * @var string
     */
    protected $defaultTop;

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
        $unspecified = array_diff($this->names, $this->priorities);

        if ($this->restKey && !empty($unspecified)) {
            $this->includeRest($unspecified);
        }

        if ($this->defaultTop) {
            $this->includeDefaultTop();
        }

        $sortedList = [];
        foreach ($this->priorities as $itemName) {
            if (isset($this->items[$itemName])) {
                $sortedList[$itemName] = $this->items[$itemName];
            }
        }
        $sortedList = array_reverse($sortedList, true);

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
        $this->names = array_keys($items);

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
     * Sets the default item that should be top priority, unless explicitly set
     * in the priorities list.
     *
     * @param $name
     * @return $this
     */
    public function setDefaultTop($name)
    {
        $this->defaultTop = $name;

        return $this;
    }

    /**
     * If variables are defined, interpolate their values
     */
    protected function addVariables()
    {
        // Remove variables from the list
        $varValues = array_values($this->variables);
        $this->names = array_filter($this->names, function ($name) use ($varValues) {
            return !in_array($name, $varValues);
        });

        // Replace variables with their values
        $this->priorities = array_map(function ($name) {
            return $this->resolveValue($name);
        }, $this->priorities);
    }

    /**
     * If the "rest" key exists in the order array,
     * replace it by the unspecified items
     */
    protected function includeRest(array $list)
    {
        $otherItemsIndex = false;
        if ($this->restKey) {
            $otherItemsIndex = array_search($this->restKey, $this->priorities);
        }
        if ($otherItemsIndex !== false) {
            array_splice($this->priorities, $otherItemsIndex, 1, $list);
        } else {
            // Otherwise just jam them on the front
            array_splice($this->priorities, 0, 0, $list);
        }
    }

    /**
     * If default top priority is used and not explicitly sorted, make sure it's on top
     */
    protected function includeDefaultTop()
    {
        $value = $this->resolveValue($this->defaultTop);
        if (!in_array($value, $this->priorities)) {
            $this->priorities[] = $value;
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