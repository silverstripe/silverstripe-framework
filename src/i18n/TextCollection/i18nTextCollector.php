<?php

namespace SilverStripe\i18n\TextCollection;

use Exception;
use LogicException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Manifest\Module;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Core\Path;
use SilverStripe\Dev\Debug;
use SilverStripe\Control\Director;
use ReflectionClass;
use SilverStripe\Forms\FormField;
use SilverStripe\i18n\i18n;
use SilverStripe\i18n\i18nEntityProvider;
use SilverStripe\i18n\Messages\Reader;
use SilverStripe\i18n\Messages\Writer;
use SilverStripe\ORM\DataObject;

/**
 * SilverStripe-variant of the "gettext" tool:
 * Parses the string content of all PHP-files and SilverStripe templates
 * for occurrences of the _t() translation method. Also uses the {@link i18nEntityProvider}
 * interface to get dynamically defined entities by executing the
 * {@link provideI18nEntities()} method on all implementors of this interface.
 *
 * Collects all found entities (and their natural language text for the default locale)
 * into language-files for each module in an array notation. Creates or overwrites these files,
 * e.g. framework/lang/en.yml.
 *
 * The collector needs to be run whenever you make new translatable
 * entities available. Please don't alter the arrays in language tables manually.
 *
 * Usage through URL: http://localhost/dev/tasks/i18nTextCollectorTask
 * Usage through URL (module-specific): http://localhost/dev/tasks/i18nTextCollectorTask/?module=mymodule
 * Usage on CLI: sake dev/tasks/i18nTextCollectorTask
 * Usage on CLI (module-specific): sake dev/tasks/i18nTextCollectorTask module=mymodule
 *
 * @author Bernat Foj Capell <bernat@silverstripe.com>
 * @author Ingo Schommer <FIRSTNAME@silverstripe.com>
 * @uses i18nEntityProvider
 * @uses i18n
 */
class i18nTextCollector
{
    use Injectable;

    private const THEME_PREFIX = 'themes:';

    /**
     * Default (master) locale
     *
     * @var string
     */
    protected $defaultLocale;

    /**
     * Trigger if warnings should be shown if default is omitted
     *
     * @var bool
     */
    protected $warnOnEmptyDefault = false;

    /**
     * The directory base on which the collector should act.
     * Usually the webroot set through {@link Director::baseFolder()}.
     *
     * @var string
     */
    public $basePath;

    /**
     * Save path
     *
     * @var string
     */
    public $baseSavePath;

    /**
     * @var Writer
     */
    protected $writer;

    /**
     * Translation reader
     *
     * @var Reader
     */
    protected $reader;

    /**
     * List of file extensions to parse
     *
     * @var array
     */
    protected $fileExtensions = ['php', 'ss'];

    /**
     * List all modules and themes
     *
     * @var array
     */
    private $modulesAndThemes;

    /**
     * @param $locale
     */
    public function __construct($locale = null)
    {
        $this->defaultLocale = $locale
            ? $locale
            : i18n::getData()->langFromLocale(i18n::config()->uninherited('default_locale'));
        $this->basePath = Director::baseFolder();
        $this->baseSavePath = Director::baseFolder();
        $this->setWarnOnEmptyDefault(i18n::config()->uninherited('missing_default_warning'));
    }

    /**
     * Assign a writer
     *
     * @param Writer $writer
     * @return $this
     */
    public function setWriter($writer)
    {
        $this->writer = $writer;
        return $this;
    }

    /**
     * Gets the currently assigned writer, or the default if none is specified.
     *
     * @return Writer
     */
    public function getWriter()
    {
        return $this->writer;
    }

    /**
     * Get reader
     *
     * @return Reader
     */
    public function getReader()
    {
        return $this->reader;
    }

    /**
     * Set reader
     *
     * @param Reader $reader
     * @return $this
     */
    public function setReader(Reader $reader)
    {
        $this->reader = $reader;
        return $this;
    }

