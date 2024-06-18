<?php

namespace SilverStripe\Dev\Validation;

use InvalidArgumentException;
use ReflectionException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Resettable;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\DB;

/**
 * Basic validation of relationship setup, this tool makes sure your relationships are set up correctly in both directions
 * The validation is configurable and inspection can be narrowed down by namespace, class and relation name
 *
 * This tool is opt-in and runs via flush and outputs notices
 * For strict validation it is recommended to hook this up to your unit test suite
 */
class RelationValidationService implements Resettable
{
    use Configurable;
    use Injectable;

    /**
     * Enable / disable validation output during flush
     * This is disabled by default (opt-in)
     *
     * @var bool
     */
    private static $output_enabled = false;

    /**
     * Only inspect classes with the following namespaces/class prefixes
     * Empty string is a special value which represents classes without namespaces
     * Set the value to null to disable the rule (useful when overriding configuration)
     *
     * @var array
     */
    private static $allow_rules = [
        'empty' => '',
        'app' => 'App',
    ];

    /**
     * Any classes with the following namespaces/class prefixes will not be inspected
     * This config is intended to be used together with @see $allow_rules to narrow down the inspected classes
     * Empty string is a special value which represents classes without namespaces
     * Set the value to null to disable the rule (useful when overriding configuration)
     *
     * @var array
     */
    private static $deny_rules = [];

    /**
     * Relations listed here will not be inspected
     * Format is <class>.<relation>
     * for example: Page::class.'.LinkTracking'
     *
     * @var array
     */
    private static $deny_relations = [];

    /**
     * Ignore any configuration, useful for debugging specific classes
     *
     * @var bool
     */
    protected $ignoreConfig = false;

    /**
     * @var array
     */
    protected $errors = [];

    public function clearErrors(): void
    {
        $this->errors = [];
    }

    public static function reset(): void
    {
        $service = RelationValidationService::singleton();
        $service->clearErrors();
        $service->ignoreConfig = false;
    }

    /**
     * Hook this into your unit tests and assert for empty array like this
     *
     * $messages = RelationValidationService::singleton()->validateRelations();
     * $this->assertEmpty($messages, print_r($messages, true));
     *
     * @return array
     * @throws ReflectionException
     */
    public function validateRelations(): array
    {
        RelationValidationService::reset();
        $classes = ClassInfo::subclassesFor(DataObject::class);

        return $this->validateClasses($classes);
    }

    /**
     * @throws ReflectionException
     */
    public function executeValidation(): void
    {
        $errors = $this->validateRelations();
        $count = count($errors ?? []);

        if ($count === 0) {
            return;
        }

        DB::alteration_message(
            sprintf(
                '%s : %d issues found (listed below)',
                ClassInfo::shortName(static::class),
                $count
            ),
            'error'
        );

        foreach ($errors as $message) {
            DB::alteration_message($message, 'error');
        }
    }

    /**
     * Inspect specified classes - this ignores any configuration
     * Useful for checking specific classes when trying to fix relation configuration
     *
     * @param array $classes
     * @return array
     */
    public function inspectClasses(array $classes): array
    {
        RelationValidationService::reset();
        $this->ignoreConfig = true;

        return $this->validateClasses($classes);
    }

