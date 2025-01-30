<?php

namespace SilverStripe\ORM\Search;

use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\SSViewer;

/**
 * A form for using a search context in the CMS.
 *
 * Note that the form submission goes through to the index action of the controller by default,
 * and is intended to return the same markup you would ordinarily get for that controller but with
 * the results filtered appropriately.
 */
class SearchContextForm extends Form
{
    private SearchContext $searchContext;

    private ?string $searchField = null;

    private static $casting = [
        'SchemaData' => 'Text',
        'getSchemaData' => 'Text',
    ];

    public function __construct(RequestHandler $controller, SearchContext $searchContext, $name = 'SearchForm')
    {
        $this->searchContext = $searchContext;
        // Need to clone the fields so when we update their names we don't alter the search context fields.
        $searchFields = clone $searchContext->getSearchFields();
        $this->setDefaultSearchField($searchFields, $controller);
        $this->prepareSearchFieldsForCMS($searchFields);
        parent::__construct($controller, $name, $searchFields);
        $this->setHTMLID(ClassInfo::shortName($controller) . '_' . $name);
        // This form is not tied to session so we disable the CSRF token
        $this->disableSecurityToken();
        $this->setFormMethod('GET');
        $this->setFormAction($controller->Link());
        $this->addExtraClass('cms-search-form');

        // Load the form with previously set search data if any
        $this->loadDataFrom($searchContext->getSearchParams());
    }

    public function setSearchField(string $fieldName): static
    {
        $this->searchField = $fieldName;
        return $this;
    }

    public function getSearchField(): string
    {
        return $this->searchField;
    }

    /**
     * Get the schema for this search context for use in the CMS search filter form.
     * Note that this is not the same as the form schema (i.e. it includes no information about form fields).
     */
    public function getSchemaData(): array
    {
        $formSchemaUrl = $this->getRequestHandler()->Link('schema');
        $singleton = singleton($this->searchContext->getModelClass());

        // Prefix "Search__" onto the search params to match the field names in the actual form
        $searchParams = $this->searchContext->getSearchParams();
        if (!empty($searchParams)) {
            $searchParams = array_combine(array_map(function ($key) {
                return 'Search__' . $key;
            }, array_keys($searchParams ?? [])), $searchParams ?? []);
        }

        $schema = [
            'formSchemaUrl' => $formSchemaUrl,
            'name' => $this->getSearchField(),
            // stdClass will map to empty json object '{}' when encoded
            'filters' => $searchParams ?: new \stdClass()
        ];

        // GridField has some special requirements to ensure the "state" of the filter header is updated
        $parentRequestHandler = $this->getController();
        if ($parentRequestHandler instanceof GridField) {
            $searchAction = GridField_FormAction::create($parentRequestHandler, 'filter', false, 'filter', null);
            $clearAction = GridField_FormAction::create($parentRequestHandler, 'reset', false, 'reset', null);
            $schema = array_merge($schema, [
                'gridfield' => $parentRequestHandler->getName(),
                'searchAction' => $searchAction->getAttribute('name'),
                'clearAction' => $clearAction->getAttribute('name'),
            ]);
            $filterHeader = $parentRequestHandler->getConfig()->getComponentByType(GridFieldFilterHeader::class);
            if ($filterHeader) {
                $placeholder = $filterHeader->getPlaceHolderText();
                if ($placeholder) {
                    $schema['placeholder'] = $placeholder;
                }
            }
        }

        if (!isset($schema['placeholder'])) {
            $schema['placeholder'] = _t(
                __CLASS__ . '.FILTERLABELTEXT',
                'Search "{model}"',
                ['model' => ClassInfo::hasMethod($singleton, 'i18n_plural_name') ? $singleton->i18n_plural_name() : ClassInfo::shortName($singleton)]
            );
        }

        return $schema;
    }

    /**
     * Get the rendered placeholder with schema data required for generating the search filter form in the CMS
     */
    public function getPlaceHolder(bool $isFiltered): DBHTMLText
    {
        $templateCandidates = SSViewer::get_templates_by_class(static::class, '_Placeholder', __CLASS__);
        return $this->renderWith($templateCandidates, ['IsFiltered' => $isFiltered]);
    }

    /**
     * Get the rendered search filter button which toggles display of the search filter form in the CMS
     */
    public function getFilterButton(bool $isFiltered): DBHTMLText
    {
        $templateCandidates = SSViewer::get_templates_by_class(static::class, '_Button', __CLASS__);
        return $this->renderWith($templateCandidates, ['IsFiltered' => $isFiltered]);
    }

    /**
     * Takes raw values submitted e.g. via AJAX and pulls them through FormField instances.
     * This allows form fields such as CurrencyField to make any relevant adjustments prior to using
     * the values in the SearchContext database comparisons.
     */
    public function prepareValuesForSearchContext(array $values): array
    {
        // Use a close so we don't alter the value of fields in the current form
        $form = clone $this;
        $this->removeSearchPrefixFromFields($form->Fields());
        $form->loadDataFrom($values);

        // Get the values from the form where possible, as form fields may transform the value for DB comparisons
        foreach ($values as $fieldName => $rawFilterValue) {
            $values[$fieldName] = $form->Fields()->dataFieldByName($fieldName)?->dataValue() ?? $rawFilterValue;
        }

        return $values;
    }

    /**
     * Sets the default general_search_field based on configuration or the first available form field.
     * Must be called before prepareSearchFieldsForCMS() which alters the field names.
     */
    private function setDefaultSearchField(FieldList $fields, RequestHandler $controller): void
    {
        $searchField = null;
        if ($controller instanceof GridField) {
            // Check for a specific search field via gridfield
            $filterHeader = $controller->getConfig()->getComponentByType(GridFieldFilterHeader::class);
            if ($filterHeader) {
                $searchField = $filterHeader->getSearchField();
            }
        }
        if (!$searchField) {
            // Check for a general search field on the model
            $modelClass = $this->searchContext->getModelClass();
            $searchField = $modelClass::config()->get('general_search_field');
        }
        if (!$searchField) {
            // Fall back on the first defined FormField
            $searchField = $fields->first();
            $searchField = $searchField && property_exists($searchField, 'name') ? $searchField->name : '';
        }
        $this->setSearchField($searchField ?? '');
    }

    /**
     * Prepares search fields for use in the CMS.
     *
     * - Appends a prefix to search field names to prevent conflicts with other fields in the search form
     * - Adds appropriate CSS classes
     */
    private function prepareSearchFieldsForCMS(FieldList $fields): void
    {
        foreach ($fields as $field) {
            $field->addExtraClass('stacked');
            $field->setName('Search__' . $field->getName());
            if ($field instanceof CompositeField) {
                $this->prepareSearchFieldsForCMS($field->getChildren());
            }
        }
    }

    /**
     * Remove the Search__ prefix from form field names so we can match them directly with submitted fields.
     */
    private function removeSearchPrefixFromFields(FieldList $fields): void
    {
        foreach ($fields as $field) {
            $field->setName(str_replace('Search__', '', $field->getName()));
            if ($field instanceof CompositeField) {
                $this->removeSearchPrefixFromFields($field->getChildren());
            }
        }
    }
}
