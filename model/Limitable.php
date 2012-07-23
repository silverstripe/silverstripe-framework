<?php

/**
 * Additional interface for {@link SS_List} classes that are limitable - able to have a subset of the list extracted.
 *
 * All methods in this interface are immutable - they should return new instances with the limit
 * applied, rather than applying the limit in place
 *
 * @see SS_List, SS_Sortable, SS_Filterable
 */
interface SS_Limitable {

	/**
	 * Returns a filtered version of this where no more than $limit records are included.
	 * If $offset is specified, then that many records at the beginning of the list will be skipped.
	 * This matches the behaviour of the SQL LIMIT clause.
	 * 
	 * @return SS_List
	 */
	public function limit($limit, $offset = 0);

}
