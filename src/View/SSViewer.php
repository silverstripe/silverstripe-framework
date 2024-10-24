<?php

namespace SilverStripe\View;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Control\Director;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use InvalidArgumentException;
use SilverStripe\Core\Injector\Injector;

/**
 * Class that manages themes and interacts with TemplateEngine classes to render templates.
 *
 * Ensures rendered templates are normalised, e.g have appropriate resources from the Requirements API.
 */
class SSViewer
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
     */
    private static array $themes = [];

    /**
     * Use the theme. Set to FALSE in order to disable themes,
     * which can be useful for scenarios where theme overrides are temporarily undesired,
     * such as an administrative interface separate from the website theme.
     * It retains the theme settings to be re-enabled, for example when a website content
     * needs to be rendered from within this administrative interface.
     */
    private static bool $theme_enabled = true;

    /**
     * If true, rendered templates will include comments indicating which template file was used.
     * May not be supported for some rendering engines.
     */
    private static bool $source_file_comments = false;

    /**
     * Set if hash links should be rewritten
     */
    private static bool $rewrite_hash_links = true;

    /**
     * Overridden value of $themes config
     */
    protected static array $current_themes = [];

    /**
     * Overridden value of rewrite_hash_links config
     *
     * Can be set to "php" to rewrite hash links with PHP executable code.
     */
    protected static null|bool|string $current_rewrite_hash_links = null;

    /**
     * Instance variable to disable rewrite_hash_links (overrides global default)
     * Leave null to use global state.
     *
     * Can be set to "php" to rewrite hash links with PHP executable code.
     */
    protected null|bool|string $rewriteHashlinks = null;

    /**
     * Determines whether resources from the Requirements API are included in a processed result.
     */
    protected bool $includeRequirements = true;

    private TemplateEngine $templateEngine;

    /**
     * @param string|array $templates If passed as a string, used as the "main" template.
     *  If passed as an array, it can be used for template inheritance (first found template "wins").
     *  Usually the array values are PHP class names, which directly correlate to template names.
     *  <code>
     *  array('MySpecificPage', 'MyPage', 'Page')
     *  </code>
     */
    public function __construct(string|array $templates, ?TemplateEngine $templateEngine = null)
    {
        if ($templateEngine) {
            $templateEngine->setTemplate($templates);
        } else {
            $templateEngine = Injector::inst()->create(TemplateEngine::class, $templates);
        }
        $this->setTemplateEngine($templateEngine);
    }

    /**
     * Assign the list of active themes to apply.
     * If default themes should be included add $default as the last entry.
     */
    public static function set_themes(array $themes): void
    {
        static::$current_themes = $themes;
    }

    /**
     * Add to the list of active themes to apply
     */
    public static function add_themes(array $themes)
    {
        $currentThemes = SSViewer::get_themes();
        $finalThemes = array_merge($themes, $currentThemes);
        // array_values is used to ensure sequential array keys as array_unique can leave gaps
        static::set_themes(array_values(array_unique($finalThemes)));
    }

    /**
     * Get the list of active themes
     */
    public static function get_themes(): array
    {
        $default = [SSViewer::PUBLIC_THEME, SSViewer::DEFAULT_THEME];

        if (!SSViewer::config()->uninherited('theme_enabled')) {
            return $default;
        }

        // Explicit list is assigned
        $themes = static::$current_themes;
        if (empty($themes)) {
            $themes = SSViewer::config()->uninherited('themes');
        }
        if ($themes) {
            return $themes;
        }

        return $default;
    }

    /**
     * Traverses the given the given class context looking for candidate template names
     * which match each item in the class hierarchy.
     *
     * This method does NOT check the filesystem, so the resulting list of template candidates
     * may or may not exist - but you can pass these template candidates into the SSViewer
     * constructor or into a TemplateEngine.
     *
     * If you really need know if a template file exists, you can call hasTemplate() on a TemplateEngine.
     *
     * @param string|object $classOrObject Valid class name, or object
     * @param string $baseClass Class to halt ancestry search at
     */
    public static function get_templates_by_class(
        string|object $classOrObject,
        string $suffix = '',
        ?string $baseClass = null
    ): array {
        // Figure out the class name from the supplied context.
        if (is_string($classOrObject) && !class_exists($classOrObject ?? '')) {
            throw new InvalidArgumentException(
                'SSViewer::get_templates_by_class() expects a valid class name or instantiated object as its first parameter.'
            );
        }

        $templates = [];
        $classes = array_reverse(ClassInfo::ancestry($classOrObject) ?? []);
        foreach ($classes as $class) {
            $template = $class . $suffix;
            $templates[] = $template;
            $templates[] = ['type' => 'Includes', $template];

            // If the class is "PageController" (PSR-2 compatibility) or "Page_Controller" (legacy), look for Page template
            if (preg_match('/^(?<name>.+[^\\\\])_?Controller$/iU', $class ?? '', $matches)) {
                $templates[] = $matches['name'] . $suffix;
            }

            if ($baseClass && $class === $baseClass) {
                break;
            }
        }

        return $templates;
    }

    /**
     * Get an associative array of names to information about callable template provider methods.
     *
     * @var boolean $createObject If true, methods will be called on instantiated objects rather than statically on the class.
     */
    public static function getMethodsFromProvider(string $providerInterface, $methodName, bool $createObject = false): array
    {
        $implementors = ClassInfo::implementorsOf($providerInterface);
        if ($implementors) {
            foreach ($implementors as $implementor) {
                // Create a new instance of the object for method calls
                if ($createObject) {
                    $implementor = new $implementor();
                    $exposedVariables = $implementor->$methodName();
                } else {
                    $exposedVariables = $implementor::$methodName();
                }

                foreach ($exposedVariables as $varName => $details) {
                    if (!is_array($details)) {
                        $details = ['method' => $details];
                    }

                    // If just a value (and not a key => value pair), use method name for both key and value
                    if (is_numeric($varName)) {
                        $varName = $details['method'];
                    }

                    // Add in a reference to the implementing class (might be a string class name or an instance)
                    $details['implementor'] = $implementor;

                    // And a callable array
                    if (isset($details['method'])) {
                        $details['callable'] = [$implementor, $details['method']];
                    }

                    // Save with both uppercase & lowercase first letter, so either works
                    $lcFirst = strtolower($varName[0] ?? '') . substr($varName ?? '', 1);
                    $result[$lcFirst] = $details;
                    $result[ucfirst($varName)] = $details;
                }
            }
        }

        return $result;
    }

    /**
     * Get the template engine used to render templates for this viewer
     */
    public function getTemplateEngine(): TemplateEngine
    {
        return $this->templateEngine;
    }

    /**
     * Check if rewrite hash links are enabled on this instance
     */
    public function getRewriteHashLinks(): null|bool|string
    {
        if ($this->rewriteHashlinks !== null) {
            return $this->rewriteHashlinks;
        }
        return static::getRewriteHashLinksDefault();
    }

    /**
     * Set if hash links are rewritten for this instance
     */
    public function setRewriteHashLinks(null|bool|string $rewrite): static
    {
        $this->rewriteHashlinks = $rewrite;
        return $this;
    }

    /**
     * Get default value for rewrite hash links for all modules
     */
    public static function getRewriteHashLinksDefault(): null|bool|string
    {
        // Check if config overridden
        if (static::$current_rewrite_hash_links !== null) {
            return static::$current_rewrite_hash_links;
        }
        return Config::inst()->get(static::class, 'rewrite_hash_links');
    }

    /**
     * Set default rewrite hash links
     */
    public static function setRewriteHashLinksDefault(null|bool|string $rewrite)
    {
        static::$current_rewrite_hash_links = $rewrite;
    }

    /**
     * Call this to disable rewriting of <a href="#xxx"> links.  This is useful in Ajax applications.
     * It returns the SSViewer objects, so that you can call new SSViewer("X")->dontRewriteHashlinks()->process();
     */
    public function dontRewriteHashlinks(): static
    {
        return $this->setRewriteHashLinks(false);
    }

    /**
     * Flag whether to include the requirements in this response.
     */
    public function includeRequirements(bool $incl = true)
    {
        $this->includeRequirements = $incl;
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
     * Note: You can call this method indirectly by {@link ModelData->renderWith()}.
     *
     * @param array $overlay Associative array of fields for use in the template.
     * These will override properties and methods with the same name from $data and from global
     * template providers.
     */
    public function process(mixed $item, array $overlay = []): DBHTMLText
    {
        $item = ViewLayerData::create($item);
        // Set hashlinks and temporarily modify global state
        $rewrite = $this->getRewriteHashLinks();
        $origRewriteDefault = static::getRewriteHashLinksDefault();
        static::setRewriteHashLinksDefault($rewrite);

        // Actually render the template
        $output = $this->getTemplateEngine()->render($item, $overlay);

        if ($this->includeRequirements) {
            $output = Requirements::includeInHTML($output);
        }

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

        // Wrap the HTML in a `DBHTMLText`. We use `HTMLFragment` here because shortcodes should
        // already have been processed, so this avoids unnecessarily trying to process them again
        /** @var DBHTMLText $html */
        $html = DBField::create_field('HTMLFragment', $output);

        // Reset global state
        static::setRewriteHashLinksDefault($origRewriteDefault);
        return $html;
    }

    /**
     * Return an appropriate base tag for the given template.
     * It will be closed on an XHTML document, and unclosed on an HTML document.
     *
     * @param bool $isXhtml Whether the DOCTYPE is xhtml or not.
     */
    public static function getBaseTag(bool $isXhtml = false): string
    {
        // Base href should always have a trailing slash
        $base = rtrim(Director::absoluteBaseURL(), '/') . '/';

        if ($isXhtml) {
            return "<base href=\"$base\" />";
        }
        return "<base href=\"$base\">";
    }

    /**
     * Get the engine used to render templates for this viewer.
     * Note that this is intentionally not public to avoid the engine being set after instantiation.
     */
    protected function setTemplateEngine(TemplateEngine $engine): static
    {
        $this->templateEngine = $engine;
        return $this;
    }
}
