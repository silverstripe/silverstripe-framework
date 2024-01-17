<?php

namespace SilverStripe\Forms\GridField;

use League\Csv\Writer;
use LogicException;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ViewableData;

/**
 * Adds an "Export list" button to the bottom of a {@link GridField}.
 */
class GridFieldExportButton extends AbstractGridFieldComponent implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler
{
    /**
     * @var array Map of a property name on the exported objects, with values being the column title in the CSV file.
     * Note that titles are only used when {@link $csvHasHeader} is set to TRUE.
     */
    protected $exportColumns;

    /**
     * @var string
     */
    protected $csvSeparator = ",";

    /**
     * @var string
     */
    protected $csvEnclosure = '"';

    /**
     * @var boolean
     */
    protected $csvHasHeader = true;

    /**
     * Fragment to write the button to
     */
    protected $targetFragment;

    /**
     * Set to true to disable XLS sanitisation
     * [SS-2017-007] Ensure all cells with leading [@=+] have a leading tab
     *
     * @config
     * @var bool
     */
    private static $xls_export_disabled = false;

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     * @param array $exportColumns The columns to include in the export
     */
    public function __construct($targetFragment = "after", $exportColumns = null)
    {
        $this->targetFragment = $targetFragment;
        $this->exportColumns = $exportColumns;
    }

    /**
     * Place the export button in a <p> tag below the field
     *
     * @param GridField $gridField
     *
     * @return array
     */
    public function getHTMLFragments($gridField)
    {
        $button = new GridField_FormAction(
            $gridField,
            'export',
            _t('SilverStripe\\Forms\\GridField\\GridField.CSVEXPORT', 'Export to CSV'),
            'export',
            null
        );
        $button->addExtraClass('btn btn-secondary no-ajax font-icon-down-circled action_export');
        $button->setForm($gridField->getForm());
        return [
            $this->targetFragment => $button->Field(),
        ];
    }

