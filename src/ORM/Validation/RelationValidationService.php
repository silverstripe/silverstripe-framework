<?php

namespace SilverStripe\ORM\Validation;

use ReflectionException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Resettable;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

/**
 * Class RelationValidationService
 *
 * Basic validation of relationship setup, this tool makes sure your relationships are setup correctly in both directions
 * The validation is configurable and inspection can be narrowed down by namespace, class and relation name
 *
 * It is recommended to hook this up either to dev/build or your unit test suite
 *
 * @package SilverStripe\ORM\Validation
 */
class RelationValidationService implements Resettable
{

    use Configurable;
    use Injectable;

    /**
     * Relation listed here will not be inspected
     * The format 1 is <class>.<relation>
     * for example: Page::class.'.LinkTracking'
     * Format 2 is <class>
     * for example: Page::class
     * This will make all relations defined in the class not being inspected
     *
     * @var array
     */
    private static $disallowed_relations = [];

    /**
     * Only inspect classes with the following namespaces
     * Empty string represents classes without namespaces
     * Set value to null will disable the namespace (useful when overriding configuration)
     *
     * @var array
     */
    private static $allowed_namespaces = [
        'no-namespace' => '',
        'app' => 'App',
    ];

    protected $errors = [];

    public function flushErrors(): void
    {
        $this->errors = [];
    }

    public static function reset(): void
    {
        self::singleton()->flushErrors();
    }

    /**
     * Hook this into the @see DataObject::requireDefaultRecords() if you want the valid to run every dev build
     * for example:
     *
     * public function requireDefaultRecords()
     * {
     *      parent::requireDefaultRecords();
     *
     *      if (static::class !== self::class) {
     *          return;
     *      }
     *
     *      RelationValidationService::singleton()->devBuildCheck();
     * }
     *
     * @throws ReflectionException
     */
    public function devBuildCheck(): void
    {
        $errors = $this->validateRelations();

        foreach ($errors as $message) {
            DB::alteration_message($message, 'notice');
        }
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
        self::reset();
        $classes = ClassInfo::subclassesFor(DataObject::class);

        foreach ($classes as $class) {
            $this->validateClass($class);
        }

        return $this->errors;
    }

    /**
     * @param string $class
     */
    protected function validateClass(string $class): void
    {
        if ($this->isIgnored($class)) {
            return;
        }

        $singleton = DataObject::singleton($class);

        if (!$singleton instanceof DataObject) {
            $this->logError($class,  '', 'Inspected class is not a DataObject.');

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
            if ($this->isIgnored($class, $relationName)) {
                continue;
            }

            $relatedObject = DataObject::singleton($relationData);

            if (!$relatedObject instanceof DataObject) {
                $this->logError($class, $relationName, sprintf('Related class %s is not a DataObject.', $relationData));

                return;
            }

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
            } else if ($found > 1) {
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
                $this->logError($class, $relationName, 'Relation is not in the expected format (class.relation)');

                continue;
            }

            $relatedClass = $parsedRelation['class'];
            $relatedRelation = $parsedRelation['relation'];
            $relatedObject = DataObject::singleton($relatedClass);

            if (!$relatedObject instanceof DataObject) {
                $this->logError($class, $relationName, sprintf('Related class %s is not a DataObject.', $relatedClass));

                continue;
            }

            $relatedRelations = (array) $relatedObject->config()->uninherited('has_one');

            if (array_key_exists($relatedRelation, $relatedRelations)) {
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
                $this->logError($class, $relationName, 'Relation is not in the expected format (class.relation)');

                continue;
            }

            $relatedClass = $parsedRelation['class'];
            $relatedRelation = $parsedRelation['relation'];
            $relatedObject = DataObject::singleton($relatedClass);

            if (!$relatedObject instanceof DataObject) {
                $this->logError($class, $relationName, sprintf('Related class %s is not a DataObject.', $relatedClass));

                continue;
            }

            $relatedRelations = (array) $relatedObject->config()->uninherited('has_one');

            if (array_key_exists($relatedRelation, $relatedRelations)) {
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
            $relatedObject = DataObject::singleton($relatedClass);

            if (!$relatedObject instanceof DataObject) {
                $this->logError($class, $relationName, sprintf('Related class %s is not a DataObject.', $relatedClass));

                continue;
            }

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
            } else if ($found > 1) {
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
                $this->logError($class, $relationName, 'Relation is not in the expected format (class.relation)');

                continue;
            }

            $relatedClass = $parsedRelation['class'];
            $relatedRelation = $parsedRelation['relation'];
            $relatedObject = DataObject::singleton($relatedClass);

            if (!$relatedObject instanceof DataObject) {
                $this->logError($class, $relationName, sprintf('Related class %s is not a DataObject.', $relatedClass));

                continue;
            }

            $relatedRelations = (array) $relatedObject->config()->uninherited('many_many');

            if (array_key_exists($relatedRelation, $relatedRelations)) {
                continue;
            }

            $this->logError($class, $relationName, 'Back relation not found');
        }
    }

    /**
     * @param string $class
     * @param string|null $relation
     * @return bool
     */
    protected function isIgnored(string $class, ?string $relation = null): bool
    {
        // First, we match against disallowed rules
        $disallowedRelations = (array) $this->config()->get('disallowed_relations');

        if ($relation === null) {
            // Any relation in a class is supposed to be ignored so we don't need to check individual relations
            return in_array($class, $disallowedRelations);
        }

        foreach ($disallowedRelations as $relationData) {
            $parsedRelation = $this->parsePlainRelation($relationData);

            if ($parsedRelation === null) {
                continue;
            }

            if ($class === $parsedRelation['class'] && $relation === $parsedRelation['relation']) {
                // This class and relation combination is supposed to be ignored
                return true;
            }
        }

        // Second, we match against allowed rules
        $allowedNamespaces = (array) $this->config()->get('allowed_namespaces');

        foreach ($allowedNamespaces as $namespace) {
            if ($namespace === null) {
                continue;
            }

            // Special case for classes without a namespace
            if ($namespace === '') {
                if ($class === ClassInfo::shortName($class)) {
                    return false;
                }

                continue;
            }

            // Match namespace
            if (mb_strpos($class, $namespace) === 0) {
                return false;
            }
        }

        // Class is not allowed
        return true;
    }

    /**
     * @param string $relationData
     * @return array|null
     */
    protected function parsePlainRelation(string $relationData): ?array
    {
        if (mb_strpos($relationData, '.') === false) {
            return null;
        }

        $segments = explode('.', $relationData);

        if (count($segments) !== 2) {
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
                if (!array_key_exists($key, $relationData)) {
                    return null;
                }
            }

            $to = $relationData['to'];
            $through = $relationData['through'];

            $throughObject = DataObject::singleton($through);

            if (!$throughObject instanceof DataObject) {
                return null;
            }

            $throughRelations = (array) $throughObject->config()->uninherited('has_one');

            if (!array_key_exists($to, $throughRelations)) {
                return null;
            }

            return $throughRelations[$to];
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