    /**
     * Check if class is ignored during inspection or not
     * Useful checking if your configuration works as expected
     * Check goes through rules in this order (from generic to more specific):
     * 1 - Allow rules
     * 2 - Deny rules
     * 3 - Deny relations
     *
     * @param string $class
     * @param string|null $relation
     * @return bool
     */
    public function isIgnored(string $class, ?string $relation = null): bool
    {
        // Top level override - bail out if configuration should be ignored
        if ($this->ignoreConfig) {
            return false;
        }

        // Allow rules - if class doesn't match any allow rule we bail out
        if (!$this->matchRules($class, 'allow_rules')) {
            return true;
        }

        // Deny rules - if class matches any deny rule we bail out
        if ($this->matchRules($class, 'deny_rules')) {
            return true;
        }

        if ($relation === null) {
            // Check is for the class as a whole so we don't need to check specific relation
            // Class is considered NOT ignored
            return false;
        }

        // Deny relations
        $rules = (array) $this->config()->get('deny_relations');

        foreach ($rules as $relationData) {
            if ($relationData === null) {
                // Disabled rule - bail out
                continue;
            }

            $parsedRelation = $this->parsePlainRelation($relationData);

            if ($parsedRelation === null) {
                // Invalid rule - bail out
                continue;
            }

            if ($class === $parsedRelation['class'] && $relation === $parsedRelation['relation']) {
                // This class and relation combination is supposed to be ignored
                return true;
            }
        }

        // Default - Class is considered NOT ignored
        return false;
    }

    /**
     * Match class against specified rules
     *
     * @param string $class
     * @param string $rule
     * @return bool
     */
    protected function matchRules(string $class, string $rule): bool
    {
        $rules = (array) $this->config()->get($rule);

        foreach ($rules as $key => $pattern) {
            if ($pattern === null) {
                // Disabled rule - bail out
                continue;
            }

            // Special case for classes without a namespace
            if ($pattern === '') {
                if ($class === ClassInfo::shortName($class)) {
                    // This is a class without namespace so we match this rule
                    return true;
                }

                continue;
            }

            if (mb_strpos($class ?? '', $pattern ?? '') === 0) {
                // Classname prefix matches the pattern
                return true;
            }
        }

        return false;
    }

    /**
     * Execute validation for specified classes
     *
     * @param array $classes
     * @return array
     */
    protected function validateClasses(array $classes): array
    {
        foreach ($classes as $class) {
            if ($class === DataObject::class) {
                // This is a generic class and doesn't need to be validated
                continue;
            }

            if ($this->isIgnored($class)) {
                continue;
            }

            $this->validateClass($class);
        }

        return $this->errors;
    }

    /**
     * @param string $class
     */
    protected function validateClass(string $class): void
    {
        if (!is_subclass_of($class, DataObject::class)) {
            $this->logError($class, '', 'Inspected class is not a DataObject.');

            return;
        }

        $this->validateHasOne($class);
        $this->validateBelongsTo($class);
        $this->validateHasMany($class);
        $this->validateManyMany($class);
        $this->validateBelongsManyMany($class);
    }

    /**
     * @param string $class
     */
    protected function validateHasOne(string $class): void
    {
        $singleton = DataObject::singleton($class);
        $relations = (array) $singleton->config()->uninherited('has_one');

        foreach ($relations as $relationName => $relationData) {
            if (is_array($relationData)) {
                $spec = $relationData;
                if (!isset($spec['class'])) {
                    $this->logError($class, $relationName, 'No class has been defined for this relation.');
                    continue;
                }
                $relationData = $spec['class'];
                if (($spec[DataObjectSchema::HAS_ONE_MULTI_RELATIONAL] ?? false) === true
                    && $relationData !== DataObject::class
                ) {
                    $this->logError(
                        $class,
                        $relationName,
                        'has_one relation that can handle multiple reciprocal has_many relations must be polymorphic.'
                    );
                    continue;
                }
            }

            if ($this->isIgnored($class, $relationName)) {
                continue;
            }

            if (mb_strpos($relationData ?? '', '.') !== false) {
                $this->logError(
                    $class,
                    $relationName,
                    sprintf('Relation %s is not in the expected format (needs class only format).', $relationData)
                );

                return;
            }

            // Skip checking for back relations when has_one is polymorphic
            if ($relationData === DataObject::class) {
                continue;
            }

            if (!is_subclass_of($relationData, DataObject::class)) {
                $this->logError(
                    $class,
                    $relationName,
                    sprintf('Related class %s is not a DataObject.', $relationData)
                );

                return;
            }

            $relatedObject = DataObject::singleton($relationData);

            // Try to find the back relation - it can be either in belongs_to or has_many
            $belongsTo = (array) $relatedObject->config()->uninherited('belongs_to');
            $hasMany = (array) $relatedObject->config()->uninherited('has_many');
            $found = 0;

            foreach ([$hasMany, $belongsTo] as $relationItem) {
                foreach ($relationItem as $key => $value) {
                    $parsedRelation = $this->parsePlainRelation($value);

                    if ($parsedRelation === null) {
                        continue;
                    }

                    if ($class !== $parsedRelation['class']) {
                        continue;
                    }

                    if ($relationName !== $parsedRelation['relation']) {
                        continue;
                    }

                    $found += 1;
                }
            }

            if ($found === 0) {
                $this->logError(
                    $class,
                    $relationName,
                    'Back relation not found or ambiguous (needs class.relation format)'
                );
            } elseif ($found > 1) {
                $this->logError($class, $relationName, 'Back relation is ambiguous');
            }
        }
    }

