<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\Filters\SearchFilter;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;
use LogicException;
use SilverStripe\Control\HTTPResponse_Exception;

/**
 * This class is is responsible for adding objects to another object's has_many
 * and many_many relation, as defined by the {@link RelationList} passed to the
 * {@link GridField} constructor.
 *
 * Objects can be searched through an input field (partially matching one or
 * more fields).
 *
 * Selecting from the results will add the object to the relation.
 *
 * Often used alongside {@link GridFieldDeleteAction} for detaching existing
 * records from a relationship.
 *
 * For easier setup, have a look at a sample configuration in
 * {@link GridFieldConfig_RelationEditor}.
 *
 * The modelClass of the GridField this component is in must be a DataObject subclass.
 */
class GridFieldAddExistingAutocompleter extends AbstractGridFieldComponent implements GridField_HTMLProvider, GridField_ActionProvider, GridField_DataManipulator, GridField_URLHandler
{

    /**
     * The HTML fragment to write this component into
     */
    protected $targetFragment;

    /**
     * @var SS_List
     */
    protected $searchList;

    /**
     * Define column names which should be included in the search.
     * By default, they're searched with a {@link StartsWithFilter}.
     * To define custom filters, use the same notation as {@link DataList->filter()},
     * e.g. "Name:EndsWith".
     *
     * If multiple fields are provided, the filtering is performed non-exclusive.
     * If no fields are provided, tries to auto-detect fields from
     * {@link DataObject->searchableFields()}.
     *
     * The fields support "dot-notation" for relationships, e.g.
     * a entry called "Team.Name" will search through the names of
     * a "Team" relationship.
     *
     * @example
     *  array(
     *      'Name',
     *      'Email:StartsWith',
     *      'Team.Name'
     *  )
     *
     * @var array
     */
    protected $searchFields = [];

    /**
     * @var string SSViewer template to render the results presentation
     */
    protected $resultsFormat = '$Title';

    /**
     * @var string Text shown on the search field, instructing what to search for.
     */
    protected $placeholderText;

    /**
     * @var int
     */
    protected $resultsLimit = 20;

    /**
     *
     * @param string $targetFragment
     * @param array $searchFields Which fields on the object in the list should be searched
     */
    public function __construct($targetFragment = 'before', $searchFields = null)
    {
        $this->targetFragment = $targetFragment;
        $this->searchFields = (array)$searchFields;
    }

    /**
     *
     * @param GridField $gridField
     * @return string[] - HTML
     */
    public function getHTMLFragments($gridField)
    {
        $dataClass = $gridField->getModelClass();

        if (!is_a($dataClass, DataObject::class, true)) {
            throw new LogicException(__CLASS__ . " must be used with DataObject subclasses. Found '$dataClass'");
        }

        $forTemplate = new ArrayData([]);
        $forTemplate->Fields = new FieldList();

        $searchField = new TextField('gridfield_relationsearch', _t('SilverStripe\\Forms\\GridField\\GridField.RelationSearch', "Relation search"));

        $searchField->setAttribute('data-search-url', Controller::join_links($gridField->Link('search')));
        $searchField->setAttribute('placeholder', $this->getPlaceholderText($dataClass));
        $searchField->addExtraClass('relation-search no-change-track action_gridfield_relationsearch');

        $findAction = new GridField_FormAction(
            $gridField,
            'gridfield_relationfind',
            _t('SilverStripe\\Forms\\GridField\\GridField.Find', "Find"),
            'find',
            'find'
        );
        $findAction->setAttribute('data-icon', 'relationfind');
        $findAction->addExtraClass('action_gridfield_relationfind');

        $addAction = new GridField_FormAction(
            $gridField,
            'gridfield_relationadd',
            _t('SilverStripe\\Forms\\GridField\\GridField.LinkExisting', "Link Existing"),
            'addto',
            'addto'
        );
        $addAction->setAttribute('data-icon', 'chain--plus');
        $addAction->addExtraClass('btn btn-outline-secondary font-icon-link action_gridfield_relationadd');

        // If an object is not found, disable the action
        if (!is_int($gridField->State->GridFieldAddRelation(null))) {
            $addAction->setDisabled(true);
        }

        $forTemplate->Fields->push($searchField);
        $forTemplate->Fields->push($findAction);
        $forTemplate->Fields->push($addAction);
        if ($form = $gridField->getForm()) {
            $forTemplate->Fields->setForm($form);
        }

        $template = SSViewer::get_templates_by_class($this, '', __CLASS__);
        return [
            $this->targetFragment => $forTemplate->renderWith($template)
        ];
    }

