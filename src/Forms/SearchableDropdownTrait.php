<?php

namespace SilverStripe\Forms;

use Error;
use InvalidArgumentException;
use LogicException;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\Relation;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\Search\SearchContext;
use SilverStripe\Security\SecurityToken;
use SilverStripe\ORM\RelationList;
use SilverStripe\ORM\UnsavedRelationList;
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;

trait SearchableDropdownTrait
{
    private static array $allowed_actions = [
        'search',
    ];

    private bool $isClearable = false;

    private bool $isLazyLoaded = false;

    private bool $isMultiple = false;

    private bool $isSearchable = true;

    private int $lazyLoadLimit = 100;

    private string $placeholder = '';

    private ?SearchContext $searchContext = null;

    private bool $useDynamicPlaceholder = true;

    private bool $useSearchContext = false;

    private ?DataList $sourceList = null;

    private string $labelField = 'Title';

    /**
     * Returns a JSON string of options for lazy loading.
     */
    public function search(HTTPRequest $request): HTTPResponse
    {
        $response = HTTPResponse::create();
        $response->addHeader('Content-Type', 'application/json');
        if (!SecurityToken::singleton()->checkRequest($request)) {
            $response->setStatusCode(400);
            $response->setBody(json_encode(['message' => 'Invalid CSRF token']));
            return $response;
        }
        $term = $request->getVar('term') ?? '';
        $options = $this->getOptionsForSearchRequest($term);
        $response->setBody(json_encode($options));
        return $response;
    }

    /**
     * Get whether the currently selected value(s) can be cleared
     */
    public function getIsClearable(): bool
    {
        return $this->isClearable;
    }

    /**
     * Set whether the currently selected value(s) can be cleared
     */
    public function setIsClearable(bool $isClearable): static
    {
        $this->isClearable = $isClearable;
        return $this;
    }

    /**
     * Get whether values are lazy loading via AJAX
     */
    public function getIsLazyLoaded(): bool
    {
        return $this->isLazyLoaded;
    }

    /**
     * Set whether values are lazy loaded via AJAX
     */
    public function setIsLazyLoaded(bool $isLazyLoaded): static
    {
        $this->isLazyLoaded = $isLazyLoaded;
        if ($isLazyLoaded) {
            $this->setIsSearchable(true);
        }
        return $this;
    }

    /**
     * Get the limit of items to lazy load
     */
    public function getLazyLoadLimit(): int
    {
        return $this->lazyLoadLimit;
    }

    /**
     * Set the limit of items to lazy load
     */
    public function setLazyLoadLimit(int $lazyLoadLimit): static
    {
        $this->lazyLoadLimit = $lazyLoadLimit;
        return $this;
    }

    /**
     * Get the placeholder text
     */
    public function getPlaceholder(): string
    {
        $placeholder = $this->placeholder;
        if ($placeholder) {
            return $placeholder;
        }
        // SearchableDropdownField will have the getEmptyString() method from SingleSelectField
        if (method_exists($this, 'getEmptyString')) {
            $emptyString = $this->getEmptyString();
            if ($emptyString) {
                return $emptyString;
            }
        }
        $name = $this->getName();
        if ($this->getUseDynamicPlaceholder()) {
            if ($this->getIsSearchable()) {
                if (!$this->getIsLazyLoaded()) {
                    return _t(__TRAIT__ . '.SELECT_OR_TYPE_TO_SEARCH', 'Select or type to search...');
                }
                return _t(__TRAIT__ . '.TYPE_TO_SEARCH', 'Type to search...');
            } else {
                return _t(__TRAIT__ . '.SELECT', 'Select...');
            }
        }
        return '';
    }

