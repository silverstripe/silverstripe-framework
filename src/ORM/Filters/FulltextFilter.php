<?php

namespace SilverStripe\ORM\Filters;

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DataObject;
use Exception;

/**
 * Filters by full-text matching on the given field.
 *
 * The following column types are supported:
 *   - Char
 *   - Varchar
 *   - Text
 *
 * To enable full-text matching on fields, you also need to add an index to the
 * database table, using the {$indexes} hash in your DataObject subclass:
 *
 * <code>
 *   private static $indexes = [
 *      'SearchFields' => [
 *          'type' => 'fulltext',
 *          'columns' => ['Name', 'Title', 'Description'],
 *   ];
 * </code>
 *
 */
class FulltextFilter extends SearchFilter
{

    protected function applyOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $predicate = sprintf("MATCH (%s) AGAINST (?)", $this->getDbName());
        return $query->where([$predicate => $this->getValue()]);
    }

    protected function excludeOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $predicate = sprintf("NOT MATCH (%s) AGAINST (?)", $this->getDbName());
        return $query->where([$predicate => $this->getValue()]);
    }

    public function isEmpty()
    {
        return $this->getValue() === [] || $this->getValue() === null || $this->getValue() === '';
    }


    /**
     * This implementation allows for a list of columns to be passed into MATCH() instead of just one.
     *
     * @example
     * <code>
     *  MyDataObject::get()->filter('SearchFields:fulltext', 'search term')
     * </code>
     *
     * @throws Exception
     * @return string
    */
    public function getDbName()
    {
        $indexes = DataObject::getSchema()->databaseIndexes($this->model);
        if (array_key_exists($this->getName(), $indexes ?? [])) {
            $index = $indexes[$this->getName()];
        } else {
            return parent::getDbName();
        }
        if (is_array($index) && array_key_exists('columns', $index ?? [])) {
            return $this->prepareColumns($index['columns']);
        } else {
            throw new Exception(sprintf(
                "Invalid fulltext index format for '%s' on '%s'",
                var_export($this->getName(), true),
                var_export($this->model, true)
            ));
        }
    }

    /**
     * Adds table identifier to the every column.
     * Columns must have table identifier to prevent duplicate column name error.
     *
     * @param array $columns
     * @return string
     */
    protected function prepareColumns($columns)
    {
        $prefix = DataQuery::applyRelationPrefix($this->relation);
        $table = DataObject::getSchema()->tableForField($this->model, current($columns ?? []));
        $fullTable = $prefix . $table;
        $columns = array_map(function ($col) use ($fullTable) {
            return "\"{$fullTable}\".\"{$col}\"";
        }, $columns ?? []);
        return implode(',', $columns);
    }
}