    /**
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        return ['addto', 'find'];
    }

    /**
     * Manipulate the state to add a new relation
     *
     * @param GridField $gridField
     * @param string $actionName Action identifier, see {@link getActions()}.
     * @param array $arguments Arguments relevant for this
     * @param array $data All form data
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        switch ($actionName) {
            case 'addto':
                if (isset($data['relationID']) && $data['relationID']) {
                    $gridField->State->GridFieldAddRelation = $data['relationID'];
                }
                break;
        }
    }

    /**
     * If an object ID is set, add the object to the list
     *
     * @param GridField $gridField
     * @param SS_List $dataList
     * @return SS_List
     */
    public function getManipulatedData(GridField $gridField, SS_List $dataList)
    {
        $dataClass = $gridField->getModelClass();

        if (!is_a($dataClass, DataObject::class, true)) {
            throw new LogicException(__CLASS__ . " must be used with DataObject subclasses. Found '$dataClass'");
        }

        $objectID = $gridField->State->GridFieldAddRelation(null);
        if (empty($objectID)) {
            return $dataList;
        }
        $gridField->State->GridFieldAddRelation = null;
        $object = DataObject::get_by_id($dataClass, $objectID);
        if ($object) {
            if (!$object->canView()) {
                throw new HTTPResponse_Exception(null, 403);
            }
            $dataList->add($object);
        }
        return $dataList;
    }

    /**
     *
     * @param GridField $gridField
     * @return array
     */
    public function getURLHandlers($gridField)
    {
        return [
            'search' => 'doSearch',
        ];
    }

