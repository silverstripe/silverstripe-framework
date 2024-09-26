<?php

namespace SilverStripe\Dev\Command;

use BadMethodCallException;
use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\DevelopmentAdmin;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\ORM\Connect\TableBuilder;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBClassName;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Command to build the database.
 * Can be run either via an HTTP request or the CLI.
 */
class DbBuild extends DevCommand implements PermissionProvider
{
    use Extensible;

    protected static string $commandName = 'db:build';

    protected static string $description = 'Build/rebuild this environment. Run this whenever you have updated your project sources';

    private static array $permissions_for_browser_execution = [
        'CAN_DEV_BUILD',
    ];

    /**
     * Obsolete classname values that should be remapped while building the database.
     * Map old FQCN to new FQCN, e.g
     * 'App\\OldNamespace\\MyClass' => 'App\\NewNamespace\\MyClass'
     */
    private static array $classname_value_remapping = [];

    /**
     * Config setting to enabled/disable the display of record counts on the build output
     */
    private static bool $show_record_counts = true;

    public function getTitle(): string
    {
        return 'Environment Builder';
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        // The default time limit of 30 seconds is normally not enough
        Environment::increaseTimeLimitTo(600);

        // If this code is being run without a flush, we need to at least flush the class manifest
        if (!$input->getOption('flush')) {
            ClassLoader::inst()->getManifest()->regenerate(false);
        }

        $populate = !$input->getOption('no-populate');
        if ($input->getOption('dont_populate')) {
            $populate = false;
            Deprecation::notice(
                '6.0.0',
                '`dont_populate` is deprecated. Use `no-populate` instead',
                Deprecation::SCOPE_GLOBAL
            );
        }
        $this->doBuild($output, $populate);
        return Command::SUCCESS;
    }

    protected function getHeading(): string
    {
        $conn = DB::get_conn();
        // Assumes database class is like "MySQLDatabase" or "MSSQLDatabase" (suffixed with "Database")
        $dbType = substr(get_class($conn), 0, -8);
        $dbVersion = $conn->getVersion();
        $databaseName = $conn->getSelectedDatabase();
        return sprintf('Building database %s using %s %s', $databaseName, $dbType, $dbVersion);
    }

    /**
     * Updates the database schema, creating tables & fields as necessary.
     *
     * @param bool $populate Populate the database, as well as setting up its schema
     */
    public function doBuild(PolyOutput $output, bool $populate = true, bool $testMode = false): void
    {
        $this->extend('onBeforeBuild', $output, $populate, $testMode);

        if ($output->isQuiet()) {
            DB::quiet();
        }

        // Set up the initial database
        if (!DB::is_active()) {
            $output->writeln(['<options=bold>Creating database</>', '']);

            // Load parameters from existing configuration
            $databaseConfig = DB::getConfig();
            if (empty($databaseConfig)) {
                throw new BadMethodCallException("No database configuration available");
            }

            // Check database name is given
            if (empty($databaseConfig['database'])) {
                throw new BadMethodCallException(
                    "No database name given; please give a value for SS_DATABASE_NAME or set SS_DATABASE_CHOOSE_NAME"
                );
            }
            $database = $databaseConfig['database'];

            // Establish connection
            unset($databaseConfig['database']);
            DB::connect($databaseConfig);

            // Create database
            DB::create_database($database);
        }

        // Build the database.  Most of the hard work is handled by DataObject
        $dataClasses = ClassInfo::subclassesFor(DataObject::class);
        array_shift($dataClasses);

        $output->writeln(['<options=bold>Creating database tables</>', '']);
        $output->startList(PolyOutput::LIST_UNORDERED);

        $showRecordCounts = (bool) static::config()->get('show_record_counts');

        // Initiate schema update
        $dbSchema = DB::get_schema();
        $tableBuilder = TableBuilder::singleton();
        $tableBuilder->buildTables($dbSchema, $dataClasses, [], $output->isQuiet(), $testMode, $showRecordCounts);
        ClassInfo::reset_db_cache();

        $output->stopList();

        if ($populate) {
            $output->writeln(['<options=bold>Creating database records</>', '']);
            $output->startList(PolyOutput::LIST_UNORDERED);

            // Remap obsolete class names
            $this->migrateClassNames();

            // Require all default records
            foreach ($dataClasses as $dataClass) {
                // Check if class exists before trying to instantiate - this sidesteps any manifest weirdness
                // Test_ indicates that it's the data class is part of testing system
                if (strpos($dataClass ?? '', 'Test_') === false && class_exists($dataClass ?? '')) {
                    $output->writeListItem($dataClass);
                    DataObject::singleton($dataClass)->requireDefaultRecords();
                }
            }

            $output->stopList();
        }

        touch(static::getLastGeneratedFilePath());

        $output->writeln(['<options=bold>Database build completed!</>', '']);

        foreach ($dataClasses as $dataClass) {
            DataObject::singleton($dataClass)->onAfterBuild();
        }

        ClassInfo::reset_db_cache();

        $this->extend('onAfterBuild', $output, $populate, $testMode);
    }

