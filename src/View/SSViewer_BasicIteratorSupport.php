<?php

namespace SilverStripe\View;

use SilverStripe\Dev\Deprecation;

/**
 * Defines an extra set of basic methods that can be used in templates
 * that are not defined on sub-classes of {@link ViewableData}.
 */
class SSViewer_BasicIteratorSupport implements TemplateIteratorProvider
{
    /**
     * @var int
     */
    protected $iteratorPos;

    /**
     * @var int
     */
    protected $iteratorTotalItems;

    /**
     * @return array
     */
    public static function get_template_iterator_variables()
    {
        return [
            'IsFirst',
            'IsLast',
            'First',
            'Last',
            'FirstLast',
            'Middle',
            'MiddleString',
            'Even',
            'Odd',
            'EvenOdd',
            'Pos',
            'FromEnd',
            'TotalItems',
            'Modulus',
            'MultipleOf',
        ];
    }

    /**
     * Set the current iterator properties - where we are on the iterator.
     *
     * @param int $pos position in iterator
     * @param int $totalItems total number of items
     */
    public function iteratorProperties($pos, $totalItems)
    {
        $this->iteratorPos = $pos;
        $this->iteratorTotalItems = $totalItems;
    }

    /**
     * Returns true if this object is the first in a set.
     *
     * @return bool
     */
    public function IsFirst()
    {
        return $this->iteratorPos == 0;
    }

    /**
     * @deprecated 5.0.0 Use IsFirst() to avoid clashes with SS_Lists
     * @return bool
     */
    public function First()
    {
        Deprecation::notice('5.0.0', 'Use IsFirst() to avoid clashes with SS_Lists');
        return $this->IsFirst();
    }

    /**
     * Returns true if this object is the last in a set.
     *
     * @return bool
     */
    public function IsLast()
    {
        return $this->iteratorPos == $this->iteratorTotalItems - 1;
    }

    /**
     * @deprecated 5.0.0 Use IsLast() to avoid clashes with SS_Lists
     * @return bool
     */
    public function Last()
    {
        Deprecation::notice('5.0.0', 'Use IsLast() to avoid clashes with SS_Lists');
        return $this->IsLast();
    }

    /**
     * Returns 'first' or 'last' if this is the first or last object in the set.
     *
     * @return string|null
     */
    public function FirstLast()
    {
        if ($this->IsFirst() && $this->IsLast()) {
            return 'first last';
        }
        if ($this->IsFirst()) {
            return 'first';
        }
        if ($this->IsLast()) {
            return 'last';
        }
        return null;
    }

    /**
     * Return true if this object is between the first & last objects.
     *
     * @return bool
     */
    public function Middle()
    {
        return !$this->IsFirst() && !$this->IsLast();
    }

    /**
     * Return 'middle' if this object is between the first & last objects.
     *
     * @return string
     */
    public function MiddleString()
    {
        if ($this->Middle()) {
            return 'middle';
        }
        return null;
    }

    /**
     * Return true if this object is an even item in the set.
     * The count starts from $startIndex, which defaults to 1.
     *
     * @param int $startIndex Number to start count from.
     * @return bool
     */
    public function Even($startIndex = 1)
    {
        return !$this->Odd($startIndex);
    }

    /**
     * Return true if this is an odd item in the set.
     *
     * @param int $startIndex Number to start count from.
     * @return bool
     */
    public function Odd($startIndex = 1)
    {
        return (bool)(($this->iteratorPos + $startIndex) % 2);
    }

    /**
     * Return 'even' or 'odd' if this object is in an even or odd position in the set respectively.
     *
     * @param int $startIndex Number to start count from.
     * @return string
     */
    public function EvenOdd($startIndex = 1)
    {
        return ($this->Even($startIndex)) ? 'even' : 'odd';
    }

    /**
     * Return the numerical position of this object in the container set. The count starts at $startIndex.
     * The default is the give the position using a 1-based index.
     *
     * @param int $startIndex Number to start count from.
     * @return int
     */
    public function Pos($startIndex = 1)
    {
        return $this->iteratorPos + $startIndex;
    }

    /**
     * Return the position of this item from the last item in the list. The position of the final
     * item is $endIndex, which defaults to 1.
     *
     * @param int $endIndex Value of the last item
     * @return int
     */
    public function FromEnd($endIndex = 1)
    {
        return $this->iteratorTotalItems - $this->iteratorPos + $endIndex - 1;
    }

    /**
     * Return the total number of "sibling" items in the dataset.
     *
     * @return int
     */
    public function TotalItems()
    {
        return $this->iteratorTotalItems;
    }

    /**
     * Returns the modulus of the numerical position of the item in the data set.
     * The count starts from $startIndex, which defaults to 1.
     *
     * @param int $mod The number to perform Mod operation to.
     * @param int $startIndex Number to start count from.
     * @return int
     */
    public function Modulus($mod, $startIndex = 1)
    {
        return ($this->iteratorPos + $startIndex) % $mod;
    }

    /**
     * Returns true or false depending on if the pos of the iterator is a multiple of a specific number.
     * So, <% if MultipleOf(3) %> would return true on indexes: 3,6,9,12,15, etc.
     * The count starts from $offset, which defaults to 1.
     *
     * @param int $factor The multiple of which to return
     * @param int $offset Number to start count from.
     * @return bool
     */
    public function MultipleOf($factor, $offset = 1)
    {
        return (bool)($this->Modulus($factor, $offset) == 0);
    }
}
