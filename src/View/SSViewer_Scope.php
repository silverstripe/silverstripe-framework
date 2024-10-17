<?php

namespace SilverStripe\View;

use InvalidArgumentException;
use Iterator;
use LogicException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;

/**
 * This tracks the current scope for an SSViewer instance. It has three goals:
 *   - Handle entering & leaving sub-scopes in loops and withs
 *   - Track Up and Top
 *   - (As a side effect) Inject data that needs to be available globally (used to live in ModelData)
 *
 * It is also responsible for mixing in data on top of what the item provides. This can be "global"
 * data that is scope-independant (like BaseURL), or type-specific data that is layered on top cross-cut like
 * (like $FirstLast etc).
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

    /**
     * The stack of previous items ("scopes") - an indexed array of: item, item iterator, item iterator total,
     * pop index, up index, current index & parent overlay
     */
    private array $itemStack = [];

    /**
     * The current "global" item (the one any lookup starts from)
     */
    protected ?ViewLayerData $item;

    /**
     * If we're looping over the current "global" item, here's the iterator that tracks with item we're up to
     */
    protected ?Iterator $itemIterator;

    /**
     * Total number of items in the iterator
     */
    protected int $itemIteratorTotal;

    /**
     * A pointer into the item stack for the item that will become the active scope on the next pop call
     */
    private ?int $popIndex;

    /**
     * A pointer into the item stack for which item is "up" from this one
     */
    private ?int $upIndex;

    /**
     * A pointer into the item stack for which the active item (or null if not in stack yet)
     */
    private int $currentIndex;

    /**
     * A store of copies of the main item stack, so it's preserved during a lookup from local scope
     * (which may push/pop items to/from the main item stack)
     */
    private array $localStack = [];

    /**
     * The index of the current item in the main item stack, so we know where to restore the scope
     * stored in $localStack.
     */
    private int $localIndex = 0;

    /**
     * List of global property providers
     *
     * @internal
     * @var TemplateGlobalProvider[]|null
     */
    private static $globalProperties = null;

    /**
     * List of global iterator providers
     *
     * @internal
     * @var TemplateIteratorProvider[]|null
     */
    private static $iteratorProperties = null;

    /**
     * Overlay variables. Take precedence over anything from the current scope
     */
    protected array $overlay;

    /**
     * Flag for whether overlay should be preserved when pushing a new scope
     */
    protected bool $preserveOverlay = false;

    /**
     * Underlay variables. Concede precedence to overlay variables or anything from the current scope
     */
    protected array $underlay;

    public function __construct(
        ?ViewLayerData $item,
        array $overlay = [],
        array $underlay = [],
        ?SSViewer_Scope $inheritedScope = null
    ) {
        $this->item = $item;

        $this->itemIterator = ($inheritedScope) ? $inheritedScope->itemIterator : null;
        $this->itemIteratorTotal = ($inheritedScope) ? $inheritedScope->itemIteratorTotal : 0;
        $this->itemStack[] = [$this->item, $this->itemIterator, $this->itemIteratorTotal, null, null, 0];

        $this->overlay = $overlay;
        $this->underlay = $underlay;

        $this->cacheGlobalProperties();
        $this->cacheIteratorProperties();
    }

    /**
     * Returns the current "current" item in scope
     */
    public function getCurrentItem(): ?ViewLayerData
    {
        return $this->itemIterator ? $this->itemIterator->current() : $this->item;
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
     * Set scope to an intermediate value, which will be used for getting output later on.
     */
    public function scopeToIntermediateValue(string $name, array $arguments, string $type): static
    {
        $overlayIndex = false;

        // $Up and $Top need to restore the overlay from the parent and top-level scope respectively.
        switch ($name) {
            case 'Up':
                $upIndex = $this->getUpIndex();
                if ($upIndex === null) {
                    throw new \LogicException('Up called when we\'re already at the top of the scope');
                }
                $overlayIndex = $upIndex; // Parent scope
                $this->preserveOverlay = true; // Preserve overlay
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
                $overlayIndex = 0; // Top-level scope
                $this->preserveOverlay = true; // Preserve overlay
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
                $this->preserveOverlay = false;
                $this->item = $this->getObj($name, $arguments, $type);
                $this->itemIterator = null;
                $this->upIndex = $this->currentIndex ? $this->currentIndex : count($this->itemStack) - 1;
                $this->currentIndex = count($this->itemStack);
                break;
        }

        if ($overlayIndex !== false) {
            $itemStack = $this->getItemStack();
            if (!$this->overlay && isset($itemStack[$overlayIndex][SSViewer_Scope::ITEM_OVERLAY])) {
                $this->overlay = $itemStack[$overlayIndex][SSViewer_Scope::ITEM_OVERLAY];
            }
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
     */
    public function self(): ?ViewLayerData
    {
        $result = $this->getCurrentItem();
        $this->resetLocalScope();

        return $result;
    }

    /**
     * Jump to the last item in the stack, called when a new item is added before a loop/with
     *
     * Store the current overlay (as it doesn't directly apply to the new scope
     * that's being pushed). We want to store the overlay against the next item
     * "up" in the stack (hence upIndex), rather than the current item, because
     * SSViewer_Scope::obj() has already been called and pushed the new item to
     * the stack by this point
     */
    public function pushScope(): static
    {
        $newLocalIndex = count($this->itemStack ?? []) - 1;

        $this->popIndex = $this->itemStack[$newLocalIndex][SSViewer_Scope::POP_INDEX] = $this->localIndex;
        $this->localIndex = $newLocalIndex;

        // $Up now becomes the parent scope - the parent of the current <% loop %> or <% with %>
        $this->upIndex = $this->itemStack[$newLocalIndex][SSViewer_Scope::UP_INDEX] = $this->popIndex;

        // We normally keep any previous itemIterator around, so local $Up calls reference the right element. But
        // once we enter a new global scope, we need to make sure we use a new one
        $this->itemIterator = $this->itemStack[$newLocalIndex][SSViewer_Scope::ITEM_ITERATOR] = null;

        $upIndex = $this->getUpIndex() ?: 0;

        $itemStack = $this->getItemStack();
        $itemStack[$upIndex][SSViewer_Scope::ITEM_OVERLAY] = $this->overlay;
        $this->setItemStack($itemStack);

        // Remove the overlay when we're changing to a new scope, as values in
        // that scope take priority. The exceptions that set this flag are $Up
        // and $Top as they require that the new scope inherits the overlay
        if (!$this->preserveOverlay) {
            $this->overlay = [];
        }

        return $this;
    }

    /**
     * Jump back to "previous" item in the stack, called after a loop/with block
     *
     * Now that we're going to jump up an item in the item stack, we need to
     * restore the overlay that was previously stored against the next item "up"
     * in the stack from the current one
     */
    public function popScope(): static
    {
        $upIndex = $this->getUpIndex();

        if ($upIndex !== null) {
            $itemStack = $this->getItemStack();
            $this->overlay = $itemStack[$upIndex][SSViewer_Scope::ITEM_OVERLAY];
        }

        $this->localIndex = $this->popIndex;
        $this->resetLocalScope();

        return $this;
    }

    /**
     * Fast-forwards the current iterator to the next item.
     * @return bool True if there's an item, false if not.
     */
    public function next(): bool
    {
        if (!$this->item) {
            return false;
        }

        if (!$this->itemIterator) {
            // Note: it is important that getIterator() is called before count() as implemenations may rely on
            // this to efficiently get both the number of records and an iterator (e.g. DataList does this)
            $this->itemIterator = $this->item->getIterator();

            // This will execute code in a generator up to the first yield. For example, this ensures that
            // DataList::getIterator() is called before Datalist::count() which means we only run the query once
            // instead of running a separate explicit count() query
            $this->itemIterator->rewind();

            // Get the number of items in the iterator.
            // Don't just use iterator_count because that results in running through the list
            // which causes some iterators to no longer be iterable for some reason
            $this->itemIteratorTotal = $this->item->getIteratorCount();

            $this->itemStack[$this->localIndex][SSViewer_Scope::ITEM_ITERATOR] = $this->itemIterator;
            $this->itemStack[$this->localIndex][SSViewer_Scope::ITEM_ITERATOR_TOTAL] = $this->itemIteratorTotal;
        } else {
            $this->itemIterator->next();
        }

        $this->resetLocalScope();

        if (!$this->itemIterator->valid()) {
            return false;
        }

        return true;
    }

    /**
     * Get the value that will be directly rendered in the template.
     */
    public function getOutputValue(string $name, array $arguments, string $type): string
    {
        $retval = $this->getObj($name, $arguments, $type);
        $this->resetLocalScope();
        return $retval === null ? '' : $retval->__toString();
    }

    /**
     * Get the value to pass as an argument to a method.
     */
    public function getValueAsArgument(string $name, array $arguments, string $type): mixed
    {
        $retval = null;

        if ($this->hasOverlay($name)) {
            $retval = $this->getOverlay($name, $arguments, true);
        } else {
            $on = $this->getCurrentItem();
            if ($on && isset($on->$name)) {
                $retval = $on->getRawDataValue($name, $arguments, $type);
            }

            if ($retval === null) {
                $retval = $this->getUnderlay($name, $arguments, true);
            }
        }

        $this->resetLocalScope();
        return $retval;
    }

    /**
     * Check if the current item in scope has a value for the named field.
     */
    public function hasValue(string $name, array $arguments, string $type): bool
    {
        $retval = null;
        $overlay = $this->getOverlay($name, $arguments);
        if ($overlay && $overlay->hasDataValue()) {
            $retval = true;
        }

        if ($retval === null) {
            $on = $this->getCurrentItem();
            if ($on) {
                $retval = $on->hasDataValue($name, $arguments, $type);
            }
        }

        if (!$retval) {
            $underlay = $this->getUnderlay($name, $arguments);
            $retval = $underlay && $underlay->hasDataValue();
        }

        $this->resetLocalScope();
        return $retval;
    }

    /**
     * Reset the local scope - restores saved state to the "global" item stack. Typically called after
     * a lookup chain has been completed
     */
    protected function resetLocalScope()
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

    /**
     * Evaluate a template override. Returns an array where the presence of
     * a 'value' key indiciates whether an override was successfully found,
     * as null is a valid override value
     *
     * @param string $property Name of override requested
     * @param array $overrides List of overrides available
     * @return array An array with a 'value' key if a value has been found, or empty if not
     */
    protected function processTemplateOverride($property, $overrides)
    {
        if (!array_key_exists($property, $overrides)) {
            return [];
        }

        // Detect override type
        $override = $overrides[$property];

        // Late-evaluate this value
        if (!is_string($override) && is_callable($override)) {
            $override = $override();

            // Late override may yet return null
            if (!isset($override)) {
                return [];
            }
        }

        return ['value' => $override];
    }

    /**
     * Build cache of global properties
     */
    protected function cacheGlobalProperties()
    {
        if (SSViewer_Scope::$globalProperties !== null) {
            return;
        }

        SSViewer_Scope::$globalProperties = SSViewer::getMethodsFromProvider(
            TemplateGlobalProvider::class,
            'get_template_global_variables'
        );
    }

    /**
     * Build cache of global iterator properties
     */
    protected function cacheIteratorProperties()
    {
        if (SSViewer_Scope::$iteratorProperties !== null) {
            return;
        }

        SSViewer_Scope::$iteratorProperties = SSViewer::getMethodsFromProvider(
            TemplateIteratorProvider::class,
            'get_template_iterator_variables',
            true // Call non-statically
        );
    }

    protected function getObj(string $name, array $arguments, string $type): ?ViewLayerData
    {
        if ($this->hasOverlay($name)) {
            return $this->getOverlay($name, $arguments);
        }

        $on = $this->getCurrentItem();
        if ($on && isset($on->$name)) {
            if ($type === ViewLayerData::TYPE_METHOD) {
                return $on->$name(...$arguments);
            }
            // property
            return $on->$name;
        }

        return $this->getUnderlay($name, $arguments);
    }

    protected function hasOverlay(string $property): bool
    {
        $result = $this->processTemplateOverride($property, $this->overlay);
        return array_key_exists('value', $result);
    }

    protected function getOverlay(string $property, array $args, bool $getRaw = false): mixed
    {
        $result = $this->processTemplateOverride($property, $this->overlay);
        if (array_key_exists('value', $result)) {
            return $this->getInjectedValue($result, $property, $args, $getRaw);
        }
        return null;
    }

    protected function getUnderlay(string $property, array $args, bool $getRaw = false): mixed
    {
        // Check for a presenter-specific override
        $result = $this->processTemplateOverride($property, $this->underlay);
        if (array_key_exists('value', $result)) {
            return $this->getInjectedValue($result, $property, $args, $getRaw);
        }

        // Then for iterator-specific overrides
        if (array_key_exists($property, SSViewer_Scope::$iteratorProperties)) {
            $source = SSViewer_Scope::$iteratorProperties[$property];
            /** @var TemplateIteratorProvider $implementor */
            $implementor = $source['implementor'];
            if ($this->itemIterator) {
                // Set the current iterator position and total (the object instance is the first item in
                // the callable array)
                $implementor->iteratorProperties(
                    $this->itemIterator->key(),
                    $this->itemIteratorTotal
                );
            } else {
                // If we don't actually have an iterator at the moment, act like a list of length 1
                $implementor->iteratorProperties(0, 1);
            }

            return $this->getInjectedValue($source, $property, $args, $getRaw);
        }

        // And finally for global overrides
        if (array_key_exists($property, SSViewer_Scope::$globalProperties)) {
            return $this->getInjectedValue(
                SSViewer_Scope::$globalProperties[$property],
                $property,
                $args,
                $getRaw
            );
        }

        return null;
    }

    protected function getInjectedValue(
        array|TemplateGlobalProvider|TemplateIteratorProvider $source,
        string $property,
        array $params,
        bool $getRaw = false
    ) {
        // Look up the value - either from a callable, or from a directly provided value
        $value = null;
        if (isset($source['callable'])) {
            $value = $source['callable'](...$params);
        } elseif (array_key_exists('value', $source)) {
            $value = $source['value'];
        } else {
            throw new InvalidArgumentException(
                "Injected property $property doesn't have a value or callable value source provided"
            );
        }

        if ($value === null) {
            return null;
        }

        // TemplateGlobalProviders can provide an explicit service to cast to which works outside of the regular cast flow
        if (!$getRaw && isset($source['casting'])) {
            $castObject = Injector::inst()->create($source['casting'], $property);
            if (!ClassInfo::hasMethod($castObject, 'setValue')) {
                throw new LogicException('Explicit cast from template global provider must have a setValue method.');
            }
            $castObject->setValue($value);
            $value = $castObject;
        }

        return $getRaw ? $value : ViewLayerData::create($value);
    }
}
