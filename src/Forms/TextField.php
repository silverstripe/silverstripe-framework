<?php

namespace SilverStripe\Forms;

/**
 * Text input field.
 */
class TextField extends FormField implements TippableFieldInterface
{
    /**
     * @var int
     */
    protected $maxLength;

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_TEXT;

    /**
     * @var Tip|null A tip to render beside the input
     */
    private $tip;

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
     * @return Tip|null
     */
    public function getTip(): ?Tip
    {
        return $this->tip;
    }

    /**
     * Applies a Tip to the field, which shows a popover on the right side of
     * the input to place additional context or explanation of the field's
     * purpose in. Currently only supported in React-based forms.
     *
     * @param Tip|null $tip The Tip to apply, or null to remove an existing one
     * @return $this
     */
    public function setTip(?Tip $tip = null): TextField
    {
        $this->tip = $tip;

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        $maxLength = $this->getMaxLength();

        $attributes = [];

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

        if ($this->getTip() instanceof Tip) {
            $data['tip'] = $this->getTip()->getTipSchema();
        }

        return $data;
    }

    /**
     * Validate this field
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        $result = true;
        if (!is_null($this->maxLength) && mb_strlen($this->value ?? '') > $this->maxLength) {
            $name = strip_tags($this->Title() ? $this->Title() : $this->getName());
            $validator->validationError(
                $this->name,
                _t(
                    'SilverStripe\\Forms\\TextField.VALIDATEMAXLENGTH',
                    'The value for {name} must not exceed {maxLength} characters in length',
                    ['name' => $name, 'maxLength' => $this->maxLength]
                ),
                "validation"
            );
            $result = false;
        }
        return $this->extendValidationResult($result, $validator);
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
