<?php

namespace SilverStripe\Forms;

/**
 * Field that generates a heading tag.
 *
 * This can be used to add extra text in your forms.
 */
class HeaderField extends DatalessField
{

    /**
     * The level of the <h1> to <h6> HTML tag.
     *
     * @var int
     */
    protected $headingLevel = 2;

    protected $schemaDataType = HeaderField::SCHEMA_DATA_TYPE_STRUCTURAL;

    /**
     * @var string
     */
    protected $schemaComponent = 'HeaderField';

    /**
     * @param string $name
     * @param string $title
     * @param int $headingLevel
     */
    public function __construct($name, $title = null, $headingLevel = 2)
    {
        $this->setHeadingLevel($headingLevel);
        parent::__construct($name, $title);
    }

    /**
     * @return int
     */
    public function getHeadingLevel()
    {
        return $this->headingLevel;
    }

    /**
     * @param int $headingLevel
     *
     * @return $this
     */
    public function setHeadingLevel($headingLevel)
    {
        $this->headingLevel = $headingLevel;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return array_merge(
            parent::getAttributes(),
            [
                'id' => $this->ID(),
                'class' => $this->extraClass(),
                'type' => null,
                'name' => null
            ]
        );
    }

    /**
     * @return null
     */
    public function Type()
    {
        return null;
    }

    /**
     * Header fields support dynamic titles via schema state
     *
     * @return array
     */
    public function getSchemaStateDefaults()
    {
        $state = parent::getSchemaStateDefaults();

        $state['data']['title'] = $this->Title();

        return $state;
    }

    /**
     * Header fields heading level to be set
     *
     * @return array
     */
    public function getSchemaDataDefaults()
    {
        $data = parent::getSchemaDataDefaults();

        $data['data']['headingLevel'] = $this->headingLevel;

        return $data;
    }
}