    /**
     * This is the main method to build the master string tables with the
     * original strings. It will search for existent modules that use the
     * i18n feature, parse the _t() calls and write the resultant files
     * in the lang folder of each module.
     *
     * @uses DataObject::collectI18nStatics()
     *
     * @param array $restrictToModules
     * @param bool $mergeWithExisting Merge new master strings with existing
     * ones already defined in language files, rather than replacing them.
     * This can be useful for long-term maintenance of translations across
     * releases, because it allows "translation backports" to older releases
     * without removing strings these older releases still rely on.
     */
    public function run($restrictToModules = null, $mergeWithExisting = false)
    {
        $entitiesByModule = $this->collect($restrictToModules, $mergeWithExisting);
        if (empty($entitiesByModule)) {
            return;
        }

        $modules = $this->getModulesAndThemes();

        // Write each module language file
        foreach ($entitiesByModule as $moduleName => $entities) {
            // Skip empty translations
            if (empty($entities)) {
                continue;
            }

            // Clean sorting prior to writing
            ksort($entities);
            $module = $modules[$moduleName];
            $this->write($module, $entities);
        }
    }

    /**
     * Extract all strings from modules and return these grouped by module name
     *
     * @param array $restrictToModules
     * @param bool $mergeWithExisting
     * @return array
     */
    public function collect($restrictToModules = [], $mergeWithExisting = false)
    {
        $entitiesByModule = $this->getEntitiesByModule();

        // Resolve conflicts between duplicate keys across modules
        $entitiesByModule = $this->resolveDuplicateConflicts($entitiesByModule);

        // Optionally merge with existing master strings
        if ($mergeWithExisting) {
            $entitiesByModule = $this->mergeWithExisting($entitiesByModule);
        }

        // Restrict modules we update to just the specified ones (if any passed)
        if (!empty($restrictToModules)) {
            // Normalise module names
            $allModules = $this->getModulesAndThemes();
            $modules = array_filter(array_map(function ($name) use ($allModules) {
                return array_key_exists($name, $allModules) ? $this->getModuleName($name, $allModules[$name]) : null;
            }, $restrictToModules ?? []));
            // Remove modules
            foreach (array_diff(array_keys($entitiesByModule ?? []), $modules) as $module) {
                unset($entitiesByModule[$module]);
            }
        }
        return $entitiesByModule;
    }

    /**
     * Resolve conflicts between duplicate keys across modules
     *
     * @param array $entitiesByModule List of all modules with keys
     * @return array Filtered listo of modules with duplicate keys unassigned
     */
    protected function resolveDuplicateConflicts($entitiesByModule)
    {
        // Find all keys that exist across multiple modules
        $conflicts = $this->getConflicts($entitiesByModule);
        foreach ($conflicts as $conflict) {
            // Determine if we can narrow down the ownership
            $bestModule = $this->getBestModuleForKey($entitiesByModule, $conflict);
            if (!$bestModule || !isset($entitiesByModule[$bestModule])) {
                continue;
            }

            // Remove foreign duplicates
            foreach ($entitiesByModule as $module => $entities) {
                if ($module !== $bestModule) {
                    unset($entitiesByModule[$module][$conflict]);
                }
            }
        }
        return $entitiesByModule;
    }

    /**
     * Find all keys in the entity list that are duplicated across modules
     *
     * @param array $entitiesByModule
     * @return array List of keys
     */
    protected function getConflicts($entitiesByModule)
    {
        $modules = array_keys($entitiesByModule ?? []);
        $allConflicts = [];
        // bubble-compare each group of modules
        for ($i = 0; $i < count($modules ?? []) - 1; $i++) {
            $left = array_keys($entitiesByModule[$modules[$i]] ?? []);
            for ($j = $i+1; $j < count($modules ?? []); $j++) {
                $right = array_keys($entitiesByModule[$modules[$j]] ?? []);
                $conflicts = array_intersect($left ?? [], $right);
                $allConflicts = array_merge($allConflicts, $conflicts);
            }
        }
        return array_unique($allConflicts ?? []);
    }

    /**
     * Map of translation keys => module names
     * @var array
     */
    protected $classModuleCache = [];

