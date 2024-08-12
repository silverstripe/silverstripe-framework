<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\ORM\DataObject;

/**
 * @uses DBField::scaffoldFormField()
 * @uses DataObject::fieldLabels()
 */
class FormScaffolder
{
    use Injectable;

    /**
     * @var DataObject $obj The object defining the fields to be scaffolded
     * through its metadata like $db, $searchable_fields, etc.
     */
    protected $obj;

    /**
     * @var boolean $tabbed Return fields in a tabset, with all main fields in the path "Root.Main",
     * relation fields in "Root.<relationname>" (if {@link $includeRelations} is enabled).
     */
    public $tabbed = false;

    /**
     * Only set up the "Root.Main" tab, but skip scaffolding actual FormFields.
     * If $tabbed is false, an empty FieldList will be returned.
     */
    public bool $mainTabOnly = false;

    /**
     * @var boolean $ajaxSafe
     * @deprecated 5.3.0 Will be removed without equivalent functionality.
     */
    public $ajaxSafe = false;

    /**
     * @var array $restrictFields Numeric array of a field name whitelist.
     * If left blank, all fields from {@link DataObject->db()} will be included.
     */
    public $restrictFields;

    /**
     * Numeric array of field names and has_one relations to explicitly not scaffold.
     */
    public array $ignoreFields = [];

    /**
     * @var array $fieldClasses Optional mapping of fieldnames to subclasses of {@link FormField}.
     * By default the scaffolder will determine the field instance by {@link DBField::scaffoldFormField()}.
     */
    public $fieldClasses;

    /**
     * @var boolean $includeRelations Include has_many and many_many relations
     */
    public $includeRelations = false;

    /**
     * Array of relation names to use as an allow list.
     * If left blank, all has_many and many_many relations will be scaffolded unless explicitly ignored.
     */
    public array $restrictRelations = [];

    /**
     * Numeric array of has_many and many_many relations to explicitly not scaffold.
     */
    public array $ignoreRelations = [];

    /**
     * @param DataObject $obj
     */
    public function __construct($obj)
    {
        $this->obj = $obj;
    }

    /**
     * Gets the form fields as defined through the metadata
     * on {@link $obj} and the custom parameters passed to FormScaffolder.
     * Depending on those parameters, the fields can be used in ajax-context,
     * contain {@link TabSet}s etc.
     *
     * @return FieldList
     */
    public function getFieldList()
    {
        $fields = new FieldList();

        // tabbed or untabbed
        if ($this->tabbed) {
            $fields->push(new TabSet("Root", $mainTab = new Tab("Main")));
            $mainTab->setTitle(_t(__CLASS__ . '.TABMAIN', 'Main'));
        }

        if ($this->mainTabOnly) {
            return $fields;
        }

        // Add logical fields directly specified in db config
        foreach ($this->obj->config()->get('db') as $fieldName => $fieldType) {
            // Skip fields that aren't in the allow list
            if ($this->restrictFields && !in_array($fieldName, $this->restrictFields ?? [])) {
                continue;
            }
            // Skip ignored fields
            if (in_array($fieldName, $this->ignoreFields)) {
                continue;
            }

            if ($this->fieldClasses && isset($this->fieldClasses[$fieldName])) {
                $fieldClass = $this->fieldClasses[$fieldName];
                $fieldObject = new $fieldClass($fieldName);
            } else {
                $fieldObject = $this
                    ->obj
                    ->dbObject($fieldName)
                    ->scaffoldFormField(null, $this->getParamsArray());
            }
            // Allow fields to opt-out of scaffolding
            if (!$fieldObject) {
                continue;
            }
            $fieldObject->setTitle($this->obj->fieldLabel($fieldName));
            if ($this->tabbed) {
                $fields->addFieldToTab("Root.Main", $fieldObject);
            } else {
                $fields->push($fieldObject);
            }
        }

        // add has_one relation fields
        if ($this->obj->hasOne()) {
            foreach ($this->obj->hasOne() as $relationship => $component) {
                if ($this->restrictFields && !in_array($relationship, $this->restrictFields ?? [])) {
                    continue;
                }
                if (in_array($relationship, $this->ignoreFields)) {
                    continue;
                }
                $fieldName = $component === 'SilverStripe\\ORM\\DataObject'
                    ? $relationship // Polymorphic has_one field is composite, so don't refer to ID subfield
                    : "{$relationship}ID";
                if ($this->fieldClasses && isset($this->fieldClasses[$fieldName])) {
                    $fieldClass = $this->fieldClasses[$fieldName];
                    $hasOneField = new $fieldClass($fieldName);
                } else {
                    $hasOneField = $this->obj->dbObject($fieldName)->scaffoldFormField(null, $this->getParamsArray());
                }
                if (empty($hasOneField)) {
                    continue; // Allow fields to opt out of scaffolding
                }
                $hasOneField->setTitle($this->obj->fieldLabel($relationship));
                if ($this->tabbed) {
                    $fields->addFieldToTab("Root.Main", $hasOneField);
                } else {
                    $fields->push($hasOneField);
                }
            }
        }

        // only add relational fields if an ID is present
        if ($this->obj->ID) {
            // add has_many relation fields
            if ($this->obj->hasMany()
                && ($this->includeRelations === true || isset($this->includeRelations['has_many']))
            ) {
                foreach ($this->obj->hasMany() as $relationship => $component) {
                    if (!empty($this->restrictRelations) && !in_array($relationship, $this->restrictRelations)) {
                        continue;
                    }
                    if (in_array($relationship, $this->ignoreRelations)) {
                        continue;
                    }
                    $includeInOwnTab = true;
                    $fieldLabel = $this->obj->fieldLabel($relationship);
                    $fieldClass = (isset($this->fieldClasses[$relationship]))
                        ? $this->fieldClasses[$relationship]
                        : null;
                    if ($fieldClass) {
                        /** @var GridField */
                        $hasManyField = Injector::inst()->create(
                            $fieldClass,
                            $relationship,
                            $fieldLabel,
                            $this->obj->$relationship(),
                            GridFieldConfig_RelationEditor::create()
                        );
                    } else {
                        /** @var DataObject */
                        $hasManySingleton = singleton($component);
                        $hasManyField = $hasManySingleton->scaffoldFormFieldForHasMany($relationship, $fieldLabel, $this->obj, $includeInOwnTab);
                    }
                    if ($this->tabbed) {
                        if ($includeInOwnTab) {
                            $fields->findOrMakeTab(
                                "Root.$relationship",
                                $fieldLabel
                            );
                            $fields->addFieldToTab("Root.$relationship", $hasManyField);
                        } else {
                            $fields->addFieldToTab('Root.Main', $hasManyField);
                        }
                    } else {
                        $fields->push($hasManyField);
                    }
                }
            }

            if ($this->obj->manyMany()
                && ($this->includeRelations === true || isset($this->includeRelations['many_many']))
            ) {
                foreach ($this->obj->manyMany() as $relationship => $component) {
                    if (!empty($this->restrictRelations) && !in_array($relationship, $this->restrictRelations)) {
                        continue;
                    }
                    if (in_array($relationship, $this->ignoreRelations)) {
                        continue;
                    }
                    static::addManyManyRelationshipFields(
                        $fields,
                        $relationship,
                        (isset($this->fieldClasses[$relationship]))
                            ? $this->fieldClasses[$relationship] : null,
                        $this->tabbed,
                        $this->obj
                    );
                }
            }
        }

        return $fields;
    }

