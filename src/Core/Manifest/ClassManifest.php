<?php

namespace SilverStripe\Core\Manifest;

use Exception;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Cache\CacheFactory;
use SilverStripe\Dev\TestOnly;

/**
 * A utility class which builds a manifest of all classes, interfaces and caches it.
 *
 * It finds the following information:
 *   - Class and interface names and paths.
 *   - All direct and indirect descendants of a class.
 *   - All implementors of an interface.
 */
class ClassManifest
{
    /**
     * base manifest directory
     * @var string
     */
    protected $base;

    /**
     * Set if including test classes
     *
     * @see TestOnly
     * @var bool
     */
    protected $tests;

    /**
     * Cache to use, if caching.
     * Set to null if uncached.
     *
     * @var CacheInterface|null
     */
    protected $cache;

    /**
     * Key to use for the top level cache of all items
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * Map of classes to paths
     *
     * @var array
     */
    protected $classes      = array();

    /**
     * List of root classes with no parent class
     *
     * @var array
     */
    protected $roots = array();

    /**
     * List of direct children for any class
     *
     * @var array
     */
    protected $children = array();

    /**
     * List of descendents for any class (direct + indirect children)
     *
     * @var array
     */
    protected $descendants = array();

    /**
     * List of interfaces and paths to those files
     *
     * @var array
     */
    protected $interfaces = array();

    /**
     * List of direct implementors of any interface
     *
     * @var array
     */
    protected $implementors = array();

    /**
     * Map of traits to paths
     *
     * @var array
     */
    protected $traits = array();

    /**
     * PHP Parser for parsing found files
     *
     * @var Parser
     */
    private $parser;

    /**
     * @var NodeTraverser
     */
    private $traverser;

    /**
     * @var ClassManifestVisitor
     */
    private $visitor;

    /**
     * Constructs and initialises a new class manifest, either loading the data
     * from the cache or re-scanning for classes.
     *
     * @param string $base The manifest base path.
     * @param bool $includeTests Include the contents of "tests" directories.
     * @param bool $forceRegen Force the manifest to be regenerated.
     * @param CacheFactory $cacheFactory Optional cache to use. Set to null to not cache.
     */
    public function __construct(
        $base,
        $includeTests = false,
        $forceRegen = false,
        CacheFactory $cacheFactory = null
    ) {
        $this->base = $base;
        $this->tests = $includeTests;

        // build cache from factory
        if ($cacheFactory) {
            $this->cache = $cacheFactory->create(
                CacheInterface::class.'.classmanifest',
                [ 'namespace' => 'classmanifest' . ($includeTests ? '_tests' : '') ]
            );
        }
        $this->cacheKey = 'manifest';

        if (!$forceRegen && $this->cache && ($data = $this->cache->get($this->cacheKey))) {
            $this->classes = $data['classes'];
            $this->descendants = $data['descendants'];
            $this->interfaces = $data['interfaces'];
            $this->implementors = $data['implementors'];
            $this->traits = $data['traits'];
        } else {
            $this->regenerate();
        }
    }

    /**
     * Get or create active parser
     *
     * @return Parser
     */
    public function getParser()
    {
        if (!$this->parser) {
            $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        }

        return $this->parser;
    }

    public function getTraverser()
    {
        if (!$this->traverser) {
            $this->traverser = new NodeTraverser;
            $this->traverser->addVisitor(new NameResolver);
            $this->traverser->addVisitor($this->getVisitor());
        }

        return $this->traverser;
    }

    public function getVisitor()
    {
        if (!$this->visitor) {
            $this->visitor = new ClassManifestVisitor;
        }

        return $this->visitor;
    }

    /**
     * Returns the file path to a class or interface if it exists in the
     * manifest.
     *
     * @param  string $name
     * @return string|null
     */
    public function getItemPath($name)
    {
        $name = strtolower($name);

        foreach ([
            $this->classes,
            $this->interfaces,
            $this->traits
        ] as $source) {
            if (isset($source[$name]) && file_exists($source[$name])) {
                return $source[$name];
            }
        }
        return null;
    }

