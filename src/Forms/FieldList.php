<?php

namespace SilverStripe\Forms;

use SilverStripe\ORM\ArrayList;

/**
 * A list designed to hold form field instances.
 *
 * @extends ArrayList<FormField>
 */
class FieldList extends ArrayList
{

    /**
     * Cached flat representation of all fields in this set,
     * including fields nested in {@link CompositeFields}.
     *
     * @var FormField[]
     */
    protected $sequentialSet;

    /**
     * @var FormField[]
     */
    protected $sequentialSaveableSet;

    /**
     * If this fieldlist is owned by a parent field (e.g. CompositeField)
     * this is the parent field.
     *
     * @var CompositeField
     */
    protected $containerField;

    public function __construct($items = [])
    {
        if (!is_array($items) || func_num_args() > 1) {
            $items = func_get_args();
        }

        parent::__construct($items);

        foreach ($items as $item) {
            if ($item instanceof FormField) {
                $item->setContainerFieldList($this);
            }
        }
    }

    public function __clone()
    {
        // Clone all fields in this list
        foreach ($this->items as $key => $field) {
            $this->items[$key] = clone $field;
        }
    }

    /**
     * Iterate over each field in the current list recursively
     *
     * @param callable $callback
     */
    public function recursiveWalk(callable $callback)
    {
        $stack = $this->toArray();
        while (!empty($stack)) {
            $field = array_shift($stack);
            $callback($field);
            if ($field instanceof CompositeField) {
                $stack = array_merge($field->getChildren()->toArray(), $stack);
            }
        }
    }

    /**
     * Return a flattened list of all fields
     *
     * @return static
     */
    public function flattenFields()
    {
        $fields = [];
        $this->recursiveWalk(function (FormField $field) use (&$fields) {
            $fields[] = $field;
        });
        return static::create($fields);
    }

    /**
     * Return a sequential set of all fields that have data.  This excludes wrapper composite fields
     * as well as heading / help text fields.
     *
     * @return FormField[]
     */
    public function dataFields()
    {
        if (empty($this->sequentialSet)) {
            $fields = [];
            $this->recursiveWalk(function (FormField $field) use (&$fields) {
                if (!$field->hasData()) {
                    return;
                }
                $name = $field->getName();
                if (isset($fields[$name])) {
                    $this->fieldNameError($field, __FUNCTION__);
                }
                $fields[$name] = $field;
            });
            $this->sequentialSet = $fields;
        }
        return $this->sequentialSet;
    }

    /**
     * @return FormField[]
     */
    public function saveableFields()
    {
        if (empty($this->sequentialSaveableSet)) {
            $fields = [];
            $this->recursiveWalk(function (FormField $field) use (&$fields) {
                if (!$field->canSubmitValue()) {
                    return;
                }
                $name = $field->getName();
                if (isset($fields[$name])) {
                    $this->fieldNameError($field, __FUNCTION__);
                }
                $fields[$name] = $field;
            });
            $this->sequentialSaveableSet = $fields;
        }
        return $this->sequentialSaveableSet;
    }

    /**
     * Return array of all field names
     *
     * @return array
     */
    public function dataFieldNames()
    {
        return array_keys($this->dataFields() ?? []);
    }

    /**
     * Trigger an error for duplicate field names
     *
     * @param FormField $field
     * @param $functionName
     */
    protected function fieldNameError(FormField $field, $functionName)
    {
        if ($field->getForm()) {
            $errorSuffix = sprintf(
                " in your '%s' form called '%s'",
                get_class($field->getForm()),
                $field->getForm()->getName()
            );
        } else {
            $errorSuffix = '';
        }

        throw new \RuntimeException(sprintf(
            "%s() I noticed that a field called '%s' appears twice%s",
            $functionName,
            $field->getName(),
            $errorSuffix
        ));
    }

    protected function flushFieldsCache()
    {
        $this->sequentialSet = null;
        $this->sequentialSaveableSet = null;
    }

