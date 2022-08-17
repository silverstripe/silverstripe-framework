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

    protected $schemaDataType = self::SCHEMA_DATA_TYPE_STRUCTURAL;

    /**
     * @skipUpgrade
     * @var string
     */
    protected $schemaComponent = 'HeaderField';

    /**
     * @param string $name
     * @param string $title
     * @param int $headingLevel
     */
    public function __construct(string $name, string|bool $title = null, int $headingLevel = 2): void
    {
        $this->setHeadingLevel($headingLevel);
        parent::__construct($name, $title);
    }

    /**
     * @return int
     */
    public function getHeadingLevel(): int
    {
        return $this->headingLevel;
    }

    /**
     * @param int $headingLevel
     *
     * @return $this
     */
    public function setHeadingLevel(int $headingLevel): SilverStripe\Forms\HeaderField
    {
        $this->headingLevel = $headingLevel;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
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
    public function Type(): null
    {
        return null;
    }

    /**
     * Header fields support dynamic titles via schema state
     *
     * @return array
     */
    public function getSchemaStateDefaults(): array
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
    public function getSchemaDataDefaults(): array
    {
        $data = parent::getSchemaDataDefaults();

        $data['data']['headingLevel'] = $this->headingLevel;

        return $data;
    }
}