    /**
     * Returns a map of lowercased class names to file paths.
     *
     * @return array
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * Returns a lowercase array of all the class names in the manifest.
     *
     * @return array
     */
    public function getClassNames()
    {
        return array_keys($this->classes);
    }

    /**
     * Returns a lowercase array of all trait names in the manifest
     *
     * @return array
     */
    public function getTraitNames()
    {
        return array_keys($this->traits);
    }

    /**
     * Returns an array of all the descendant data.
     *
     * @return array
     */
    public function getDescendants()
    {
        return $this->descendants;
    }

    /**
     * Returns an array containing all the descendants (direct and indirect)
     * of a class.
     *
     * @param  string|object $class
     * @return array
     */
    public function getDescendantsOf($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $lClass = strtolower($class);

        if (array_key_exists($lClass, $this->descendants)) {
            return $this->descendants[$lClass];
        } else {
            return array();
        }
    }

    /**
     * Returns a map of lowercased interface names to file locations.
     *
     * @return array
     */
    public function getInterfaces()
    {
        return $this->interfaces;
    }

    /**
     * Returns a map of lowercased interface names to the classes the implement
     * them.
     *
     * @return array
     */
    public function getImplementors()
    {
        return $this->implementors;
    }

    /**
     * Returns an array containing the class names that implement a certain
     * interface.
     *
     * @param  string $interface
     * @return array
     */
    public function getImplementorsOf($interface)
    {
        $interface = strtolower($interface);

        if (array_key_exists($interface, $this->implementors)) {
            return $this->implementors[$interface];
        } else {
            return array();
        }
    }

    /**
     * Get module that owns this class
     *
     * @param string $class Class name
     * @return Module
     */
    public function getOwnerModule($class)
    {
        $path = realpath($this->getItemPath($class));
        if (!$path) {
            return null;
        }

        /** @var Module $rootModule */
        $rootModule = null;

        // Find based on loaded modules
        $modules = ModuleLoader::instance()->getManifest()->getModules();
        foreach ($modules as $module) {
            // Leave root module as fallback
            if (empty($module->getRelativePath())) {
                $rootModule = $module;
            } elseif (stripos($path, realpath($module->getPath())) === 0) {
                return $module;
            }
        }

        // Fall back to top level module
        return $rootModule;
    }

    /**
     * Completely regenerates the manifest file.
     */
    public function regenerate()
    {
        $resets = array(
            'classes', 'roots', 'children', 'descendants', 'interfaces',
            'implementors', 'traits'
        );

        // Reset the manifest so stale info doesn't cause errors.
        foreach ($resets as $reset) {
            $this->$reset = array();
        }

        $finder = new ManifestFileFinder();
        $finder->setOptions(array(
            'name_regex'    => '/^[^_].*\\.php$/',
            'ignore_files'  => array('index.php', 'main.php', 'cli-script.php'),
            'ignore_tests'  => !$this->tests,
            'file_callback' => array($this, 'handleFile'),
        ));
        $finder->find($this->base);

        foreach ($this->roots as $root) {
            $this->coalesceDescendants($root);
        }

        if ($this->cache) {
            $data = array(
                'classes'      => $this->classes,
                'descendants'  => $this->descendants,
                'interfaces'   => $this->interfaces,
                'implementors' => $this->implementors,
                'traits'       => $this->traits,
            );
            $this->cache->set($this->cacheKey, $data);
        }
    }

