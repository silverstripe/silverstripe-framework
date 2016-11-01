<?php

namespace SilverStripe\View;

use ArrayIterator;
use Iterator;

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

	// The stack of previous "global" items
	// An indexed array of item, item iterator, item iterator total, pop index, up index, current index & parent overlay
	private $itemStack = array();

	/**
	 * The current "global" item (the one any lookup starts from)
	 */
	protected $item;

	/**
	 * If we're looping over the current "global" item, here's the iterator that tracks with item we're up to
	 *
	 * @var Iterator
	 */
	protected $itemIterator;

	//Total number of items in the iterator
	protected $itemIteratorTotal;

	// A pointer into the item stack for which item should be scope on the next pop call
	private $popIndex;

	// A pointer into the item stack for which item is "up" from this one
	private $upIndex = null;

	// A pointer into the item stack for which item is this one (or null if not in stack yet)
	private $currentIndex = null;

	private $localIndex;

	public function __construct($item, $inheritedScope = null)
	{
		$this->item = $item;
		$this->localIndex = 0;
		$this->localStack = array();
		if ($inheritedScope instanceof SSViewer_Scope) {
			$this->itemIterator = $inheritedScope->itemIterator;
			$this->itemIteratorTotal = $inheritedScope->itemIteratorTotal;
			$this->itemStack[] = array($this->item, $this->itemIterator, $this->itemIteratorTotal, null, null, 0);
		} else {
			$this->itemStack[] = array($this->item, null, 0, null, null, 0);
		}
	}

	public function getItem()
	{
		return $this->itemIterator ? $this->itemIterator->current() : $this->item;
	}

	/** Called at the start of every lookup chain by SSTemplateParser to indicate a new lookup from local scope */
	public function locally()
	{
		list($this->item, $this->itemIterator, $this->itemIteratorTotal, $this->popIndex, $this->upIndex,
			$this->currentIndex) = $this->itemStack[$this->localIndex];

		// Remember any  un-completed (resetLocalScope hasn't been called) lookup chain. Even if there isn't an
		// un-completed chain we need to store an empty item, as resetLocalScope doesn't know the difference later
		$this->localStack[] = array_splice($this->itemStack, $this->localIndex + 1);

		return $this;
	}

	public function resetLocalScope()
	{
		$previousLocalState = $this->localStack ? array_pop($this->localStack) : null;

		array_splice($this->itemStack, $this->localIndex + 1, count($this->itemStack), $previousLocalState);

		list($this->item, $this->itemIterator, $this->itemIteratorTotal, $this->popIndex, $this->upIndex,
			$this->currentIndex) = end($this->itemStack);
	}

	public function getObj($name, $arguments = [], $cache = false, $cacheName = null)
	{
		$on = $this->itemIterator ? $this->itemIterator->current() : $this->item;
		return $on->obj($name, $arguments, $cache, $cacheName);
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 * @param bool $cache
	 * @param string $cacheName
	 * @return $this
	 */
	public function obj($name, $arguments = [], $cache = false, $cacheName = null)
	{
		switch ($name) {
			case 'Up':
				if ($this->upIndex === null) {
					user_error('Up called when we\'re already at the top of the scope', E_USER_ERROR);
				}

				list($this->item, $this->itemIterator, $this->itemIteratorTotal, $unused2, $this->upIndex,
					$this->currentIndex) = $this->itemStack[$this->upIndex];
				break;

			case 'Top':
				list($this->item, $this->itemIterator, $this->itemIteratorTotal, $unused2, $this->upIndex,
					$this->currentIndex) = $this->itemStack[0];
				break;

			default:
				$this->item = $this->getObj($name, $arguments, $cache, $cacheName);
				$this->itemIterator = null;
				$this->upIndex = $this->currentIndex ? $this->currentIndex : count($this->itemStack) - 1;
				$this->currentIndex = count($this->itemStack);
				break;
		}

		$this->itemStack[] = array(
			$this->item,
			$this->itemIterator,
			$this->itemIteratorTotal,
			null,
			$this->upIndex,
			$this->currentIndex
		);
		return $this;
	}

	/**
	 * Gets the current object and resets the scope.
	 *
	 * @return object
	 */
	public function self()
	{
		$result = $this->itemIterator ? $this->itemIterator->current() : $this->item;
		$this->resetLocalScope();

		return $result;
	}

	public function pushScope()
	{
		$newLocalIndex = count($this->itemStack) - 1;

		$this->popIndex = $this->itemStack[$newLocalIndex][SSViewer_Scope::POP_INDEX] = $this->localIndex;
		$this->localIndex = $newLocalIndex;

		// We normally keep any previous itemIterator around, so local $Up calls reference the right element. But
		// once we enter a new global scope, we need to make sure we use a new one
		$this->itemIterator = $this->itemStack[$newLocalIndex][SSViewer_Scope::ITEM_ITERATOR] = null;

		return $this;
	}

	public function popScope()
	{
		$this->localIndex = $this->popIndex;
		$this->resetLocalScope();

		return $this;
	}

	public function next()
	{
		if (!$this->item) {
			return false;
		}

		if (!$this->itemIterator) {
			if (is_array($this->item)) {
				$this->itemIterator = new ArrayIterator($this->item);
			} else {
				$this->itemIterator = $this->item->getIterator();
			}

			$this->itemStack[$this->localIndex][SSViewer_Scope::ITEM_ITERATOR] = $this->itemIterator;
			$this->itemIteratorTotal = iterator_count($this->itemIterator); //count the total number of items
			$this->itemStack[$this->localIndex][SSViewer_Scope::ITEM_ITERATOR_TOTAL] = $this->itemIteratorTotal;
			$this->itemIterator->rewind();
		} else {
			$this->itemIterator->next();
		}

		$this->resetLocalScope();

		if (!$this->itemIterator->valid()) {
			return false;
		}
		return $this->itemIterator->key();
	}

	public function __call($name, $arguments)
	{
		$on = $this->itemIterator ? $this->itemIterator->current() : $this->item;
		$retval = $on ? call_user_func_array(array($on, $name), $arguments) : null;

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
	 * @param array
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
}
