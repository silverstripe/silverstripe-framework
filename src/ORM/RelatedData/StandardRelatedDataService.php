<?php

namespace SilverStripe\ORM\RelatedData;

use ReflectionException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\Connect\Query;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\Queries\SQLSelect;

/**
 * Service class used to find all other DataObject instances that are related to a DataObject instance
 * in the database
 *
 * Example demonstrating what '$component' and '$componentClassName' variables refer to:
 * PHP model: private static $has_one = [ 'MyFile' => File::class ]
 * - $component: 'MyFile'
 * - $componentClassName: SilverStripe\Assets\File::class
 *
 * @internal
 */
class StandardRelatedDataService implements RelatedDataService
{

    /**
     * Used to prevent duplicate database queries
     *
     * @var array
     */
    private $queryIdens = [];

    /**
     * @var array
     */
    private $config;

    /**
     * @var DataObjectSchema
     */
    private $dataObjectSchema;

    /**
     * @var array
     */
    private $classToTableName;

    /**
     * Find all DataObject instances that have a linked relationship with $record
     *
     * @param DataObject $record
     * @param string[] $excludedClasses
     * @return SS_List
     */
    public function findAll(DataObject $record, array $excludedClasses = []): SS_List
    {
        // Do not query unsaved DataObjects
        if (!$record->exists()) {
            return ArrayList::create();
        }

        $this->config = Config::inst()->getAll();
        $this->dataObjectSchema = DataObjectSchema::create();
        $this->initClassToTableName();
        $classIDs = [];
        $throughClasses = [];

        // "regular" relations i.e. point from $record to different DataObject
        $this->addRelatedHasOnes($classIDs, $record);
        $this->addRelatedManyManys($classIDs, $record, $throughClasses);

        // Loop config data to find "reverse" relationships pointing back to $record
        foreach (array_keys($this->config ?? []) as $lowercaseClassName) {
            if (!class_exists($lowercaseClassName ?? '')) {
                continue;
            }
            // Example of $class: My\App\MyPage (extends SiteTree)
            try {
                $class = ClassInfo::class_name($lowercaseClassName);
            } catch (ReflectionException $e) {
                continue;
            }
            if (!is_subclass_of($class, DataObject::class)) {
                continue;
            }
            $this->addRelatedReverseHasOnes($classIDs, $record, $class);
            $this->addRelatedReverseManyManys($classIDs, $record, $class, $throughClasses);
        }
        $this->removeClasses($classIDs, $excludedClasses, $throughClasses);
        $classObjs = $this->fetchClassObjs($classIDs);
        return $this->deriveList($classIDs, $classObjs);
    }

    /**
     * Loop has_one relationships on the DataObject we're getting usage for
     * e.g. File.has_one = Page, Page.has_many = File
     *
     * @param array $classIDs
     * @param DataObject $record
     */
    private function addRelatedHasOnes(array &$classIDs, DataObject $record): void
    {
        $class = get_class($record);
        foreach ($record->hasOne() as $component => $componentClass) {
            $componentIDField = "{$component}ID";
            $tableName = $this->findTableNameContainingComponentIDField($class, $componentIDField);
            if ($tableName === '') {
                continue;
            }

            $select = sprintf('"%s"', $componentIDField);
            $where = sprintf('"ID" = %u AND "%s" > 0', $record->ID, $componentIDField);

            // Polymorphic
            // $record->ParentClass will return null if the column doesn't exist
            if ($componentIDField === 'ParentID' && $record->ParentClass) {
                $select .= ', "ParentClass"';
            }

            // Prevent duplicate counting of self-referential relations
            // The relation will still be fetched by $this::fetchReverseHasOneResults()
            if ($record instanceof $componentClass) {
                $where .= sprintf(' AND "%s" != %u', $componentIDField, $record->ID);
            }

            // Example SQL:
            // Normal:
            //   SELECT "MyPageID" FROM "MyFile" WHERE "ID" = 789 AND "MyPageID" > 0;
            // Prevent self-referential e.g. File querying File:
            //   SELECT "MyFileSubClassID" FROM "MyFile" WHERE "ID" = 456
            //      AND "MyFileSubClassID" > 0 AND MyFileSubClassID != 456;
            // Polymorphic:
            //   SELECT "ParentID", "ParentClass" FROM "MyFile" WHERE "ID" = 789 AND "ParentID" > 0;
            $results = SQLSelect::create(
                $select,
                sprintf('"%s"', $tableName),
                $where
            )->execute();
            $this->addResultsToClassIDs($classIDs, $results, $componentClass);
        }
    }

