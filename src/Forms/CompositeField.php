<?php

namespace SilverStripe\Forms;

use SilverStripe\Dev\Debug;

/**
 * Base class for all fields that contain other fields.
 *
 * Implements sequentialisation - so that when we're saving / loading data, we
 * can populate a tabbed form properly. All of the children are stored in
 * $this->children
 */
class CompositeField extends FormField
{

    /**
     * @var FieldList
     */
    protected $children;

    /**
     * Set to true when this field is a readonly field
     *
     * @var bool
     */
    protected $readonly;

    /**
     * @var int Toggle different css-rendering for multiple columns
     * ("onecolumn", "twocolumns", "threecolumns"). The content is determined
     * by the $children-array, so wrap all items you want to have grouped in a
     * column inside a CompositeField.
     * Caution: Please make sure that this variable actually matches the
     * count of your $children.
     */
    protected $columnCount = null;

    /**
     * @var string custom HTML tag to render with, e.g. to produce a <fieldset>.
     */
    protected $tag = 'div';

    /**
     * @var string Optional description for this set of fields.
     * If the {@link $tag} property is set to use a 'fieldset', this will be
     * rendered as a <legend> tag, otherwise its a 'title' attribute.
     */
    protected $legend;

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_STRUCTURAL;

    protected $schemaComponent = 'CompositeField';

    public function __construct($children = null)
    {
        // Normalise $children to a FieldList
        if (!$children instanceof FieldList) {
            if (!is_array($children)) {
                // Fields are provided as a list of arguments
                $children = array_filter(func_get_args());
            }
            $children = new FieldList($children);
        }
        $this->setChildren($children);

        parent::__construct(null, false);
    }

    /**
     * Merge child field data into this form
     */
    public function getSchemaDataDefaults()
    {
        $defaults = parent::getSchemaDataDefaults();
        $children = $this->getChildren();
        if ($children && $children->count()) {
            $childSchema = [];
            foreach ($children as $child) {
                $childSchema[] = $child->getSchemaData();
            }
            $defaults['children'] = $childSchema;
        }

        $defaults['data']['tag'] = $this->getTag();
        $defaults['data']['legend'] = $this->getLegend();

        // Scaffolded children will inherit this data
        $defaults['data']['inherited'] = [
            'data' => [
                'fieldholder' => 'small'
            ],
        ];

        return $defaults;
    }

    /**
     * Returns all the sub-fields, suitable for <% loop FieldList %>
     *
     * @return FieldList
     */
    public function FieldList()
    {
        return $this->children;
    }

    /**
     * Accessor method for $this->children
     *
     * @return FieldList
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Returns the name (ID) for the element.
     * If the CompositeField doesn't have a name, but we still want the ID/name to be set.
     * This code generates the ID from the nested children.
     *
     * @return String $name
     */
    public function getName()
    {
        if ($this->name) {
            return $this->name;
        }

        $fieldList = $this->FieldList();
        $compositeTitle = '';
        $count = 0;
        foreach ($fieldList as $subfield) {
            $compositeTitle .= $subfield->getName();
            if ($subfield->getName()) {
                $count++;
            }
        }
        if ($count === 1) {
            $compositeTitle .= 'Group';
        }
        return preg_replace("/[^a-zA-Z0-9]+/", "", $compositeTitle ?? '');
    }

    /**
     * @param FieldList $children
     * @return $this
     */
    public function setChildren($children)
    {
        $this->children = $children;
        $children->setContainerField($this);
        return $this;
    }

    /**
     * @param string $tag
     * @return $this
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param string $legend
     * @return $this
     */
    public function setLegend($legend)
    {
        $this->legend = $legend;
        return $this;
    }

    /**
     * @return string
     */
    public function getLegend()
    {
        return $this->legend;
    }

    public function extraClass()
    {
        $classes = ['field', 'CompositeField', parent::extraClass()];
        if ($this->columnCount) {
            $classes[] = 'multicolumn';
        }

        return implode(' ', $classes);
    }

