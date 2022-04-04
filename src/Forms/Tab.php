<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Convert;
use InvalidArgumentException;

/**
 * Implements a single tab in a {@link TabSet}.
 *
 * Here is a simple implementation of a Tab. Obviously, you can include as much fields
 * inside as you want. A tab can contain other tabs as well.
 *
 * <code>
 * new Tab(
 *  $title='Tab one',
 *  new HeaderField("A header"),
 *  new LiteralField("Lipsum","Lorem ipsum dolor sit amet enim.")
 * )
 * </code>
 */
class Tab extends CompositeField
{

    /**
     * Use custom react component
     *
     * @var string
     */
    protected $schemaComponent = 'TabItem';

    /**
     * @var TabSet
     */
    protected $tabSet;

    /**
     * @var string
     */
    protected $id;

    /**
     * @uses FormField::name_to_label()
     *
     * @param string $name Identifier of the tab, without characters like dots or spaces
     * @param string|FormField $titleOrField Natural language title of the tabset, or first tab.
     * If its left out, the class uses {@link FormField::name_to_label()} to produce a title
     * from the {@link $name} parameter.
     * @param FormField ...$fields All following parameters are inserted as children to this tab
     */
    public function __construct($name, $titleOrField = null, $fields = null)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Invalid string parameter for $name');
        }

        // Get following arguments
        $fields = func_get_args();
        array_shift($fields);

        // Detect title from second argument, if it is a string
        if ($titleOrField && is_string($titleOrField)) {
            $title = $titleOrField;
            array_shift($fields);
        } else {
            $title = static::name_to_label($name);
        }

        // Remaining arguments are child fields
        parent::__construct($fields);

        // Assign name and title (not assigned by parent constructor)
        $this->setName($name);
        $this->setTitle($title);
        $this->setID(Convert::raw2htmlid($name));
    }

    public function ID()
    {
        if ($this->tabSet) {
            return $this->tabSet->ID() . '_' . $this->id;
        } else {
            return $this->id;
        }
    }

    /**
     * Set custom HTML ID to use for this tabset
     *
     * @param string $id
     * @return $this
     */
    public function setID($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get child fields
     *
     * @return FieldList
     */
    public function Fields()
    {
        return $this->children;
    }

    /**
     * Assign to a TabSet instance
     *
     * @param TabSet $val
     * @return $this
     */
    public function setTabSet($val)
    {
        $this->tabSet = $val;
        return $this;
    }

    /**
     * Get parent tabset
     *
     * @return TabSet
     */
    public function getTabSet()
    {
        return $this->tabSet;
    }

    public function extraClass()
    {
        $classes = (array)$this->extraClasses;

        return implode(' ', $classes);
    }

    public function getAttributes()
    {
        $attributes = array_merge(
            $this->attributes,
            [
                'id' => $this->ID(),
                'class' => 'tab ' . $this->extraClass()
            ]
        );

        $this->extend('updateAttributes', $attributes);

        return $attributes;
    }
}
