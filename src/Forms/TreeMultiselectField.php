<?php

namespace SilverStripe\Forms;

use http\Exception\InvalidArgumentException;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\Relation;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Group;
use SilverStripe\View\ViewableData;

/**
 * This formfield represents many-many joins using a tree selector shown in a dropdown styled element
 * which can be added to any form usually in the CMS.
 *
 * This form class allows you to represent Many-Many Joins in a handy single field. The field has javascript which
 * generates a AJAX tree of the site structure allowing you to save selected options to a component set on a given
 * {@link DataObject}.
 *
 * <b>Saving</b>
 *
 * This field saves a {@link ComponentSet} object which is present on the {@link DataObject} passed by the form,
 * returned by calling a function with the same name as the field. The Join is updated by running setByIDList on the
 * {@link ComponentSet}
 *
 * <b>Customizing Save Behaviour</b>
 *
 * Before the data is saved, you can modify the ID list sent to the {@link ComponentSet} by specifying a function on
 * the {@link DataObject} called "onChange[fieldname](&items)". This will be passed by reference the IDlist (an array
 * of ID's) from the Treefield to be saved to the component set.
 *
 * Returning false on this method will prevent treemultiselect from saving to the {@link ComponentSet} of the given
 * {@link DataObject}
 *
 * <code>
 * // Called when we try and set the Parents() component set
 * // by Tree Multiselect Field in the administration.
 * function onChangeParents(&$items) {
 *  // This ensures this DataObject can never be a parent of itself
 *  if($items){
 *      foreach($items as $k => $id){
 *          if($id == $this->ID){
 *              unset($items[$k]);
 *          }
 *      }
 *  }
 *  return true;
 * }
 * </code>
 *
 * @see TreeDropdownField for the sample implementation, but only allowing single selects
 */
class TreeMultiselectField extends TreeDropdownField
{
    public function __construct(
        $name,
        $title = null,
        $sourceObject = Group::class,
        $keyField = "ID",
        $labelField = "Title"
    ) {
        parent::__construct($name, $title, $sourceObject, $keyField, $labelField);
        $this->removeExtraClass('single');
        $this->addExtraClass('multiple');
        $this->value = 'unchanged';
    }

    public function getSchemaDataDefaults()
    {
        $data = parent::getSchemaDataDefaults();

        $data['data'] = array_merge($data['data'], [
            'hasEmptyDefault' => false,
            'multiple' => true,
        ]);
        return $data;
    }

    public function getSchemaStateDefaults()
    {
        $data = parent::getSchemaStateDefaults();
        unset($data['data']['valueObject']);

        $items = $this->getItems();
        $values = [];
        foreach ($items as $item) {
            if ($item instanceof DataObject) {
                $values[] = [
                    'id' => $item->obj($this->getKeyField())->getValue(),
                    'title' => $item->obj($this->getTitleField())->getValue(),
                    'parentid' => $item->ParentID,
                    'treetitle' => $item->obj($this->getLabelField())->getSchemaValue(),
                ];
            } else {
                $values[] = $item;
            }
        }
        $data['data']['valueObjects'] = $values;

        // cannot rely on $this->value as this could be a many-many relationship
        $value = array_column($values ?? [], 'id');
        if ($value) {
            sort($value);
            $data['value'] = $value;
        } else {
            $data['value'] = 'unchanged';
        }

        return $data;
    }

    /**
     * Return this field's linked items
     * @return ArrayList|DataList $items
     */
    public function getItems()
    {
        $value = $this->Value();

        // If unchanged, load from record
        if ($value === 'unchanged') {
            // Verify a form exists
            $form = $this->getForm();
            if (!$form) {
                return ArrayList::create();
            }

            // Verify this form has an attached record with the necessary relation
            $fieldName = $this->getName();
            $record = $form->getRecord();
            if ($record instanceof DataObject && $record->hasMethod($fieldName)) {
                return $record->$fieldName();
            }

            // No relation on parent record found
            return ArrayList::create();
        }

        // Value is a list
        if ($value instanceof SS_List) {
            return $value;
        }

        // Parse ids from value string / array
        $ids = [];

        if (is_string($value)) {
            $ids = preg_split("#\s*,\s*#", trim($value ?? ''));
        } elseif (is_array($value)) {
            $ids = array_values($value ?? []);
        }

        // Filter out empty strings
        $ids = array_filter($ids ?? []);

        // No value
        if (empty($ids)) {
            return ArrayList::create();
        }

        // Query source records by value field
        return DataObject::get($this->getSourceObject())
            ->filter($this->getKeyField(), $ids);
    }