    /**
     * Determine the best module to be given ownership over this key
     *
     * @param array $entitiesByModule
     * @param string $key
     * @return string Best module, if found
     */
    protected function getBestModuleForKey($entitiesByModule, $key)
    {
        // Check classes
        $class = current(explode('.', $key ?? ''));
        if (array_key_exists($class, $this->classModuleCache ?? [])) {
            return $this->classModuleCache[$class];
        }
        $owner = $this->findModuleForClass($class);
        if ($owner) {
            $this->classModuleCache[$class] = $owner;
            return $owner;
        }

        // Display notice if not found
        Debug::message(
            "Duplicate key {$key} detected in no / multiple modules with no obvious owner",
            false
        );

        // Fall back to framework then cms modules
        foreach (['framework', 'cms'] as $module) {
            if (isset($entitiesByModule[$module][$key])) {
                $this->classModuleCache[$class] = $module;
                return $module;
            }
        }

        // Do nothing
        $this->classModuleCache[$class] = null;
        return null;
    }

    /**
     * Given a partial class name, attempt to determine the best module to assign strings to.
     *
     * @param string $class Either a FQN class name, or a non-qualified class name.
     * @return string Name of module
     */
    protected function findModuleForClass($class)
    {
        if (ClassInfo::exists($class)) {
            $module = ClassLoader::inst()
                ->getManifest()
                ->getOwnerModule($class);
            if ($module) {
                return $module->getName();
            }
        }

        // If we can't find a class, see if it needs to be fully qualified
        if (strpos($class ?? '', '\\') !== false) {
            return null;
        }

        // Find FQN that ends with $class
        $classes = preg_grep(
            '/' . preg_quote("\\{$class}", '\/') . '$/i',
            ClassLoader::inst()->getManifest()->getClassNames() ?? []
        );

        // Find all modules for candidate classes
        $modules = array_unique(array_map(function ($class) {
            $module = ClassLoader::inst()->getManifest()->getOwnerModule($class);
            return $module ? $module->getName() : null;
        }, $classes ?? []));

        if (count($modules ?? []) === 1) {
            return reset($modules);
        }

        // Couldn't find it! Exists in none, or multiple modules.
        return null;
    }

    /**
     * Merge all entities with existing strings
     *
     * @param array $entitiesByModule
     * @return array
     */
    protected function mergeWithExisting($entitiesByModule)
    {
        // For each module do a simple merge of the default yml with these strings
        $modules = $this->getModulesAndThemes();
        foreach ($entitiesByModule as $module => $messages) {
            // Load existing localisations
            $masterFile = Path::join($modules[$module]->getPath(), 'lang', $this->defaultLocale . '.yml');
            $existingMessages = $this->getReader()->read($this->defaultLocale, $masterFile);

            // Merge
            if ($existingMessages) {
                $entitiesByModule[$module] = array_merge(
                    $messages,
                    $existingMessages
                );
            }
        }
        return $entitiesByModule;
    }

    /**
     * Collect all entities grouped by module
     *
     * @return array
     */
    protected function getEntitiesByModule()
    {
        // A master string tables array (one mst per module)
        $entitiesByModule = [];
        $modules = $this->getModulesAndThemes();
        foreach ($modules as $moduleName => $module) {
            // we store the master string tables
            $processedEntities = $this->processModule($module);
            $moduleName = $this->getModuleName($moduleName, $module);
            if (isset($entitiesByModule[$moduleName])) {
                $entitiesByModule[$moduleName] = array_merge_recursive(
                    $entitiesByModule[$moduleName],
                    $processedEntities
                );
            } else {
                $entitiesByModule[$moduleName] = $processedEntities;
            }

            // Extract all entities for "foreign" modules ('module' key in array form)
            // @see CMSMenu::provideI18nEntities for an example usage
            foreach ($entitiesByModule[$moduleName] as $fullName => $spec) {
                $specModuleName = $moduleName;

                // Rewrite spec if module is specified
                if (is_array($spec) && isset($spec['module'])) {
                    // Normalise name (in case non-composer name is specified)
                    $specModule = ModuleLoader::inst()->getManifest()->getModule($spec['module']);
                    if ($specModule) {
                        $specModuleName = $specModule->getName();
                    }
                    unset($spec['module']);

                    // If only element is default, simplify
                    if (count($spec ?? []) === 1 && !empty($spec['default'])) {
                        $spec = $spec['default'];
                    }
                }

                // Remove from source module
                if ($specModuleName !== $moduleName) {
                    unset($entitiesByModule[$moduleName][$fullName]);
                }

                // Write to target module
                if (!isset($entitiesByModule[$specModuleName])) {
                    $entitiesByModule[$specModuleName] = [];
                }
                $entitiesByModule[$specModuleName][$fullName] = $spec;
            }
        }
        return $entitiesByModule;
    }