    public function getAttributes()
    {
        return array_merge(
            parent::getAttributes(),
            [
                'tabindex' => null,
                'type' => null,
                'value' => null,
                'title' => ($this->tag === 'fieldset') ? null : $this->legend
            ]
        );
    }

    /**
     * Add all of the non-composite fields contained within this field to the
     * list.
     *
     * Sequentialisation is used when connecting the form to its data source
     *
     * @param array $list
     * @param bool $saveableOnly
     */
    public function collateDataFields(&$list, $saveableOnly = false)
    {
        foreach ($this->children as $field) {
            if (! $field instanceof FormField) {
                continue;
            }
            if ($field instanceof CompositeField) {
                $field->collateDataFields($list, $saveableOnly);
            }
            if ($saveableOnly) {
                $isIncluded =  ($field->hasData() && !$field->isReadonly() && !$field->isDisabled());
            } else {
                $isIncluded =  ($field->hasData());
            }
            if ($isIncluded) {
                $name = $field->getName();
                if ($name) {
                    $formName = (isset($this->form)) ? $this->form->FormName() : '(unknown form)';
                    if (isset($list[$name])) {
                        $fieldClass = get_class($field);
                        $otherFieldClass = get_class($list[$name]);
                        throw new \RuntimeException(
                            "collateDataFields() I noticed that a field called '$name' appears twice in"
                             . " your form: '{$formName}'.  One is a '{$fieldClass}' and the other is a"
                             . " '{$otherFieldClass}'"
                        );
                    }
                    $list[$name] = $field;
                }
            }
        }
    }

    public function setForm($form)
    {
        foreach ($this->getChildren() as $field) {
            if ($field instanceof FormField) {
                $field->setForm($form);
            }
        }

        parent::setForm($form);
        return $this;
    }



    public function setDisabled($disabled)
    {
        parent::setDisabled($disabled);
        foreach ($this->getChildren() as $child) {
            $child->setDisabled($disabled);
        }
        return $this;
    }

    public function setReadonly($readonly)
    {
        parent::setReadonly($readonly);
        foreach ($this->getChildren() as $child) {
            $child->setReadonly($readonly);
        }
        return $this;
    }

    public function setColumnCount($columnCount)
    {
        $this->columnCount = $columnCount;
        return $this;
    }

    public function getColumnCount()
    {
        return $this->columnCount;
    }

    public function isComposite()
    {
        return true;
    }

    public function hasData()
    {
        return false;
    }

    public function fieldByName($name)
    {
        return $this->children->fieldByName($name);
    }

    /**
     * Add a new child field to the end of the set.
     *
     * @param FormField $field
     */
    public function push(FormField $field)
    {
        $this->children->push($field);
    }

    /**
     * Add a new child field to the beginning of the set.
     *
     * @param FormField $field
     */
    public function unshift(FormField $field)
    {
        $this->children->unshift($field);
    }

    /**
     * @uses FieldList::insertBefore()
     *
     * @param string $insertBefore
     * @param FormField $field
     * @param bool $appendIfMissing
     * @return false|FormField
     */
    public function insertBefore($insertBefore, $field, $appendIfMissing = true)
    {
        return $this->children->insertBefore($insertBefore, $field, $appendIfMissing);
    }

    /**
     * @uses FieldList::insertAfter()
     * @param string $insertAfter
     * @param FormField $field
     * @param bool $appendIfMissing
     * @return false|FormField
     */
    public function insertAfter($insertAfter, $field, $appendIfMissing = true)
    {
        return $this->children->insertAfter($insertAfter, $field, $appendIfMissing);
    }

    /**
     * Remove a field from this CompositeField by Name.
     * The field could also be inside a CompositeField.
     *
     * @param string $fieldName The name of the field
     * @param boolean $dataFieldOnly If this is true, then a field will only
     * be removed if it's a data field.  Dataless fields, such as tabs, will
     * be left as-is.
     */
    public function removeByName($fieldName, $dataFieldOnly = false)
    {
        $this->children->removeByName($fieldName, $dataFieldOnly);
    }