    public function setValue($value, $source = null)
    {
        // If loading from a dataobject, get items by relation
        if ($source instanceof DataObject) {
            $name = $this->getName();
            if ($source->hasMethod($name)) {
                $value = $source->$name();
            }
        }

        // Handle legacy value; form-submitted `unchanged` implies empty set.
        // See TreeDropdownField.js
        if ($value === 'unchanged') {
            $value = [];
        }

        return parent::setValue($value);
    }

    public function dataValue()
    {
        return $this->getItems()->column($this->getKeyField());
    }

    /**
     * We overwrite the field attribute to add our hidden fields, as this
     * formfield can contain multiple values.
     *
     * @param array $properties
     * @return DBHTMLText
     */
    public function Field($properties = [])
    {
        $value = '';
        $titleArray = [];
        $idArray = [];
        $items = $this->getItems();
        $emptyTitle = _t('SilverStripe\\Forms\\DropdownField.CHOOSE', '(Choose)', 'start value of a dropdown');

        if ($items && count($items ?? [])) {
            foreach ($items as $item) {
                $idArray[] = $item->ID;
                $titleArray[] = ($item instanceof ViewableData)
                    ? $item->obj($this->getLabelField())->forTemplate()
                    : Convert::raw2xml($item->{$this->getLabelField()});
            }

            $title = implode(", ", $titleArray);
            sort($idArray);
            $value = implode(",", $idArray);
        } else {
            $title = $emptyTitle;
        }

        $dataUrlTree = '';
        if ($this->form) {
            $dataUrlTree = $this->Link('tree');
            if (!empty($idArray)) {
                $dataUrlTree = Controller::join_links($dataUrlTree, '?forceValue=' . implode(',', $idArray));
            }
        }
        $properties = array_merge(
            $properties,
            [
                'Title' => $title,
                'EmptyTitle' => $emptyTitle,
                'Link' => $dataUrlTree,
                'Value' => $value
            ]
        );
        return FormField::Field($properties);
    }

    /**
     * Save the results into the form
     * Calls function $record->onChange($items) before saving to the assumed
     * Component set.
     *
     * @param DataObjectInterface $record
     */
    public function saveInto(DataObjectInterface $record)
    {
        $fieldName = $this->getName();

        /** @var Relation $saveDest */
        $saveDest = $record->$fieldName();
        if (!$saveDest) {
            $recordClass = get_class($record);
            throw new \RuntimeException(
                "TreeMultiselectField::saveInto() Field '$fieldName' not found on"
                . " {$recordClass}.{$record->ID}"
            );
        }

        $itemIDs = $this->getItems()->column('ID');

        // Allows you to modify the itemIDs on your object before save
        $funcName = "onChange$fieldName";
        if ($record->hasMethod($funcName)) {
            $result = $record->$funcName($itemIDs);
            if (!$result) {
                return;
            }
        }
        $saveDest->setByIDList($itemIDs);
    }

    /**
     * Changes this field to the readonly field.
     */
    public function performReadonlyTransformation()
    {
        $copy = $this->castedCopy(TreeMultiselectField_Readonly::class);
        $copy->setKeyField($this->getKeyField());
        $copy->setLabelField($this->getLabelField());
        $copy->setSourceObject($this->getSourceObject());
        $copy->setTitleField($this->getTitleField());
        return $copy;
    }

    /**
     * {@inheritdoc}
     *
     * @internal To be removed in 5.0
     */
    protected function objectForKey($key)
    {
        /**
         * Fixes https://github.com/silverstripe/silverstripe-framework/issues/8332
         *
         * Due to historic reasons, the default (empty) value for this field is 'unchanged', even though
         * the field is usually integer on the database side.
         * MySQL handles that gracefully and returns an empty result in that case,
         * whereas some other databases (e.g. PostgreSQL) do not support comparison
         * of numeric types with string values, issuing a database error.
         *
         * This fix is not ideal, but supposed to keep backward compatibility for SS4.
         *
         * In 5.0 this method to be removed and NULL should be used instead of 'unchanged' (or an empty array. to be decided).
         * In 5.0 this class to be refactored so that $this->value is always an array of values (or null)
         */
        if ($this->getKeyField() === 'ID' && $key === 'unchanged') {
            $key = null;
        } elseif (is_string($key)) {
            $key = preg_split('/\s*,\s*/', trim($key ?? ''));
        }

        return parent::objectForKey($key);
    }
}
