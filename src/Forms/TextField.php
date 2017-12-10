<?php

namespace SilverStripe\Forms;

use SilverStripe\Dev\Deprecation;

/**
 * Text input field.
 */
class TextField extends FormField
{
    /**
     * @var int
     */
    protected $maxLength;

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_TEXT;

    /**
     * Returns an input field.
     *
     * @param string $name
     * @param null|string $title
     * @param string $value
     * @param null|int $maxLength Max characters to allow for this field. If this value is stored
     * against a DB field with a fixed size it's recommended to set an appropriate max length
     * matching this size.
     * @param null|Form $form
     */
    public function __construct($name, $title = null, $value = '', $maxLength = null, $form = null)
    {
        if ($maxLength) {
            $this->setMaxLength($maxLength);
        }

        if ($form) {
            $this->setForm($form);
        }

        parent::__construct($name, $title, $value);
    }

    /**
     * @param int $maxLength
     * @return $this
     */
    public function setMaxLength($maxLength)
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        $maxLength = $this->getMaxLength();

        $attributes = array();

        if ($maxLength) {
            $attributes['maxLength'] = $maxLength;
            $attributes['size'] = min($maxLength, 30);
        }

        return array_merge(
            parent::getAttributes(),
            $attributes
        );
    }

    public function getSchemaDataDefaults()
    {
        $data = parent::getSchemaDataDefaults();
        $data['data']['maxlength'] =  $this->getMaxLength();
        return $data;
    }

    /**
     * @return string
     */
    public function InternallyLabelledField()
    {
        Deprecation::notice('4.0', 'Please use ->setValue() instead');

        if (!$this->value) {
            $this->value = $this->Title();
        }

        return $this->Field();
    }

    /**
     * Validate this field
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        if (!is_null($this->maxLength) && mb_strlen($this->value) > $this->maxLength) {
            $validator->validationError(
                $this->name,
                _t(
                    'SilverStripe\\Forms\\TextField.VALIDATEMAXLENGTH',
                    'The value for {name} must not exceed {maxLength} characters in length',
                    array('name' => $this->getName(), 'maxLength' => $this->maxLength)
                ),
                "validation"
            );
            return false;
        }
        return true;
    }

    public function getSchemaValidation()
    {
        $rules = parent::getSchemaValidation();
        if ($this->getMaxLength()) {
            $rules['max'] = [
                'length' => $this->getMaxLength(),
            ];
        }
        return $rules;
    }
}
