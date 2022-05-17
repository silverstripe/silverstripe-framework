<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\Requirements;
use InvalidArgumentException;

/**
 * Defines a set of tabs in a form.
 * The tabs are build with our standard tabstrip javascript library.
 * By default, the HTML is generated using FieldHolder.
 *
 * <b>Usage</b>
 *
 * <code>
 * new TabSet(
 *  $name = "TheTabSetName",
 *  new Tab(
 *      $title='Tab one',
 *      new HeaderField("A header"),
 *      new LiteralField("Lipsum","Lorem ipsum dolor sit amet enim.")
 *  ),
 *  new Tab(
 *      $title='Tab two',
 *      new HeaderField("A second header"),
 *      new LiteralField("Lipsum","Ipsum dolor sit amet enim.")
 *  )
 * )
 * </code>
 *
 * Caution: The form field does not include any JavaScript or CSS when used outside of the CMS context,
 * since the required frontend dependencies are included through CMS bundling.
 */
class TabSet extends CompositeField
{

    /**
     * Use custom react component
     *
     * @var string
     */
    protected $schemaComponent = 'Tabs';

    /**
     * @var TabSet
     */
    protected $tabSet;

    /**
     * @var string
     */
    protected $id;

    /**
     * @param string $name Identifier
     * @param string|Tab|TabSet $titleOrTab Natural language title of the tabset, or first tab.
     * If its left out, the class uses {@link FormField::name_to_label()} to produce a title
     * from the {@link $name} parameter.
     * @param Tab|TabSet ...$tabs All further parameters are inserted as children into the TabSet
     */
    public function __construct($name, $titleOrTab = null, $tabs = null)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Invalid string parameter for $name');
        }

        // Get following arguments
        $tabs = func_get_args();
        array_shift($tabs);

        // Detect title from second argument, if it is a string
        if ($titleOrTab && is_string($titleOrTab)) {
            $title = $titleOrTab;
            array_shift($tabs);
        } else {
            $title = static::name_to_label($name);
        }

        // Normalise children list
        if (count($tabs ?? []) === 1 && (is_array($tabs[0]) || $tabs[0] instanceof FieldList)) {
            $tabs = $tabs[0];
        }

        // Ensure tabs are assigned to this tabset
        if ($tabs) {
            foreach ($tabs as $tab) {
                if ($tab instanceof Tab || $tab instanceof TabSet) {
                    $tab->setTabSet($this);
                } else {
                    throw new InvalidArgumentException("TabSet can only contain instances of other Tab or Tabsets");
                }
            }
        }

        parent::__construct($tabs);

        // Assign name and title (not assigned by parent constructor)
        $this->setName($name);
        $this->setTitle($title);
        $this->setID(Convert::raw2htmlid($name));
    }

    public function ID()
    {
        if ($this->tabSet) {
            return $this->tabSet->ID() . '_' . $this->id . '_set';
        }
        return $this->id;
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
     * Returns a tab-strip and the associated tabs.
     * The HTML is a standardised format, containing a &lt;ul;
     *
     * @param array $properties
     * @return DBHTMLText|string
     */
    public function FieldHolder($properties = [])
    {
        $obj = $properties ? $this->customise($properties) : $this;

        return $obj->renderWith($this->getTemplates());
    }

    /**
     * Return a set of all this classes tabs
     *
     * @return FieldList
     */
    public function Tabs()
    {
        return $this->children;
    }

    /**
     * @param FieldList $children Assign list of tabs
     */
    public function setTabs($children)
    {
        $this->children = $children;
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

    public function getAttributes()
    {
        $attributes = array_merge(
            $this->attributes,
            [
                'id' => $this->ID(),
                'class' => $this->extraClass()
            ]
        );

        $this->extend('updateAttributes', $attributes);

        return $attributes;
    }

    /**
     * Add a new child field to the end of the set.
     *
     * @param FormField $field
     */
    public function push(FormField $field)
    {
        if ($field instanceof Tab || $field instanceof TabSet) {
            $field->setTabSet($this);
        }
        parent::push($field);
    }

    /**
     * Add a new child field to the beginning of the set.
     *
     * @param FormField $field
     */
    public function unshift(FormField $field)
    {
        if ($field instanceof Tab || $field instanceof TabSet) {
            $field->setTabSet($this);
        }
        parent::unshift($field);
    }

    /**
     * Inserts a field before a particular field in a FieldList.
     *
     * @param string $insertBefore Name of the field to insert before
     * @param FormField $field The form field to insert
     * @param bool $appendIfMissing
     * @return FormField|null
     */
    public function insertBefore($insertBefore, $field, $appendIfMissing = true)
    {
        if ($field instanceof Tab || $field instanceof TabSet) {
            $field->setTabSet($this);
        }
        return parent::insertBefore($insertBefore, $field, $appendIfMissing);
    }

    /**
     * Inserts a field after a particular field in a FieldList.
     *
     * @param string $insertAfter Name of the field to insert after
     * @param FormField $field The form field to insert
     * @param bool $appendIfMissing
     * @return FormField|null
     */
    public function insertAfter($insertAfter, $field, $appendIfMissing = true)
    {
        if ($field instanceof Tab || $field instanceof TabSet) {
            $field->setTabSet($this);
        }
        return parent::insertAfter($insertAfter, $field, $appendIfMissing);
    }

    /**
     * Sets an additional default for $schemaData.
     * The existing keys are immutable. HideNav is added in this overriding method to ensure it is not ignored by
     * {@link setSchemaData()}
     * It allows hiding of the navigation in the Tabs.js React component.
     *
     * @return array
     */
    public function getSchemaStateDefaults()
    {
        $defaults = parent::getSchemaStateDefaults();
        $defaults['hideNav'] = false;
        return $defaults;
    }
}
