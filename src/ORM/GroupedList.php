<?php

namespace SilverStripe\ORM;

use SilverStripe\View\ArrayData;

/**
 * A list decorator that allows a list to be grouped into sub-lists by common
 * values of a field.
 *
 * @template TList
 * @template T
 * @extends ListDecorator<TList, T>
 */
class GroupedList extends ListDecorator
{

    /**
     * @param  string $index
     * @return array
     */
    public function groupBy($index)
    {
        $result = [];

        foreach ($this->list as $item) {
            // if $item is an Object, $index can be a method or a value,
            // if $item is an array, $index is used as the index
            $key = is_object($item) ? ($item->hasMethod($index) ? $item->$index() : $item->$index) : $item[$index];

            if (array_key_exists($key, $result ?? [])) {
                $result[$key]->push($item);
            } else {
                $result[$key] = new ArrayList([$item]);
            }
        }

        return $result;
    }

    /**
     * Similar to {@link groupBy()}, but returns
     * the data in a format which is suitable for usage in templates.
     *
     * @param  string $index
     * @param  string $children Name of the control under which children can be iterated on
     * @return ArrayList<ArrayData>
     */
    public function GroupedBy($index, $children = 'Children')
    {
        $grouped = $this->groupBy($index);
        $result  = new ArrayList();

        foreach ($grouped as $indVal => $list) {
            $list = GroupedList::create($list);
            $result->push(new ArrayData([
                $index    => $indVal,
                $children => $list
            ]));
        }

        return $result;
    }
}
