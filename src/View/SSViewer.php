<?php

namespace SilverStripe\View;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\ClassInfo;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Control\Director;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\Permission;
use InvalidArgumentException;

/**
 * Parses a template file with an *.ss file extension.
 *
 * In addition to a full template in the templates/ folder, a template in
 * templates/Content or templates/Layout will be rendered into $Content and
 * $Layout, respectively.
 *
 * A single template can be parsed by multiple nested {@link SSViewer} instances
 * through $Layout/$Content placeholders, as well as <% include MyTemplateFile %> template commands.
 *
 * <b>Themes</b>
 *
 * See http://doc.silverstripe.org/themes and http://doc.silverstripe.org/themes:developing
 *
 * <b>Caching</b>
 *
 * Compiled templates are cached via {@link Cache}, usually on the filesystem.
 * If you put ?flush=1 on your URL, it will force the template to be recompiled.
 *
 * @see http://doc.silverstripe.org/themes
 * @see http://doc.silverstripe.org/themes:developing
 */
class SSViewer implements Flushable
{
    use Configurable;
    use Injectable;

    /**
     * Identifier for the default theme
     */
    const DEFAULT_THEME = '$default';

    /**
     * Identifier for the public theme
     */
    const PUBLIC_THEME = '$public';

    /**
     * A list (highest priority first) of themes to use
     * Only used when {@link $theme_enabled} is set to TRUE.
     *
     * @config
     * @var string
     */
    private static $themes = [];

    /**
     * Overridden value of $themes config
     *
     * @var array
     */
    protected static $current_themes = null;

    /**
     * Use the theme. Set to FALSE in order to disable themes,
     * which can be useful for scenarios where theme overrides are temporarily undesired,
     * such as an administrative interface separate from the website theme.
     * It retains the theme settings to be re-enabled, for example when a website content
     * needs to be rendered from within this administrative interface.
     *
     * @config
     * @var bool
     */
    private static $theme_enabled = true;

    /**
     * Default prepended cache key for partial caching
     *
     * @config
     * @var string
     */
    private static $global_key = '$CurrentReadingMode, $CurrentUser.ID';

    /**
     * @config
     * @var bool
     */
    private static $source_file_comments = false;

    /**
     * Set if hash links should be rewritten
     *
     * @config
     * @var bool
     */
    private static $rewrite_hash_links = true;

    /**
     * Overridden value of rewrite_hash_links config
     *
     * @var bool
     */
    protected static $current_rewrite_hash_links = null;

    /**
     * Instance variable to disable rewrite_hash_links (overrides global default)
     * Leave null to use global state.
     *
     * @var bool|null
     */
    protected $rewriteHashlinks = null;

    /**
     * @internal
     * @ignore
     */
    private static $template_cache_flushed = false;

    /**
     * @internal
     * @ignore
     */
    private static $cacheblock_cache_flushed = false;

    /**
     * List of items being processed
     *
     * @var array
     */
    protected static $topLevel = [];

    /**
     * List of templates to select from
     *
     * @var array
     */
    protected $templates = null;

    /**
     * Absolute path to chosen template file
     *
     * @var string
     */
    protected $chosen = null;

    /**
     * Templates to use when looking up 'Layout' or 'Content'
     *
     * @var array
     */
    protected $subTemplates = [];

    /**
     * @var bool
     */
    protected $includeRequirements = true;

    /**
     * @var TemplateParser
     */
    protected $parser;

    /**
     * @var CacheInterface
     */
    protected $partialCacheStore = null;

    /**
     * @param string|array $templates If passed as a string with .ss extension, used as the "main" template.
     *  If passed as an array, it can be used for template inheritance (first found template "wins").
     *  Usually the array values are PHP class names, which directly correlate to template names.
     *  <code>
     *  array('MySpecificPage', 'MyPage', 'Page')
     *  </code>
     * @param TemplateParser $parser
     */
    public function __construct($templates, TemplateParser $parser = null)
    {
        if ($parser) {
            $this->setParser($parser);
        }

        $this->setTemplate($templates);

        if (!$this->chosen) {
            $message = 'None of the following templates could be found: ';
            $message .= print_r($templates, true);

            $themes = SSViewer::get_themes();
            if (!$themes) {
                $message .= ' (no theme in use)';
            } else {
                $message .= ' in themes "' . print_r($themes, true) . '"';
            }

            user_error($message ?? '', E_USER_WARNING);
        }
    }