    /**
     * Loads all modules and themes installed, including app. Uses the format of
     * the @link ModuleLoader manifest for themes as well.
     * Themes can be references with "themes:{theme name}".
     */
    private function getModulesAndThemes(): array
    {
        if (!$this->modulesAndThemes) {
            $modules = ModuleLoader::inst()->getManifest()->getModules();
            // load themes as modules
            $themes = [];
            if (is_dir(THEMES_PATH)) {
                $themes = array_diff(scandir(THEMES_PATH), ['..', '.']);
            }
            if (!empty($themes)) {
                foreach ($themes as $theme) {
                    if (is_dir(Path::join(THEMES_PATH, $theme))) {
                        $modules[i18nTextCollector::THEME_PREFIX . $theme] = new Module(Path::join(THEMES_PATH, $theme), BASE_PATH);
                    }
                }
            }
            $this->modulesAndThemes = $modules;
        }
        return $this->modulesAndThemes;
    }

    /**
     * Returns the name of the module or theme
     */
    private function getModuleName(string $origName, Module $module): string
    {
        return strpos($origName, i18nTextCollector::THEME_PREFIX) === 0 ? $origName : $module->getName();
    }

    /**
     * Write entities to a module
     *
     * @param Module $module
     * @param array $entities
     * @return $this
     */
    public function write(Module $module, $entities)
    {
        $this->getWriter()->write(
            $entities,
            $this->defaultLocale,
            Path::join($this->baseSavePath, $module->getRelativePath())
        );
        return $this;
    }

    /**
     * Builds a master string table from php and .ss template files for the module passed as the $module param
     * @see collectFromCode() and collectFromTemplate()
     *
     * @param Module $module Module instance
     * @return array An array of entities found in the files that comprise the module
     */
    protected function processModule(Module $module)
    {
        $entities = [];

        // Search for calls in code files if these exists
        $fileList = $this->getFileListForModule($module);
        foreach ($fileList as $filePath) {
            $extension = pathinfo($filePath ?? '', PATHINFO_EXTENSION);
            $content = file_get_contents($filePath ?? '');
            // Filter based on extension
            if ($extension === 'php') {
                $entities = array_merge(
                    $entities,
                    $this->collectFromORM($filePath),
                    $this->collectFromCode($content, $filePath, $module),
                    $this->collectFromEntityProviders($filePath, $module)
                );
            } elseif ($extension === 'ss') {
                // templates use their filename as a namespace
                $entities = array_merge(
                    $entities,
                    $this->collectFromTemplate($content, $filePath, $module)
                );
            }
        }

        // sort for easier lookup and comparison with translated files
        ksort($entities);

        return $entities;
    }

    /**
     * Retrieves the list of files for this module
     *
     * @param Module $module Module instance
     * @return array List of files to parse
     */
    protected function getFileListForModule(Module $module)
    {
        $modulePath = $module->getPath();

        // Search all .ss files in themes
        if (stripos($module->getRelativePath() ?? '', i18nTextCollector::THEME_PREFIX) === 0) {
            return $this->getFilesRecursive($modulePath, null, 'ss');
        }

        // If non-standard module structure, search all root files
        if (!is_dir(Path::join($modulePath, 'code')) && !is_dir(Path::join($modulePath, 'src'))) {
            return $this->getFilesRecursive($modulePath);
        }

        // Get code files
        if (is_dir(Path::join($modulePath, 'src'))) {
            $files = $this->getFilesRecursive(Path::join($modulePath, 'src'), null, 'php');
        } else {
            $files = $this->getFilesRecursive(Path::join($modulePath, 'code'), null, 'php');
        }

        // Search for templates in this module
        if (is_dir(Path::join($modulePath, 'templates'))) {
            $templateFiles = $this->getFilesRecursive(Path::join($modulePath, 'templates'), null, 'ss');
        } else {
            $templateFiles = $this->getFilesRecursive($modulePath, null, 'ss');
        }

        return array_merge($files, $templateFiles);
    }