    /**
     * Find the table that contains $componentIDField - this is relevant for subclassed DataObjects
     * that live in the database as two tables that are joined together
     *
     * @param string $class
     * @param string $componentIDField
     * @return string
     */
    private function findTableNameContainingComponentIDField(string $class, string $componentIDField): string
    {
        $tableName = '';
        $candidateClass = $class;
        while ($candidateClass) {
            $dbFields = $this->dataObjectSchema->databaseFields($candidateClass, false);
            if (array_key_exists($componentIDField, $dbFields ?? [])) {
                $tableName = $this->dataObjectSchema->tableName($candidateClass);
                break;
            }
            $candidateClass = get_parent_class($candidateClass ?? '');
        }
        return $tableName;
    }

    /**
     * Loop many_many relationships on the DataObject we're getting usage for
     *
     * @param array $classIDs
     * @param DataObject $record
     * @param string[] $throughClasses
     */
    private function addRelatedManyManys(array &$classIDs, DataObject $record, array &$throughClasses): void
    {
        $class = get_class($record);
        foreach ($record->manyMany() as $component => $componentClass) {
            $componentClass = $this->updateComponentClass($componentClass, $throughClasses);
            // Ignore belongs_many_many_through with dot syntax, AND
            // Prevent duplicate counting of self-referential relations e.g.
            // MyFile::$many_many = [ 'MyFile' => MyFile::class ]
            // This relation will still be counted in $this::addRelatedReverseManyManys()
            if (strpos($componentClass ?? '', '.') !== false || $record instanceof $componentClass) {
                continue;
            }
            $results = $this->fetchManyManyResults($record, $class, $component, false);
            $this->addResultsToClassIDs($classIDs, $results, $componentClass);
        }
    }

    /**
     * Query the database to retrieve many-many results
     *
     * @param DataObject $record - The DataObject whose usage data is being retrieved, usually a File
     * @param string $class - example: My\App\SomePageType
     * @param string $component - example: 'SomeFiles' - My\App\SomePageType::SomeFiles()
     * @param bool $reverse - true: SomePage::SomeFiles(), false: SomeFile::SomePages()
     * @return Query|null
     */
    private function fetchManyManyResults(
        DataObject $record,
        string $class,
        string $component,
        bool $reverse
    ): ?Query {
        // Example php file: class MyPage ... private static $many_many = [ 'MyFile' => File::class ]
        $data = $this->dataObjectSchema->manyManyComponent($class, $component);
        if (!$data || !($data['join'] ?? false)) {
            return null;
        }
        $joinTableName = $this->deriveJoinTableName($data);
        if (!ClassInfo::hasTable($joinTableName)) {
            return null;
        }
        $usesThroughTable = $data['join'] != $joinTableName;

        $parentField = preg_replace('#ID$#', '', $data['parentField'] ?? '') . 'ID';
        $childField = preg_replace('#ID$#', '', $data['childField'] ?? '') . 'ID';
        $selectField = !$reverse ? $childField : $parentField;
        $selectFields = [$selectField];
        $whereField = !$reverse ? $parentField : $childField;

        // Support for polymorphic through objects such FileLink that allow for multiple class types on one side e.g.
        // ParentID: int, ParentClass: enum('File::class, SiteTree::class, ElementContent::class, ...')
        if ($usesThroughTable) {
            $dbFields = $this->dataObjectSchema->databaseFields($data['join']);
            if ($parentField === 'ParentID' && isset($dbFields['ParentClass'])) {
                $selectFields[] = 'ParentClass';
                if (!$reverse) {
                    return null;
                }
            }
        }

        // Prevent duplicate queries which can happen when an Image is inserted on a Page subclass via TinyMCE
        // and FileLink will make the same query multiple times for all the different page subclasses because
        // the FileLink is associated with the Base Page class database table
        $queryIden = implode('-', array_merge($selectFields, [$joinTableName, $whereField, $record->ID]));
        if (array_key_exists($queryIden, $this->queryIdens ?? [])) {
            return null;
        }
        $this->queryIdens[$queryIden] = true;

        return SQLSelect::create(
            sprintf('"' . implode('", "', $selectFields) . '"'),
            sprintf('"%s"', $joinTableName),
            sprintf('"%s" = %u', $whereField, $record->ID)
        )->execute();
    }

