<?php

namespace SilverStripe\Forms;

/**
 * TextareaField creates a multi-line text field,
 * allowing more data to be entered than a standard
 * text field. It creates the `<textarea>` tag in the
 * form HTML.
 *
 * <code>
 * new TextareaField(
 *    $name = "description",
 *    $title = "Description",
 *    $value = "This is the default description"
 * );
 * </code>
 */
class TextareaField extends FormField
{

    /**
     * Value should be XML
     *
     * @var array
     */
    private static $casting = [
        'Value' => 'Text',
        'ValueEntities' => 'HTMLFragment([\'shortcodes\' => false])',
    ];

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_TEXT;

    /**
     * Visible number of text lines.
     *
     * @var int
     */
    protected $rows = 5;

    /**
     * Visible number of text columns.
     *
     * @var int
     */
    protected $cols = 20;

    /**
     * @var int
     */
    protected $maxLength;

    /**
     * Set textarea specific schema data
     */
    public function getSchemaDataDefaults()
    {
        $data = parent::getSchemaDataDefaults();
        $data['data']['rows'] = $this->getRows();
        $data['data']['columns'] = $this->getColumns();
        $data['data']['maxlength'] =  $this->getMaxLength();
        return $data;
    }

    /**
     * Set the number of rows in the textarea
     *
     * @param int $rows
     *
     * @return $this
     */
    public function setRows($rows)
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * Gets number of rows
     *
     * @return int
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Set the number of columns in the textarea
     *
     * @param int $cols
     *
     * @return $this
     */
    public function setColumns($cols)
    {
        $this->cols = $cols;

        return $this;
    }

    /**
     * Gets the number of columns in this textarea
     *
     * @return int
     */
    public function getColumns()
    {
        return $this->cols;
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
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        $attributes = array_merge(
            parent::getAttributes(),
            [
                'rows' => $this->getRows(),
                'cols' => $this->getColumns(),
                'value' => null,
                'type' => null,
            ]
        );

        $maxLength = $this->getMaxLength();
        if ($maxLength) {
            $attributes['maxlength'] = $maxLength;
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function Type()
    {
        $parent = parent::Type();

        if ($this->readonly) {
            return $parent . ' readonly';
        }

        return $parent;
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

    /**
     * Return value with all values encoded in html entities
     *
     * @return string Raw HTML
     */
    public function ValueEntities()
    {
        return htmlentities($this->Value() ?? '', ENT_COMPAT, 'UTF-8');
    }
}