    /**
     * Triggered early in the request when someone requests a flush.
     */
    public static function flush()
    {
        SSViewer::flush_template_cache(true);
        SSViewer::flush_cacheblock_cache(true);
    }

    /**
     * Create a template from a string instead of a .ss file
     *
     * @param string $content The template content
     * @param bool|void $cacheTemplate Whether or not to cache the template from string
     * @return SSViewer
     */
    public static function fromString($content, $cacheTemplate = null)
    {
        $viewer = SSViewer_FromString::create($content);
        if ($cacheTemplate !== null) {
            $viewer->setCacheTemplate($cacheTemplate);
        }
        return $viewer;
    }

    /**
     * Assign the list of active themes to apply.
     * If default themes should be included add $default as the last entry.
     *
     * @param array $themes
     */
    public static function set_themes($themes = [])
    {
        static::$current_themes = $themes;
    }

    /**
     * Add to the list of active themes to apply
     *
     * @param array $themes
     */
    public static function add_themes($themes = [])
    {
        $currentThemes = SSViewer::get_themes();
        $finalThemes = array_merge($themes, $currentThemes);
        // array_values is used to ensure sequential array keys as array_unique can leave gaps
        static::set_themes(array_values(array_unique($finalThemes ?? [])));
    }

    /**
     * Get the list of active themes
     *
     * @return array
     */
    public static function get_themes()
    {
        $default = [SSViewer::PUBLIC_THEME, SSViewer::DEFAULT_THEME];

        if (!SSViewer::config()->uninherited('theme_enabled')) {
            return $default;
        }

        // Explicit list is assigned
        $themes = static::$current_themes;
        if (!isset($themes)) {
            $themes = SSViewer::config()->uninherited('themes');
        }
        if ($themes) {
            return $themes;
        }

        return $default;
    }

    /**
     * Traverses the given the given class context looking for candidate template names
     * which match each item in the class hierarchy. The resulting list of template candidates
     * may or may not exist, but you can invoke {@see SSViewer::chooseTemplate} on any list
     * to determine the best candidate based on the current themes.
     *
     * @param string|object $classOrObject Valid class name, or object
     * @param string $suffix
     * @param string $baseClass Class to halt ancestry search at
     * @return array
     */
    public static function get_templates_by_class($classOrObject, $suffix = '', $baseClass = null)
    {
        // Figure out the class name from the supplied context.
        if (!is_object($classOrObject) && !(
            is_string($classOrObject) && class_exists($classOrObject ?? '')
        )) {
            throw new InvalidArgumentException(
                'SSViewer::get_templates_by_class() expects a valid class name as its first parameter.'
            );
        }

        $templates = [];
        $classes = array_reverse(ClassInfo::ancestry($classOrObject) ?? []);
        foreach ($classes as $class) {
            $template = $class . $suffix;
            $templates[] = $template;
            $templates[] = ['type' => 'Includes', $template];

            // If the class is "PageController" (PSR-2 compatibility) or "Page_Controller" (legacy), look for Page.ss
            if (preg_match('/^(?<name>.+[^\\\\])_?Controller$/iU', $class ?? '', $matches)) {
                $templates[] = $matches['name'] . $suffix;
            }

            if ($baseClass && $class == $baseClass) {
                break;
            }
        }

        return $templates;
    }

    /**
     * Get the current item being processed
     *
     * @return ViewableData
     */
    public static function topLevel()
    {
        if (SSViewer::$topLevel) {
            return SSViewer::$topLevel[sizeof(SSViewer::$topLevel)-1];
        }
        return null;
    }

    /**
     * Check if rewrite hash links are enabled on this instance
     *
     * @return bool
     */
    public function getRewriteHashLinks()
    {
        if (isset($this->rewriteHashlinks)) {
            return $this->rewriteHashlinks;
        }
        return static::getRewriteHashLinksDefault();
    }

    /**
     * Set if hash links are rewritten for this instance
     *
     * @param bool $rewrite
     * @return $this
     */
    public function setRewriteHashLinks($rewrite)
    {
        $this->rewriteHashlinks = $rewrite;
        return $this;
    }

    /**
     * Get default value for rewrite hash links for all modules
     *
     * @return bool
     */
    public static function getRewriteHashLinksDefault()
    {
        // Check if config overridden
        if (isset(static::$current_rewrite_hash_links)) {
            return static::$current_rewrite_hash_links;
        }
        return Config::inst()->get(static::class, 'rewrite_hash_links');
    }

    /**
     * Set default rewrite hash links
     *
     * @param bool $rewrite
     */
    public static function setRewriteHashLinksDefault($rewrite)
    {
        static::$current_rewrite_hash_links = $rewrite;
    }

