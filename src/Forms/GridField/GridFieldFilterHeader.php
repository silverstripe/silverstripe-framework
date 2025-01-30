<?php

namespace SilverStripe\Forms\GridField;

use LogicException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\Filters\PartialMatchFilter;
use SilverStripe\ORM\Search\BasicSearchContext;
use SilverStripe\ORM\Search\SearchContext;
use SilverStripe\Model\List\SS_List;
use SilverStripe\ORM\Search\SearchContextForm;

/**
 * GridFieldFilterHeader alters the {@link GridField} with some filtering
 * fields in the header of each column.
 *
 * @see GridField
 */
class GridFieldFilterHeader extends AbstractGridFieldComponent implements GridField_URLHandler, GridField_HTMLProvider, GridField_DataManipulator, GridField_ActionProvider, GridField_StateProvider
{
    /**
     * @var SearchContext
     */
    protected $searchContext = null;

    /**
     * @var Form
     */
    protected $searchForm = null;

    /**
     * The name of the default search field
     * @var string|null
     */
    protected ?string $searchField = null;

    private string $placeHolderText = '';

    /**
     * @inheritDoc
     */
    public function getURLHandlers($gridField)
    {
        return [
            'GET SearchForm' => 'getSearchForm',
        ];
    }

    public function getSearchField(): ?string
    {
        return $this->searchField;
    }

    public function setSearchField(string $field): GridFieldFilterHeader
    {
        $this->searchField = $field;
        return $this;
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
        if ($dataList instanceof SS_List) {
            return true;
        }
        throw new LogicException(
            static::class . " expects an SS_List list to be passed to the GridField."
        );
    }

    /**
     * If the GridField has a filterable datalist, return an array of actions
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        if (!$this->checkDataType($gridField->getList())) {
            return [];
        }

        return ['filter', 'reset'];
    }

    /**
     * If the GridField has a filterable datalist, return an array of actions
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param array $data
     * @return void
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if (!$this->checkDataType($gridField->getList())) {
            return;
        }

        $state = $this->getState($gridField);
        $state->Columns = [];

        if ($actionName === 'filter') {
            $filterValues = $data['filter'][$gridField->getName()] ?? null;
            if ($filterValues !== null) {
                foreach ($filterValues as $fieldName => $value) {
                    $state->Columns->$fieldName = $value;
                }
            }
        }
    }

    /**
     * Extract state data from the parent gridfield
     * @param GridField $gridField
     * @return GridState_Data
     */
    private function getState(GridField $gridField): GridState_Data
    {
        return $gridField->State->GridFieldFilterHeader;
    }

    public function initDefaultState(GridState_Data $data): void
    {
        $data->GridFieldFilterHeader->initDefaults(['Columns' => []]);
    }

    /**
     * @inheritDoc
     */
    public function getManipulatedData(GridField $gridField, SS_List $dataList)
    {
        if (!$this->checkDataType($dataList)) {
            return $dataList;
        }

        /** @var array $filterArguments */
        $filterArguments = $this->getState($gridField)->Columns->toArray();
        if (empty($filterArguments)) {
            return $dataList;
        }

        $form = $this->getSearchForm($gridField);
        $filterArguments = $form->prepareValuesForSearchContext($filterArguments);

        $dataListClone = clone($dataList);
        $results = $this->getSearchContext($gridField)
            ->getQuery($filterArguments, false, null, $dataListClone);

        return $results;
    }

