<?php

namespace SilverStripe\ORM;

/**
 * Additional interface for {@link SS_List} classes that are limitable - able to have a subset of the list extracted.
 *
 * All methods in this interface are immutable - they should return new instances with the limit
 * applied, rather than applying the limit in place
 *
 * @see SS_List
 * @see Sortable
 * @see Filterable
 *
 * @template T
 * @implements SS_List<T>
 */
interface Limitable extends SS_List
{

    /**
     * Returns a new instance of this list where no more than $limit records are included.
     * If $offset is specified, then that many records at the beginning of the list will be skipped.
     * This matches the behaviour of the SQL LIMIT clause.
     *
     * If `$length` is null, then no limit is applied. If `$length` is 0, then an empty list is returned.
     *
     * @throws InvalidArgumentException if $length or offset are negative
     * @return static<T>
     */
    public function limit(?int $length, int $offset = 0): Limitable;
}
