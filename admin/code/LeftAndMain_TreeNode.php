<?php

namespace SilverStripe\Admin;

use SilverStripe\View\SSViewer;
use SilverStripe\View\ViewableData;

/**
 * Wrapper around objects being displayed in a tree.
 * Caution: Volatile API.
 *
 * @todo Implement recursive tree node rendering.
 */
class LeftAndMain_TreeNode extends ViewableData
{

    /**
     * Object represented by this node
     *
     * @var Object
     */
    protected $obj;

    /**
     * Edit link to the current record in the CMS
     *
     * @var string
     */
    protected $link;

    /**
     * True if this is the currently selected node in the tree
     *
     * @var bool
     */
    protected $isCurrent;

    /**
     * Name of method to count the number of children
     *
     * @var string
     */
    protected $numChildrenMethod;


    /**
     *
     * @var LeftAndMain_SearchFilter
     */
    protected $filter;

    /**
     * @param Object $obj
     * @param string $link
     * @param bool $isCurrent
     * @param string $numChildrenMethod
     * @param LeftAndMain_SearchFilter $filter
     */
    public function __construct(
        $obj,
        $link = null,
        $isCurrent = false,
        $numChildrenMethod = 'numChildren',
        $filter = null
    ) {
        parent::__construct();
        $this->obj = $obj;
        $this->link = $link;
        $this->isCurrent = $isCurrent;
        $this->numChildrenMethod = $numChildrenMethod;
        $this->filter = $filter;
    }

    /**
     * Returns template, for further processing by {@link Hierarchy->getChildrenAsUL()}.
     * Does not include closing tag to allow this method to inject its own children.
     *
     * @todo Remove hardcoded assumptions around returning an <li>, by implementing recursive tree node rendering
     *
     * @return string
     */
    public function forTemplate()
    {
        $obj = $this->obj;

        return (string)SSViewer::execute_template(
            'SilverStripe\\Admin\\Includes\\LeftAndMain_TreeNode',
            $obj,
            array(
                'Classes' => $this->getClasses(),
                'Link' => $this->getLink(),
                'Title' => sprintf(
                    '(%s: %s) %s',
                    trim(_t('LeftAndMain.PAGETYPE', 'Page type'), " :"),
                    $obj->i18n_singular_name(),
                    $obj->Title
                ),
            )
        );
    }

    /**
     * Determine the CSS classes to apply to this node
     *
     * @return string
     */
    public function getClasses()
    {
        // Get classes from object
        $classes = $this->obj->CMSTreeClasses($this->numChildrenMethod);
        if ($this->isCurrent) {
            $classes .= ' current';
        }
        // Get status flag classes
        $flags = $this->obj->hasMethod('getStatusFlags')
            ? $this->obj->getStatusFlags()
            : false;
        if ($flags) {
            $statuses = array_keys($flags);
            foreach ($statuses as $s) {
                $classes .= ' status-' . $s;
            }
        }
        // Get additional filter classes
        if ($this->filter && ($filterClasses = $this->filter->getPageClasses($this->obj))) {
            if (is_array($filterClasses)) {
                $filterClasses = implode(' ' . $filterClasses);
            }
            $classes .= ' ' . $filterClasses;
        }
        return $classes ?: '';
    }

    public function getObj()
    {
        return $this->obj;
    }

    public function setObj($obj)
    {
        $this->obj = $obj;
        return $this;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function setLink($link)
    {
        $this->link = $link;
        return $this;
    }

    public function getIsCurrent()
    {
        return $this->isCurrent;
    }

    public function setIsCurrent($bool)
    {
        $this->isCurrent = $bool;
        return $this;
    }
}