    /**
     * Extracts translatables from .php files.
     * Note: Translations without default values are omitted.
     *
     * @param string $content The text content of a parsed template-file
     * @param string $fileName Filename Optional filename
     * @param Module $module Module being collected
     * @return array Map of localised keys to default values provided for this code
     */
    public function collectFromCode($content, $fileName, Module $module)
    {
        // Get "namespace" either from $fileName or $module fallback
        $namespace = $fileName ? basename($fileName) : $module->getName();

        $usedFQCNs = [];
        $entities = [];

        $tokens = token_get_all("<?php\n" . $content);
        $inTransFn = false;
        $inConcat = false;
        $inNamespace = false;
        $inClass = false; // after `class` but before `{`
        $inUse = false; // pulling in classes from other namespaces
        $inArrayClosedBy = false; // Set to the expected closing token, or false if not in array
        $inSelf = false; // Tracks progress of collecting i18nTextCollector::class
        $currentEntity = [];
        $currentNameSpace = []; // The actual namespace for the current class
        $currentClass = []; // Class components
        $previousToken = null;
        $thisToken = null; // used to populate $previousToken on next iteration
        $potentialClassName = null;
        $currentUse = null;
        $currentUseAlias = null;
        foreach ($tokens as $token) {
            // Shuffle last token to $lastToken
            $previousToken = $thisToken;
            $thisToken = $token;
            if (is_array($token)) {
                list($id, $text) = $token;

                // Collect use statements so we can get fully qualified class names
                if ($id === T_USE) {
                    $inUse = true;
                    $currentUse = [];
                    continue;
                }

                if ($inUse) {
                    // PHP 8.0+
                    if (defined('T_NAME_QUALIFIED') && $id === T_NAME_QUALIFIED) {
                        $currentUse[] = $text;
                        $text = explode('\\', $text);
                        $currentUseAlias = end($text);
                        continue;
                    }
                    // PHP 7.4 or an alias declaration
                    if ($id === T_STRING) {
                        // Only add to the FQCN if it's the first string or comes after a namespace separator
                        if (empty($currentUse) || (is_array($previousToken) && $previousToken[0] === T_NS_SEPARATOR)) {
                            $currentUse[] = $text;
                        }
                        // The last part of the use statement is always the alias or the actual class name
                        $currentUseAlias = $text;
                        continue;
                    }
                }

                // Check class
                if ($id === T_NAMESPACE) {
                    $inNamespace = true;
                    $currentClass = [];
                    $currentNameSpace = [];
                    continue;
                }
                if ($inNamespace && ($id === T_STRING || (defined('T_NAME_QUALIFIED') && $id === T_NAME_QUALIFIED))) {
                    $currentClass[] = $text;
                    $currentNameSpace[] = $text;
                    continue;
                }

                // This could be a ClassName::class declaration
                if ($id === T_DOUBLE_COLON && is_array($previousToken) && $previousToken[0] === T_STRING) {
                    $prevString = $previousToken[1];
                    if (!in_array($prevString, ['self', 'static', 'parent'])) {
                        $potentialClassName = $prevString;
                    }
                }

                // Check class and trait
                if ($id === T_CLASS || $id === T_TRAIT) {
                    // Skip if previous token was '::'. E.g. 'Object::class'
                    if (is_array($previousToken) && $previousToken[0] === T_DOUBLE_COLON) {
                        if ($inSelf) {
                            // Handle i18nTextCollector::class by allowing logic further down
                            // for __CLASS__/__TRAIT__ to handle an array of class parts
                            $id = $id === T_TRAIT ? T_TRAIT_C : T_CLASS_C;
                            $inSelf = false;
                        } elseif ($potentialClassName) {
                            $id = T_CONSTANT_ENCAPSED_STRING;
                            if (array_key_exists($potentialClassName, $usedFQCNs)) {
                                // Handle classes that we explicitly know about from use statements
                                $text = "'" . $usedFQCNs[$potentialClassName] . "'";
                            } else {
                                // Assume the class is in the current namespace
                                $potentialFQCN = [...$currentNameSpace, $potentialClassName];
                                $text = "'" . implode('\\', $potentialFQCN) . "'";
                            }
                        } else {
                            // Don't handle other ::class definitions. We can't determine which
                            // class was invoked, so parent::class is not possible at this point.
                            continue;
                        }
                    } else {
                        $inClass = true;
                        continue;
                    }
                } elseif (is_array($previousToken) && $previousToken[0] === T_DOUBLE_COLON) {
                    // We had a potential class but it turns out it was probably a method call.
                    $potentialClassName = null;
                }

                if ($inClass && $id === T_STRING) {
                    $currentClass[] = $text;
                    $inClass = false;
                    continue;
                }

                // Suppress tokenisation within array
                if ($inTransFn && !$inArrayClosedBy && $id == T_ARRAY) {
                    $inArrayClosedBy = ')'; // Array will close with this element
                    continue;
                }

                // Start definition
                if ($id == T_STRING && $text == '_t') {
                    $inTransFn = true;
                    continue;
                }

                // Skip rest of processing unless we are in a translation, and not inside a nested array
                if (!$inTransFn || $inArrayClosedBy) {
                    continue;
                }

                // If inside this translation, some elements might be unreachable
                if (in_array($id, [T_VARIABLE, T_STATIC]) ||
                    ($id === T_STRING && in_array($text, ['static', 'parent']))
                ) {
                    // Un-collectable strings such as _t(static::class.'.KEY').
                    // Should be provided by i18nEntityProvider instead
                    $inTransFn = false;
                    $inArrayClosedBy = false;
                    $inConcat = false;
                    $currentEntity = [];
                    continue;
                }

                // Start collecting i18nTextCollector::class declarations
                if ($id === T_STRING && $text === 'self') {
                    $inSelf = true;
                    continue;
                }

                // Check text
                if ($id == T_CONSTANT_ENCAPSED_STRING) {
                    // Fixed quoting escapes, and remove leading/trailing quotes
                    if (preg_match('/^\'(?<text>.*)\'$/s', $text ?? '', $matches)) {
                        $text = preg_replace_callback(
                            '/\\\\([\\\\\'])/s', // only \ and '
                            function ($input) {
                                return stripcslashes($input[0] ?? '');
                            },
                            $matches['text'] ?? ''
                        );
                    } elseif (preg_match('/^\"(?<text>.*)\"$/s', $text ?? '', $matches)) {
                        $text = preg_replace_callback(
                            '/\\\\([nrtvf\\\\$"]|[0-7]{1,3}|\x[0-9A-Fa-f]{1,2})/s', // rich replacement
                            function ($input) {
                                return stripcslashes($input[0] ?? '');
                            },
                            $matches['text'] ?? ''
                        );
                    } else {
                        throw new LogicException("Invalid string escape: " . $text);
                    }
                } elseif ($id === T_CLASS_C || $id === T_TRAIT_C) {
                    // Evaluate __CLASS__ . '.KEY' and i18nTextCollector::class concatenation
                    $text = implode('\\', $currentClass);
                } else {
                    continue;
                }

                if ($inConcat) {
                    // Parser error
                    if (empty($currentEntity)) {
                        user_error('Error concatenating localisation key', E_USER_WARNING);
                    } else {
                        $currentEntity[count($currentEntity) - 1] .= $text;
                    }
                } else {
                    $currentEntity[] = $text;
                }
                continue; // is_array
            }

            // Test we can close this array
            if ($inTransFn && $inArrayClosedBy && ($token === $inArrayClosedBy)) {
                $inArrayClosedBy = false;
                continue;
            }

            // Check if we can close the namespace or use statement
            if ($token === ';') {
                if ($inNamespace) {
                    $inNamespace = false;
                    continue;
                }
                if ($inUse) {
                    $inUse = false;
                    $usedFQCNs[$currentUseAlias] = implode('\\', $currentUse);
                    $currentUse = null;
                    $currentUseAlias = null;
                    continue;
                }
            }

            // Continue only if in translation and not in array
            if (!$inTransFn || $inArrayClosedBy) {
                continue;
            }

            switch ($token) {
                case '.':
                    $inConcat = true;
                    break;
                case ',':
                    $inConcat = false;
                    break;
                case '[':
                    // Enter array
                    $inArrayClosedBy = ']';
                    break;
                case ')':
                    // finalize definition
                    $inTransFn = false;
                    $inConcat = false;
                    // Ensure key is valid before saving
                    if (!empty($currentEntity[0])) {
                        $key = $currentEntity[0];
                        $default = '';
                        $comment = '';
                        if (!empty($currentEntity[1])) {
                            $default = $currentEntity[1];
                            if (!empty($currentEntity[2])) {
                                $comment = $currentEntity[2];
                            }
                        }
                        // Save in appropriate format
                        if ($default) {
                            $plurals = i18n::parse_plurals($default);
                            // Use array form if either plural or metadata is provided
                            if ($plurals) {
                                $entity = $plurals;
                            } elseif ($comment) {
                                $entity = ['default' => $default];
                            } else {
                                $entity = $default;
                            }
                            if ($comment) {
                                $entity['comment'] = $comment;
                            }
                            $entities[$key] = $entity;
                        } elseif ($this->getWarnOnEmptyDefault()) {
                            trigger_error("Missing localisation default for key " . $currentEntity[0], E_USER_NOTICE);
                        }
                    }
                    $currentEntity = [];
                    $inArrayClosedBy = false;
                    break;
            }
        }

        // Normalise all keys
        foreach ($entities as $key => $entity) {
            unset($entities[$key]);
            $entities[$this->normalizeEntity($key, $namespace)] = $entity;
        }
        ksort($entities);

        return $entities;
    }

