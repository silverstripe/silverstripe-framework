<?php

namespace SilverStripe\Core;

use Exception;
use ReflectionClass;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

/**
 * Provides introspection information about the class tree.
 *
 * It's a cached wrapper around the built-in class functions.  SilverStripe uses
 * class introspection heavily and without the caching it creates an unfortunate
 * performance hit.
 */
class ClassInfo
{
    /**
     * Cache for {@link hasTable()}
     *
     * @internal
     * @var array
     */
    private static $_cache_all_tables = array();

    /**
     * @internal
     * @var array Cache for {@link ancestry()}.
     */
    private static $_cache_ancestry = array();

    /**
     * Cache for parse_class_spec
     *
     * @internal
     * @var array
     */
    private static $_cache_parse = [];

    /**
     * Cache for has_method_from
     *
     * @internal
     * @var array
     */
    private static $_cache_methods = array();

    /**
     * Cache for class_name
     *
     * @internal
     * @var array
     */
    private static $_cache_class_names = [];

    /**
     * Wrapper for classes getter.
     *
     * @return array List of all class names
     */
    public static function allClasses()
    {
        return ClassLoader::inst()->getManifest()->getClassNames();
    }

    /**
     * Returns true if a class or interface name exists.
     *
     * @param string $class
     * @return bool
     */
    public static function exists($class)
    {
        return class_exists($class, false)
            || interface_exists($class, false)
            || ClassLoader::inst()->getItemPath($class);
    }

    /**
     * @todo Move this to SS_Database or DB
     *
     * @param string $tableName
     * @return bool
     */
    public static function hasTable($tableName)
    {
        // Cache the list of all table names to reduce on DB traffic
        if (empty(self::$_cache_all_tables) && DB::is_active()) {
            self::$_cache_all_tables = DB::get_schema()->tableList();
        }
        return !empty(self::$_cache_all_tables[strtolower($tableName)]);
    }

    public static function reset_db_cache()
    {
        self::$_cache_all_tables = null;
        self::$_cache_ancestry = array();
    }

    /**
     * Returns the manifest of all classes which are present in the database.
     *
     * @param string $class Class name to check enum values for ClassName field
     * @param boolean $includeUnbacked Flag indicating whether or not to include
     * types that don't exist as implemented classes. By default these are excluded.
     * @return array List of subclasses
     */
    public static function getValidSubClasses($class = SiteTree::class, $includeUnbacked = false)
    {
        if (is_string($class) && !class_exists($class)) {
            return array();
        }

        $class = self::class_name($class);
        if ($includeUnbacked) {
            $table = DataObject::getSchema()->tableName($class);
            $classes = DB::get_schema()->enumValuesForField($table, 'ClassName');
        } else {
            $classes = static::subclassesFor($class);
        }
        return $classes;
    }

    /**
     * Returns an array of the current class and all its ancestors and children
     * which require a DB table.
     *
     * @todo Move this into {@see DataObjectSchema}
     *
     * @param string|object $nameOrObject Class or object instance
     * @return array
     */
    public static function dataClassesFor($nameOrObject)
    {
        if (is_string($nameOrObject) && !class_exists($nameOrObject)) {
            return [];
        }

        // Get all classes
        $class = self::class_name($nameOrObject);
        $classes = array_merge(
            self::ancestry($class),
            self::subclassesFor($class)
        );

        // Filter by table
        return array_filter($classes, function ($next) {
            return DataObject::getSchema()->classHasTable($next);
        });
    }

    /**
     * @deprecated 4.0..5.0
     * @param string $class
     * @return string
     */
    public static function baseDataClass($class)
    {
        Deprecation::notice('5.0', 'Use DataObject::getSchema()->baseDataClass()');
        return DataObject::getSchema()->baseDataClass($class);
    }

    /**
     * Returns a list of classes that inherit from the given class.
     * The resulting array includes the base class passed
     * through the $class parameter as the first array value.
     * Note that keys are lowercase, while the values are correct case.
     *
     * Example usage:
     * <code>
     * ClassInfo::subclassesFor('BaseClass');
     *  array(
     *  'baseclass' => 'BaseClass',
     *  'childclass' => 'ChildClass',
     *  'grandchildclass' => 'GrandChildClass'
     * )
     * </code>
     *
     * @param string|object $nameOrObject The classname or object
     * @return array List of class names with lowercase keys and correct-case values
     */
    public static function subclassesFor($nameOrObject)
    {
        if (is_string($nameOrObject) && !class_exists($nameOrObject)) {
            return [];
        }

        // Get class names
        $className = self::class_name($nameOrObject);
        $lowerClassName = strtolower($className);

        // Merge with descendants
        $descendants = ClassLoader::inst()->getManifest()->getDescendantsOf($className);
        return array_merge(
            [$lowerClassName => $className],
            $descendants
        );
    }