    /**
     * Add an extra field to a tab within this FieldList.
     * This is most commonly used when overloading getCMSFields()
     *
     * @param string $tabName The name of the tab or tabset.  Subtabs can be referred to as TabSet.Tab
     *                        or TabSet.Tab.Subtab. This function will create any missing tabs.
     * @param FormField $field The {@link FormField} object to add to the end of that tab.
     * @param string $insertBefore The name of the field to insert before.  Optional.
     *
     * @return $this
     */
    public function addFieldToTab($tabName, $field, $insertBefore = null)
    {
        // This is a cache that must be flushed
        $this->flushFieldsCache();

        // Find the tab
        $tab = $this->findOrMakeTab($tabName);

        // Add the field to the end of this set
        if ($insertBefore) {
            $tab->insertBefore($insertBefore, $field);
        } else {
            $tab->push($field);
        }

        return $this;
    }

    /**
     * Add a number of extra fields to a tab within this FieldList.
     * This is most commonly used when overloading getCMSFields()
     *
     * @param string $tabName The name of the tab or tabset.  Subtabs can be referred to as TabSet.Tab
     *                        or TabSet.Tab.Subtab. This function will create any missing tabs.
     * @param array $fields An array of {@link FormField} objects.
     * @param string $insertBefore Name of field to insert before
     *
     * @return $this
     */
    public function addFieldsToTab($tabName, $fields, $insertBefore = null)
    {
        $this->flushFieldsCache();

        // Find the tab
        $tab = $this->findOrMakeTab($tabName);

        // Add the fields to the end of this set
        foreach ($fields as $field) {
            // Check if a field by the same name exists in this tab
            if ($insertBefore) {
                $tab->insertBefore($insertBefore, $field);
            } elseif (($name = $field->getName()) && $tab->fieldByName($name)) {
                // It exists, so we need to replace the old one
                $this->replaceField($field->getName(), $field);
            } else {
                $tab->push($field);
            }
        }

        return $this;
    }

    /**
     * Remove the given field from the given tab in the field.
     *
     * @param string $tabName The name of the tab
     * @param string $fieldName The name of the field
     *
     * @return $this
     */
    public function removeFieldFromTab($tabName, $fieldName)
    {
        $this->flushFieldsCache();

        // Find the tab
        $tab = $this->findTab($tabName);
        if ($tab) {
            $tab->removeByName($fieldName);
        }

        return $this;
    }

    /**
     * Removes a number of fields from a Tab/TabSet within this FieldList.
     *
     * @param string $tabName The name of the Tab or TabSet field
     * @param array $fields A list of fields, e.g. array('Name', 'Email')
     *
     * @return $this
     */
    public function removeFieldsFromTab($tabName, $fields)
    {
        $this->flushFieldsCache();

        // Find the tab
        if ($tab = $this->findTab($tabName)) {
            // Remove the fields from this set
            foreach ($fields as $field) {
                $tab->removeByName($field);
            }
        }

        return $this;
    }

    /**
     * Remove a field or fields from this FieldList by Name.
     * The field could also be inside a CompositeField.
     *
     * @param string|array $fieldName The name of, or an array with the field(s) or tab(s)
     * @param boolean $dataFieldOnly If this is true, then a field will only
     * be removed if it's a data field.  Dataless fields, such as tabs, will
     * be left as-is.
     *
     * @return $this
     */
    public function removeByName($fieldName, $dataFieldOnly = false)
    {
        if (!$fieldName) {
            user_error('FieldList::removeByName() was called with a blank field name.', E_USER_WARNING);
        }

        // Handle array syntax
        if (is_array($fieldName)) {
            foreach ($fieldName as $field) {
                $this->removeByName($field, $dataFieldOnly);
            }
            return $this;
        }

        $this->flushFieldsCache();
        foreach ($this as $i => $child) {
            $childName = $child->getName();
            if (!$childName) {
                $childName = $child->Title();
            }

            if (($childName == $fieldName) && (!$dataFieldOnly || $child->hasData())) {
                array_splice($this->items, $i ?? 0, 1);
                break;
            } elseif ($child instanceof CompositeField) {
                $child->removeByName($fieldName, $dataFieldOnly);
            }
        }

        return $this;
    }