    /**
     * @param string $class
     */
    protected function validateBelongsTo(string $class): void
    {
        $singleton = DataObject::singleton($class);
        $relations = (array) $singleton->config()->uninherited('belongs_to');

        foreach ($relations as $relationName => $relationData) {
            if ($this->isIgnored($class, $relationName)) {
                continue;
            }

            $parsedRelation = $this->parsePlainRelation($relationData);

            if ($parsedRelation === null) {
                $this->logError(
                    $class,
                    $relationName,
                    'Relation is not in the expected format (needs class.relation format)'
                );

                continue;
            }

            $relatedClass = $parsedRelation['class'];
            $relatedRelation = $parsedRelation['relation'];

            if (!is_subclass_of($relatedClass, DataObject::class)) {
                $this->logError(
                    $class,
                    $relationName,
                    sprintf('Related class %s is not a DataObject.', $relatedClass)
                );

                continue;
            }

            $relatedObject = DataObject::singleton($relatedClass);
            $relatedRelations = (array) $relatedObject->config()->uninherited('has_one');

            if (array_key_exists($relatedRelation, $relatedRelations ?? [])) {
                continue;
            }

            $this->logError($class, $relationName, 'Back relation not found');
        }
    }

    /**
     * @param string $class
     */
    protected function validateHasMany(string $class): void
    {
        $singleton = DataObject::singleton($class);
        $relations = (array) $singleton->config()->uninherited('has_many');

        foreach ($relations as $relationName => $relationData) {
            if ($this->isIgnored($class, $relationName)) {
                continue;
            }

            $parsedRelation = $this->parsePlainRelation($relationData);

            if ($parsedRelation === null) {
                $this->logError(
                    $class,
                    $relationName,
                    'Relation is not in the expected format (needs class.relation format)'
                );

                continue;
            }

            $relatedClass = $parsedRelation['class'];
            $relatedRelation = $parsedRelation['relation'];

            if (!is_subclass_of($relatedClass, DataObject::class)) {
                $this->logError(
                    $class,
                    $relationName,
                    sprintf('Related class %s is not a DataObject.', $relatedClass)
                );

                continue;
            }

            $relatedObject = DataObject::singleton($relatedClass);
            $relatedRelations = (array) $relatedObject->config()->uninherited('has_one');

            if (array_key_exists($relatedRelation, $relatedRelations ?? [])) {
                continue;
            }

            $this->logError(
                $class,
                $relationName,
                'Back relation not found or ambiguous (needs class.relation format)'
            );
        }
    }

