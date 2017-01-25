<?php

namespace SilverStripe\i18n\TextCollection;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Dev\Debug;
use SilverStripe\Control\Director;
use ReflectionClass;
use SilverStripe\Dev\Deprecation;
use SilverStripe\i18n\i18n;
use SilverStripe\i18n\i18nEntityProvider;
use SilverStripe\i18n\Messages\Reader;
use SilverStripe\i18n\Messages\Writer;

/**
 * SilverStripe-variant of the "gettext" tool:
 * Parses the string content of all PHP-files and SilverStripe templates
 * for ocurrences of the _t() translation method. Also uses the {@link i18nEntityProvider}
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
     * @todo Fully support changing of basePath through {@link SSViewer} and {@link ManifestBuilder}
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
    protected $fileExtensions = array('php', 'ss');

    /**
     * @param $locale
     */
    public function __construct($locale = null)
    {
        $this->defaultLocale = $locale
            ? $locale
            : i18n::get_lang_from_locale(i18n::config()->get('default_locale'));
        $this->basePath = Director::baseFolder();
        $this->baseSavePath = Director::baseFolder();
        $this->setWarnOnEmptyDefault(i18n::config()->get('missing_default_warning'));
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
     * @uses DataObject->collectI18nStatics()
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

        // Write each module language file
        foreach ($entitiesByModule as $module => $entities) {
            // Skip empty translations
            if (empty($entities)) {
                continue;
            }

            // Clean sorting prior to writing
            ksort($entities);
            $path = $this->baseSavePath . '/' . $module;
            $this->getWriter()->write($entities, $this->defaultLocale, $path);
        }
    }

    /**
     * Gets the list of modules in this installer
     *
     * @param string $directory Path to look in
     * @return array List of modules as paths relative to base
     */
    protected function getModules($directory)
    {
        // Include self as head module
        $modules = array();

        // Get all standard modules
        foreach (glob($directory."/*", GLOB_ONLYDIR) as $path) {
            // Check for _config
            if (!is_file("$path/_config.php") && !is_dir("$path/_config")) {
                continue;
            }
            $modules[] = basename($path);
        }

        // Get all themes
        foreach (glob($directory."/themes/*", GLOB_ONLYDIR) as $path) {
            // Check for templates
            if (is_dir("$path/templates")) {
                $modules[] = 'themes/'.basename($path);
            }
        }

        return $modules;
    }

    /**
     * Extract all strings from modules and return these grouped by module name
     *
     * @param array $restrictToModules
     * @param bool $mergeWithExisting
     * @return array
     */
    public function collect($restrictToModules = array(), $mergeWithExisting = false)
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
            foreach (array_diff(array_keys($entitiesByModule), $restrictToModules) as $module) {
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
            if (!$bestModule) {
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
        $modules = array_keys($entitiesByModule);
        $allConflicts = array();
        // bubble-compare each group of modules
        for ($i = 0; $i < count($modules) - 1; $i++) {
            $left = array_keys($entitiesByModule[$modules[$i]]);
            for ($j = $i+1; $j < count($modules); $j++) {
                $right = array_keys($entitiesByModule[$modules[$j]]);
                $conflicts = array_intersect($left, $right);
                $allConflicts = array_merge($allConflicts, $conflicts);
            }
        }
        return array_unique($allConflicts);
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
        $class = current(explode('.', $key));
        if (array_key_exists($class, $this->classModuleCache)) {
            return $this->classModuleCache[$class];
        }
        $owner = $this->findModuleForClass($class);
        if ($owner) {
            $this->classModuleCache[$class] = $owner;
            return $owner;
        }

        // @todo - How to determine ownership of templates? Templates can
        // exist in multiple locations with the same name.

        // Display notice if not found
        Debug::message(
            "Duplicate key {$key} detected in no / multiple modules with no obvious owner",
            false
        );

        // Fall back to framework then cms modules
        foreach (array('framework', 'cms') as $module) {
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
            return i18n::get_owner_module($class);
        }


        // If we can't find a class, see if it needs to be fully qualified
        if (strpos($class, '\\') !== false) {
            return null;
        }

        // Find FQN that ends with $class
        $classes = preg_grep(
            '/'.preg_quote("\\{$class}", '\/').'$/i',
            ClassLoader::instance()->getManifest()->getClassNames()
        );

        // Find all modules for candidate classes
        $modules = array_unique(array_map(function ($class) {
            return i18n::get_owner_module($class);
        }, $classes));

        if (count($modules) === 1) {
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
        foreach ($entitiesByModule as $module => $messages) {
            // Load existing localisations
            $masterFile = "{$this->basePath}/{$module}/lang/{$this->defaultLocale}.yml";
            $existingMessages = $this->getReader()->read($this->defaultLocale, $masterFile);

            // Merge
            if ($existingMessages) {
                $entitiesByModule[$module] = array_merge(
                    $existingMessages,
                    $messages
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
        $entitiesByModule = array();
        $modules = $this->getModules($this->basePath);
        foreach ($modules as $module) {
            // we store the master string tables
            $processedEntities = $this->processModule($module);
            if (isset($entitiesByModule[$module])) {
                $entitiesByModule[$module] = array_merge_recursive($entitiesByModule[$module], $processedEntities);
            } else {
                $entitiesByModule[$module] = $processedEntities;
            }

            // Extract all entities for "foreign" modules ('module' key in array form)
            // @see CMSMenu::provideI18nEntities for an example usage
            foreach ($entitiesByModule[$module] as $fullName => $spec) {
                $specModule = $module;

                // Rewrite spec if module is specified
                if (is_array($spec) && isset($spec['module'])) {
                    $specModule = $spec['module'];
                    unset($spec['module']);

                    // If only element is defalt, simplify
                    if (count($spec) === 1 && !empty($spec['default'])) {
                        $spec = $spec['default'];
                    }
                }

                // Remove from source module
                if ($specModule !== $module) {
                    unset($entitiesByModule[$module][$fullName]);
                }

                // Write to target module
                if (!isset($entitiesByModule[$specModule])) {
                    $entitiesByModule[$specModule] = [];
                }
                $entitiesByModule[$specModule][$fullName] = $spec;
            }
        }
        return $entitiesByModule;
    }


    public function write($module, $entities)
    {
        $this->getWriter()->write($entities, $this->defaultLocale, $this->baseSavePath . '/' . $module);
        return $this;
    }

    /**
     * Builds a master string table from php and .ss template files for the module passed as the $module param
     * @see collectFromCode() and collectFromTemplate()
     *
     * @param string $module A module's name or just 'themes/<themename>'
     * @return array An array of entities found in the files that comprise the module
     */
    protected function processModule($module)
    {
        $entities = array();

        // Search for calls in code files if these exists
        $fileList = $this->getFileListForModule($module);
        foreach ($fileList as $filePath) {
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $content = file_get_contents($filePath);
            // Filter based on extension
            if ($extension === 'php') {
                $entities = array_merge(
                    $entities,
                    $this->collectFromCode($content, $module),
                    $this->collectFromEntityProviders($filePath, $module)
                );
            } elseif ($extension === 'ss') {
                // templates use their filename as a namespace
                $namespace = basename($filePath);
                $entities = array_merge(
                    $entities,
                    $this->collectFromTemplate($content, $module, $namespace)
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
     * @param string $module
     * @return array List of files to parse
     */
    protected function getFileListForModule($module)
    {
        $modulePath = "{$this->basePath}/{$module}";

        // Search all .ss files in themes
        if (stripos($module, 'themes/') === 0) {
            return $this->getFilesRecursive($modulePath, null, 'ss');
        }

        // If Framework or non-standard module structure, so we'll scan all subfolders
        if ($module === FRAMEWORK_DIR || !is_dir("{$modulePath}/code")) {
            return $this->getFilesRecursive($modulePath);
        }

        // Get code files
        $files = $this->getFilesRecursive("{$modulePath}/code", null, 'php');

        // Search for templates in this module
        if (is_dir("{$modulePath}/templates")) {
            $templateFiles = $this->getFilesRecursive("{$modulePath}/templates", null, 'ss');
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
     * @param string $module Module's name or 'themes'. Could also be a namespace
     * Generated by templates includes. E.g. 'UploadField.ss'
     * @return array Map of localised keys to default values provided for this code
     */
    public function collectFromCode($content, $module)
    {
        $entities = array();

        $tokens = token_get_all("<?php\n" . $content);
        $inTransFn = false;
        $inConcat = false;
        $inArrayClosedBy = false; // Set to the expected closing token, or false if not in array
        $currentEntity = array();
        foreach ($tokens as $token) {
            if (is_array($token)) {
                list($id, $text) = $token;

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
                if (in_array($id, [T_VARIABLE, T_STATIC, T_CLASS_C]) ||
                    ($id === T_STRING && in_array($text, ['self', 'static', 'parent']))
                ) {
                    // Un-collectable strings such as _t(static::class.'.KEY').
                    // Should be provided by i18nEntityProvider instead
                    $inTransFn = false;
                    $inArrayClosedBy = false;
                    $inConcat = false;
                    $currentEntity = array();
                    continue;
                }

                if ($id == T_CONSTANT_ENCAPSED_STRING) {
                    // Fixed quoting escapes, and remove leading/trailing quotes
                    if (preg_match('/^\'/', $text)) {
                        $text = str_replace("\\'", "'", $text);
                        $text = preg_replace('/^\'/', '', $text);
                        $text = preg_replace('/\'$/', '', $text);
                    } else {
                        $text = str_replace('\"', '"', $text);
                        $text = preg_replace('/^"/', '', $text);
                        $text = preg_replace('/"$/', '', $text);
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
                }
                continue; // is_array
            }

            // Test we can close this array
            if ($inTransFn && $inArrayClosedBy && ($token === $inArrayClosedBy)) {
                $inArrayClosedBy = false;
                continue;
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
                    $currentEntity = array();
                    $inArrayClosedBy = false;
                    break;
            }
        }

        // Normalise all keys
        foreach ($entities as $key => $entity) {
            unset($entities[$key]);
            $entities[$this->normalizeEntity($key, $module)] = $entity;
        }
        ksort($entities);

        return $entities;
    }

    /**
     * Extracts translatables from .ss templates (Self referencing)
     *
     * @param string $content The text content of a parsed template-file
     * @param string $fileName The name of a template file when method is used in self-referencing mode
     * @param string $module Module's name or 'themes'
     * @param array $parsedFiles
     * @return array $entities An array of entities representing the extracted template function calls
     */
    public function collectFromTemplate($content, $fileName, $module, &$parsedFiles = array())
    {
        // use parser to extract <%t style translatable entities
        $entities = Parser::getTranslatables($content, $this->getWarnOnEmptyDefault());

        // use the old method of getting _t() style translatable entities
        // Collect in actual template
        if (preg_match_all('/(_t\([^\)]*?\))/ms', $content, $matches)) {
            foreach ($matches[1] as $match) {
                $entities = array_merge($entities, $this->collectFromCode($match, $module));
            }
        }

        foreach ($entities as $entity => $spec) {
            unset($entities[$entity]);
            $entities[$this->normalizeEntity($entity, $module)] = $spec;
        }
        ksort($entities);

        return $entities;
    }

    /**
     * Allows classes which implement i18nEntityProvider to provide
     * additional translation strings.
     *
     * Not all classes can be instanciated without mandatory arguments,
     * so entity collection doesn't work for all SilverStripe classes currently
     *
     * @uses i18nEntityProvider
     * @param string $filePath
     * @param string $module
     * @return array
     */
    public function collectFromEntityProviders($filePath, $module = null)
    {
        $entities = array();
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
            // Handle deprecated return syntax
            foreach ($provided as $key => $value) {
                // Detect non-associative result for any key
                if (is_array($value) && $value === array_values($value)) {
                    Deprecation::notice('5.0', 'Non-associative translations from providei18nEntities is deprecated');
                    $entity = array_filter([
                        'default' => $value[0],
                        'comment' => isset($value[1]) ? $value[1] : null,
                        'module' => isset($value[2]) ? $value[2] : null,
                    ]);
                    if (count($entity) === 1) {
                        $provided[$key] = $value[0];
                    } elseif ($entity) {
                        $provided[$key] = $entity;
                    } else {
                        unset($provided[$key]);
                    }
                }
            }
            $entities = array_merge($entities, $provided);
        }

        ksort($entities);
        return $entities;
    }

    /**
     * Normalizes enitities with namespaces.
     *
     * @param string $fullName
     * @param string $_namespace
     * @return string|boolean FALSE
     */
    protected function normalizeEntity($fullName, $_namespace = null)
    {
        // split fullname into entity parts
        $entityParts = explode('.', $fullName);
        if (count($entityParts) > 1) {
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
        if ($entity && strpos('$', $entity) !== false) {
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
    protected function getFilesRecursive($folder, $fileList = array(), $type = null, $folderExclude = '/\/(tests)$/')
    {
        if (!$fileList) {
            $fileList = array();
        }
        // Skip ignored folders
        if (is_file("{$folder}/_manifest_exclude") || preg_match($folderExclude, $folder)) {
            return $fileList;
        }

        foreach (glob($folder.'/*') as $path) {
            // Recurse if directory
            if (is_dir($path)) {
                $fileList = array_merge(
                    $fileList,
                    $this->getFilesRecursive($path, $fileList, $type, $folderExclude)
                );
                continue;
            }

            // Check if this extension is included
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            if (in_array($extension, $this->fileExtensions)
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
