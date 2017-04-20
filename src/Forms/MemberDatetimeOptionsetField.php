<?php

namespace SilverStripe\Forms;

use InvalidArgumentException;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\View\ArrayData;

class MemberDatetimeOptionsetField extends OptionsetField
{
    /**
     * Option value for custom date format option
     */
    const CUSTOM_OPTION = '__custom__';

    /**
     * Non-ambiguous date to use for the preview.
     * Must be in ISO 8601 'y-MM-dd HH:mm:ss' format
     *
     * @var string
     */
    private static $preview_date = '2011-12-25 17:30:00';

    private static $casting = ['Description' => 'HTMLText'];

    /**
     * Template name to use for rendering the field description
     *
     * @var string
     */
    protected $descriptionTemplate = '';

    public function Field($properties = array())
    {
        $options = array();
        $odd = false;

        // Add all options striped
        $anySelected = false;
        foreach ($this->getSourceEmpty() as $value => $title) {
            $odd = !$odd;
            if (!$anySelected) {
                $anySelected = $this->isSelectedValue($value, $this->Value());
            }
            $options[] = $this->getFieldOption($value, $title, $odd);
        }

        // Add "custom" input field option
        $options[] = $this->getCustomFieldOption(!$anySelected, !$odd);

        // Build fieldset
        $properties = array_merge($properties, array(
            'Options' => new ArrayList($options)
        ));


        return $this->customise($properties)->renderWith(
            $this->getTemplates()
        );
    }

    /**
     * Create the "custom" selection field option
     *
     * @param bool $isChecked True if this is checked
     * @param bool $odd Is odd striped
     * @return ArrayData
     */
    protected function getCustomFieldOption($isChecked, $odd)
    {
        // Add "custom" input field
        $option = $this->getFieldOption(
            self::CUSTOM_OPTION,
            _t('SilverStripe\\Forms\\MemberDatetimeOptionsetField.Custom', 'Custom'),
            $odd
        );
        $option->setField('isChecked', $isChecked);
        $option->setField('CustomName', $this->getName().'[Custom]');
        $option->setField('CustomValue', $this->Value());
        if ($this->Value()) {
            $preview = Convert::raw2xml($this->previewFormat($this->Value()));
            $option->setField('CustomPreview', $preview);
            $option->setField('CustomPreviewLabel', _t('SilverStripe\\Forms\\MemberDatetimeOptionsetField.Preview', 'Preview'));
        }
        return $option;
    }

    /**
     * For a given format, generate a preview for the date
     *
     * @param string $format Date format
     * @return string
     */
    protected function previewFormat($format)
    {
        $date = DBDatetime::create_field('Datetime', MemberDatetimeOptionsetField::config()->preview_date);
        return $date->Format($format);
    }

    public function getOptionName()
    {
        return parent::getOptionName() . '[Options]';
    }

    public function Type()
    {
        return 'optionset memberdatetimeoptionset';
    }

    public function getDescription()
    {
        if ($template = $this->getDescriptionTemplate()) {
            return $this->renderWith($template);
        }
        return parent::getDescription();
    }

    /**
     * Get template name used to render description
     *
     * @return string
     */
    public function getDescriptionTemplate()
    {
        return $this->descriptionTemplate;
    }

    /**
     * Assign a template to use for description. If assigned the description
     * value will be ignored.
     *
     * @param string $template
     * @return $this
     */
    public function setDescriptionTemplate($template)
    {
        $this->descriptionTemplate = $template;
        return $this;
    }

    public function setSubmittedValue($value, $data = null)
    {
        // Extract custom option from postback
        if (is_array($value)) {
            if (empty($value['Options'])) {
                $value = '';
            } elseif ($value['Options'] === self::CUSTOM_OPTION) {
                $value = $value['Custom'];
            } else {
                $value = $value['Options'];
            }
        }

        return parent::setSubmittedValue($value);
    }

    public function setValue($value, $data = null)
    {
        if (is_array($value)) {
            throw new InvalidArgumentException("Invalid array value: Expected string");
        }
        return parent::setValue($value, $data);
    }


    /**
     * Validate this field
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        $value = $this->Value();
        if (!$value) {
            return true; // no custom value, don't validate
        }

        // Check that the current date with the date format is valid or not
        $date = DBDatetime::now()->Format($value);
        if ($date && $date !== $value) {
            return true;
        }

        // Fail
        $validator->validationError(
            $this->getName(),
            _t(
                'SilverStripe\\Forms\\MemberDatetimeOptionsetField.DATEFORMATBAD',
                "Date format is invalid"
            ),
            "validation"
        );
        return false;
    }
}