    /**
     * Extracts translatables from .ss templates (Self referencing)
     *
     * @param string $content The text content of a parsed template-file
     * @param string $fileName The name of a template file when method is used in self-referencing mode
     * @param Module $module Module being collected
     * @param array $parsedFiles
     * @return array $entities An array of entities representing the extracted template function calls
     */
    public function collectFromTemplate($content, $fileName, Module $module, &$parsedFiles = [])
    {
        // Get namespace either from $fileName or $module fallback
        $namespace = $fileName ? basename($fileName) : $module->getName();

        // use parser to extract <%t style translatable entities
        $entities = Parser::getTranslatables($content, $this->getWarnOnEmptyDefault());

        // use the old method of getting _t() style translatable entities
        // Collect in actual template
        if (preg_match_all('/(_t\([^\)]*?\))/ms', $content ?? '', $matches)) {
            foreach ($matches[1] as $match) {
                $entities = array_merge($entities, $this->collectFromCode($match, $fileName, $module));
            }
        }

        foreach ($entities as $entity => $spec) {
            unset($entities[$entity]);
            $entities[$this->normalizeEntity($entity, $namespace)] = $spec;
        }
        ksort($entities);

        return $entities;
    }

    /**
     * Allows classes which implement i18nEntityProvider to provide
     * additional translation strings.
     *
     * Not all classes can be instantiated without mandatory arguments,
     * so entity collection doesn't work for all SilverStripe classes currently
     *
     * @uses i18nEntityProvider
     * @param string $filePath
     * @param Module $module
     * @return array
     */
    public function collectFromEntityProviders($filePath, Module $module = null)
    {
        $entities = [];
        $classes = ClassInfo::classes_for_file($filePath);
        foreach ($classes as $class) {
            // Skip non-implementing classes
            if (!class_exists($class) || !is_a($class, i18nEntityProvider::class, true)) {
                continue;
            }

            // Skip abstract classes
            $reflectionClass = new ReflectionClass($class);
            if ($reflectionClass->isAbstract()) {
                continue;
            }

            /** @var i18nEntityProvider $obj */
            $obj = singleton($class);
            $provided = $obj->provideI18nEntities();
            foreach ($provided as $key => $value) {
                // Detect non-associative result for any key
                if (is_array($value) && $value === array_values($value)) {
                    throw new Exception('Translations from provideI18nEntities() must be an associative array for key $key');
                }
            }
            $entities = array_merge($entities, $provided);
        }

        ksort($entities);
        return $entities;
    }