    /**
     * Contains special logic for some many_many_through relationships
     * $joinTableName, instead of the name of the join table, it will be a namespaced classname
     * Example $class: SilverStripe\Assets\Shortcodes\FileLinkTracking
     * Example $joinTableName: SilverStripe\Assets\Shortcodes\FileLink
     *
     * @param array $data
     * @return string
     */
    private function deriveJoinTableName(array $data): string
    {
        $joinTableName = $data['join'];
        if (!ClassInfo::hasTable($joinTableName) && class_exists($joinTableName ?? '')) {
            $class = $joinTableName;
            if (!isset($this->classToTableName[$class])) {
                return null;
            }
            $joinTableName = $this->classToTableName[$class];
        }
        return $joinTableName;
    }

    /**
     * @param array $classIDs
     * @param DataObject $record
     * @param string $class
     */
    private function addRelatedReverseHasOnes(array &$classIDs, DataObject $record, string $class): void
    {
        foreach (singleton($class)->hasOne() as $component => $componentClass) {
            if (!($record instanceof $componentClass)) {
                continue;
            }
            $results = $this->fetchReverseHasOneResults($record, $class, $component);
            $this->addResultsToClassIDs($classIDs, $results, $class);
        }
    }

    /**
     * Query the database to retrieve has_one results
     *
     * @param DataObject $record - The DataObject whose usage data is being retrieved, usually a File
     * @param string $class - Name of class with the relation to record
     * @param string $component - Name of relation to `$record` on `$class`
     * @return Query|null
     */
    private function fetchReverseHasOneResults(DataObject $record, string $class, string $component): ?Query
    {
        // Ensure table exists, this is required for TestOnly SapphireTest classes
        if (!isset($this->classToTableName[$class])) {
            return null;
        }
        $componentIDField = "{$component}ID";

        // Only get database fields from the current class model, not parent class model
        $dbFields = $this->dataObjectSchema->databaseFields($class, false);
        if (!isset($dbFields[$componentIDField])) {
            return null;
        }
        $tableName = $this->dataObjectSchema->tableName($class);
        $where = sprintf('"%s" = %u', $componentIDField, $record->ID);

        // Polymorphic - if $component is "Parent" or "Owner" then usually it will be polymorphic
        $isPolymorphic = DataObject::getSchema()->hasOneComponent($class, $component) === DataObject::class;
        if ($isPolymorphic) {
            $where .= sprintf(' AND "' . $component . 'Class" = %s', $this->prepareClassNameLiteral(get_class($record)));
        }

        // Example SQL:
        // Normal:
        //   SELECT "ID" FROM "MyPage" WHERE "MyFileID" = 123;
        // Polymorphic:
        //   SELECT "ID" FROM "MyPage" WHERE "ParentID" = 456 AND "ParentClass" = 'MyFile';
        return SQLSelect::create(
            '"ID"',
            sprintf('"%s"', $tableName),
            $where
        )->execute();
    }

    /**
     * @param array $classIDs
     * @param DataObject $record
     * @param string $class
     * @param string[] $throughClasses
     */
    private function addRelatedReverseManyManys(
        array &$classIDs,
        DataObject $record,
        string $class,
        array &$throughClasses
    ): void {
        foreach (singleton($class)->manyMany() as $component => $componentClass) {
            $componentClass = $this->updateComponentClass($componentClass, $throughClasses);
            if (!($record instanceof $componentClass) ||
                // Ignore belongs_many_many_through with dot syntax
                strpos($componentClass ?? '', '.') !== false
            ) {
                continue;
            }
            $results = $this->fetchManyManyResults($record, $class, $component, true);
            $this->addResultsToClassIDs($classIDs, $results, $class);
        }
    }

