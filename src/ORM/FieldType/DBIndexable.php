<?php

namespace SilverStripe\ORM\FieldType;

/**
 * Classes that implement the DBIndexable interface will provide options to set various index types and index
 * contents, which will be processed by {@link \SilverStripe\ORM\DataObjectSchema}
 */
interface DBIndexable
{
    /**
     * Index types that can be used. Please check your database driver to ensure choices are supported.
     *
     * @var string
     */
    const TYPE_INDEX = 'index';
    const TYPE_UNIQUE = 'unique';
    const TYPE_FULLTEXT = 'fulltext';

    /**
     * If "true" is provided to setIndexType, this default index type will be returned
     *
     * @var string
     */
    const TYPE_DEFAULT = 'index';

    /**
     * Set the desired index type to use
     *
     * @param string|bool $type Either of the types listed in {@link SilverStripe\ORM\FieldType\DBIndexable}, or
     *                          boolean true to indicate that the default index type should be used.
     * @return $this
     * @throws \InvalidArgumentException If $type is not one of TYPE_INDEX, TYPE_UNIQUE or TYPE_FULLTEXT
     */
    public function setIndexType($type);

    /**
     * Return the desired index type to use. Will return false if the field instance should not be indexed.
     *
     * @return string|bool
     */
    public function getIndexType();

    /**
     * Returns the index specifications for the field instance, for example:
     *
     * <code>
     * [
     *     'type' => 'unique',
     *     'columns' => ['FieldName']
     * ]
     * </code>
     *
     * @return array
     */
    public function getIndexSpecs();
}
