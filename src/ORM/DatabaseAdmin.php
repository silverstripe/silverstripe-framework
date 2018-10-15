<?php

namespace SilverStripe\ORM;

use BadMethodCallException;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Dev\DevelopmentAdmin;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Connect\DatabaseException;
use SilverStripe\ORM\FieldType\DBClassName;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;

/**
 * DatabaseAdmin class
 *
 * Utility functions for administrating the database. These can be accessed
 * via URL, e.g. http://www.yourdomain.com/db/build.
 */
class DatabaseAdmin extends Controller
{

    /// SECURITY ///
    private static $allowed_actions = array(
        'index',
        'build',
        'cleanup',
        'import'
    );

    /**
     * Obsolete classname values that should be remapped in dev/build
     */
    private static $classname_value_remapping = [
        'File' => 'SilverStripe\\Assets\\File',
        'Image' => 'SilverStripe\\Assets\\Image',
        'Folder' => 'SilverStripe\\Assets\\Folder',
        'Group' => 'SilverStripe\\Security\\Group',
        'LoginAttempt' => 'SilverStripe\\Security\\LoginAttempt',
        'Member' => 'SilverStripe\\Security\\Member',
        'MemberPassword' => 'SilverStripe\\Security\\MemberPassword',
        'Permission' => 'SilverStripe\\Security\\Permission',
        'PermissionRole' => 'SilverStripe\\Security\\PermissionRole',
        'PermissionRoleCode' => 'SilverStripe\\Security\\PermissionRoleCode',
        'RememberLoginHash' => 'SilverStripe\\Security\\RememberLoginHash',
    ];

    /**
     * Config setting to enabled/disable the display of record counts on the dev/build output
     */
    private static $show_record_counts = true;

    protected function init()
    {
        parent::init();

        // We allow access to this controller regardless of live-status or ADMIN permission only
        // if on CLI or with the database not ready. The latter makes it less errorprone to do an
        // initial schema build without requiring a default-admin login.
        // Access to this controller is always allowed in "dev-mode", or of the user is ADMIN.
        $allowAllCLI = DevelopmentAdmin::config()->get('allow_all_cli');
        $canAccess = (
            Director::isDev()
            || !Security::database_is_ready()
            // We need to ensure that DevelopmentAdminTest can simulate permission failures when running
            // "dev/tests" from CLI.
            || (Director::is_cli() && $allowAllCLI)
            || Permission::check("ADMIN")
        );
        if (!$canAccess) {
            Security::permissionFailure(
                $this,
                "This page is secured and you need administrator rights to access it. " .
                "Enter your credentials below and we will send you right along."
            );
        }
    }

    /**
     * Get the data classes, grouped by their root class
     *
     * @return array Array of data classes, grouped by their root class
     */
    public function groupedDataClasses()
    {
        // Get all root data objects
        $allClasses = get_declared_classes();
        $rootClasses = [];
        foreach ($allClasses as $class) {
            if (get_parent_class($class) == DataObject::class) {
                $rootClasses[$class] = array();
            }
        }

        // Assign every other data object one of those
        foreach ($allClasses as $class) {
            if (!isset($rootClasses[$class]) && is_subclass_of($class, DataObject::class)) {
                foreach ($rootClasses as $rootClass => $dummy) {
                    if (is_subclass_of($class, $rootClass)) {
                        $rootClasses[$rootClass][] = $class;
                        break;
                    }
                }
            }
        }
        return $rootClasses;
    }


    /**
     * When we're called as /dev/build, that's actually the index. Do the same
     * as /dev/build/build.
     */
    public function index()
    {
        return $this->build();
    }

