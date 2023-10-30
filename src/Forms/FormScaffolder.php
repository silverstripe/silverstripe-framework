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
     * @var boolean $ajaxSafe
     */
    public $ajaxSafe = false;

    /**
     * @var array $restrictFields Numeric array of a field name whitelist.
     * If left blank, all fields from {@link DataObject->db()} will be included.
     */
    public $restrictFields;

    /**
     * @var array $fieldClasses Optional mapping of fieldnames to subclasses of {@link FormField}.
     * By default the scaffolder will determine the field instance by {@link DBField::scaffoldFormField()}.
     */
    public $fieldClasses;

    /**
     * @var boolean $includeRelations Include has_one, has_many and many_many relations
     */
    public $includeRelations = false;

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

        // Add logical fields directly specified in db config
        foreach ($this->obj->config()->get('db') as $fieldName => $fieldType) {
            // Skip restricted fields
            if ($this->restrictFields && !in_array($fieldName, $this->restrictFields ?? [])) {
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
                    if ($this->tabbed) {
                        $fields->findOrMakeTab(
                            "Root.$relationship",
                            $this->obj->fieldLabel($relationship)
                        );
                    }
                    $fieldClass = (isset($this->fieldClasses[$relationship]))
                        ? $this->fieldClasses[$relationship]
                        : 'SilverStripe\\Forms\\GridField\\GridField';
                    /** @var GridField $grid */
                    $grid = Injector::inst()->create(
                        $fieldClass,
                        $relationship,
                        $this->obj->fieldLabel($relationship),
                        $this->obj->$relationship(),
                        GridFieldConfig_RelationEditor::create()
                    );
                    if ($this->tabbed) {
                        $fields->addFieldToTab("Root.$relationship", $grid);
                    } else {
                        $fields->push($grid);
                    }
                }
            }

            if ($this->obj->manyMany()
                && ($this->includeRelations === true || isset($this->includeRelations['many_many']))
            ) {
                foreach ($this->obj->manyMany() as $relationship => $component) {
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
     * @param mixed $overrideFieldClass Specify the field class to use here or leave as null to use default.
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
        if ($tabbed) {
            $fields->findOrMakeTab(
                "Root.$relationship",
                $dataObject->fieldLabel($relationship)
            );
        }

        $fieldClass = $overrideFieldClass ?: GridField::class;

        /** @var GridField $grid */
        $grid = Injector::inst()->create(
            $fieldClass,
            $relationship,
            $dataObject->fieldLabel($relationship),
            $dataObject->$relationship(),
            GridFieldConfig_RelationEditor::create()
        );

        if ($tabbed) {
            $fields->addFieldToTab("Root.$relationship", $grid);
        } else {
            $fields->push($grid);
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
            'includeRelations' => $this->includeRelations,
            'restrictFields' => $this->restrictFields,
            'fieldClasses' => $this->fieldClasses,
            'ajaxSafe' => $this->ajaxSafe
        ];
    }
}