    /**
     * Extracts translations for ORM fields
     *
     * @param string $filePath
     * @return array
     */
    public function collectFromORM($filePath)
    {
        $entities = [];
        $classes = ClassInfo::classes_for_file($filePath);
        foreach ($classes as $class) {
            // Skip non-implementing classes
            if (!class_exists($class)) {
                continue;
            }

            // Skip abstract classes
            $reflectionClass = new ReflectionClass($class);
            if ($reflectionClass->isAbstract()) {
                continue;
            }

            $provided = [];
            // add labels for ORM fields
            if (is_a($class, DataObject::class, true) || is_a($class, Extension::class, true)) {
                foreach (['db', 'has_one', 'has_many', 'belongs_to', 'many_many', 'belongs_many_many'] as $type) {
                    if ($config = Config::inst()->get($class, $type, Config::UNINHERITED)) {
                        foreach ($config as $name => $spec) {
                            // add type in translation identifier as used in DataObject::fieldLabels()
                            $provided["{$class}.{$type}_{$name}"] = FormField::name_to_label($name);
                        }
                    }
                }
            }
            $entities = array_merge($entities, $provided);
        }

        ksort($entities);
        return $entities;
    }

    /**
     * Normalizes entities with namespaces.
     *
     * @param string $fullName
     * @param string $_namespace
     * @return string|boolean FALSE
     */
    protected function normalizeEntity($fullName, $_namespace = null)
    {
        // split fullname into entity parts
        $entityParts = explode('.', $fullName ?? '');
        if (count($entityParts ?? []) > 1) {
            // templates don't have a custom namespace
            $entity = array_pop($entityParts);
            // namespace might contain dots, so we explode
            $namespace = implode('.', $entityParts);
        } else {
            $entity = array_pop($entityParts);
            $namespace = $_namespace;
        }

        // If a dollar sign is used in the entity name,
        // we can't resolve without running the method,
        // and skip the processing. This is mostly used for
        // dynamically translating static properties, e.g. looping
        // through $db, which are detected by {@link collectFromEntityProviders}.
        if ($entity && strpos('$', $entity ?? '') !== false) {
            return false;
        }

        return "{$namespace}.{$entity}";
    }