    public function getOptions(): array
    {
        return [
            new InputOption(
                'no-populate',
                null,
                InputOption::VALUE_NONE,
                'Don\'t run <info>requireDefaultRecords()</info> on the models when building.'
                . 'This will build the table but not insert any records'
            ),
            new InputOption(
                'dont_populate',
                null,
                InputOption::VALUE_NONE,
                'Deprecated - use <info>no-populate</info> instead'
            )
        ];
    }

    public function providePermissions(): array
    {
        return [
            'CAN_DEV_BUILD' => [
                'name' => _t(__CLASS__ . '.CAN_DEV_BUILD_DESCRIPTION', 'Can execute /dev/build'),
                'help' => _t(__CLASS__ . '.CAN_DEV_BUILD_HELP', 'Can execute the build command (/dev/build).'),
                'category' => DevelopmentAdmin::permissionsCategory(),
                'sort' => 100
            ],
        ];
    }

    /**
     * Given a base data class, a field name and a mapping of class replacements, look for obsolete
     * values in the $dataClass's $fieldName column and replace it with $mapping
     *
     * @param string $dataClass The data class to look up
     * @param string $fieldName The field name to look in for obsolete class names
     * @param string[] $mapping Map of old to new classnames
     */
    protected function updateLegacyClassNameField(string $dataClass, string $fieldName, array $mapping): void
    {
        $schema = DataObject::getSchema();
        // Check first to ensure that the class has the specified field to update
        if (!$schema->databaseField($dataClass, $fieldName, false)) {
            return;
        }

        // Load a list of any records that have obsolete class names
        $table = $schema->tableName($dataClass);
        $currentClassNameList = DB::query("SELECT DISTINCT(\"{$fieldName}\") FROM \"{$table}\"")->column();

        // Get all invalid classes for this field
        $invalidClasses = array_intersect($currentClassNameList ?? [], array_keys($mapping ?? []));
        if (!$invalidClasses) {
            return;
        }

        $numberClasses = count($invalidClasses ?? []);
        DB::alteration_message(
            "Correcting obsolete {$fieldName} values for {$numberClasses} outdated types",
            'obsolete'
        );

        // Build case assignment based on all intersected legacy classnames
        $cases = [];
        $params = [];
        foreach ($invalidClasses as $invalidClass) {
            $cases[] = "WHEN \"{$fieldName}\" = ? THEN ?";
            $params[] = $invalidClass;
            $params[] = $mapping[$invalidClass];
        }

        foreach ($this->getClassTables($dataClass) as $table) {
            $casesSQL = implode(' ', $cases);
            $sql = "UPDATE \"{$table}\" SET \"{$fieldName}\" = CASE {$casesSQL} ELSE \"{$fieldName}\" END";
            DB::prepared_query($sql, $params);
        }
    }

    /**
     * Get tables to update for this class
     */
    protected function getClassTables(string $dataClass): iterable
    {
        $schema = DataObject::getSchema();
        $table = $schema->tableName($dataClass);

        // Base table
        yield $table;

        // Remap versioned table class name values as well
        /** @var Versioned|DataObject $dataClass */
        $dataClass = DataObject::singleton($dataClass);
        if ($dataClass->hasExtension(Versioned::class)) {
            if ($dataClass->hasStages()) {
                yield "{$table}_Live";
            }
            yield "{$table}_Versions";
        }
    }

    /**
     * Find all DBClassName fields on valid subclasses of DataObject that should be remapped. This includes
     * `ClassName` fields as well as polymorphic class name fields.
     *
     * @return array[]
     */
    protected function getClassNameRemappingFields(): array
    {
        $dataClasses = ClassInfo::getValidSubClasses(DataObject::class);
        $schema = DataObject::getSchema();
        $remapping = [];

        foreach ($dataClasses as $className) {
            $fieldSpecs = $schema->fieldSpecs($className);
            foreach ($fieldSpecs as $fieldName => $fieldSpec) {
                if (Injector::inst()->create($fieldSpec, 'Dummy') instanceof DBClassName) {
                    $remapping[$className][] = $fieldName;
                }
            }
        }

        return $remapping;
    }

    /**
     * Migrate all class names
     */
    protected function migrateClassNames(): void
    {
        $remappingConfig = static::config()->get('classname_value_remapping');
        $remappingFields = $this->getClassNameRemappingFields();
        foreach ($remappingFields as $className => $fieldNames) {
            foreach ($fieldNames as $fieldName) {
                $this->updateLegacyClassNameField($className, $fieldName, $remappingConfig);
            }
        }
    }

    /**
     * Returns the timestamp of the time that the database was last built
     * or an empty string if we can't find that information.
     */
    public static function lastBuilt(): string
    {
        $file = static::getLastGeneratedFilePath();
        if (file_exists($file)) {
            return filemtime($file);
        }
        return '';
    }

    public static function canRunInBrowser(): bool
    {
        // Must allow running in browser if DB hasn't been built yet or is broken
        // or the permission checks will throw an error
        return !Security::database_is_ready() || parent::canRunInBrowser();
    }

    private static function getLastGeneratedFilePath(): string
    {
        return TEMP_PATH
            . DIRECTORY_SEPARATOR
            . 'database-last-generated-'
            . str_replace(['\\', '/', ':'], '.', Director::baseFolder());
    }
}
