<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\Limitable;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\UnsavedRelationList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;
use LogicException;
use SilverStripe\Dev\Deprecation;

/**
 * GridFieldPaginator paginates the {@link GridField} list and adds controls
 * to the bottom of the {@link GridField}.
 */
class GridFieldPaginator extends AbstractGridFieldComponent implements GridField_HTMLProvider, GridField_DataManipulator, GridField_ActionProvider, GridField_StateProvider
{
    use Configurable;

    /**
     * Specifies default items per page
     *
     * @config
     * @var int
     */
    private static $default_items_per_page = 15;

    /**
     * @var int
     */
    protected $itemsPerPage;

    /**
     * See {@link setThrowExceptionOnBadDataType()}
     * @deprecated 5.2.0 Will be removed without equivalent functionality
     */
    protected $throwExceptionOnBadDataType = true;

    /**
     *
     * @param int $itemsPerPage - How many items should be displayed per page
     */
    public function __construct($itemsPerPage = null)
    {
        $this->itemsPerPage = $itemsPerPage
            ?: GridFieldPaginator::config()->uninherited('default_items_per_page');
    }

    /**
     * Determine what happens when this component is used with a list that isn't {@link SS_Filterable}.
     *
     *  - true: An exception is thrown
     *  - false: This component will be ignored - it won't make any changes to the GridField.
     *
     * By default, this is set to true so that it's clearer what's happening, but the predefined
     * {@link GridFieldConfig} subclasses set this to false for flexibility.
     *
     * @param bool $throwExceptionOnBadDataType
     * @return $this
     * @deprecated 5.2.0 Will be removed without equivalent functionality
     */
    public function setThrowExceptionOnBadDataType($throwExceptionOnBadDataType)
    {
        Deprecation::notice('5.2.0', 'Will be removed without equivalent functionality');
        $this->throwExceptionOnBadDataType = $throwExceptionOnBadDataType;
        return $this;
    }

    /**
     * See {@link setThrowExceptionOnBadDataType()}
     *
     * @return bool
     * @deprecated 5.2.0 Will be removed without equivalent functionality
     */
    public function getThrowExceptionOnBadDataType()
    {
        Deprecation::notice('5.2.0', 'Will be removed without equivalent functionality');
        return $this->throwExceptionOnBadDataType;
    }

    /**
     * Check that this dataList is of the right data type.
     * Returns false if it's a bad data type, and if appropriate, throws an exception.
     *
     * @param SS_List $dataList
     * @return bool
     */
    protected function checkDataType($dataList)
    {
        if ($dataList instanceof Limitable) {
            return true;
        } else {
            // This will be changed to always throw an exception in a future major release.
            if ($this->throwExceptionOnBadDataType) {
                throw new LogicException(
                    static::class . " expects an SS_Limitable list to be passed to the GridField."
                );
            }
            return false;
        }
    }

    /**
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        if (!$this->checkDataType($gridField->getList())) {
            return [];
        }

        return ['paginate'];
    }

    /**
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param string $arguments
     * @param array $data
     * @return void
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if (!$this->checkDataType($gridField->getList())) {
            return;
        }

        if ($actionName !== 'paginate') {
            return;
        }
        $state = $this->getGridPagerState($gridField);
        $state->currentPage = (int)$arguments;
    }

    protected $totalItems = 0;

    /**
     * Retrieves/Sets up the state object used to store and retrieve information
     * about the current paging details of this GridField
     * @param GridField $gridField
     * @return GridState_Data
     */
    protected function getGridPagerState(GridField $gridField)
    {
        return $gridField->State->GridFieldPaginator;
    }

    public function initDefaultState(GridState_Data $data): void
    {
        $data->GridFieldPaginator->initDefaults([
            'currentPage' => 1,
            'itemsPerPage' => $this->getItemsPerPage()
        ]);
    }

    /**
     *
     * @param GridField $gridField
     * @param SS_List $dataList
     * @return SS_List
     */
    public function getManipulatedData(GridField $gridField, SS_List $dataList)
    {
        if (!$this->checkDataType($dataList)) {
            return $dataList;
        }

        $state = $this->getGridPagerState($gridField);

        // Update item count prior to filter. GridFieldPageCount will rely on this value
        $this->totalItems = $dataList->count();

        $startRow = $this->itemsPerPage * ($state->currentPage - 1);

        // Prevent visiting a page with an offset higher than the total number of items
        if ($startRow >= $this->totalItems) {
            $state->currentPage = 1;
            $startRow = 0;
        }

        if (!($dataList instanceof Limitable) || ($dataList instanceof UnsavedRelationList)) {
            return $dataList;
        }

        return $dataList->limit((int)$this->itemsPerPage, (int)$startRow);
    }

