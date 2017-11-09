<?php

namespace SilverStripe\ORM;

use SilverStripe\ORM\FieldType\DBField;

/**
 * Abstract representation of a DB relation field, either saved or in memory
 *
 * Below methods will be added in 5.x
 *
 * @method Relation relation($relationName)
 * @method Relation forForeignID($id)
 * @method string dataClass()
 */
interface Relation extends SS_List, Filterable, Sortable, Limitable
{

    /**
     * Sets the ComponentSet to be the given ID list.
     * Records will be added and deleted as appropriate.
     *
     * @param array $idList List of IDs.
     */
    public function setByIDList($idList);

    /**
     * Returns an array with both the keys and values set to the IDs of the records in this list.
     *
     * Does not return the IDs for unsaved DataObjects
     *
     * @return array
     */
    public function getIDList();

    /**
     * Return the DBField object that represents the given field on the related class.
     *
     * @param string $fieldName Name of the field
     * @return DBField The field as a DBField object
     */
    public function dbObject($fieldName);
}
