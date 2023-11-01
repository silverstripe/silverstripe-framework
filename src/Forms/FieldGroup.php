<?php

namespace SilverStripe\Forms;

use InvalidArgumentException;
use SilverStripe\ORM\ValidationResult;

/**
 * Lets you include a nested group of fields inside a template.
 * This control gives you more flexibility over form layout.
 *
 * Note: the child fields within a field group aren't rendered using FieldHolder().  Instead,
 * SmallFieldHolder() is called, which just prefixes $Field with a <label> tag, if the Title is set.
 *
 * <b>Usage</b>
 *
 * <code>
 * FieldGroup::create(
 *  FieldGroup::create(
 *      HeaderField::create('FieldGroup 1'),
 *      TextField::create('Firstname')
 *  ),
 *  FieldGroup::create(
 *      HeaderField::create('FieldGroup 2'),
 *      TextField::create('Surname')
 *  )
 * )
 * </code>
 *
 * <b>Adding to existing FieldGroup instances</b>
 *
 * <code>
 * function getCMSFields() {
 *  $fields = parent::getCMSFields();
 *
 *  $fields->addFieldToTab(
 *      'Root.Main',
 *      FieldGroup::create(
 *          TimeField::create("StartTime","What's the start time?"),
 *          TimeField::create("EndTime","What's the end time?")
 *      ),
 *      'Content'
 *  );
 *
 *  return $fields;
 *
 * }
 * </code>
 *
 * <b>Setting a title to a FieldGroup</b>
 *
 * <code>
 * $fields->addFieldToTab("Root.Main",
 *      FieldGroup::create(
 *          TimeField::create('StartTime','What's the start time?'),
 *          TimeField::create('EndTime', 'What's the end time?')
 *      )->setTitle('Time')
 * );
 * </code>
 */
class FieldGroup extends CompositeField
{
    protected $schemaComponent = 'FieldGroup';

    protected $zebra;

    /**
     * Create a new field group.
     *
     * Accepts any number of arguments.
     *
     * @param mixed $titleOrField Either the field title, list of fields, or first field
     * @param mixed ...$otherFields Subsequent fields or field list (if passing in title to $titleOrField)
     */
    public function __construct($titleOrField = null, $otherFields = null)
    {
        $title = null;
        if (is_array($titleOrField) || $titleOrField instanceof FieldList) {
            $fields = $titleOrField;

            // This would be discarded otherwise
            if ($otherFields) {
                throw new InvalidArgumentException(
                    '$otherFields is not accepted if passing in field list to $titleOrField'
                );
            }
        } elseif (is_array($otherFields) || $otherFields instanceof FieldList) {
            $title = $titleOrField;
            $fields = $otherFields;
        } else {
            $fields = func_get_args();
            if (!is_object(reset($fields))) {
                $title = array_shift($fields);
            }
        }

        parent::__construct($fields);

        if ($title) {
            $this->setTitle($title);
        }
    }

    /**
     * Returns the name (ID) for the element.
     * In some cases the FieldGroup doesn't have a title, but we still want
     * the ID / name to be set. This code, generates the ID from the nested children
     */
    public function getName()
    {
        if ($this->name) {
            return $this->name;
        }

        if (!$this->title) {
            return parent::getName();
        }

        return preg_replace("/[^a-zA-Z0-9]+/", "", $this->title ?? '');
    }

    /**
     * Set an odd/even class
     *
     * @param string $zebra one of odd or even.
     * @return $this
     */
    public function setZebra($zebra)
    {
        if ($zebra == 'odd' || $zebra == 'even') {
            $this->zebra = $zebra;
        } else {
            user_error("setZebra passed '$zebra'.  It should be passed 'odd' or 'even'", E_USER_WARNING);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getZebra()
    {
        return $this->zebra;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        $dataFields = [];
        $this->collateDataFields($dataFields);

        /** @var FormField $subfield */
        $messages = [];
        foreach ($dataFields as $subfield) {
            $message = $subfield->obj('Message')->forTemplate();
            if ($message) {
                $messages[] = rtrim($message ?? '', ".");
            }
        }

        if (!$messages) {
            return null;
        }

        return implode(", ", $messages) . ".";
    }

    /**
     * @return string
     */
    public function getMessageType()
    {
        $dataFields = [];
        $this->collateDataFields($dataFields);

        /** @var FormField $subfield */
        foreach ($dataFields as $subfield) {
            $type = $subfield->getMessageType();
            if ($type) {
                return $type;
            }
        }

        return null;
    }

    public function getMessageCast()
    {
        return ValidationResult::CAST_HTML;
    }
}
