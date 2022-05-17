<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;

/**
 * Adding this class to a {@link GridFieldConfig} of a {@link GridField} adds
 * a footer bar to that field.
 *
 * The footer looks just like the {@link GridFieldPaginator} control, except
 * without the pagination controls.
 *
 * It only display the "Viewing 1-8 of 8" status text and (optionally) a
 * configurable status message.
 *
 * The purpose of this class is to have a footer that can round off
 * {@link GridField} without having to use pagination.
 */
class GridFieldFooter extends AbstractGridFieldComponent implements GridField_HTMLProvider
{

    /**
     * A message to display in the footer
     *
     * @var string
     */
    protected $message = null;

    /**
     * True to show record count
     *
     * @var bool
     */
    protected $showrecordcount = false;

    /**
     *
     * @param string $message A message to display in the footer
     * @param bool $showrecordcount
     */
    public function __construct($message = null, $showrecordcount = true)
    {
        if ($message) {
            $this->message = $message;
        }
        $this->showrecordcount = $showrecordcount;
    }


    public function getHTMLFragments($gridField)
    {
        $count = $gridField->getList()->count();

        $forTemplate = new ArrayData([
            'ShowRecordCount' => $this->showrecordcount,
            'Message' => $this->message,
            'FirstShownRecord' => 1,
            'LastShownRecord' => $count,
            'NumRecords' => $count
        ]);

        $template = SSViewer::get_templates_by_class($this, '', __CLASS__);
        return [
            'footer' => $forTemplate->renderWith(
                $template,
                [
                    'Colspan' => count($gridField->getColumns() ?? [])
                ]
            )
        ];
    }
}