    /**
     * @param string|array $templates
     */
    public function setTemplate($templates)
    {
        $this->templates = $templates;
        $this->chosen = $this->chooseTemplate($templates);
        $this->subTemplates = [];
    }

    /**
     * Find the template to use for a given list
     *
     * @param array|string $templates
     * @return string
     */
    public static function chooseTemplate($templates)
    {
        return ThemeResourceLoader::inst()->findTemplate($templates, SSViewer::get_themes());
    }

    /**
     * Set the template parser that will be used in template generation
     *
     * @param TemplateParser $parser
     */
    public function setParser(TemplateParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Returns the parser that is set for template generation
     *
     * @return TemplateParser
     */
    public function getParser()
    {
        if (!$this->parser) {
            $this->setParser(Injector::inst()->get('SilverStripe\\View\\SSTemplateParser'));
        }
        return $this->parser;
    }

    /**
     * Returns true if at least one of the listed templates exists.
     *
     * @param array|string $templates
     *
     * @return bool
     */
    public static function hasTemplate($templates)
    {
        return (bool)ThemeResourceLoader::inst()->findTemplate($templates, SSViewer::get_themes());
    }

    /**
     * Call this to disable rewriting of <a href="#xxx"> links.  This is useful in Ajax applications.
     * It returns the SSViewer objects, so that you can call new SSViewer("X")->dontRewriteHashlinks()->process();
     *
     * @return $this
     */
    public function dontRewriteHashlinks()
    {
        return $this->setRewriteHashLinks(false);
    }

    /**
     * @return string
     */
    public function exists()
    {
        return $this->chosen;
    }

    /**
     * @param string $identifier A template name without '.ss' extension or path
     * @param string $type The template type, either "main", "Includes" or "Layout"
     * @return string Full system path to a template file
     */
    public static function getTemplateFileByType($identifier, $type = null)
    {
        return ThemeResourceLoader::inst()->findTemplate(['type' => $type, $identifier], SSViewer::get_themes());
    }

    /**
     * Clears all parsed template files in the cache folder.
     *
     * Can only be called once per request (there may be multiple SSViewer instances).
     *
     * @param bool $force Set this to true to force a re-flush. If left to false, flushing
     * may only be performed once a request.
     */
    public static function flush_template_cache($force = false)
    {
        if (!SSViewer::$template_cache_flushed || $force) {
            $dir = dir(TEMP_PATH);
            while (false !== ($file = $dir->read())) {
                if (strstr($file ?? '', '.cache')) {
                    unlink(TEMP_PATH . DIRECTORY_SEPARATOR . $file);
                }
            }
            SSViewer::$template_cache_flushed = true;
        }
    }

    /**
     * Clears all partial cache blocks.
     *
     * Can only be called once per request (there may be multiple SSViewer instances).
     *
     * @param bool $force Set this to true to force a re-flush. If left to false, flushing
     * may only be performed once a request.
     */
    public static function flush_cacheblock_cache($force = false)
    {
        if (!SSViewer::$cacheblock_cache_flushed || $force) {
            $cache = Injector::inst()->get(CacheInterface::class . '.cacheblock');
            $cache->clear();


            SSViewer::$cacheblock_cache_flushed = true;
        }
    }

    /**
     * Set the cache object to use when storing / retrieving partial cache blocks.
     *
     * @param CacheInterface $cache
     */
    public function setPartialCacheStore($cache)
    {
        $this->partialCacheStore = $cache;
    }

    /**
     * Get the cache object to use when storing / retrieving partial cache blocks.
     *
     * @return CacheInterface
     */
    public function getPartialCacheStore()
    {
        if ($this->partialCacheStore) {
            return $this->partialCacheStore;
        }

        return Injector::inst()->get(CacheInterface::class . '.cacheblock');
    }

    /**
     * Flag whether to include the requirements in this response.
     *
     * @param bool $incl
     */
    public function includeRequirements($incl = true)
    {
        $this->includeRequirements = $incl;
    }

    /**
     * An internal utility function to set up variables in preparation for including a compiled
     * template, then do the include
     *
     * Effectively this is the common code that both SSViewer#process and SSViewer_FromString#process call
     *
     * @param string $cacheFile The path to the file that contains the template compiled to PHP
     * @param ViewableData $item The item to use as the root scope for the template
     * @param array $overlay Any variables to layer on top of the scope
     * @param array $underlay Any variables to layer underneath the scope
     * @param ViewableData $inheritedScope The current scope of a parent template including a sub-template
     * @return string The result of executing the template
     */
    protected function includeGeneratedTemplate($cacheFile, $item, $overlay, $underlay, $inheritedScope = null)
    {
        if (isset($_GET['showtemplate']) && $_GET['showtemplate'] && Permission::check('ADMIN')) {
            $lines = file($cacheFile ?? '');
            echo "<h2>Template: $cacheFile</h2>";
            echo "<pre>";
            foreach ($lines as $num => $line) {
                echo str_pad($num+1, 5) . htmlentities($line, ENT_COMPAT, 'UTF-8');
            }
            echo "</pre>";
        }

        $cache = $this->getPartialCacheStore();
        $scope = new SSViewer_DataPresenter($item, $overlay, $underlay, $inheritedScope);
        $val = '';

        // Placeholder for values exposed to $cacheFile
        [$cache, $scope, $val];
        include($cacheFile);

        return $val;
    }

    /**
     * The process() method handles the "meat" of the template processing.
     *
     * It takes care of caching the output (via {@link Cache}), as well as
     * replacing the special "$Content" and "$Layout" placeholders with their
     * respective subtemplates.
     *
     * The method injects extra HTML in the header via {@link Requirements::includeInHTML()}.
     *
     * Note: You can call this method indirectly by {@link ViewableData->renderWith()}.
     *
     * @param ViewableData $item
     * @param array|null $arguments Arguments to an included template
     * @param ViewableData $inheritedScope The current scope of a parent template including a sub-template
     * @return DBHTMLText Parsed template output.
     */
    public function process($item, $arguments = null, $inheritedScope = null)
    {
        // Set hashlinks and temporarily modify global state
        $rewrite = $this->getRewriteHashLinks();
        $origRewriteDefault = static::getRewriteHashLinksDefault();
        static::setRewriteHashLinksDefault($rewrite);

        SSViewer::$topLevel[] = $item;

        $template = $this->chosen;

        $cacheFile = TEMP_PATH . DIRECTORY_SEPARATOR . '.cache'
            . str_replace(['\\','/',':'], '.', Director::makeRelative(realpath($template ?? '')) ?? '');
        $lastEdited = filemtime($template ?? '');

        if (!file_exists($cacheFile ?? '') || filemtime($cacheFile ?? '') < $lastEdited) {
            $content = file_get_contents($template ?? '');
            $content = $this->parseTemplateContent($content, $template);

            $fh = fopen($cacheFile ?? '', 'w');
            fwrite($fh, $content ?? '');
            fclose($fh);
        }

        $underlay = ['I18NNamespace' => basename($template ?? '')];

        // Makes the rendered sub-templates available on the parent item,
        // through $Content and $Layout placeholders.
        foreach (['Content', 'Layout'] as $subtemplate) {
            // Detect sub-template to use
            $sub = $this->getSubtemplateFor($subtemplate);
            if (!$sub) {
                continue;
            }

            // Create lazy-evaluated underlay for this subtemplate
            $underlay[$subtemplate] = function () use ($item, $arguments, $sub) {
                $subtemplateViewer = clone $this;
                // Disable requirements - this will be handled by the parent template
                $subtemplateViewer->includeRequirements(false);
                // Select the right template
                $subtemplateViewer->setTemplate($sub);

                // Render if available
                if ($subtemplateViewer->exists()) {
                    return $subtemplateViewer->process($item, $arguments);
                }
                return null;
            };
        }

        $output = $this->includeGeneratedTemplate($cacheFile, $item, $arguments, $underlay, $inheritedScope);

        if ($this->includeRequirements) {
            $output = Requirements::includeInHTML($output);
        }

        array_pop(SSViewer::$topLevel);

        // If we have our crazy base tag, then fix # links referencing the current page.
        if ($rewrite) {
            if (strpos($output ?? '', '<base') !== false) {
                if ($rewrite === 'php') {
                    $thisURLRelativeToBase = <<<PHP
<?php echo \\SilverStripe\\Core\\Convert::raw2att(preg_replace("/^(\\\\/)+/", "/", \$_SERVER['REQUEST_URI'])); ?>
PHP;
                } else {
                    $thisURLRelativeToBase = Convert::raw2att(preg_replace("/^(\\/)+/", "/", $_SERVER['REQUEST_URI'] ?? ''));
                }

                $output = preg_replace('/(<a[^>]+href *= *)"#/i', '\\1"' . $thisURLRelativeToBase . '#', $output ?? '');
            }
        }

        /** @var DBHTMLText $html */
        $html = DBField::create_field('HTMLFragment', $output);

        // Reset global state
        static::setRewriteHashLinksDefault($origRewriteDefault);
        return $html;
    }

    /**
     * Get the appropriate template to use for the named sub-template, or null if none are appropriate
     *
     * @param string $subtemplate Sub-template to use
     *
     * @return array|null
     */
    protected function getSubtemplateFor($subtemplate)
    {
        // Get explicit subtemplate name
        if (isset($this->subTemplates[$subtemplate])) {
            return $this->subTemplates[$subtemplate];
        }

        // Don't apply sub-templates if type is already specified (e.g. 'Includes')
        if (isset($this->templates['type'])) {
            return null;
        }

        // Filter out any other typed templates as we can only add, not change type
        $templates = array_filter(
            (array)$this->templates,
            function ($template) {
                return !isset($template['type']);
            }
        );
        if (empty($templates)) {
            return null;
        }

        // Set type to subtemplate
        $templates['type'] = $subtemplate;
        return $templates;
    }

    /**
     * Execute the given template, passing it the given data.
     * Used by the <% include %> template tag to process templates.
     *
     * @param string $template Template name
     * @param mixed $data Data context
     * @param array $arguments Additional arguments
     * @param Object $scope
     * @param bool $globalRequirements
     *
     * @return string Evaluated result
     */
    public static function execute_template($template, $data, $arguments = null, $scope = null, $globalRequirements = false)
    {
        $v = SSViewer::create($template);

        if ($globalRequirements) {
            $v->includeRequirements(false);
        } else {
            //nest a requirements backend for our template rendering
            $origBackend = Requirements::backend();
            Requirements::set_backend(Requirements_Backend::create());
        }
        try {
            return $v->process($data, $arguments, $scope);
        } finally {
            if (!$globalRequirements) {
                Requirements::set_backend($origBackend);
            }
        }
    }

    /**
     * Execute the evaluated string, passing it the given data.
     * Used by partial caching to evaluate custom cache keys expressed using
     * template expressions
     *
     * @param string $content Input string
     * @param mixed $data Data context
     * @param array $arguments Additional arguments
     * @param bool $globalRequirements
     *
     * @return string Evaluated result
     */
    public static function execute_string($content, $data, $arguments = null, $globalRequirements = false)
    {
        $v = SSViewer::fromString($content);

        if ($globalRequirements) {
            $v->includeRequirements(false);
        } else {
            //nest a requirements backend for our template rendering
            $origBackend = Requirements::backend();
            Requirements::set_backend(Requirements_Backend::create());
        }
        try {
            return $v->process($data, $arguments);
        } finally {
            if (!$globalRequirements) {
                Requirements::set_backend($origBackend);
            }
        }
    }

    /**
     * Parse given template contents
     *
     * @param string $content The template contents
     * @param string $template The template file name
     * @return string
     */
    public function parseTemplateContent($content, $template = "")
    {
        return $this->getParser()->compileString(
            $content,
            $template,
            Director::isDev() && SSViewer::config()->uninherited('source_file_comments')
        );
    }

    /**
     * Returns the filenames of the template that will be rendered.  It is a map that may contain
     * 'Content' & 'Layout', and will have to contain 'main'
     *
     * @return array
     */
    public function templates()
    {
        return array_merge(['main' => $this->chosen], $this->subTemplates);
    }

    /**
     * @param string $type "Layout" or "main"
     * @param string $file Full system path to the template file
     */
    public function setTemplateFile($type, $file)
    {
        if (!$type || $type == 'main') {
            $this->chosen = $file;
        } else {
            $this->subTemplates[$type] = $file;
        }
    }

    /**
     * Return an appropriate base tag for the given template.
     * It will be closed on an XHTML document, and unclosed on an HTML document.
     *
     * @param string $contentGeneratedSoFar The content of the template generated so far; it should contain
     * the DOCTYPE declaration.
     * @return string
     */
    public static function get_base_tag($contentGeneratedSoFar)
    {
        // Base href should always have a trailing slash
        $base = rtrim(Director::absoluteBaseURL(), '/') . '/';

        // Is the document XHTML?
        if (preg_match('/<!DOCTYPE[^>]+xhtml/i', $contentGeneratedSoFar ?? '')) {
            return "<base href=\"$base\" />";
        } else {
            return "<base href=\"$base\"><!--[if lte IE 6]></base><![endif]-->";
        }
    }
}