    /**
     * Updates the database schema, creating tables & fields as necessary.
     */
    public function build()
    {
        // The default time limit of 30 seconds is normally not enough
        Environment::increaseTimeLimitTo(600);

        // If this code is being run outside of a dev/build or without a ?flush query string param,
        // the class manifest hasn't been flushed, so do it here
        $request = $this->getRequest();
        if (!array_key_exists('flush', $request->getVars()) && strpos($request->getURL(), 'dev/build') !== 0) {
            ClassLoader::inst()->getManifest()->regenerate(false);
        }

        $url = $this->getReturnURL();
        if ($url) {
            echo "<p>Setting up the database; you will be returned to your site shortly....</p>";
            $this->doBuild(true);
            echo "<p>Done!</p>";
            $this->redirect($url);
        } else {
            $quiet = $this->request->requestVar('quiet') !== null;
            $fromInstaller = $this->request->requestVar('from_installer') !== null;
            $populate = $this->request->requestVar('dont_populate') === null;
            $this->doBuild($quiet || $fromInstaller, $populate);
        }
    }

    /**
     * Gets the url to return to after build
     *
     * @return string|null
     */
    protected function getReturnURL()
    {
        $url = $this->request->getVar('returnURL');

        // Check that this url is a site url
        if (empty($url) || !Director::is_site_url($url)) {
            return null;
        }

        // Convert to absolute URL
        return Director::absoluteURL($url, true);
    }

    /**
     * Build the default data, calling requireDefaultRecords on all
     * DataObject classes
     */
    public function buildDefaults()
    {
        $dataClasses = ClassInfo::subclassesFor(DataObject::class);
        array_shift($dataClasses);

        if (!Director::is_cli()) {
            echo "<ul>";
        }

        foreach ($dataClasses as $dataClass) {
            singleton($dataClass)->requireDefaultRecords();
            if (Director::is_cli()) {
                echo "Defaults loaded for $dataClass\n";
            } else {
                echo "<li>Defaults loaded for $dataClass</li>\n";
            }
        }

        if (!Director::is_cli()) {
            echo "</ul>";
        }
    }

    /**
     * Returns the timestamp of the time that the database was last built
     *
     * @return string Returns the timestamp of the time that the database was
     *                last built
     */
    public static function lastBuilt()
    {
        $file = TEMP_PATH
            . DIRECTORY_SEPARATOR
            . 'database-last-generated-'
            . str_replace(array('\\','/',':'), '.', Director::baseFolder());

        if (file_exists($file)) {
            return filemtime($file);
        }
        return null;
    }