    /**
     * Convert a class name in any case and return it as it was defined in PHP
     *
     * eg: self::class_name('dataobJEct'); //returns 'DataObject'
     *
     * @param string|object $nameOrObject The classname or object you want to normalise
     * @throws \ReflectionException
     * @return string The normalised class name
     */
    public static function class_name($nameOrObject)
    {
        if (is_object($nameOrObject)) {
            return get_class($nameOrObject);
        }

        $key = strtolower($nameOrObject);
        if (!isset(static::$_cache_class_names[$key])) {
            // Get manifest name
            $name = ClassLoader::inst()->getManifest()->getItemName($nameOrObject);

            // Use reflection for non-manifest classes
            if (!$name) {
                $reflection = new ReflectionClass($nameOrObject);
                $name = $reflection->getName();
            }
            static::$_cache_class_names[$key] = $name;
        }

        return static::$_cache_class_names[$key];
    }

    /**
     * Returns the passed class name along with all its parent class names in an
     * array, sorted with the root class first.
     *
     * @param string|object $nameOrObject Class or object instance
     * @param bool $tablesOnly Only return classes that have a table in the db.
     * @return array List of class names with lowercase keys and correct-case values
     */
    public static function ancestry($nameOrObject, $tablesOnly = false)
    {
        if (is_string($nameOrObject) && !class_exists($nameOrObject)) {
            return [];
        }

        $class = self::class_name($nameOrObject);

        $lowerClass = strtolower($class);

        $cacheKey = $lowerClass . '_' . (string)$tablesOnly;
        $parent = $class;
        if (!isset(self::$_cache_ancestry[$cacheKey])) {
            $ancestry = [];
            do {
                if (!$tablesOnly || DataObject::getSchema()->classHasTable($parent)) {
                    $ancestry[strtolower($parent)] = $parent;
                }
            } while ($parent = get_parent_class($parent));
            self::$_cache_ancestry[$cacheKey] = array_reverse($ancestry);
        }

        return self::$_cache_ancestry[$cacheKey];
    }

    /**
     * @param string $interfaceName
     * @return array A self-keyed array of class names with lowercase keys and correct-case values.
     * Note that this is only available with Silverstripe classes and not built-in PHP classes.
     */
    public static function implementorsOf($interfaceName)
    {
        return ClassLoader::inst()->getManifest()->getImplementorsOf($interfaceName);
    }

    /**
     * Returns true if the given class implements the given interface
     *
     * @param string $className
     * @param string $interfaceName
     * @return bool
     */
    public static function classImplements($className, $interfaceName)
    {
        $lowerClassName = strtolower($className);
        $implementors = self::implementorsOf($interfaceName);
        return isset($implementors[$lowerClassName]);
    }

    /**
     * Get all classes contained in a file.
     *
     * @param string $filePath Path to a PHP file (absolute or relative to webroot)
     * @return array Map of lowercase class names to correct class name
     */
    public static function classes_for_file($filePath)
    {
        $absFilePath = Director::getAbsFile($filePath);
        $classManifest = ClassLoader::inst()->getManifest();
        $classes = $classManifest->getClasses();
        $classNames = $classManifest->getClassNames();

        $matchedClasses = [];
        foreach ($classes as $lowerClass => $compareFilePath) {
            if (strcasecmp($absFilePath, $compareFilePath) === 0) {
                $matchedClasses[$lowerClass] = $classNames[$lowerClass];
            }
        }

        return $matchedClasses;
    }

    /**
     * Returns all classes contained in a certain folder.
     *
     * @param string $folderPath Relative or absolute folder path
     * @return array Map of lowercase class names to correct class name
     */
    public static function classes_for_folder($folderPath)
    {
        $absFolderPath = Director::getAbsFile($folderPath);
        $classManifest = ClassLoader::inst()->getManifest();
        $classes = $classManifest->getClasses();
        $classNames = $classManifest->getClassNames();

        $matchedClasses = [];
        foreach ($classes as $lowerClass => $compareFilePath) {
            if (stripos($compareFilePath, $absFolderPath) === 0) {
                $matchedClasses[$lowerClass] = $classNames[$lowerClass];
            }
        }

        return $matchedClasses;
    }

    /**
     * Determine if the given class method is implemented at the given comparison class
     *
     * @param string $class Class to get methods from
     * @param string $method Method name to lookup
     * @param string $compclass Parent class to test if this is the implementor
     * @return bool True if $class::$method is declared in $compclass
     */
    public static function has_method_from($class, $method, $compclass)
    {
        $lClass = strtolower($class);
        $lMethod = strtolower($method);
        $lCompclass = strtolower($compclass);
        if (!isset(self::$_cache_methods[$lClass])) {
            self::$_cache_methods[$lClass] = array();
        }

        if (!array_key_exists($lMethod, self::$_cache_methods[$lClass])) {
            self::$_cache_methods[$lClass][$lMethod] = false;

            $classRef = new ReflectionClass($class);

            if ($classRef->hasMethod($method)) {
                $methodRef = $classRef->getMethod($method);
                self::$_cache_methods[$lClass][$lMethod] = $methodRef->getDeclaringClass()->getName();
            }
        }

        return strtolower(self::$_cache_methods[$lClass][$lMethod]) === $lCompclass;
    }

    /**
     * @deprecated 4.0..5.0
     */
    public static function table_for_object_field($candidateClass, $fieldName)
    {
        Deprecation::notice('5.0', 'Use DataObject::getSchema()->tableForField()');
        return DataObject::getSchema()->tableForField($candidateClass, $fieldName);
    }

