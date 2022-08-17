<?php

namespace SilverStripe\Forms\HTMLEditor;

use SilverStripe\Assets\Shortcodes\ImageShortcodeProvider;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use Exception;
use SilverStripe\View\Parsers\HTMLValue;

/**
 * A TinyMCE-powered WYSIWYG HTML editor field with image and link insertion and tracking capabilities. Editor fields
 * are created from `<textarea>` tags, which are then converted with JavaScript.
 *
 * Caution: The form field does not include any JavaScript or CSS when used outside of the CMS context,
 * since the required frontend dependencies are included through CMS bundling.
 */
class HTMLEditorField extends TextareaField
{

    private static $casting = [
        'Value' => 'HTMLText',
    ];

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_HTML;

    protected $schemaComponent = 'HtmlEditorField';

    /**
     * @config
     * @var string Default alignment for Images and Media. Options: leftAlone|center|left|right
     */
    private static $media_alignment = 'leftAlone';

    /**
     * Should we check the valid_elements (& extended_valid_elements) rules from HTMLEditorConfig server side?
     *
     * @config
     * @var bool
     */
    private static $sanitise_server_side = true;

    /**
     * Number of rows
     *
     * @config
     * @var int
     */
    private static $default_rows = 20;

    /**
     * Extra height per row
     *
     * @var int
     */
    private static $fixed_row_height = 20;

    /**
     * ID or instance of editorconfig
     *
     * @var string|HTMLEditorConfig
     */
    protected $editorConfig = null;

    /**
     * Gets the HTMLEditorConfig instance
     *
     * @return HTMLEditorConfig
     */
    public function getEditorConfig(): SilverStripe\Forms\HTMLEditor\TinyMCEConfig
    {
        // Instance override
        if ($this->editorConfig instanceof HTMLEditorConfig) {
            return $this->editorConfig;
        }

        // Get named / active config
        return HTMLEditorConfig::get($this->editorConfig);
    }

    /**
     * Assign a new configuration instance or identifier
     *
     * @param string|HTMLEditorConfig $config
     * @return $this
     */
    public function setEditorConfig($config)
    {
        $this->editorConfig = $config;
        return $this;
    }

    /**
     * Creates a new HTMLEditorField.
     * @see TextareaField::__construct()
     *
     * @param string $name The internal field name, passed to forms.
     * @param string $title The human-readable field label.
     * @param mixed $value The value of the field.
     * @param string $config HTMLEditorConfig identifier to be used. Default to the active one.
     */
    public function __construct(string $name, string|bool $title = null, string $value = '', $config = null): void
    {
        parent::__construct($name, $title, $value);

        if ($config) {
            $this->setEditorConfig($config);
        }

        $this->setRows(HTMLEditorField::config()->default_rows);
    }

    public function getAttributes(): array
    {
        // Fix CSS height based on rows
        $rowHeight = $this->config()->get('fixed_row_height');
        $attributes = [];
        if ($rowHeight) {
            $height = $this->getRows() * $rowHeight;
            $attributes['style'] = sprintf('height: %dpx;', $height);
        }

        // Merge attributes
        return array_merge(
            $attributes,
            parent::getAttributes(),
            $this->getEditorConfig()->getAttributes()
        );
    }

    /**
     * @param DataObject|DataObjectInterface $record
     * @throws Exception
     */
    public function saveInto(DataObjectInterface $record): void
    {
        if ($record->hasField($this->name) && $record->escapeTypeForField($this->name) != 'xml') {
            throw new Exception(
                'HTMLEditorField->saveInto(): This field should save into a HTMLText or HTMLVarchar field.'
            );
        }

        // Sanitise if requested
        $htmlValue = HTMLValue::create($this->Value());
        if (HTMLEditorField::config()->sanitise_server_side) {
            $santiser = HTMLEditorSanitiser::create(HTMLEditorConfig::get_active());
            $santiser->sanitise($htmlValue);
        }

        // optionally manipulate the HTML after a TinyMCE edit and prior to a save
        $this->extend('processHTML', $htmlValue);

        // Store into record
        $record->{$this->name} = $htmlValue->getContent();
    }

    public function setValue(string $value, array|DNADesign\Elemental\Models\ElementContent $data = null): SilverStripe\Forms\HTMLEditor\HTMLEditorField
    {
        // Regenerate links prior to preview, so that the editor can see them.
        $value = ImageShortcodeProvider::regenerate_html_links($value);
        return parent::setValue($value);
    }

    /**
     * @return HTMLEditorField_Readonly
     */
    public function performReadonlyTransformation(): SilverStripe\Forms\HTMLEditor\HTMLEditorField_Readonly
    {
        return $this->castedCopy(HTMLEditorField_Readonly::class);
    }

    public function performDisabledTransformation(): SilverStripe\Forms\HTMLEditor\HTMLEditorField_Readonly
    {
        return $this->performReadonlyTransformation();
    }

    public function Field($properties = []): SilverStripe\ORM\FieldType\DBHTMLText
    {
        // Include requirements
        $this->getEditorConfig()->init();
        return parent::Field($properties);
    }

    public function getSchemaStateDefaults(): array
    {
        $stateDefaults = parent::getSchemaStateDefaults();
        $config = $this->getEditorConfig();
        $stateDefaults['data'] = $config->getConfigSchemaData();
        return $stateDefaults;
    }
}