    /**
     * Returns a json array of a search results that can be used by for example Jquery.ui.autosuggestion
     *
     * @param GridField $gridField
     * @param HTTPRequest $request
     * @return string
     */
    public function doSearch($gridField, $request)
    {
        $searchStr = $request->getVar('gridfield_relationsearch');
        $dataClass = $gridField->getModelClass();

        if (!is_a($dataClass, DataObject::class, true)) {
            throw new LogicException(__CLASS__ . " must be used with DataObject subclasses. Found '$dataClass'");
        }

        $searchFields = ($this->getSearchFields())
            ? $this->getSearchFields()
            : $this->scaffoldSearchFields($dataClass);
        if (!$searchFields) {
            throw new LogicException(
                sprintf(
                    'GridFieldAddExistingAutocompleter: No searchable fields could be found for class "%s"',
                    $dataClass
                )
            );
        }

        $params = [];
        foreach ($searchFields as $searchField) {
            $name = (strpos($searchField ?? '', ':') !== false) ? $searchField : "$searchField:StartsWith";
            $params[$name] = $searchStr;
        }

        $results = null;
        if ($this->searchList) {
            // Assume custom sorting, don't apply default sorting
            $results = $this->searchList;
        } else {
            $results = DataList::create($dataClass)
                ->sort(strtok($searchFields[0] ?? '', ':'), 'ASC');
        }

        // Apply baseline filtering and limits which should hold regardless of any customisations
        $results = $results
            ->subtract($gridField->getList())
            ->filterAny($params)
            ->limit($this->getResultsLimit());

        $json = [];
        Config::nest();
        SSViewer::config()->set('source_file_comments', false);
        $viewer = SSViewer::fromString($this->resultsFormat);
        foreach ($results as $result) {
            if (!$result->canView()) {
                continue;
            }
            $title = Convert::html2raw($viewer->process($result));
            $json[] = [
                'label' => $title,
                'value' => $title,
                'id' => $result->ID,
            ];
        }
        Config::unnest();
        $response = new HTTPResponse(json_encode($json));
        $response->addHeader('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @param string $format
     *
     * @return $this
     */
    public function setResultsFormat($format)
    {
        $this->resultsFormat = $format;
        return $this;
    }

    /**
     * @return string
     */
    public function getResultsFormat()
    {
        return $this->resultsFormat;
    }

    /**
     * Sets the base list instance which will be used for the autocomplete
     * search.
     *
     * @param SS_List $list
     */
    public function setSearchList(SS_List $list)
    {
        $this->searchList = $list;
        return $this;
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function setSearchFields($fields)
    {
        $this->searchFields = $fields;
        return $this;
    }

    /**
     * @return array
     */
    public function getSearchFields()
    {
        return $this->searchFields;
    }

    /**
     * Detect searchable fields and searchable relations.
     * Falls back to {@link DataObject->summaryFields()} if
     * no custom search fields are defined.
     *
     * @param string $dataClass The class name
     * @return array|null names of the searchable fields
     */
    public function scaffoldSearchFields($dataClass)
    {
        if (!is_a($dataClass, DataObject::class, true)) {
            throw new LogicException(__CLASS__ . " must be used with DataObject subclasses. Found '$dataClass'");
        }

        $obj = DataObject::singleton($dataClass);
        $fields = null;
        if ($fieldSpecs = $obj->searchableFields()) {
            $customSearchableFields = $obj->config()->get('searchable_fields');
            foreach ($fieldSpecs as $name => $spec) {
                if (is_array($spec) && array_key_exists('filter', $spec ?? [])) {
                    // The searchableFields() spec defaults to PartialMatch,
                    // so we need to check the original setting.
                    // If the field is defined $searchable_fields = array('MyField'),
                    // then default to StartsWith filter, which makes more sense in this context.
                    if (!$customSearchableFields || array_search($name, $customSearchableFields ?? []) !== false) {
                        $filter = 'StartsWith';
                    } else {
                        $filterName = $spec['filter'];
                        // It can be an instance
                        if ($filterName instanceof SearchFilter) {
                            $filterName = get_class($filterName);
                        }
                        // It can be a fully qualified class name
                        if (strpos($filterName ?? '', '\\') !== false) {
                            $filterNameParts = explode("\\", $filterName ?? '');
                            // We expect an alias matching the class name without namespace, see #coresearchaliases
                            $filterName = array_pop($filterNameParts);
                        }
                        $filter = preg_replace('/Filter$/', '', $filterName ?? '');
                    }
                    $fields[] = "{$name}:{$filter}";
                } else {
                    $fields[] = $name;
                }
            }
        }
        if (is_null($fields)) {
            if ($obj->hasDatabaseField('Title')) {
                $fields = ['Title'];
            } elseif ($obj->hasDatabaseField('Name')) {
                $fields = ['Name'];
            }
        }

        return $fields;
    }

    /**
     * @param string $dataClass The class of the object being searched for
     *
     * @return string
     */
    public function getPlaceholderText($dataClass)
    {
        if (!is_a($dataClass, DataObject::class, true)) {
            throw new LogicException(__CLASS__ . " must be used with DataObject subclasses. Found '$dataClass'");
        }

        $searchFields = ($this->getSearchFields())
            ? $this->getSearchFields()
            : $this->scaffoldSearchFields($dataClass);

        if ($this->placeholderText) {
            return $this->placeholderText;
        } else {
            $labels = [];
            if ($searchFields) {
                foreach ($searchFields as $searchField) {
                    $searchField = explode(':', $searchField ?? '');
                    $label = singleton($dataClass)->fieldLabel($searchField[0]);
                    if ($label) {
                        $labels[] = $label;
                    }
                }
            }
            if ($labels) {
                return _t(
                    'SilverStripe\\Forms\\GridField\\GridField.PlaceHolderWithLabels',
                    'Find {type} by {name}',
                    ['type' => singleton($dataClass)->i18n_plural_name(), 'name' => implode(', ', $labels)]
                );
            } else {
                return _t(
                    'SilverStripe\\Forms\\GridField\\GridField.PlaceHolder',
                    'Find {type}',
                    ['type' => singleton($dataClass)->i18n_plural_name()]
                );
            }
        }
    }

    /**
     * @param string $text
     *
     * @return $this
     */
    public function setPlaceholderText($text)
    {
        $this->placeholderText = $text;
        return $this;
    }

    /**
     * Gets the maximum number of autocomplete results to display.
     *
     * @return int
     */
    public function getResultsLimit()
    {
        return $this->resultsLimit;
    }

    /**
     * @param int $limit
     *
     * @return $this
     */
    public function setResultsLimit($limit)
    {
        $this->resultsLimit = $limit;
        return $this;
    }
}