    /**
     * Update the `$classIDs` array with the relationship IDs from database `$results`
     *
     * @param array $classIDs
     * @param Query|null $results
     * @param string $class
     */
    private function addResultsToClassIDs(array &$classIDs, ?Query $results, string $class): void
    {
        if (is_null($results) || (!is_subclass_of($class, DataObject::class) && $class !== DataObject::class)) {
            return;
        }
        foreach ($results as $row) {
            if (count(array_keys($row ?? [])) === 2 && isset($row['ParentClass']) && isset($row['ParentID'])) {
                // Example $class: SilverStripe\Assets\Shortcodes\FileLinkTracking
                // Example $parentClass: Page
                $parentClass = $row['ParentClass'];
                $classIDs[$parentClass] = $classIDs[$parentClass] ?? [];
                $classIDs[$parentClass][] = $row['ParentID'];
            } else {
                if ($class === DataObject::class) {
                    continue;
                }
                foreach (array_values($row ?? []) as $classID) {
                    $classIDs[$class] = $classIDs[$class] ?? [];
                    $classIDs[$class][] = $classID;
                }
            }
        }
    }

    /**
     * Prepare an FQCN literal for database querying so that backslashes are escaped properly
     *
     * @param string $value
     * @return string
     */
    private function prepareClassNameLiteral(string $value): string
    {
        return DB::get_conn()->quoteString($value);
    }

    /**
     * Convert a many_many_through $componentClass array to the 'to' component on the 'through' object
     * If $componentClass represents a through object, then also update the $throughClasses array
     *
     * @param string|array $componentClass
     * @param string[] $throughClasses
     * @return string
     */
    private function updateComponentClass($componentClass, array &$throughClasses): string
    {
        if (!is_array($componentClass)) {
            return $componentClass;
        }
        $throughClass = $componentClass['through'];
        $throughClasses[$throughClass] = true;
        $lowercaseThroughClass = strtolower($throughClass ?? '');
        $toComponent = $componentClass['to'];
        return $this->config[$lowercaseThroughClass]['has_one'][$toComponent];
    }

    /**
     * Setup function to fix unit test specific issue
     */
    private function initClassToTableName(): void
    {
        $this->classToTableName = $this->dataObjectSchema->getTableNames();

        // Fix issue that only happens when unit-testing via SapphireTest
        // TestOnly class tables are only created if they're defined in SapphireTest::$extra_dataobject
        // This means there's a large number of TestOnly classes, unrelated to the UsedOnTable, that
        // do not have tables.  Remove these table-less classes from $classToTableName.
        foreach ($this->classToTableName as $class => $tableName) {
            if (!ClassInfo::hasTable($tableName)) {
                unset($this->classToTableName[$class]);
            }
        }
    }

    /**
     * Remove classes excluded via Extensions
     * Remove "through" classes used in many-many relationships
     *
     * @param array $classIDs
     * @param string[] $excludedClasses
     * @param string[] $throughClasses
     */
    private function removeClasses(array &$classIDs, array $excludedClasses, array $throughClasses): void
    {
        foreach (array_keys($classIDs ?? []) as $class) {
            if (isset($throughClasses[$class]) || in_array($class, $excludedClasses ?? [])) {
                unset($classIDs[$class]);
            }
        }
    }

    /**
     * Fetch all objects of a class in a single query for better performance
     *
     * @param array $classIDs
     * @return array
     */
    private function fetchClassObjs(array $classIDs): array
    {
        /** @var DataObject $class */
        $classObjs = [];
        foreach ($classIDs as $class => $ids) {
            $classObjs[$class] = [];
            foreach ($class::get()->filter('ID', $ids) as $obj) {
                $classObjs[$class][$obj->ID] = $obj;
            }
        }
        return $classObjs;
    }

    /**
     * Returned ArrayList can have multiple entries for the same DataObject
     * For example, the File is used multiple times on a single Page
     *
     * @param array $classIDs
     * @param array $classObjs
     * @return ArrayList
     */
    private function deriveList(array $classIDs, array $classObjs): ArrayList
    {
        $list = ArrayList::create();
        foreach ($classIDs as $class => $ids) {
            foreach ($ids as $id) {
                // Ensure the $classObj exists, this is to cover an edge case where there is an orphaned
                // many-many join table database record with no corresponding DataObject database record
                if (!isset($classObjs[$class][$id])) {
                    continue;
                }
                $list->push($classObjs[$class][$id]);
            }
        }
        return $list;
    }
}
