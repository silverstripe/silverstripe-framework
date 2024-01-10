<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\View\SSViewer;
use LogicException;

/**
 * GridFieldPage displays a simple current page count summary.
 * E.g. "View 1 - 15 of 32"
 *
 * Depends on {@link GridFieldPaginator} being added to the {@link GridField}.
 */
class GridFieldPageCount extends AbstractGridFieldComponent implements GridField_HTMLProvider
{
    use Configurable;

    /**
     * @var string placement indicator for this control
     */
    protected $targetFragment;

    /**
     * @param string $targetFragment The fragment indicating the placement of this page count
     */
    public function __construct($targetFragment = 'before')
    {
        $this->targetFragment = $targetFragment;
    }

    /**
     * Flag indicating whether or not this control should throw an error if a
     * {@link GridFieldPaginator} is not present on the same {@link GridField}
     *
     * @config
     * @var boolean
     */
    private static $require_paginator = true;

    /**
     * Retrieves an instance of a GridFieldPaginator attached to the same control
     * @param GridField $gridField The parent gridfield
     * @return GridFieldPaginator The attached GridFieldPaginator, if found.
     * @throws LogicException
     */
    protected function getPaginator($gridField)
    {
        $paginator = $gridField->getConfig()->getComponentByType(GridFieldPaginator::class);

        if (!$paginator && GridFieldPageCount::config()->uninherited('require_paginator')) {
            throw new LogicException(
                static::class . " relies on a GridFieldPaginator to be added " . "to the same GridField, but none are present."
            );
        }

        return $paginator;
    }

    /**
     * @param GridField $gridField
     * @return array
     */
    public function getHTMLFragments($gridField)
    {
        // Retrieve paging parameters from the directing paginator component
        $paginator = $this->getPaginator($gridField);
        if ($paginator && ($forTemplate = $paginator->getTemplateParameters($gridField))) {
            $template = SSViewer::get_templates_by_class($this, '', __CLASS__);
            return [
                $this->targetFragment => $forTemplate->renderWith($template)
            ];
        }

        return null;
    }
}