    /**
     * Updates the database schema, creating tables & fields as necessary.
     *
     * @param boolean $quiet Don't show messages
     * @param boolean $populate Populate the database, as well as setting up its schema
     * @param bool $testMode
     */
    public function doBuild($quiet = false, $populate = true, $testMode = false)
    {
        if ($quiet) {
            DB::quiet();
        } else {
            $conn = DB::get_conn();
            // Assumes database class is like "MySQLDatabase" or "MSSQLDatabase" (suffixed with "Database")
            $dbType = substr(get_class($conn), 0, -8);
            $dbVersion = $conn->getVersion();
            $databaseName = $conn->getSelectedDatabase();

            if (Director::is_cli()) {
                echo sprintf("\n\nBuilding database %s using %s %s\n\n", $databaseName, $dbType, $dbVersion);
            } else {
                echo sprintf("<h2>Building database %s using %s %s</h2>", $databaseName, $dbType, $dbVersion);
            }
        }

        // Set up the initial database
        if (!DB::is_active()) {
            if (!$quiet) {
                echo '<p><b>Creating database</b></p>';
            }

            // Load parameters from existing configuration
            $databaseConfig = DB::getConfig();
            if (empty($databaseConfig) && empty($_REQUEST['db'])) {
                throw new BadMethodCallException("No database configuration available");
            }
            $parameters = (!empty($databaseConfig)) ? $databaseConfig : $_REQUEST['db'];

            // Check database name is given
            if (empty($parameters['database'])) {
                throw new BadMethodCallException(
                    "No database name given; please give a value for SS_DATABASE_NAME or set SS_DATABASE_CHOOSE_NAME"
                );
            }
            $database = $parameters['database'];

            // Establish connection
            unset($parameters['database']);
            DB::connect($parameters);

            // Check to ensure that the re-instated SS_DATABASE_SUFFIX functionality won't unexpectedly
            // rename the database. To be removed for SS5
            if ($suffix = Environment::getEnv('SS_DATABASE_SUFFIX')) {
                $previousName = preg_replace("/{$suffix}$/", '', $database);

                if (!isset($_GET['force_suffix_rename']) && DB::get_conn()->databaseExists($previousName)) {
                    throw new DatabaseException(
                        "SS_DATABASE_SUFFIX was previously broken, but has now been fixed. This will result in your "
                        . "database being named \"{$database}\" instead of \"{$previousName}\" from now on. If this "
                        . "change is intentional, please visit dev/build?force_suffix_rename=1 to continue"
                    );
                }
            }

            // Create database
            DB::create_database($database);
        }

        // Build the database.  Most of the hard work is handled by DataObject
        $dataClasses = ClassInfo::subclassesFor(DataObject::class);
        array_shift($dataClasses);

        if (!$quiet) {
            if (Director::is_cli()) {
                echo "\nCREATING DATABASE TABLES\n\n";
            } else {
                echo "\n<p><b>Creating database tables</b></p><ul>\n\n";
            }
        }

        $showRecordCounts = (boolean)$this->config()->show_record_counts;

        // Initiate schema update
        $dbSchema = DB::get_schema();
        $dbSchema->schemaUpdate(function () use ($dataClasses, $testMode, $quiet, $showRecordCounts) {
            $dataObjectSchema = DataObject::getSchema();

            foreach ($dataClasses as $dataClass) {
                // Check if class exists before trying to instantiate - this sidesteps any manifest weirdness
                if (!class_exists($dataClass)) {
                    continue;
                }

                // Check if this class should be excluded as per testing conventions
                $SNG = singleton($dataClass);
                if (!$testMode && $SNG instanceof TestOnly) {
                    continue;
                }
                $tableName = $dataObjectSchema->tableName($dataClass);

                // Log data
                if (!$quiet) {
                    if ($showRecordCounts && DB::get_schema()->hasTable($tableName)) {
                        try {
                            $count = DB::query("SELECT COUNT(*) FROM \"$tableName\"")->value();
                            $countSuffix = " ($count records)";
                        } catch (Exception $e) {
                            $countSuffix = " (error getting record count)";
                        }
                    } else {
                        $countSuffix = "";
                    }

                    if (Director::is_cli()) {
                        echo " * $tableName$countSuffix\n";
                    } else {
                        echo "<li>$tableName$countSuffix</li>\n";
                    }
                }

                // Instruct the class to apply its schema to the database
                $SNG->requireTable();
            }
        });
        ClassInfo::reset_db_cache();

        if (!$quiet && !Director::is_cli()) {
            echo "</ul>";
        }

        if ($populate) {
            if (!$quiet) {
                if (Director::is_cli()) {
                    echo "\nCREATING DATABASE RECORDS\n\n";
                } else {
                    echo "\n<p><b>Creating database records</b></p><ul>\n\n";
                }
            }

            // Remap obsolete class names
            $remappingConfig = $this->config()->get('classname_value_remapping');
            $remappingFields = $this->getClassNameRemappingFields();
            foreach ($remappingFields as $className => $fieldNames) {
                foreach ($fieldNames as $fieldName) {
                    foreach ($remappingConfig as $oldClassName => $newClassName) {
                        $this->updateLegacyClassNames($className, $fieldName, $oldClassName, $newClassName);
                    }
                }
            }

            // Require all default records
            foreach ($dataClasses as $dataClass) {
                // Check if class exists before trying to instantiate - this sidesteps any manifest weirdness
                // Test_ indicates that it's the data class is part of testing system
                if (strpos($dataClass, 'Test_') === false && class_exists($dataClass)) {
                    if (!$quiet) {
                        if (Director::is_cli()) {
                            echo " * $dataClass\n";
                        } else {
                            echo "<li>$dataClass</li>\n";
                        }
                    }

                    DataObject::singleton($dataClass)->requireDefaultRecords();
                }
            }

            if (!$quiet && !Director::is_cli()) {
                echo "</ul>";
            }
        }

        touch(TEMP_PATH
            . DIRECTORY_SEPARATOR
            . 'database-last-generated-'
            . str_replace(array('\\', '/', ':'), '.', Director::baseFolder()));

        if (isset($_REQUEST['from_installer'])) {
            echo "OK";
        }

        if (!$quiet) {
            echo (Director::is_cli()) ? "\n Database build completed!\n\n" :"<p>Database build completed!</p>";
        }

        ClassInfo::reset_db_cache();
    }

