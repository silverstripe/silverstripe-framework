<?php

namespace SilverStripe\View;

use ArrayIterator;
use Countable;
use Iterator;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\ORM\FieldType\DBFloat;
use SilverStripe\ORM\FieldType\DBInt;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\PaginatedList;

/**
 * This tracks the current scope for an SSViewer instance. It has three goals:
 *   - Handle entering & leaving sub-scopes in loops and withs
 *   - Track Up and Top
 *   - (As a side effect) Inject data that needs to be available globally (used to live in ViewableData)
 *
 * In order to handle up, rather than tracking it using a tree, which would involve constructing new objects
 * for each step, we use indexes into the itemStack (which already has to exist).
 *
 * Each item has three indexes associated with it
 *
 *   - Pop. Which item should become the scope once the current scope is popped out of
 *   - Up. Which item is up from this item
 *   - Current. Which item is the first time this object has appeared in the stack
 *
 * We also keep the index of the current starting point for lookups. A lookup is a sequence of obj calls -
 * when in a loop or with tag the end result becomes the new scope, but for injections, we throw away the lookup
 * and revert back to the original scope once we've got the value we're after
 *
 * @deprecated 5.4.0 Will be renamed to SilverStripe\TemplateEngine\ScopeManager
 */
class SSViewer_Scope
{
    const ITEM = 0;
    const ITEM_ITERATOR = 1;
    const ITEM_ITERATOR_TOTAL = 2;
    const POP_INDEX = 3;
    const UP_INDEX = 4;
    const CURRENT_INDEX = 5;
    const ITEM_OVERLAY = 6;

    /**
     * The stack of previous items ("scopes") - an indexed array of: item, item iterator, item iterator total,
     * pop index, up index, current index & parent overlay
     *
     * @var array
     */
    private $itemStack = [];

    /**
     * The current "global" item (the one any lookup starts from)
     *
     * @var object
     */
    protected $item;

    /**
     * If we're looping over the current "global" item, here's the iterator that tracks with item we're up to
     *
     * @var Iterator
     */
    protected $itemIterator;

    /**
     * Total number of items in the iterator
     *
     * @var int
     */
    protected $itemIteratorTotal;

    /**
     * A pointer into the item stack for the item that will become the active scope on the next pop call
     *
     * @var int
     */
    private $popIndex;

    /**
     * A pointer into the item stack for which item is "up" from this one
     *
     * @var int
     */
    private $upIndex;

    /**
     * A pointer into the item stack for which the active item (or null if not in stack yet)
     *
     * @var int
     */
    private $currentIndex;

    /**
     * A store of copies of the main item stack, so it's preserved during a lookup from local scope
     * (which may push/pop items to/from the main item stack)
     *
     * @var array
     */
    private $localStack = [];

    /**
     * The index of the current item in the main item stack, so we know where to restore the scope
     * stored in $localStack.
     *
     * @var int
     */
    private $localIndex = 0;

    /**
     * @var object $item
     * @var SSViewer_Scope $inheritedScope
     */
    public function __construct($item, SSViewer_Scope $inheritedScope = null)
    {
        Deprecation::noticeWithNoReplacment(
            '5.4.0',
            'Will be renamed to SilverStripe\TemplateEngine\ScopeManager in a future major release',
            Deprecation::SCOPE_CLASS
        );
        $this->item = $item;

        $this->itemIterator = ($inheritedScope) ? $inheritedScope->itemIterator : null;
        $this->itemIteratorTotal = ($inheritedScope) ? $inheritedScope->itemIteratorTotal : 0;
        $this->itemStack[] = [$this->item, $this->itemIterator, $this->itemIteratorTotal, null, null, 0];
    }

    /**
     * Returns the current "active" item
     *
     * @return object
     * @deprecated 5.4.0 use getCurrentItem() instead.
     */
    public function getItem()
    {
        Deprecation::notice('5.4.0', 'use getCurrentItem() instead.');
        $item = $this->itemIterator ? $this->itemIterator->current() : $this->item;
        if (is_scalar($item)) {
            $item = $this->convertScalarToDBField($item);
        }
        return $item;
    }