    /**
     * export is an action button
     *
     * @param GridField $gridField
     *
     * @return array
     */
    public function getActions($gridField)
    {
        return ['export'];
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName == 'export') {
            return $this->handleExport($gridField);
        }
        return null;
    }

    /**
     * it is also a URL
     *
     * @param GridField $gridField
     *
     * @return array
     */
    public function getURLHandlers($gridField)
    {
        return [
            'export' => 'handleExport',
        ];
    }

    /**
     * Handle the export, for both the action button and the URL
     *
     * @param GridField $gridField
     * @param HTTPRequest $request
     *
     * @return HTTPResponse
     */
    public function handleExport($gridField, $request = null)
    {
        $now = date("d-m-Y-H-i");
        $fileName = "export-$now.csv";

        if ($fileData = $this->generateExportFileData($gridField)) {
            return HTTPRequest::send_file($fileData, $fileName, 'text/csv');
        }
        return null;
    }

    /**
     * Return the columns to export
     *
     * @param GridField $gridField
     *
     * @return array
     */
    protected function getExportColumnsForGridField(GridField $gridField)
    {
        if ($this->exportColumns) {
            return $this->exportColumns;
        }

        $dataCols = $gridField->getConfig()->getComponentByType(GridFieldDataColumns::class);
        if ($dataCols) {
            return $dataCols->getDisplayFields($gridField);
        }

        $modelClass = $gridField->getModelClass();
        $singleton = singleton($modelClass);
        if (!$singleton->hasMethod('summaryFields')) {
            throw new LogicException(
                'Cannot dynamically determine columns. Add a GridFieldDataColumns component to your GridField'
                . " or implement a summaryFields() method on $modelClass"
            );
        }
        return $singleton->summaryFields();
    }

    /**
     * Generate export fields for CSV.
     *
     * @param GridField $gridField
     *
     * @return string
     */
    public function generateExportFileData($gridField)
    {
        $csvColumns = $this->getExportColumnsForGridField($gridField);

        $csvWriter = Writer::createFromFileObject(new \SplTempFileObject());
        $csvWriter->setDelimiter($this->getCsvSeparator());
        $csvWriter->setEnclosure($this->getCsvEnclosure());
        $csvWriter->setNewline("\r\n"); //use windows line endings for compatibility with some csv libraries
        $csvWriter->setOutputBOM(Writer::BOM_UTF8);

        if (!Config::inst()->get(get_class($this), 'xls_export_disabled')) {
            $csvWriter->addFormatter(function (array $row) {
                foreach ($row as &$item) {
                    // [SS-2017-007] Sanitise XLS executable column values with a leading tab
                    if (preg_match('/^[-@=+].*/', $item ?? '')) {
                        $item = "\t" . $item;
                    }
                }
                return $row;
            });
        }

        if ($this->csvHasHeader) {
            $headers = [];

            // determine the CSV headers. If a field is callable (e.g. anonymous function) then use the
            // source name as the header instead
            foreach ($csvColumns as $columnSource => $columnHeader) {
                if (is_array($columnHeader) && array_key_exists('title', $columnHeader ?? [])) {
                    $headers[] = $columnHeader['title'];
                } else {
                    $headers[] = (!is_string($columnHeader) && is_callable($columnHeader)) ? $columnSource : $columnHeader;
                }
            }

            $csvWriter->insertOne($headers);
            unset($headers);
        }

        //Remove GridFieldPaginator as we're going to export the entire list.
        $gridField->getConfig()->removeComponentsByType(GridFieldPaginator::class);

        $items = $gridField->getManipulatedList();

        foreach ($gridField->getConfig()->getComponents() as $component) {
            if ($component instanceof GridFieldFilterHeader || $component instanceof GridFieldSortableHeader) {
                $items = $component->getManipulatedData($gridField, $items);
            }
        }

        $gridFieldColumnsComponent = $gridField->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $columnsHandled = ($gridFieldColumnsComponent)
            ? $gridFieldColumnsComponent->getColumnsHandled($gridField)
            : [];

        /** @var SS_List<ViewableData> $items */
        // Remove limit as the list may be paginated, we want the full list for the export
        $items = $items->limit(null);

        foreach ($items as $item) {
            // Assume item can be viewed if canView() isn't implemented
            if (!$item->hasMethod('canView') || $item->canView()) {
                $columnData = [];

                foreach ($csvColumns as $columnSource => $columnHeader) {
                    if (!is_string($columnHeader) && is_callable($columnHeader)) {
                        if ($item->hasMethod($columnSource)) {
                            $relObj = $item->{$columnSource}();
                        } else {
                            $relObj = $item->relObject($columnSource);
                        }

                        $value = $columnHeader($relObj);
                    } elseif ($gridFieldColumnsComponent && in_array($columnSource, $columnsHandled ?? [])) {
                        $value = strip_tags(
                            $gridFieldColumnsComponent->getColumnContent($gridField, $item, $columnSource) ?? ''
                        );
                    } else {
                        $value = $gridField->getDataFieldValue($item, $columnSource);

                        if ($value === null) {
                            $value = $gridField->getDataFieldValue($item, $columnHeader);
                        }
                    }

                    $columnData[] = $value;
                }

                $csvWriter->insertOne($columnData);
            }

            if ($item->hasMethod('destroy')) {
                $item->destroy();
            }
        }

        if (method_exists($csvWriter, 'getContent')) {
            return $csvWriter->getContent();
        }

        return (string)$csvWriter;
    }

    /**
     * @return array
     */
    public function getExportColumns()
    {
        return $this->exportColumns;
    }

    /**
     * @param array $cols
     *
     * @return $this
     */
    public function setExportColumns($cols)
    {
        $this->exportColumns = $cols;
        return $this;
    }

    /**
     * @return string
     */
    public function getCsvSeparator()
    {
        return $this->csvSeparator;
    }

    /**
     * @param string $separator
     *
     * @return $this
     */
    public function setCsvSeparator($separator)
    {
        $this->csvSeparator = $separator;
        return $this;
    }

    /**
     * @return string
     */
    public function getCsvEnclosure()
    {
        return $this->csvEnclosure;
    }

    /**
     * @param string $enclosure
     *
     * @return $this
     */
    public function setCsvEnclosure($enclosure)
    {
        $this->csvEnclosure = $enclosure;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getCsvHasHeader()
    {
        return $this->csvHasHeader;
    }

    /**
     * @param boolean $bool
     *
     * @return $this
     */
    public function setCsvHasHeader($bool)
    {
        $this->csvHasHeader = $bool;
        return $this;
    }
}