    /**
     * Determines arguments to be passed to the template for building this field
     *
     * @param GridField $gridField
     * @return ArrayData If paging is available this will be an ArrayData
     * object of paging details with these parameters:
     * <ul>
     *  <li>OnlyOnePage:                boolean - Is there only one page?</li>
     *  <li>FirstShownRecord:           integer - Number of the first record displayed</li>
     *  <li>LastShownRecord:            integer - Number of the last record displayed</li>
     *  <li>NumRecords:                 integer - Total number of records</li>
     *  <li>NumPages:                   integer - The number of pages</li>
     *  <li>CurrentPageNum (optional):  integer - If OnlyOnePage is false, the number of the current page</li>
     *  <li>FirstPage (optional):       GridField_FormAction - Button to go to the first page</li>
     *  <li>PreviousPage (optional):    GridField_FormAction - Button to go to the previous page</li>
     *  <li>NextPage (optional):        GridField_FormAction - Button to go to the next page</li>
     *  <li>LastPage (optional):        GridField_FormAction - Button to go to last page</li>
     * </ul>
     */
    public function getTemplateParameters(GridField $gridField)
    {
        if (!$this->checkDataType($gridField->getList())) {
            return null;
        }

        $state = $this->getGridPagerState($gridField);

        // Figure out which page and record range we're on
        $totalRows = $this->totalItems;
        if (!$totalRows) {
            return null;
        }

        $totalPages = 1;
        $firstShownRecord = 1;
        $lastShownRecord = $totalRows;
        if ($itemsPerPage = $this->getItemsPerPage()) {
            $totalPages = (int)ceil($totalRows / $itemsPerPage);
            if ($totalPages == 0) {
                $totalPages = 1;
            }
            $firstShownRecord = ($state->currentPage - 1) * $itemsPerPage + 1;
            if ($firstShownRecord > $totalRows) {
                $firstShownRecord = $totalRows;
            }
            $lastShownRecord = $state->currentPage * $itemsPerPage;
            if ($lastShownRecord > $totalRows) {
                $lastShownRecord = $totalRows;
            }
        }

        // If there is only 1 page for all the records in list, we don't need to go further
        // to sort out those first page, last page, pre and next pages, etc
        // we are not render those in to the paginator.
        if ($totalPages === 1) {
            return new ArrayData([
                'OnlyOnePage' => true,
                'FirstShownRecord' => $firstShownRecord,
                'LastShownRecord' => $lastShownRecord,
                'NumRecords' => $totalRows,
                'NumPages' => $totalPages
            ]);
        } else {
            // First page button
            $firstPage = new GridField_FormAction($gridField, 'pagination_first', 'First', 'paginate', 1);
            $firstPage->addExtraClass('btn btn-secondary btn--hide-text btn-sm font-icon-angle-double-left ss-gridfield-pagination-action ss-gridfield-firstpage');
            if ($state->currentPage == 1) {
                $firstPage = $firstPage->performDisabledTransformation();
            }

            // Previous page button
            $previousPageNum = $state->currentPage <= 1 ? 1 : $state->currentPage - 1;
            $previousPage = new GridField_FormAction(
                $gridField,
                'pagination_prev',
                'Previous',
                'paginate',
                $previousPageNum
            );
            $previousPage->addExtraClass('btn btn-secondary btn--hide-text btn-sm font-icon-angle-left ss-gridfield-pagination-action ss-gridfield-previouspage');
            if ($state->currentPage == 1) {
                $previousPage = $previousPage->performDisabledTransformation();
            }

            // Next page button
            $nextPageNum = $state->currentPage >= $totalPages ? $totalPages : $state->currentPage + 1;
            $nextPage = new GridField_FormAction(
                $gridField,
                'pagination_next',
                'Next',
                'paginate',
                $nextPageNum
            );
            $nextPage->addExtraClass('btn btn-secondary btn--hide-text btn-sm font-icon-angle-right ss-gridfield-pagination-action ss-gridfield-nextpage');
            if ($state->currentPage == $totalPages) {
                $nextPage = $nextPage->performDisabledTransformation();
            }

            // Last page button
            $lastPage = new GridField_FormAction($gridField, 'pagination_last', 'Last', 'paginate', $totalPages);
            $lastPage->addExtraClass('btn btn-secondary btn--hide-text btn-sm font-icon-angle-double-right ss-gridfield-pagination-action ss-gridfield-lastpage');
            if ($state->currentPage == $totalPages) {
                $lastPage = $lastPage->performDisabledTransformation();
            }

            // Render in template
            return new ArrayData([
                'OnlyOnePage' => false,
                'FirstPage' => $firstPage,
                'PreviousPage' => $previousPage,
                'CurrentPageNum' => $state->currentPage,
                'NumPages' => $totalPages,
                'NextPage' => $nextPage,
                'LastPage' => $lastPage,
                'FirstShownRecord' => $firstShownRecord,
                'LastShownRecord' => $lastShownRecord,
                'NumRecords' => $totalRows
            ]);
        }
    }

    /**
     *
     * @param GridField $gridField
     * @return array
     */
    public function getHTMLFragments($gridField)
    {
        $forTemplate = $this->getTemplateParameters($gridField);
        if (!$forTemplate) {
            return null;
        }
        $template = SSViewer::get_templates_by_class($this, '_Row', __CLASS__);
        return [
            'footer' => $forTemplate->renderWith(
                $template,
                ['Colspan' => count($gridField->getColumns() ?? [])]
            )
        ];
    }

    /**
     * @param int $num
     * @return $this
     */
    public function setItemsPerPage($num)
    {
        $this->itemsPerPage = $num;
        return $this;
    }

    /**
     * @return Int
     */
    public function getItemsPerPage()
    {
        return $this->itemsPerPage;
    }
}