    public function getCurrentItem()
    {
        return $this->getItem();
    }

    /**
     * Called at the start of every lookup chain by SSTemplateParser to indicate a new lookup from local scope
     *
     * @return SSViewer_Scope
     */
    public function locally()
    {
        list(
            $this->item,
            $this->itemIterator,
            $this->itemIteratorTotal,
            $this->popIndex,
            $this->upIndex,
            $this->currentIndex
        ) = $this->itemStack[$this->localIndex];

        // Remember any  un-completed (resetLocalScope hasn't been called) lookup chain. Even if there isn't an
        // un-completed chain we need to store an empty item, as resetLocalScope doesn't know the difference later
        $this->localStack[] = array_splice($this->itemStack, $this->localIndex + 1);

        return $this;
    }

    /**
     * Reset the local scope - restores saved state to the "global" item stack. Typically called after
     * a lookup chain has been completed
     */
    public function resetLocalScope()
    {
        // Restore previous un-completed lookup chain if set
        $previousLocalState = $this->localStack ? array_pop($this->localStack) : null;
        array_splice($this->itemStack, $this->localIndex + 1, count($this->itemStack ?? []), $previousLocalState);

        list(
            $this->item,
            $this->itemIterator,
            $this->itemIteratorTotal,
            $this->popIndex,
            $this->upIndex,
            $this->currentIndex
        ) = end($this->itemStack);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @param bool $cache
     * @param string $cacheName
     * @return mixed
     */
    public function getObj($name, $arguments = [], $cache = false, $cacheName = null)
    {
        $on = $this->getCurrentItem();
        if ($on === null) {
            return null;
        }
        return $on->obj($name, $arguments, $cache, $cacheName);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @param bool $cache
     * @param string $cacheName
     * @return $this
     * @deprecated 5.4.0 Will be renamed scopeToIntermediateValue()
     */
    public function obj($name, $arguments = [], $cache = false, $cacheName = null)
    {
        Deprecation::noticeWithNoReplacment('5.4.0', 'Will be renamed scopeToIntermediateValue()');
        switch ($name) {
            case 'Up':
                if ($this->upIndex === null) {
                    throw new \LogicException('Up called when we\'re already at the top of the scope');
                }

                list(
                    $this->item,
                    $this->itemIterator,
                    $this->itemIteratorTotal,
                    /* dud */,
                    $this->upIndex,
                    $this->currentIndex
                ) = $this->itemStack[$this->upIndex];
                break;
            case 'Top':
                list(
                    $this->item,
                    $this->itemIterator,
                    $this->itemIteratorTotal,
                    /* dud */,
                    $this->upIndex,
                    $this->currentIndex
                ) = $this->itemStack[0];
                break;
            default:
                $this->item = $this->getObj($name, $arguments, $cache, $cacheName);
                $this->itemIterator = null;
                $this->upIndex = $this->currentIndex ? $this->currentIndex : count($this->itemStack) - 1;
                $this->currentIndex = count($this->itemStack);
                break;
        }

        $this->itemStack[] = [
            $this->item,
            $this->itemIterator,
            $this->itemIteratorTotal,
            null,
            $this->upIndex,
            $this->currentIndex
        ];
        return $this;
    }

    /**
     * Gets the current object and resets the scope.
     *
     * @return object
     */
    public function self()
    {
        $result = $this->getCurrentItem();
        $this->resetLocalScope();

        return $result;
    }

    /**
     * Jump to the last item in the stack, called when a new item is added before a loop/with
     *
     * @return SSViewer_Scope
     */
    public function pushScope()
    {
        $newLocalIndex = count($this->itemStack ?? []) - 1;

        $this->popIndex = $this->itemStack[$newLocalIndex][SSViewer_Scope::POP_INDEX] = $this->localIndex;
        $this->localIndex = $newLocalIndex;

        // $Up now becomes the parent scope - the parent of the current <% loop %> or <% with %>
        $this->upIndex = $this->itemStack[$newLocalIndex][SSViewer_Scope::UP_INDEX] = $this->popIndex;

        // We normally keep any previous itemIterator around, so local $Up calls reference the right element. But
        // once we enter a new global scope, we need to make sure we use a new one
        $this->itemIterator = $this->itemStack[$newLocalIndex][SSViewer_Scope::ITEM_ITERATOR] = null;

        return $this;
    }

    /**
     * Jump back to "previous" item in the stack, called after a loop/with block
     *
     * @return SSViewer_Scope
     */
    public function popScope()
    {
        $this->localIndex = $this->popIndex;
        $this->resetLocalScope();

        return $this;
    }

    /**
     * Fast-forwards the current iterator to the next item
     *
     * @return mixed
     */
    public function next()
    {
        if (!$this->item) {
            return false;
        }

        if (!$this->itemIterator) {
            // Note: it is important that getIterator() is called before count() as implementations may rely on
            // this to efficiently get both the number of records and an iterator (e.g. DataList does this)

            // Item may be an array or a regular IteratorAggregate
            if (is_array($this->item)) {
                $this->itemIterator = new ArrayIterator($this->item);
            } else {
                $this->itemIterator = $this->item->getIterator();

                // This will execute code in a generator up to the first yield. For example, this ensures that
                // DataList::getIterator() is called before Datalist::count()
                $this->itemIterator->rewind();
            }

            // Special case: we *don't* want to use count() on PaginatedList. This is because it'll call
            // PaginatedList::count(), which currently returns the full list count rather than the count of items
            // on the current page (which is what we need for the iterator count)
            if ($this->item instanceof PaginatedList) {
                // We have to re-fetch the iterator before calling getInnerIterator(): we need to count a copy of the
                // inner iterator because it's a generator so can't be rewound or cloned
                $innerIterator = $this->item->getIterator()->getInnerIterator();
                $this->itemIteratorTotal = iterator_count($innerIterator);
            } elseif ($this->item instanceof Countable) {
                // If the item implements Countable, use that to fetch the count
                $this->itemIteratorTotal = count($this->item);
            } else {
                // Otherwise we have to inspect the iterator and then rewind it
                $this->itemIteratorTotal = iterator_count($this->itemIterator);
                $this->itemIterator->rewind();
            }

            $this->itemStack[$this->localIndex][SSViewer_Scope::ITEM_ITERATOR] = $this->itemIterator;
            $this->itemStack[$this->localIndex][SSViewer_Scope::ITEM_ITERATOR_TOTAL] = $this->itemIteratorTotal;
        } else {
            $this->itemIterator->next();
        }

        $this->resetLocalScope();

        if (!$this->itemIterator->valid()) {
            return false;
        }

        return $this->itemIterator->key();
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $on = $this->getCurrentItem();
        if ($on instanceof ViewableData && $name === 'XML_val') {
            $retval = $on->XML_val(...$arguments);
        } else {
            $retval = $on ? $on->$name(...$arguments) : null;
        }

        $this->resetLocalScope();
        return $retval;
    }

    /**
     * @return array
     */
    protected function getItemStack()
    {
        return $this->itemStack;
    }

    /**
     * @param array $stack
     */
    protected function setItemStack(array $stack)
    {
        $this->itemStack = $stack;
    }

    /**
     * @return int|null
     */
    protected function getUpIndex()
    {
        return $this->upIndex;
    }

    private function convertScalarToDBField(bool|string|float|int $value): DBField
    {
        return match (gettype($value)) {
            'boolean' => DBBoolean::create()->setValue($value),
            'string' => DBText::create()->setValue($value),
            'double' => DBFloat::create()->setValue($value),
            'integer' => DBInt::create()->setValue($value),
        };
    }
}
