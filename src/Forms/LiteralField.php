<?php

namespace SilverStripe\Forms;

use SilverStripe\View\ViewableData;

/**
 * This field lets you put an arbitrary piece of HTML into your forms.
 *
 * <code>
 * new LiteralField (
 *    $name = "literalfield",
 *    $content = '<b>some bold text</b> and <a href="http://silverstripe.com">a link</a>'
 * )
 * </code>
 */
class LiteralField extends DatalessField
{

    private static $casting = [
        'Value' => 'HTMLFragment',
    ];

    /**
     * @var string|FormField
     */
    protected $content;

    protected $schemaDataType = LiteralField::SCHEMA_DATA_TYPE_STRUCTURAL;

    /**
     * @var string
     */
    protected $schemaComponent = 'LiteralField';

    /**
     * @param string $name
     * @param string|FormField $content
     */
    public function __construct($name, $content)
    {
        $this->setContent($content);

        parent::__construct($name);
    }

    /**
     * @param array $properties
     *
     * @return string
     */
    public function FieldHolder($properties = [])
    {
        if ($this->content instanceof ViewableData) {
            $context = $this->content;

            if ($properties) {
                $context = $context->customise($properties);
            }

            return $context->forTemplate();
        }

        return $this->content;
    }

    /**
     * @param array $properties
     *
     * @return string
     */
    public function Field($properties = [])
    {
        return $this->FieldHolder($properties);
    }

    /**
     * Sets the content of this field to a new value.
     *
     * @param string|FormField $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Synonym of {@link setContent()} so that LiteralField is more compatible with other field types.
     *
     * @param string|FormField $content
     * @param mixed $data
     * @return $this
     */
    public function setValue($content, $data = null)
    {
        $this->setContent($content);

        return $this;
    }

    /**
     * @return static
     */
    public function performReadonlyTransformation()
    {
        $clone = clone $this;

        $clone->setReadonly(true);

        return $clone;
    }

    /**
     * Header fields support dynamic titles via schema state
     *
     * @return array
     */
    public function getSchemaStateDefaults()
    {
        $state = parent::getSchemaStateDefaults();
        $state['value'] = $this->FieldHolder();

        return $state;
    }
}