    /**
     * @param string $class
     */
    protected function validateManyMany(string $class): void
    {
        $singleton = DataObject::singleton($class);
        $relations = (array) $singleton->config()->uninherited('many_many');

        foreach ($relations as $relationName => $relationData) {
            if ($this->isIgnored($class, $relationName)) {
                continue;
            }

            $relatedClass = $this->parseManyManyRelation($relationData);

            if (!is_subclass_of($relatedClass, DataObject::class)) {
                $this->logError(
                    $class,
                    $relationName,
                    sprintf('Related class %s is not a DataObject.', $relatedClass)
                );

                continue;
            }

            $relatedObject = DataObject::singleton($relatedClass);
            $relatedRelations = (array) $relatedObject->config()->uninherited('belongs_many_many');
            $found = 0;

            foreach ($relatedRelations as $key => $value) {
                $parsedRelation = $this->parsePlainRelation($value);

                if ($parsedRelation === null) {
                    continue;
                }

                if ($class !== $parsedRelation['class']) {
                    continue;
                }

                if ($relationName !== $parsedRelation['relation']) {
                    continue;
                }

                $found += 1;
            }

            if ($found === 0) {
                $this->logError(
                    $class,
                    $relationName,
                    'Back relation not found or ambiguous (needs class.relation format)'
                );
            } elseif ($found > 1) {
                $this->logError($class, $relationName, 'Back relation is ambiguous');
            }
        }
    }

    /**
     * @param string $class
     */
    protected function validateBelongsManyMany(string $class): void
    {
        $singleton = DataObject::singleton($class);
        $relations = (array) $singleton->config()->uninherited('belongs_many_many');

        foreach ($relations as $relationName => $relationData) {
            if ($this->isIgnored($class, $relationName)) {
                continue;
            }

            $parsedRelation = $this->parsePlainRelation($relationData);

            if ($parsedRelation === null) {
                $this->logError(
                    $class,
                    $relationName,
                    'Relation is not in the expected format (needs class.relation format)'
                );

                continue;
            }

            $relatedClass = $parsedRelation['class'];
            $relatedRelation = $parsedRelation['relation'];

            if (!is_subclass_of($relatedClass, DataObject::class)) {
                $this->logError(
                    $class,
                    $relationName,
                    sprintf('Related class %s is not a DataObject.', $relatedClass)
                );

                continue;
            }

            $relatedObject = DataObject::singleton($relatedClass);
            $relatedRelations = (array) $relatedObject->config()->uninherited('many_many');

            if (array_key_exists($relatedRelation, $relatedRelations ?? [])) {
                continue;
            }

            $this->logError($class, $relationName, 'Back relation not found');
        }
    }

    /**
     * @param string $relationData
     * @return array|null
     */
    protected function parsePlainRelation(string $relationData): ?array
    {
        if (mb_strpos($relationData ?? '', '.') === false) {
            return null;
        }

        $segments = explode('.', $relationData ?? '');

        if (count($segments ?? []) !== 2) {
            return null;
        }

        $class = array_shift($segments);
        $relation = array_shift($segments);

        return [
            'class' => $class,
            'relation' => $relation,
        ];
    }

    /**
     * @param array|string $relationData
     * @return string|null
     */
    protected function parseManyManyRelation($relationData): ?string
    {
        if (is_array($relationData)) {
            foreach (['through', 'to'] as $key) {
                if (!array_key_exists($key, $relationData ?? [])) {
                    return null;
                }
            }

            $to = $relationData['to'];
            $through = $relationData['through'];

            if (!is_subclass_of($through, DataObject::class)) {
                return null;
            }

            $throughObject = DataObject::singleton($through);
            $throughRelations = (array) $throughObject->config()->uninherited('has_one');

            if (!array_key_exists($to, $throughRelations ?? [])) {
                return null;
            }

            $spec = $throughRelations[$to];
            return is_array($spec) ? $spec['class'] ?? null : $spec;
        }

        return $relationData;
    }

    /**
     * @param string $class
     * @param string $relation
     * @param string $message
     */
    protected function logError(string $class, string $relation, string $message)
    {
        $classPrefix = $relation ? sprintf('%s / %s', $class, $relation) : $class;
        $this->errors[] = sprintf('%s : %s', $classPrefix, $message);
    }
}