    /**
     * Helper function that searches for potential files (templates and code) to be parsed
     *
     * @param string $folder base directory to scan (will scan recursively)
     * @param array $fileList Array to which potential files will be appended
     * @param string $type Optional, "php" or "ss" only
     * @param string $folderExclude Regular expression matching folder names to exclude
     * @return array $fileList An array of files
     */
    protected function getFilesRecursive($folder, $fileList = [], $type = null, $folderExclude = '/\/(tests)$/')
    {
        if (!$fileList) {
            $fileList = [];
        }
        // Skip ignored folders
        if (is_file(Path::join($folder, '_manifest_exclude')) || preg_match($folderExclude ?? '', $folder ?? '')) {
            return $fileList;
        }

        foreach (glob($folder . '/*') as $path) {
            // Recurse if directory
            if (is_dir($path ?? '')) {
                $fileList = array_merge(
                    $fileList,
                    $this->getFilesRecursive($path, $fileList, $type, $folderExclude)
                );
                continue;
            }

            // Check if this extension is included
            $extension = pathinfo($path ?? '', PATHINFO_EXTENSION);
            if (in_array($extension, $this->fileExtensions ?? [])
                && (!$type || $type === $extension)
            ) {
                $fileList[$path] = $path;
            }
        }
        return $fileList;
    }

    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;
    }

    /**
     * @return bool
     */
    public function getWarnOnEmptyDefault()
    {
        return $this->warnOnEmptyDefault;
    }

    /**
     * @param bool $warnOnEmptyDefault
     * @return $this
     */
    public function setWarnOnEmptyDefault($warnOnEmptyDefault)
    {
        $this->warnOnEmptyDefault = $warnOnEmptyDefault;
        return $this;
    }
}
