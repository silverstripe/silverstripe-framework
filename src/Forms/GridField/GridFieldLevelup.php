<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\View\ArrayData;
use SilverStripe\View\HTML;
use SilverStripe\View\SSViewer;

/**
 * Adds a "level up" link to a GridField table, which is useful when viewing
 * hierarchical data. Requires the managed record to have a "getParent()"
 * method or has_one relationship called "Parent".
 */
class GridFieldLevelup implements GridField_HTMLProvider
{
    use Injectable;

    /**
     * @var integer - the record id of the level up to
     */
    protected $currentID = null;

    /**
     * sprintf() spec for link to link to parent.
     * Only supports one variable replacement - the parent ID.
     * @var string
     */
    protected $linkSpec = '';

    /**
     * @var array Extra attributes for the link
     */
    protected $attributes = array();

    /**
     *
     * @param integer $currentID - The ID of the current item; this button will find that item's parent
     */
    public function __construct($currentID)
    {
        if ($currentID && is_numeric($currentID)) {
            $this->currentID = $currentID;
        }
    }

    /**
     * @param GridField $gridField
     * @return array|null
     */
    public function getHTMLFragments($gridField)
    {
        $modelClass = $gridField->getModelClass();
        $parentID = 0;

        if (!$this->currentID) {
            return null;
        }

        /** @var DataObject|Hierarchy $modelObj */
        $modelObj = DataObject::get_by_id($modelClass, $this->currentID);

        $parent = null;
        if ($modelObj->hasMethod('getParent')) {
            $parent = $modelObj->getParent();
        } elseif ($modelObj->ParentID) {
            $parent = $modelObj->Parent();
        }

        if ($parent) {
            $parentID = $parent->ID;
        }

        // Attributes
        $attrs = array_merge($this->attributes, array(
            'href' => sprintf($this->linkSpec, $parentID),
            'class' => 'cms-panel-link ss-ui-button font-icon-level-up no-text grid-levelup'
        ));
        $linkTag = HTML::createTag('a', $attrs);

        $forTemplate = new ArrayData(array(
            'UpLink' => DBField::create_field('HTMLFragment', $linkTag)
        ));

        $template = SSViewer::get_templates_by_class($this, '', __CLASS__);
            return array(
            'before' => $forTemplate->renderWith($template),
        );
    }

    public function setAttributes($attrs)
    {
        $this->attributes = $attrs;
        return $this;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function setLinkSpec($link)
    {
        $this->linkSpec = $link;
        return $this;
    }

    public function getLinkSpec()
    {
        return $this->linkSpec;
    }
}
