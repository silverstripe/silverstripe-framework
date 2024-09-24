<?php

namespace SilverStripe\Model\ModelFields;

use SilverStripe\Core\Convert;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use SilverStripe\Model\ModelData;
use SilverStripe\Core\Validation\ValidationResult;

abstract class ModelField extends ModelData
{
    /**
     * Raw value of this field
     */
    protected mixed $value = null;

    /**
     * Name of this field
     */
    protected ?string $name = null;

    /**
     * The escape type for this field when inserted into a template - either "xml" or "raw".
     */
    private static string $escape_type = 'raw';

    private static array $casting = [
        'ATT' => 'HTMLFragment',
        'CDATA' => 'HTMLFragment',
        'HTML' => 'HTMLFragment',
        'HTMLATT' => 'HTMLFragment',
        'JS' => 'HTMLFragment',
        'RAW' => 'HTMLFragment',
        'RAWURLATT' => 'HTMLFragment',
        'URLATT' => 'HTMLFragment',
        'XML' => 'HTMLFragment',
        'ProcessedRAW' => 'HTMLFragment',
    ];

    public function __construct(?string $name = null)
    {
        $this->name = $name;
        parent::__construct();
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Returns the name of this field.
     */
    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * Returns the value of this field.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Determine 'default' casting for this field.
     */
    public function forTemplate(): string
    {
        // Default to XML encoding
        return $this->XML();
    }

    /**
     * Gets the value appropriate for a HTML attribute string
     */
    public function HTMLATT(): string
    {
        return Convert::raw2htmlatt($this->RAW());
    }

    /**
     * urlencode this string
     */
    public function URLATT(): string
    {
        return urlencode($this->RAW() ?? '');
    }

    /**
     * rawurlencode this string
     */
    public function RAWURLATT(): string
    {
        return rawurlencode($this->RAW() ?? '');
    }

    /**
     * Gets the value appropriate for a HTML attribute string
     */
    public function ATT(): string
    {
        return Convert::raw2att($this->RAW());
    }

    /**
     * Gets the raw value for this field.
     * Note: Skips processors implemented via forTemplate()
     */
    public function RAW(): mixed
    {
        return $this->getValue();
    }

    /**
     * Gets javascript string literal value
     */
    public function JS(): string
    {
        return Convert::raw2js($this->RAW());
    }

    /**
     * Return JSON encoded value
     */
    public function JSON(): string
    {
        return json_encode($this->RAW());
    }

    /**
     * Alias for {@see XML()}
     */
    public function HTML(): string
    {
        return $this->XML();
    }

    /**
     * XML encode this value
     */
    public function XML(): string
    {
        return Convert::raw2xml($this->RAW());
    }

    /**
     * Safely escape for XML string
     */
    public function CDATA(): string
    {
        return $this->XML();
    }

    /**
     * Saves this field to the given data object.
     *
     * TODO: probably rename, it's just setting a field on a model
     */
    public function saveInto(ModelData $model): void
    {
        $fieldName = $this->name;
        if (empty($fieldName)) {
            throw new \BadMethodCallException(
                "ModelField::saveInto() Called on a nameless '" . static::class . "' object"
            );
        }
        if ($this->value instanceof ModelField) {
            $this->value->saveInto($model);
        } else {
            $model->__set($fieldName, $this->value);
        }
    }

    public function validate(): ValidationResult
    {
        return ValidationResult::create();
    }

    /**
     * Returns a FormField instance used as a default
     * for form scaffolding.
     *
     * Used by {@link SearchContext}, {@link ModelAdmin}, {@link DataObject::scaffoldFormFields()}
     *
     * @param string $title Optional. Localized title of the generated instance
     */
    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        return TextField::create($this->name, $title);
    }

    /**
     * Returns a FormField instance used as a default
     * for searchform scaffolding.
     *
     * Used by {@link SearchContext}, {@link ModelAdmin}, {@link DataObject::scaffoldFormFields()}.
     *
     * @param string $title Optional. Localized title of the generated instance
     */
    public function scaffoldSearchField(?string $title = null): ?FormField
    {
        return $this->scaffoldFormField($title);
    }

    /**
     * Get formfield schema value for use in formschema response
     */
    public function getSchemaValue(): mixed
    {
        return $this->RAW();
    }

    public function debug(): string
    {
        return <<<DBG
<ul>
	<li><b>Name:</b>{$this->name}</li>
	<li><b>Table:</b>{$this->tableName}</li>
	<li><b>Value:</b>{$this->value}</li>
</ul>
DBG;
    }

    public function __toString(): string
    {
        return (string)$this->forTemplate();
    }

    /**
     * Whether or not this ModelField only accepts scalar values.
     *
     * Composite ModelFields can override this method and return `false` so they can accept arrays of values.
     */
    public function scalarValueOnly(): bool
    {
        return true;
    }
}