    /**
     * Adds the default many-many relation fields for the relationship provided.
     *
     * @param FieldList $fields Reference to the @FieldList to add fields to.
     * @param string $relationship The relationship identifier.
     * @param string|null $overrideFieldClass Specify the field class to use here or leave as null to use default.
     * @param bool $tabbed Whether this relationship has it's own tab or not.
     * @param DataObject $dataObject The @DataObject that has the relation.
     */
    public static function addManyManyRelationshipFields(
        FieldList &$fields,
        $relationship,
        $overrideFieldClass,
        $tabbed,
        DataObject $dataObject
    ) {
        $includeInOwnTab = true;
        $fieldLabel = $dataObject->fieldLabel($relationship);

        if ($overrideFieldClass) {
            /** @var GridField */
            $manyManyField = Injector::inst()->create(
                $overrideFieldClass,
                $relationship,
                $fieldLabel,
                $dataObject->$relationship(),
                GridFieldConfig_RelationEditor::create()
            );
        } else {
            $manyManyComponent = DataObject::getSchema()->manyManyComponent(get_class($dataObject), $relationship);
            /** @var DataObject */
            $manyManySingleton = singleton($manyManyComponent['childClass']);
            $manyManyField = $manyManySingleton->scaffoldFormFieldForManyMany($relationship, $fieldLabel, $dataObject, $includeInOwnTab);
        }

        if ($tabbed) {
            if ($includeInOwnTab) {
                $fields->findOrMakeTab(
                    "Root.$relationship",
                    $fieldLabel
                );
                $fields->addFieldToTab("Root.$relationship", $manyManyField);
            } else {
                $fields->addFieldToTab('Root.Main', $manyManyField);
            }
        } else {
            $fields->push($manyManyField);
        }
    }

    /**
     * Return an array suitable for passing on to {@link DBField->scaffoldFormField()}
     * without tying this call to a FormScaffolder interface.
     *
     * @return array
     */
    protected function getParamsArray()
    {
        return [
            'tabbed' => $this->tabbed,
            'mainTabOnly' => $this->mainTabOnly,
            'includeRelations' => $this->includeRelations,
            'restrictRelations' => $this->restrictRelations,
            'ignoreRelations' => $this->ignoreRelations,
            'restrictFields' => $this->restrictFields,
            'ignoreFields' => $this->ignoreFields,
            'fieldClasses' => $this->fieldClasses,
            'ajaxSafe' => $this->ajaxSafe
        ];
    }
}