    /**
     * @param $fieldName
     * @param $newField
     * @param boolean $dataFieldOnly If this is true, then a field will only
     * be replaced if it's a data field.  Dataless fields, such as tabs, will
     * not be considered for replacement.
     * @return bool
     */
    public function replaceField($fieldName, $newField, $dataFieldOnly = true)
    {
        return $this->children->replaceField($fieldName, $newField, $dataFieldOnly);
    }

    public function rootFieldList()
    {
        if (is_object($this->containerFieldList)) {
            return $this->containerFieldList->rootFieldList();
        } else {
            return $this->children;
        }
    }

    public function __clone()
    {
        /** {@see FieldList::__clone(}} */
        $this->setChildren(clone $this->children);
    }

    /**
     * Return a readonly version of this field. Keeps the composition but returns readonly
     * versions of all the child {@link FormField} objects.
     *
     * @return CompositeField
     */
    public function performReadonlyTransformation()
    {
        $newChildren = new FieldList();
        $clone = clone $this;
        if ($clone->getChildren()) {
            foreach ($clone->getChildren() as $child) {
                $child = $child->transform(new ReadonlyTransformation());
                $newChildren->push($child);
            }
        }

        $clone->setChildren($newChildren);
        $clone->setReadonly(true);
        $clone->addExtraClass($this->extraClass());
        $clone->setDescription($this->getDescription());

        return $clone;
    }

    /**
     * Return a disabled version of this field. Keeps the composition but returns disabled
     * versions of all the child {@link FormField} objects.
     *
     * @return CompositeField
     */
    public function performDisabledTransformation()
    {
        $newChildren = new FieldList();
        $clone = clone $this;
        if ($clone->getChildren()) {
            foreach ($clone->getChildren() as $child) {
                $child = $child->transform(new DisabledTransformation());
                $newChildren->push($child);
            }
        }

        $clone->setChildren($newChildren);
        $clone->setDisabled(true);
        $clone->addExtraClass($this->extraClass());
        $clone->setDescription($this->getDescription());
        foreach ($this->attributes as $k => $v) {
            $clone->setAttribute($k, $v);
        }

        return $clone;
    }

    public function IsReadonly()
    {
        return $this->readonly;
    }

    /**
     * Find the numerical position of a field within
     * the children collection. Doesn't work recursively.
     *
     * @param string|FormField $field
     * @return int Position in children collection (first position starts with 0). Returns FALSE if the field can't
     *             be found.
     */
    public function fieldPosition($field)
    {
        if (is_string($field)) {
            $field = $this->fieldByName($field);
        }
        if (!$field) {
            return false;
        }

        $i = 0;
        foreach ($this->children as $child) {
            if ($child->getName() == $field->getName()) {
                return $i;
            }
            $i++;
        }

        return false;
    }

    /**
     * Transform the named field into a readonly field.
     *
     * @param string|FormField $field
     * @return bool
     */
    public function makeFieldReadonly($field)
    {
        $fieldName = ($field instanceof FormField) ? $field->getName() : $field;

        // Iterate on items, looking for the applicable field
        foreach ($this->children as $i => $item) {
            if ($item instanceof CompositeField) {
                if ($item->makeFieldReadonly($fieldName)) {
                    return true;
                };
            } elseif ($item instanceof FormField && $item->getName() == $fieldName) {
                // Once it's found, use FormField::transform to turn the field into a readonly version of itself.
                $this->children->replaceField($fieldName, $item->transform(new ReadonlyTransformation()));

                // A true results indicates that the field was found
                return true;
            }
        }
        return false;
    }

    public function debug()
    {
        $class = static::class;
        $result = "$class ($this->name) <ul>";
        foreach ($this->children as $child) {
            $result .= "<li>" . Debug::text($child) . "&nbsp;</li>";
        }
        $result .= "</ul>";
        return $result;
    }

    /**
     * Validate this field
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        $valid = true;
        foreach ($this->children as $child) {
            $valid = ($child && $child->validate($validator) && $valid);
        }
        return $this->extendValidationResult($valid, $validator);
    }
}
