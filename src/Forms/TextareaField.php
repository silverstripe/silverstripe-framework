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
    public function getSchemaDataDefaults(): array
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
    public function setRows(int $rows): SilverStripe\Forms\HTMLEditor\HTMLEditorField
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * Gets number of rows
     *
     * @return int
     */
    public function getRows(): int
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
    public function getColumns(): int
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
    public function getMaxLength(): null
    {
        return $this->maxLength;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
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
    public function Type(): string
    {
        $parent = parent::Type();

        if ($this->readonly) {
            return $parent . ' readonly';
        }

        return $parent;
    }

    /**
     * Return value with all values encoded in html entities
     *
     * @return string Raw HTML
     */
    public function ValueEntities(): string
    {
        return htmlentities($this->Value() ?? '', ENT_COMPAT, 'UTF-8');
    }
}