    /**
     * Returns whether this {@link GridField} has any columns to filter on at all
     *
     * @param GridField $gridField
     * @return boolean
     */
    public function canFilterAnyColumns($gridField)
    {
        $list = $gridField->getList();
        if (!($list instanceof SS_List) || !$this->checkDataType($list)) {
            return false;
        }
        $modelClass = $gridField->getModelClass();
        $singleton = singleton($modelClass);
        if (ClassInfo::hasMethod($singleton, 'summaryFields')
            && ClassInfo::hasMethod($singleton, 'searchableFields')
        ) {
            // note: searchableFields() will return summary_fields if there are no searchable_fields on the model
            $searchableFields = array_keys($singleton->searchableFields());
            $summaryFields = array_keys($singleton->summaryFields());
            sort($searchableFields);
            sort($summaryFields);
            // searchable_fields has been explictily defined i.e. searchableFields() is not falling back to summary_fields
            if (!empty($searchableFields) && ($searchableFields !== $summaryFields)) {
                return true;
            }
            // we have fallen back to summary_fields, check they are filterable
            foreach ($searchableFields as $searchableField) {
                if ($list->canFilterBy($searchableField)) {
                    return true;
                }
            }
        } else {
            // Allows non-DataObject classes to be used with this component
            $columns = $gridField->getColumns();
            foreach ($columns as $columnField) {
                $metadata = $gridField->getColumnMetadata($columnField);
                $title = $metadata['title'];

                if ($title && $list->canFilterBy($columnField)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get the text to be used as a placeholder in the search field.
     * If blank, the placeholder will be generated based on the class held in the GridField.
     */
    public function getPlaceHolderText(): string
    {
        return $this->placeHolderText;
    }

    /**
     * Set the text to be used as a placeholder in the search field.
     * If blank, this text will be generated based on the class held in the GridField.
     */
    public function setPlaceHolderText(string $placeHolderText): static
    {
        $this->placeHolderText = $placeHolderText;
        return $this;
    }

    /**
     * Generate a search context based on the model class of the of the GridField
     *
     * @param GridField $gridfield
     * @return SearchContext
     */
    public function getSearchContext(GridField $gridField)
    {
        if (!$this->searchContext) {
            $modelClass = $gridField->getModelClass();
            $singleton = singleton($modelClass);
            if (!$singleton->hasMethod('getDefaultSearchContext')) {
                throw new LogicException(
                    'Cannot dynamically instantiate SearchContext. Pass the SearchContext to setSearchContext()'
                    . " or implement a getDefaultSearchContext() method on $modelClass"
                );
            }

            $list = $gridField->getList();
            $searchContext = $singleton->getDefaultSearchContext();

            // In case we are working with a list not backed by the database we need to convert the search context into a BasicSearchContext
            // This is because the scaffolded filters use the ORM for data searching
            if (!$list instanceof DataList) {
                $searchContext = $this->getBasicSearchContext($gridField, $searchContext);
            }

            $this->searchContext = $searchContext;
        }

        return $this->searchContext;
    }

    /**
     * Sets a specific SearchContext instance for this component to use, instead of the default
     * context provided by the ModelClass.
     */
    public function setSearchContext(SearchContext $context): static
    {
        $this->searchContext = $context;
        return $this;
    }

    /**
     * Returns the search form for the component if relevant
     */
    public function getSearchForm(GridField $gridField): ?SearchContextForm
    {
        if (!$this->searchForm) {
            $searchContext = $this->getSearchContext($gridField);

            if ($searchContext->getSearchFields()->count() === 0) {
                return null;
            }

            $this->searchForm = SearchContextForm::create($gridField, $searchContext);
            $this->searchForm->setHTMLID('GridField_' . $gridField->getName() . '_SearchForm');
            $this->searchForm->addExtraClass('form--no-dividers');
        }

        return $this->searchForm;
    }

    /**
     * Either returns the legacy filter header or the search button and field
     *
     * @param GridField $gridField
     * @return array|null
     */
    public function getHTMLFragments($gridField)
    {
        if (!$this->canFilterAnyColumns($gridField)) {
            return null;
        }

        $form = $this->getSearchForm($gridField);
        $isFiltered = !empty($this->getSearchContext($gridField)->getSearchParams());

        return [
            'before' => $form->getPlaceHolder($isFiltered),
            'buttons-before-right' => $form->getFilterButton($isFiltered),
        ];
    }

    /**
     * Transform search context into BasicSearchContext (preserves all relevant search settings)
     */
    private function getBasicSearchContext(GridField $gridField, SearchContext $searchContext): BasicSearchContext
    {
        // Retrieve filters settings as these can be carried over as is
        $defaultSearchFields = $searchContext->getSearchFields();
        $defaultFilters = $searchContext->getFilters();

        // Carry over any search form settings
        $basicSearchContext = BasicSearchContext::create($gridField->getModelClass());
        $basicSearchContext->setFields($defaultSearchFields);

        // Carry over filter configuration (make changes to filter classes so they work with this list)
        foreach ($defaultFilters as $defaultFilter) {
            $fieldFilter = PartialMatchFilter::create(
                // Use name instead of full name as this plain filter doesn't understand relations
                $defaultFilter->getName(),
                $defaultFilter->getValue(),
                $defaultFilter->getModifiers(),
            );
            $basicSearchContext->addFilter($fieldFilter);
        }

        return $basicSearchContext;
    }
}