    /**
     * Given a base data class, a field name and an old and new class name (value), look for obsolete ($oldClassName)
     * values in the $dataClass's $fieldName column and replace it with $newClassName.
     *
     * @param string $dataClass    The data class to look up
     * @param string $fieldName    The field name to look in for obsolete class names
     * @param string $oldClassName The old class name
     * @param string $newClassName The new class name
     */
    protected function updateLegacyClassNames($dataClass, $fieldName, $oldClassName, $newClassName)
    {
        $schema = DataObject::getSchema();
        // Check first to ensure that the class has the specified field to update
        if (!$schema->databaseField($dataClass, $fieldName, false)) {
            return;
        }

        // Load a list of any records that have obsolete class names
        $badRecordCount = DataObject::get($dataClass)
            ->filter([$fieldName => $oldClassName])
            ->count();

        if (!$badRecordCount) {
            return;
        }

        if (Director::is_cli()) {
            echo " * Correcting {$badRecordCount} obsolete {$fieldName} values for {$newClassName}\n";
        } else {
            echo "<li>Correcting {$badRecordCount} obsolete {$fieldName} values for {$newClassName}</li>\n";
        }
        $table = $schema->tableName($dataClass);

        $updateQuery = "UPDATE \"{$table}%s\" SET \"{$fieldName}\" = ? WHERE \"{$fieldName}\" = ?";
        $updateQueries = [sprintf($updateQuery, '')];

        // Remap versioned table class name values as well
        /** @var Versioned|DataObject $class */
        $class = DataObject::singleton($dataClass);
        if ($class->hasExtension(Versioned::class)) {
            if ($class->hasStages()) {
                $updateQueries[] = sprintf($updateQuery, '_Live');
            }
            $updateQueries[] = sprintf($updateQuery, '_Versions');
        }

        foreach ($updateQueries as $query) {
            DB::prepared_query($query, [$newClassName, $oldClassName]);
        }
    }

    /**
     * Find all DBClassName fields on valid subclasses of DataObject that should be remapped. This includes
     * `ClassName` fields as well as polymorphic class name fields.
     *
     * @return array[]
     */
    protected function getClassNameRemappingFields()
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
     * Remove invalid records from tables - that is, records that don't have
     * corresponding records in their parent class tables.
     */
    public function cleanup()
    {
        $baseClasses = [];
        foreach (ClassInfo::subclassesFor(DataObject::class) as $class) {
            if (get_parent_class($class) == DataObject::class) {
                $baseClasses[] = $class;
            }
        }

        $schema = DataObject::getSchema();
        foreach ($baseClasses as $baseClass) {
            // Get data classes
            $baseTable = $schema->baseDataTable($baseClass);
            $subclasses = ClassInfo::subclassesFor($baseClass);
            unset($subclasses[0]);
            foreach ($subclasses as $k => $subclass) {
                if (!DataObject::getSchema()->classHasTable($subclass)) {
                    unset($subclasses[$k]);
                }
            }

            if ($subclasses) {
                $records = DB::query("SELECT * FROM \"$baseTable\"");


                foreach ($subclasses as $subclass) {
                    $subclassTable = $schema->tableName($subclass);
                    $recordExists[$subclass] =
                        DB::query("SELECT \"ID\" FROM \"$subclassTable\"")->keyedColumn();
                }

                foreach ($records as $record) {
                    foreach ($subclasses as $subclass) {
                        $subclassTable = $schema->tableName($subclass);
                        $id = $record['ID'];
                        if (($record['ClassName'] != $subclass)
                            && (!is_subclass_of($record['ClassName'], $subclass))
                            && isset($recordExists[$subclass][$id])
                        ) {
                            $sql = "DELETE FROM \"$subclassTable\" WHERE \"ID\" = ?";
                            echo "<li>$sql [{$id}]</li>";
                            DB::prepared_query($sql, [$id]);
                        }
                    }
                }
            }
        }
    }
}
