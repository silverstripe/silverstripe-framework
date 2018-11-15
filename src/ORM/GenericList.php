<?php

namespace SilverStripe\ORM;

/**
 * A Generic implementation of `SS_List` with all related interface to enable filtering, sorting and limiting.
 *
 * @see SS_List, Sortable, Limitable, Filterable
 */
interface GenericList extends SS_List, Filterable, Sortable, Limitable
{
}
