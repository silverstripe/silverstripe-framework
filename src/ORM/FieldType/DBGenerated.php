<?php

namespace SilverStripe\ORM\FieldType;

use InvalidArgumentException;
use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\FormField;
use SilverStripe\Model\ModelData;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Filters\SearchFilter;

/**
 * A column which gets generated based on some SQL statement.
 * This can be stored (generated whenever columns in the generation statement are updated), or virtual (generated on the fly when accessed).
 */
class DBGenerated extends DBField
{
    public const string GENERATION_STORED = 'STORED';

    public const string GENERATION_VIRTUAL = 'VIRTUAL';

    /**
     * DBField classes which must not be used as the underlying data representation for a generated column.
     */
    private static array $invalid_child_field_types = [
        DBClassName::class,
        DBClassNameVarchar::class,
        DBComposite::class,
        DBFile::class,
        DBForeignKey::class,
        DBGenerated::class,
        DBPolymorphicForeignKey::class,
        DBPrimaryKey::class,
    ];

    private DBField $childField;

    private string $sqlExpression;

    private string $generationType;

    public function __construct(
        ?string $name = null,
        string $fieldSpec = '',
        string $sqlExpression = '',
        string $generationType = DBGenerated::GENERATION_VIRTUAL
    ) {
        if (!in_array($generationType, [DBGenerated::GENERATION_STORED, DBGenerated::GENERATION_VIRTUAL])) {
            throw new InvalidArgumentException('$generationType must be "STORED" or "VIRTUAL"');
        }
        if ($name === null && $fieldSpec === '' && $sqlExpression === '') {
            // DataObjectSchema needs to be able to make a singleton with no args.
            // This means we need these to be able to be empty....
            // In that case, we can just skip all the fancy stuff.
            // Don't use the parent constructor because it calls setValue() even though we have no value.
            return;
        } elseif ($fieldSpec === '' || $sqlExpression === '') {
            throw new InvalidArgumentException('$fieldSpec and $sqlExpression must not be empty');
        }
        $this->sqlExpression = $sqlExpression;
        $this->generationType = $generationType;
        $this->childField = DBField::create_field($fieldSpec, null, $name);
        $this->validateChildFieldClass();
        $this->setFailover($this->childField);
        parent::__construct($name, []);
    }

    public function getChildField(): DBField
    {
        return $this->childField;
    }

    public function requireField(): void
    {
        $spec = $this->getChildField()->getFieldSpec();
        if (is_string($spec)) {
            $defaultValue = $this->getChildField()->getDefaultValue();
            $spec = DB::get_schema()->makeGenerated(
                $spec,
                ['default' => $defaultValue],
                $this->sqlExpression,
                $this->generationType
            );
        } else {
            $spec['generated'] = [
                'type' => $this->generationType,
                'expression' => $this->sqlExpression,
            ];
        }
        DB::require_field($this->getTable(), $this->getName(), $spec);
    }

    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        return $this->getChildField()->scaffoldFormField($title, $params)->performReadonlyTransformation();
    }

    public function scaffoldSearchField(?string $title = null): ?FormField
    {
        return $this->getChildField()->scaffoldSearchField($title);
    }

    public function defaultSearchFilter(?string $name = null): SearchFilter
    {
        return $this->getChildField()->defaultSearchFilter($name);
    }

    public function getIndexSpecs(): ?array
    {
        return $this->getChildField()->getIndexSpecs();
    }

    public function getIndexType()
    {
        return $this->getChildField()->getIndexType();
    }

    public function getSchemaValue(): mixed
    {
        return $this->getChildField()->getSchemaValue();
    }

    public function getValue(): mixed
    {
        return $this->getChildField()->getValue();
    }

    public function getArrayValue()
    {
        return $this->getChildField()->getArrayValue();
    }

    public function getDefaultValue(): mixed
    {
        return $this->getChildField()->getDefaultValue();
    }

    public function setValue(mixed $value, null|array|ModelData $record = null, bool $markChanged = true): static
    {
        $this->getChildField()->setValue($value, $record, $markChanged);
        return parent::setValue($value, $record, $markChanged);
    }

    public function setArrayValue($value): static
    {
        $this->getChildField()->setArrayValue($value);
        return parent::setArrayValue($value);
    }

    public function setDefaultValue(mixed $defaultValue): static
    {
        $this->getChildField()->setDefaultValue($defaultValue);
        return parent::setDefaultValue($defaultValue);
    }

    public function setName(string $name): static
    {
        $this->getChildField()->setName($name);
        return parent::setName($name);
    }

    public function setTable(string $tableName): static
    {
        $this->getChildField()->setTable($tableName);
        return parent::setTable($tableName);
    }

    public function hasValue(string $field, array $arguments = [], bool $cache = true): bool
    {
        return $this->getChildField()->hasValue($field, $arguments, $cache);
    }

    public function nullValue(): mixed
    {
        return $this->getChildField()->nullValue();
    }

    public function exists(): bool
    {
        return $this->getChildField()->exists();
    }

    public function obj(string $fieldName, array $arguments = [], bool $cache = false): ?object
    {
        return $this->getChildField()->obj($fieldName, $arguments, $cache);
    }

    public function castingHelper(string $field): ?string
    {
        return $this->getChildField()->castingHelper($field);
    }

    public function __toString(): string
    {
        return $this->getChildField()->__toString();
    }

    public function forTemplate(): string
    {
        return $this->getChildField()->forTemplate();
    }

    public function HTMLATT(): string
    {
        return $this->getChildField()->HTMLATT();
    }

    public function URLATT(): string
    {
        return $this->getChildField()->URLATT();
    }

    public function RAWURLATT(): string
    {
        return $this->getChildField()->RAWURLATT();
    }

    public function ATT(): string
    {
        return $this->getChildField()->ATT();
    }

    public function RAW(): mixed
    {
        return $this->getChildField()->RAW();
    }

    public function JS(): string
    {
        return $this->getChildField()->JS();
    }

    public function JSON(): string
    {
        return $this->getChildField()->JSON();
    }

    public function HTML(): string
    {
        return $this->getChildField()->HTML();
    }

    public function XML(): string
    {
        return $this->getChildField()->XML();
    }

    public function CDATA(): string
    {
        return $this->getChildField()->CDATA();
    }

    public function debug(): string
    {
        return $this->getChildField()->debug();
    }

    public function scalarValueOnly(): bool
    {
        return $this->getChildField()->scalarValueOnly();
    }

    public function renderWith($template, ModelData|array $customFields = []): DBHTMLText
    {
        return $this->getChildField()->renderWith($template, $customFields);
    }

    public function validate(): ValidationResult
    {
        return $this->getChildField()->validate();
    }

    public function writeToManipulation(array &$manipulation): void
    {
        // no-op - generated columns can't be updated manually.
    }

    private function validateChildFieldClass(): void
    {
        foreach (static::config()->get('invalid_child_field_types') as $invalidClass) {
            if (is_a($this->childField, $invalidClass)) {
                $class = get_class($this->childField);
                throw new InvalidArgumentException("Cannot create a generated field based on class '$class'.");
            }
        }
        if (!$this->childField->hasMethod('getFieldSpec')) {
            $class = get_class($this->childField);
            throw new InvalidArgumentException("Cannot create a generated field based on class '$class' - it needs a 'getFieldSpec' method.");
        }
    }
}