    /**
     * Replace a single field with another.  Ignores dataless fields such as Tabs and TabSets
     *
     * @param string $fieldName The name of the field to replace
     * @param FormField $newField The field object to replace with
     * @param boolean $dataFieldOnly If this is true, then a field will only be replaced if it's a data field.  Dataless
     *                               fields, such as tabs, will be not be considered for replacement.
     * @return bool TRUE field was successfully replaced
     *                   FALSE field wasn't found, nothing changed
     */
    public function replaceField($fieldName, $newField, $dataFieldOnly = true)
    {
        $this->flushFieldsCache();
        foreach ($this as $i => $field) {
            if ($field->getName() == $fieldName && (!$dataFieldOnly || $field->hasData())) {
                $this->items[$i] = $newField;
                return true;
            } elseif ($field instanceof CompositeField) {
                if ($field->replaceField($fieldName, $newField)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Rename the title of a particular field name in this set.
     *
     * @param string $fieldName Name of field to rename title of
     * @param string $newFieldTitle New title of field
     * @return bool
     */
    public function renameField($fieldName, $newFieldTitle)
    {
        $field = $this->dataFieldByName($fieldName);
        if (!$field) {
            return false;
        }

        $field->setTitle($newFieldTitle);

        return $field->Title() == $newFieldTitle;
    }

    /**
     * @return bool
     */
    public function hasTabSet()
    {
        foreach ($this->items as $i => $field) {
            if (is_object($field) && $field instanceof TabSet) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the specified tab object, if it exists
     *
     * @param string $tabName The tab to return, in the form "Tab.Subtab.Subsubtab".
     * @return Tab|null The found or null
     */
    public function findTab($tabName)
    {
        $parts = explode('.', $tabName ?? '');

        $currentPointer = $this;

        foreach ($parts as $k => $part) {
            $currentPointer = $currentPointer->fieldByName($part);
        }

        return $currentPointer;
    }

    /**
     * Returns the specified tab object, creating it if necessary.
     *
     * @param string $tabName The tab to return, in the form "Tab.Subtab.Subsubtab".
     *   Caution: Does not recursively create TabSet instances, you need to make sure everything
     *   up until the last tab in the chain exists.
     * @param string $title Natural language title of the tab. If {@link $tabName} is passed in dot notation,
     *   the title parameter will only apply to the innermost referenced tab.
     *   The title is only changed if the tab doesn't exist already.
     * @return Tab The found or newly created Tab instance
     */
    public function findOrMakeTab($tabName, $title = null)
    {
        $parts = explode('.', $tabName ?? '');
        $last_idx = count($parts ?? []) - 1;
        // We could have made this recursive, but I've chosen to keep all the logic code within FieldList rather than
        // add it to TabSet and Tab too.
        $currentPointer = $this;
        foreach ($parts as $k => $part) {
            $parentPointer = $currentPointer;
            $currentPointer = $currentPointer->fieldByName($part);
            // Create any missing tabs
            if (!$currentPointer) {
                if ($parentPointer instanceof TabSet) {
                    // use $title on the innermost tab only
                    if ($k == $last_idx) {
                        $currentPointer = isset($title) ? new Tab($part, $title) : new Tab($part);
                    } else {
                        $currentPointer = new TabSet($part);
                    }
                    $parentPointer->push($currentPointer);
                } else {
                    $withName = $parentPointer instanceof FormField
                        ? " named '{$parentPointer->getName()}'"
                        : null;
                    $parentPointerClass = get_class($parentPointer);
                    throw new \InvalidArgumentException(
                        "FieldList::addFieldToTab() Tried to add a tab to object"
                        . " '{$parentPointerClass}'{$withName} - '{$part}' didn't exist."
                    );
                }
            }
        }

        return $currentPointer;
    }

    /**
     * Returns a named field.
     * You can use dot syntax to get fields from child composite fields
     *
     * @param string $name
     * @return FormField|null
     */
    public function fieldByName($name)
    {
        $fullName = $name;
        if (strpos($name ?? '', '.') !== false) {
            list($name, $remainder) = explode('.', $name ?? '', 2);
        } else {
            $remainder = null;
        }

        foreach ($this as $child) {
            if (trim($fullName ?? '') == trim($child->getName() ?? '') || $fullName == $child->id) {
                return $child;
            } elseif (trim($name ?? '') == trim($child->getName() ?? '') || $name == $child->id) {
                if ($remainder) {
                    if ($child instanceof CompositeField) {
                        return $child->fieldByName($remainder);
                    } else {
                        $childClass = get_class($child);
                        user_error(
                            "Trying to get field '{$remainder}' from non-composite field {$childClass}.{$name}",
                            E_USER_WARNING
                        );
                        return null;
                    }
                } else {
                    return $child;
                }
            }
        }
        return null;
    }

    /**
     * Returns a named field in a sequential set.
     * Use this if you're using nested FormFields.
     *
     * @param string $name The name of the field to return
     * @return FormField|null
     */
    public function dataFieldByName($name)
    {
        if ($dataFields = $this->dataFields()) {
            foreach ($dataFields as $child) {
                if (trim($name ?? '') == trim($child->getName() ?? '') || $name == $child->id) {
                    return $child;
                }
            }
        }
        return null;
    }

    /**
     * Inserts a field before a particular field in a FieldList.
     * Will traverse CompositeFields depth-first to find the matching $name, and insert before the first match
     */
    public function insertBefore(string $name, FormField $item, bool $appendIfMissing = true): FormField|bool
    {
        $this->onBeforeInsert($item);
        $item->setContainerFieldList($this);

        $i = 0;
        foreach ($this as $child) {
            if ($name == $child->getName() || $name == $child->id) {
                array_splice($this->items, $i ?? 0, 0, [$item]);
                return $item;
            } elseif ($child instanceof CompositeField) {
                $ret = $child->insertBefore($name, $item, false);
                if ($ret) {
                    return $ret;
                }
            }
            $i++;
        }

        // $name not found, append if needed
        if ($appendIfMissing) {
            $this->push($item);
            return $item;
        }

        return false;
    }

    /**
     * Inserts a field after a particular field in a FieldList.
     * Will traverse CompositeFields depth-first to find the matching $name, and insert after the first match
     */
    public function insertAfter(string $name, FormField $item, bool $appendIfMissing = true): FormField|bool
    {
        $this->onBeforeInsert($item);
        $item->setContainerFieldList($this);

        $i = 0;
        foreach ($this as $child) {
            if ($name == $child->getName() || $name == $child->id) {
                array_splice($this->items, $i+1, 0, [$item]);
                return $item;
            } elseif ($child instanceof CompositeField) {
                $ret = $child->insertAfter($name, $item, false);
                if ($ret) {
                    return $ret;
                }
            }
            $i++;
        }

        // $name not found, append if needed
        if ($appendIfMissing) {
            $this->push($item);
            return $item;
        }

        return false;
    }

    /**
     * Push a single field onto the end of this FieldList instance.
     *
     * @param FormField $item The FormField to add
     */
    public function push($item)
    {
        $this->onBeforeInsert($item);
        $item->setContainerFieldList($this);

        return parent::push($item);
    }

    /**
     * Push a single field onto the beginning of this FieldList instance.
     *
     * @param FormField $item The FormField to add
     */
    public function unshift($item)
    {
        $this->onBeforeInsert($item);
        $item->setContainerFieldList($this);

        return parent::unshift($item);
    }

    /**
     * Handler method called before the FieldList is going to be manipulated.
     *
     * @param FormField $item
     */
    protected function onBeforeInsert($item)
    {
        $this->flushFieldsCache();

        if ($item->getName()) {
            $this->rootFieldList()->removeByName($item->getName(), true);
        }
    }


    /**
     * Set the Form instance for this FieldList.
     *
     * @param Form $form The form to set this FieldList to
     * @return $this
     */
    public function setForm($form)
    {
        foreach ($this as $field) {
            $field->setForm($form);
        }

        return $this;
    }

    /**
     * Load the given data into this form.
     *
     * @param array $data An map of data to load into the FieldList
     * @return $this
     */
    public function setValues($data)
    {
        foreach ($this->dataFields() as $field) {
            $fieldName = $field->getName();
            if (isset($data[$fieldName])) {
                $field->setValue($data[$fieldName]);
            }
        }
        return $this;
    }

    /**
     * Return all <input type="hidden"> fields
     * in a form - including fields nested in {@link CompositeFields}.
     * Useful when doing custom field layouts.
     *
     * @return FieldList
     */
    public function HiddenFields()
    {
        $hiddenFields = new FieldList();
        $dataFields = $this->dataFields();

        if ($dataFields) {
            foreach ($dataFields as $field) {
                if ($field instanceof HiddenField) {
                    $hiddenFields->push($field);
                }
            }
        }

        return $hiddenFields;
    }

    /**
     * Return all fields except for the hidden fields.
     * Useful when making your own simplified form layouts.
     */
    public function VisibleFields()
    {
        $visibleFields = new FieldList();

        foreach ($this as $field) {
            if (!($field instanceof HiddenField)) {
                $visibleFields->push($field);
            }
        }

        return $visibleFields;
    }

    /**
     * Transform this FieldList with a given transform method,
     * e.g. $this->transform(new ReadonlyTransformation())
     *
     * @param FormTransformation $trans
     * @return FieldList
     */
    public function transform($trans)
    {
        $this->flushFieldsCache();
        $newFields = new FieldList();
        foreach ($this as $field) {
            $newFields->push($field->transform($trans));
        }
        return $newFields;
    }

    /**
     * Returns the root field set that this belongs to
     *
     * @return FieldList|FormField
     */
    public function rootFieldList()
    {
        if ($this->containerField) {
            return $this->containerField->rootFieldList();
        }

        return $this;
    }

    /**
     * @return CompositeField|null
     */
    public function getContainerField()
    {
        return $this->containerField;
    }

    /**
     * @param CompositeField|null $field
     * @return $this
     */
    public function setContainerField($field)
    {
        $this->containerField = $field;
        return $this;
    }

    /**
     * Transforms this FieldList instance to readonly.
     *
     * @return FieldList
     */
    public function makeReadonly()
    {
        return $this->transform(new ReadonlyTransformation());
    }

    /**
     * Transform the named field into a readonly field.
     *
     * @param string|array|FormField $field
     */
    public function makeFieldReadonly($field)
    {
        if (!is_array($field)) {
            $field = [$field];
        }

        foreach ($field as $item) {
            $fieldName = ($item instanceof FormField) ? $item->getName() : $item;
            $srcField = $this->dataFieldByName($fieldName);
            if ($srcField) {
                $this->replaceField($fieldName, $srcField->performReadonlyTransformation());
            } else {
                user_error("Trying to make field '$fieldName' readonly, but it does not exist in the list", E_USER_WARNING);
            }
        }
    }

    /**
     * Change the order of fields in this FieldList by specifying an ordered list of field names.
     * This works well in conjunction with SilverStripe's scaffolding functions: take the scaffold, and
     * shuffle the fields around to the order that you want.
     *
     * Please note that any tabs or other dataless fields will be clobbered by this operation.
     *
     * @param array $fieldNames Field names can be given as an array, or just as a list of arguments.
     */
    public function changeFieldOrder($fieldNames)
    {
        // Field names can be given as an array, or just as a list of arguments.
        if (!is_array($fieldNames)) {
            $fieldNames = func_get_args();
        }

        // Build a map of fields indexed by their name.  This will make the 2nd step much easier.
        $fieldMap = [];
        foreach ($this->dataFields() as $field) {
            $fieldMap[$field->getName()] = $field;
        }

        // Iterate through the ordered list of names, building a new array to be put into $this->items.
        // While we're doing this, empty out $fieldMap so that we can keep track of leftovers.
        // Unrecognised field names are okay; just ignore them
        $fields = [];
        foreach ($fieldNames as $fieldName) {
            if (isset($fieldMap[$fieldName])) {
                $fields[] = $fieldMap[$fieldName];
                unset($fieldMap[$fieldName]);
            }
        }

        // Add the leftover fields to the end of the list.
        $fields = array_values($fields + $fieldMap);

        // Update our internal $this->items parameter.
        $this->items = $fields;

        $this->flushFieldsCache();
    }

    /**
     * Find the numerical position of a field within
     * the children collection. Doesn't work recursively.
     *
     * @param string|FormField $field
     * @return int Position in children collection (first position starts with 0).
     * Returns FALSE if the field can't be found.
     */
    public function fieldPosition($field)
    {
        if ($field instanceof FormField) {
            $field = $field->getName();
        }

        $i = 0;
        foreach ($this->dataFields() as $child) {
            if ($child->getName() == $field) {
                return $i;
            }
            $i++;
        }

        return false;
    }

    /**
     * Default template rendering of a FieldList will concatenate all FieldHolder values.
     */
    public function forTemplate()
    {
        $output = "";
        foreach ($this as $field) {
            $output .= $field->FieldHolder();
        }
        return $output;
    }
}