    /**
     * Strip namespace from class
     *
     * @param string|object $nameOrObject Name of class, or instance
     * @return string Name of class without namespace
     */
    public static function shortName($nameOrObject)
    {
        $name = static::class_name($nameOrObject);
        $parts = explode('\\', $name);
        return end($parts);
    }

    /**
     * Helper to determine if the given object has a method
     *
     * @param object $object
     * @param string $method
     * @return bool
     */
    public static function hasMethod($object, $method)
    {
        if (empty($object)) {
            return false;
        }
        if (method_exists($object, $method)) {
            return true;
        }
        return method_exists($object, 'hasMethod') && $object->hasMethod($method);
    }

    /**
     * Parses a class-spec, such as "Versioned('Stage','Live')", as passed to create_from_string().
     * Returns a 2-element array, with classname and arguments
     *
     * @param string $classSpec
     * @return array
     * @throws Exception
     */
    public static function parse_class_spec($classSpec)
    {
        if (isset(static::$_cache_parse[$classSpec])) {
            return static::$_cache_parse[$classSpec];
        }

        $tokens = token_get_all("<?php $classSpec");
        $class = null;
        $args = array();

        // Keep track of the current bucket that we're putting data into
        $bucket = &$args;
        $bucketStack = array();
        $hadNamespace = false;
        $currentKey = null;

        foreach ($tokens as $token) {
            // $forceResult used to allow null result to be detected
            $result = $forceResult = null;
            $tokenName = is_array($token) ? $token[0] : $token;

            // Get the class name
            if ($class === null && is_array($token) && $token[0] === T_STRING) {
                $class = $token[1];
            } elseif (is_array($token) && $token[0] === T_NS_SEPARATOR) {
                $class .= $token[1];
                $hadNamespace = true;
            } elseif ($token === '.') {
                // Treat service name separator as NS separator
                $class .= '.';
                $hadNamespace = true;
            } elseif ($hadNamespace && is_array($token) && $token[0] === T_STRING) {
                $class .= $token[1];
                $hadNamespace = false;
            // Get arguments
            } elseif (is_array($token)) {
                switch ($token[0]) {
                    case T_CONSTANT_ENCAPSED_STRING:
                        $argString = $token[1];
                        switch ($argString[0]) {
                            case '"':
                                $result = stripcslashes(substr($argString, 1, -1));
                                break;
                            case "'":
                                $result = str_replace(
                                    ["\\\\", "\\'"],
                                    ["\\", "'"],
                                    substr($argString, 1, -1)
                                );
                                break;
                            default:
                                throw new Exception("Bad T_CONSTANT_ENCAPSED_STRING arg $argString");
                        }

                        break;

                    case T_DNUMBER:
                        $result = (double)$token[1];
                        break;

                    case T_LNUMBER:
                        $result = (int)$token[1];
                        break;

                    case T_DOUBLE_ARROW:
                        // We've encountered an associative array (the array itself has already been
                        // added to the bucket), so the previous item added to the bucket is the key
                        end($bucket);
                        $currentKey = current($bucket);
                        array_pop($bucket);
                        break;

                    case T_STRING:
                        switch ($token[1]) {
                            case 'true':
                                $result = true;

                                break;
                            case 'false':
                                $result = false;

                                break;
                            case 'null':
                                $result = null;
                                $forceResult = true;

                                break;
                            default:
                                throw new Exception("Bad T_STRING arg '{$token[1]}'");
                        }

                        break;

                    case T_ARRAY:
                        $result = array();
                        break;
                }
            } else {
                if ($tokenName === '[') {
                    $result = array();
                } elseif (($tokenName === ')' || $tokenName === ']') && !empty($bucketStack)) {
                    // Store the bucket we're currently working on
                    $oldBucket = $bucket;
                    // Fetch the key for the bucket at the top of the stack
                    end($bucketStack);
                    $key = key($bucketStack);
                    reset($bucketStack);
                    // Re-instate the bucket from the top of the stack
                    $bucket = &$bucketStack[$key];
                    // Add our saved, "nested" bucket to the bucket we just popped off the stack
                    $bucket[$key] = $oldBucket;
                    // Remove the bucket we just popped off the stack
                    array_pop($bucketStack);
                }
            }

            // If we've got something to add to the bucket, add it
            if ($result !== null || $forceResult) {
                if ($currentKey) {
                    $bucket[$currentKey] = $result;
                    $currentKey = null;
                } else {
                    $bucket[] = $result;
                }

                // If we've just pushed an array, that becomes our new bucket
                if ($result === array()) {
                    // Fetch the key that the array was pushed to
                    end($bucket);
                    $key = key($bucket);
                    reset($bucket);
                    // Store reference to "old" bucket in the stack
                    $bucketStack[$key] = &$bucket;
                    // Set the active bucket to be our newly-pushed, empty array
                    $bucket = &$bucket[$key];
                }
            }
        }

        $result = [$class, $args];
        static::$_cache_parse[$classSpec] = $result;
        return $result;
    }
}