    public function handleFile($basename, $pathname)
    {
        $classes    = null;
        $interfaces = null;
        $traits = null;

        // The results of individual file parses are cached, since only a few
        // files will have changed and TokenisedRegularExpression is quite
        // slow. A combination of the file name and file contents hash are used,
        // since just using the datetime lead to problems with upgrading.
        $key = preg_replace('/[^a-zA-Z0-9_]/', '_', $basename) . '_' . md5_file($pathname);

        // Attempt to load from cache
        if ($this->cache
            && ($data = $this->cache->get($key))
            && $this->validateItemCache($data)
        ) {
            $classes = $data['classes'];
            $interfaces = $data['interfaces'];
            $traits = $data['traits'];
        } else {
            // Build from php file parser
            $fileContents = ClassContentRemover::remove_class_content($pathname);
            try {
                $stmts = $this->getParser()->parse($fileContents);
            } catch (Error $e) {
                // if our mangled contents breaks, try again with the proper file contents
                $stmts = $this->getParser()->parse(file_get_contents($pathname));
            }
            $this->getTraverser()->traverse($stmts);

            $classes = $this->getVisitor()->getClasses();
            $interfaces = $this->getVisitor()->getInterfaces();
            $traits = $this->getVisitor()->getTraits();

            // Save back to cache if configured
            if ($this->cache) {
                $cache = array(
                    'classes' => $classes,
                    'interfaces' => $interfaces,
                    'traits' => $traits,
                );
                $this->cache->set($key, $cache);
            }
        }

        // Merge this data into the global list
        foreach ($classes as $className => $classInfo) {
            $extends = isset($classInfo['extends']) ? $classInfo['extends'] : null;
            $implements = isset($classInfo['interfaces']) ? $classInfo['interfaces'] : null;

            $lowercaseName = strtolower($className);
            if (array_key_exists($lowercaseName, $this->classes)) {
                throw new Exception(sprintf(
                    'There are two files containing the "%s" class: "%s" and "%s"',
                    $className,
                    $this->classes[$lowercaseName],
                    $pathname
                ));
            }

            $this->classes[$lowercaseName] = $pathname;

            if ($extends) {
                foreach ($extends as $ancestor) {
                    $ancestor = strtolower($ancestor);

                    if (!isset($this->children[$ancestor])) {
                        $this->children[$ancestor] = array($className);
                    } else {
                        $this->children[$ancestor][] = $className;
                    }
                }
            } else {
                $this->roots[] = $className;
            }

            if ($implements) {
                foreach ($implements as $interface) {
                    $interface = strtolower($interface);

                    if (!isset($this->implementors[$interface])) {
                        $this->implementors[$interface] = array($className);
                    } else {
                        $this->implementors[$interface][] = $className;
                    }
                }
            }
        }

        foreach ($interfaces as $interfaceName => $interfaceInfo) {
            $this->interfaces[strtolower($interfaceName)] = $pathname;
        }
        foreach ($traits as $traitName => $traitInfo) {
            $this->traits[strtolower($traitName)] = $pathname;
        }
    }

    /**
     * Recursively coalesces direct child information into full descendant
     * information.
     *
     * @param  string $class
     * @return array
     */
    protected function coalesceDescendants($class)
    {
        $lClass = strtolower($class);

        if (array_key_exists($lClass, $this->children)) {
            $this->descendants[$lClass] = array();

            foreach ($this->children[$lClass] as $class) {
                $this->descendants[$lClass] = array_merge(
                    $this->descendants[$lClass],
                    array($class),
                    $this->coalesceDescendants($class)
                );
            }

            return $this->descendants[$lClass];
        } else {
            return array();
        }
    }

    /**
     * Verify that cached data is valid for a single item
     *
     * @param array $data
     * @return bool
     */
    protected function validateItemCache($data)
    {
        foreach (['classes', 'interfaces', 'traits'] as $key) {
            // Must be set
            if (!isset($data[$key])) {
                return false;
            }
            // and an array
            if (!is_array($data[$key])) {
                return false;
            }
            // Detect legacy cache keys (non-associative)
            $array = $data[$key];
            if (!empty($array) && is_numeric(key($array))) {
                return false;
            }
        }
        return true;
    }
}