    /**
     * Set the placeholder text
     *
     * Calling this will also call setHasEmptyDefault(true), if the method exists on the class,
     * which is required for the placeholder functionality to work on SearchableDropdownField
     *
     * In the case of SearchableDropField this method should be used instead of setEmptyString() which
     * will be remvoved in a future version
     */
    public function setPlaceholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        // SearchableDropdownField will have the setHasEmptyDefault() method from SingleSelectField
        if (method_exists($this, 'setHasEmptyDefault')) {
            $this->setHasEmptyDefault(true);
        }
        return $this;
    }

    /**
     * Get the search context to use
     * If a search context has been set via setSearchContext() that will be used
     * Will fallback to using the dataobjects default search context if a sourceList has been set
     * Otherwise will return null
     *
     * @return SearchContext<DataObject>|null
     */
    public function getSearchContext(): ?SearchContext
    {
        if ($this->searchContext) {
            return $this->searchContext;
        }
        if ($this->sourceList) {
            $dataClass = $this->sourceList->dataClass();
            /** @var DataObject $obj */
            $obj = $dataClass::create();
            return $obj->getDefaultSearchContext();
        }
        return null;
    }

    /**
     * Set the search context to use instead of the dataobjects default search context
     *
     * Calling this will also call setUseSearchContext(true)
     */
    public function setSearchContext(?SearchContext $searchContext): static
    {
        $this->searchContext = $searchContext;
        $this->setUseSearchContext(true);
        return $this;
    }

    /**
     * Get whether to use a dynamic placeholder if a normal placeholder is not set
     */
    public function getUseDynamicPlaceholder(): bool
    {
        return $this->useDynamicPlaceholder;
    }

    /**
     * Set whether to use a dynamic placeholder if a normal placeholder is not set
     */
    public function setUseDynamicPlaceholder(bool $useDynamicPlaceholder): static
    {
        $this->useDynamicPlaceholder = $useDynamicPlaceholder;
        return $this;
    }

    /**
     * Get whether to use a search context instead searching on labelField
     */
    public function getUseSearchContext(): bool
    {
        return $this->useSearchContext;
    }

    /**
     * Set whether to use a search context instead searching on labelField
     */
    public function setUseSearchContext(bool $useSearchContext): static
    {
        $this->useSearchContext = $useSearchContext;
        return $this;
    }

    /**
     * Get whether the field allows searching by typing characters into field
     */
    public function getIsSearchable(): bool
    {
        return $this->isSearchable;
    }

    /**
     * Set whether the field allows searching by typing characters into field
     */
    public function setIsSearchable(bool $isSearchable): static
    {
        $this->isSearchable = $isSearchable;
        return $this;
    }

    /**
     * This returns an array rather than a DataList purely to retain compatibility with ancestor getSource()
     */
    public function getSource(): array
    {
        return $this->getListMap($this->sourceList);
    }

    public function Field($properties = [])
    {
        $context = $this;
        $this->extend('onBeforeRender', $context, $properties);
        return $context->customise($properties)->renderWith($context->getTemplates());
    }

    /*
     * @param mixed $source
     */
    public function setSource($source): static
    {
        // Setting to $this->sourceList instead of $this->source because SelectField.source
        // docblock type is array|ArrayAccess i.e. does not allow DataList
        $this->sourceList = $source;
        return $this;
    }

    /**
     * Get the field to use for the label of the option
     *
     * The default value of 'Title' will map to DataObject::getTitle() if a Title DB field does not exist
     */
    public function getLabelField(): string
    {
        return $this->labelField;
    }

    /**
     * Set the field to use for the label of the option
     */
    public function setLabelField(string $labelField): static
    {
        $this->labelField = $labelField;
        return $this;
    }

    public function getAttributes(): array
    {
        $name = $this->getName();
        if ($this->isMultiple) {
            $name .= '[]';
        }
        return array_merge(
            parent::getAttributes(),
            [
                'name' => $name,
                'data-schema' => json_encode($this->getSchemaData()),
            ]
        );
    }

    /**
     * Get a list of selected ID's
     */
    public function getValueArray(): array
    {
        $value = $this->Value();
        if (empty($value)) {
            return [];
        }
        if (is_array($value)) {
            $arr = $value;
            // Normalise FormBuilder values to be like Page EditForm values
            //
            // FormBuilder $values for non-multi field will be
            // [
            //   'label' => 'MyTitle15', 'value' => '10'
            // ]
            if (array_key_exists('value', $arr)) {
                $val = (int) $arr['value'];
                return $val ? [$val] : [];
            }
            // FormBuilder $values for multi will be
            // [
            //   0 => ['label' => '10', 'value' => 'MyTitle10', 'selected' => false],
            //   1 => ['label' => '15', 'value' => 'MyTitle15', 'selected' => false]
            // ];
            $firstKey = array_key_first($arr);
            if (is_array($arr[$firstKey]) && array_key_exists('value', $arr[$firstKey])) {
                $newArr = [];
                foreach ($arr as $innerArr) {
                    $val = (int) $innerArr['value'];
                    if ($val) {
                        $newArr[] = $val;
                    }
                }
                return $newArr;
            }
            // Page EditForm $values for non-multi field will be
            // [
            //   0 => '10',
            // ];
            // Page EditForm $values for multi will be
            // [
            //   0 => '10',
            //   1 => '15'
            // ];
            return array_map('intval', $arr);
        }
        if ((is_string($value) || is_int($value)) && ctype_digit((string) $value) && $value != 0) {
            return [(int) $value];
        }
        if ($value instanceof SS_List) {
            return array_filter($value->column('ID'));
        }
        if ($value instanceof DataObject && $value->exists()) {
            return [$value->ID];
        }
        // Don't know what value is, handle gracefully. We should not raise an exception here because
        // of there is a bad data for whatever a content editor will not be able to resolve and it will
        // render part of the CMS unusable
        $logger = Injector::inst()->get(LoggerInterface::class);
        $logger->warning('Could not determine value in ' . __CLASS__ . '::getValueArray()');
        return [];
    }

    public function saveInto(DataObjectInterface $record): void
    {
        $name = $this->getName();
        $ids = $this->getValueArray();
        if (substr($name, -2) === 'ID') {
            // has_one field
            $record->$name = $ids[0] ?? 0;
            // polymorphic has_one
            if (is_a($record, DataObject::class)) {
                /** @var DataObject $record */
                $classNameField = substr($name, 0, -2) . 'Class';
                if ($record->hasField($classNameField)) {
                    $record->$classNameField = $ids ? $record->ClassName : '';
                }
            }
        } else {
            // has_many / many_many field
            if (!method_exists($record, 'hasMethod')) {
                throw new LogicException('record does not have method hasMethod()');
            }
            /** @var DataObject $record */
            if (!$record->hasMethod($name)) {
                throw new LogicException("Relation $name does not exist");
            }
            /** @var Relation $relationList */
            $relationList = $record->$name();
            // Use RelationList rather than Relation here since some Relation classes don't allow setting value
            // but RelationList does
            if (!is_a($relationList, RelationList::class) && !is_a($relationList, UnsavedRelationList::class)) {
                throw new LogicException("'$name()' method on {$record->ClassName} doesn't return a relation list");
            }
            $relationList->setByIDList($ids);
        }
    }

    /**
     * @param Validator $validator
     */
    public function validate($validator): bool
    {
        return $this->extendValidationResult(true, $validator);
    }

    public function getSchemaDataType(): string
    {
        if ($this->isMultiple) {
            return FormField::SCHEMA_DATA_TYPE_MULTISELECT;
        }
        return FormField::SCHEMA_DATA_TYPE_SINGLESELECT;
    }

    /**
     * Provide data to the JSON schema for the frontend component
     */
    public function getSchemaDataDefaults(): array
    {
        $data = parent::getSchemaDataDefaults();
        $data = $this->updateDataForSchema($data);
        $name = $this->getName();
        if ($this->isMultiple && strpos($name, '[') === false) {
            $name .= '[]';
        }
        $data['name'] = $name;
        $data['disabled'] = $this->isDisabled() || $this->isReadonly();
        if ($this->getIsLazyLoaded()) {
            $data['optionUrl'] = Controller::join_links($this->Link(), 'search');
        } else {
            $data['options'] = array_values($this->getOptionsForSchema()->toNestedArray());
        }
        return $data;
    }

    public function getSchemaStateDefaults(): array
    {
        $state = [
            'name' => $this->getName(),
            'id' => $this->ID(),
            'value' => $this->getDefaultSchemaValue(),
            'message' => $this->getSchemaMessage(),
            'data' => [],
        ];

        $state = $this->updateDataForSchema($state);
        return $state;
    }

    /**
     * Set whether the field allows multiple values
     * This is only intended to be called from init() by implemented classes, and not called directly
     * To instantiate a dropdown where only a single value is allowed, use SearchableDropdownField.
     * To instantiate a dropdown where multiple values are allowed, use SearchableMultiDropdownField
     */
    protected function setIsMultiple(bool $isMultiple): static
    {
        $this->isMultiple = $isMultiple;
        return $this;
    }

    private function getDefaultSchemaValue()
    {
        if (!$this->getIsLazyLoaded() && $this->hasMethod('getDefaultValue')) {
            return $this->getDefaultValue();
        }
        return $this->Value();
    }

    private function getOptionsForSearchRequest(string $term): array
    {
        if (!$this->sourceList) {
            return [];
        }
        $dataClass = $this->sourceList->dataClass();
        $labelField = $this->getLabelField();
        /** @var DataObject $obj */
        $obj = $dataClass::create();
        $key = $this->getUseSearchContext() ? $obj->getGeneralSearchFieldName() : $this->getLabelField();
        $searchParams = [$key => $term];
        $hasLabelField = (bool) $obj->getSchema()->fieldSpec($dataClass, $labelField);
        $sort = $hasLabelField ? $labelField : null;
        $limit = $this->getLazyLoadLimit();
        $newList = $this->getSearchContext()->getQuery($searchParams, $sort, $limit);
        $options = [];
        foreach ($newList as $item) {
            $options[] = [
                'value' => $item->ID,
                'label' => $item->$labelField,
            ];
        }
        return $options;
    }

    private function getOptionsForSchema(bool $onlySelected = false): ArrayList
    {
        $options = ArrayList::create();
        if (!$this->sourceList) {
            return $options;
        }
        $values = $this->getValueArray();
        if (empty($values)) {
            $selectedValuesList = ArrayList::create();
        } else {
            $selectedValuesList = $this->sourceList->filterAny(['ID' => $values]);
        }
        // SearchableDropdownField will have the getHasEmptyDefault() method from SingleSelectField
        // Note that SingleSelectField::getSourceEmpty() will not be called for the react-select component
        if (!$onlySelected && method_exists($this, 'getHasEmptyDefault') && $this->getHasEmptyDefault()) {
            // Add an empty option to the start of the list of options
            $options->push(ArrayData::create([
                'value' => 0,
                'label' => $this->getPlaceholder(),
                'selected' => $selectedValuesList->count() === 0
            ]));
        }
        if ($onlySelected) {
            $options = $this->updateOptionsForSchema($options, $selectedValuesList, $selectedValuesList);
        } else {
            $options = $this->updateOptionsForSchema($options, $this->sourceList, $selectedValuesList);
        }
        return $options;
    }

    private function updateDataForSchema(array $data): array
    {
        $selectedOptions = $this->getOptionsForSchema(true);
        $value = $selectedOptions->count() ? $selectedOptions->toNestedArray() : null;
        if (is_null($value)
            && method_exists($this, 'getHasEmptyDefault')
            && !$this->getHasEmptyDefault()
        ) {
            $allOptions = $this->getOptionsForSchema();
            $value = $allOptions->first()?->toMap();
        }
        $data['lazyLoad'] = $this->getIsLazyLoaded();
        $data['clearable'] = $this->getIsClearable();
        $data['multi'] = $this->isMultiple;
        $data['placeholder'] = $this->getPlaceholder();
        $data['searchable'] = $this->getIsSearchable();
        $data['value'] = $value;
        return $data;
    }

    /**
     * @param ArrayList $options The options list being updated that will become <options>
     * @param DataList|ArrayList $items The items to be turned into options
     * @param DataList|ArrayList $values The values that have been selected i.e. the value of the Field
     */
    private function updateOptionsForSchema(
        ArrayList $options,
        DataList|ArrayList $items,
        DataList|ArrayList $selectedValuesList
    ): ArrayList {
        $labelField = $this->getLabelField();
        $selectedIDs = $selectedValuesList->column('ID');
        /** @var DataObject $item */
        foreach ($items as $item) {
            $selected = in_array($item->ID, $selectedIDs);
            $options->push(ArrayData::create([
                'value' => $item->ID,
                'label' => $item->$labelField,
                'selected' => $selected,
            ]));
        }
        return $options;
    }
}
