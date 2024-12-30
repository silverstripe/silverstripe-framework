<?php

namespace SilverStripe\i18n;

use Exception;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\i18n\Data\Locales;
use SilverStripe\i18n\Data\Sources;
use SilverStripe\i18n\Messages\MessageProvider;
use SilverStripe\View\TemplateGlobalProvider;
use InvalidArgumentException;

/**
 * Base-class for storage and retrieval of translated entities.
 *
 * <b>Usage</b>
 *
 * PHP:
 * <code>
 * _t('MyNamespace.MYENTITY', 'My default natural language value');
 * _t('MyNamespace.MYENTITY', 'My default natural language value', 'My explanatory context');
 * _t('MyNamespace.MYENTITY', 'Counting {number} things', ['number' => 42]);
 * </code>
 *
 * Templates:
 * <code>
 * <%t MyNamespace.MYENTITY 'My default natural language value' %>
 * <%t MyNamespace.MYENTITY 'Counting {count} things' count=$ThingsCount %>
 * </code>
 *
 * Javascript (see framework/client/dist/js/i18n.js):
 * <code>
 * ss.i18n._t('MyEntity.MyNamespace','My default natural language value');
 * </code>
 *
 * File-based i18n-translations always have a "locale" (e.g. 'en_US').
 *
 * <b>Text Collection</b>
 *
 * Features a "textcollector-mode" that parses all files with a certain extension
 * (currently *.php and *.ss) for new translatable strings. Textcollector will write
 * updated string-tables to their respective folders inside the module, and automatically
 * namespace entities to the classes/templates they are found in (e.g. $lang['en_US']['AssetAdmin']['UPLOADFILES']).
 *
 * Caution: Does not apply any character-set conversion, it is assumed that all content
 * is stored and represented in UTF-8 (Unicode). Please make sure your files are created with the correct
 * character-set, and your HTML-templates render UTF-8.
 *
 * Caution: The language file has to be stored in the same module path as the "filename namespaces"
 * on the entities. So an entity stored in $lang['en_US']['AssetAdmin']['DETAILSTAB'] has to
 * in the language file cms/lang/en_US.php, as the referenced file (AssetAdmin.php) is stored
 * in the "cms" module.
 *
 * <b>Locales</b>
 *
 * For the i18n class, a "locale" consists of a language code plus a region code separated by an underscore,
 * for example "de_AT" for German language ("de") in the region Austria ("AT").
 * See http://www.w3.org/International/articles/language-tags/ for a detailed description.
 *
 * @see http://doc.silverstripe.org/i18n
 * @see http://www.w3.org/TR/i18n-html-tech-lang
 * @author Bernat Foj Capell <bernat@silverstripe.com>
 */
class i18n implements TemplateGlobalProvider
{
    use Configurable;

    /**
     * This static variable is used to store the current defined locale.
     *
     * @var string
     */
    protected static $current_locale = '';

    /**
     * @config
     * @var string
     */
    private static $default_locale = 'en_US';

    /**
     * System-wide date format. Will be overruled for CMS UI display
     * by the format defaults inferred from the browser as well as
     * any user-specific locale preferences.
     *
     * @config
     * @var string
     */
    private static $date_format = 'yyyy-MM-dd';

    /**
     * System-wide time format. Will be overruled for CMS UI display
     * by the format defaults inferred from the browser as well as
     * any user-specific locale preferences.
     *
     * @config
     * @var string
     */
    private static $time_format = 'H:mm';

    /**
     * Map of rails plurals into standard order (fewest to most)
     * Note: Default locale only supplies one|other, but non-default locales
     * can specify custom plurals.
     *
     * @config
     * @var array
     */
    private static $plurals = [
        'zero',
        'one',
        'two',
        'few',
        'many',
        'other',
    ];

    /**
     * Plural forms in default (en) locale
     *
     * @var array
     */
    private static $default_plurals = [
        'one',
        'other',
    ];

    /**
     * Warn if _t() invoked without a default.
     *
     * @config
     * @var bool
     */
    private static $missing_default_warning = true;

    /**
     * This is the main translator function. Returns the string defined by $entity according to the
     * currently set locale.
     *
     * Also supports pluralisation of strings. Pass in a `count` argument, as well as a
     * default value with `|` pipe-delimited options for each plural form.
     *
     * @param string $entity Entity that identifies the string. It must be in the form
     * "Namespace.Entity" where Namespace will be usually the class name where this
     * string is used and Entity identifies the string inside the namespace.
     * @param mixed $arg Additional arguments are parsed as such:
     *  - Next string argument is a default. Pass in a `|` pipe-delimited value with `{count}`
     *    to do pluralisation.
     *  - Any other string argument after default is context for i18nTextCollector
     *  - Any array argument in any order is an injection parameter list. Pass in a `count`
     *    injection parameter to pluralise.
     * @return string
     */
    public static function _t($entity, $arg = null)
    {
        // Detect args
        $default = null;
        $injection = [];
        foreach (array_slice(func_get_args(), 1) as $arg) {
            if (is_array($arg)) {
                $injection = $arg;
            } elseif (!isset($default)) {
                $default = $arg ?: '';
            }
        }

        // Encourage the provision of default values so that text collector can discover new strings
        if (!$default && i18n::config()->uninherited('missing_default_warning')) {
            user_error("Missing default for localisation key $entity", E_USER_WARNING);
        }

        // Deprecate legacy injection format (`string %s, %d`)
        // inject the variables from injectionArray (if present)
        $sprintfArgs = [];
        if ($default && !preg_match('/\{[\w\d]*\}/i', $default ?? '') && preg_match('/%[s,d]/', $default ?? '')) {
            $sprintfArgs = array_values($injection ?? []);
            $injection = [];
        }

        // If injection isn't associative, assume legacy injection format
        $failUnlessSprintf = false;
        if ($injection && array_values($injection ?? []) === $injection) {
            $failUnlessSprintf = true; // Note: Will trigger either a deprecation error or exception below
            $sprintfArgs = array_values($injection ?? []);
            $injection = [];
        }

        // Detect plurals: Has a {count} argument as well as a `|` pipe delimited string (if provided)
        $isPlural = isset($injection['count']);
        $count = $isPlural ? $injection['count'] : null;
        // Refine check against default
        if ($isPlural && $default && !static::parse_plurals($default)) {
            $isPlural = false;
        }

        // Pass back to translation backend
        if ($isPlural) {
            $result = static::getMessageProvider()->pluralise($entity, $default, $injection, $count);
        } else {
            $result = static::getMessageProvider()->translate($entity, $default, $injection);
        }

        if (!$default && !preg_match('/\{[\w\d]*\}/i', $result ?? '') && preg_match('/%[s,d]/', $result ?? '')) {
            throw new Exception('sprintf style localisation cannot be used in translations - detected in $result');
        }

        if ($failUnlessSprintf) {
            // Note: After removing deprecated code, you can move this error up into the is-associative check
            // Neither default nor translated strings were %s substituted, and our array isn't associative
            throw new InvalidArgumentException('Injection must be an associative array');
        }

        return $result;
    }

    /**
     * Split plural string into standard CLDR array form.
     * A string is considered a pluralised form if it has a {count} argument, and
     * a single `|` pipe-delimiting character.
     *
     * Note: Only splits in the default (en) locale as the string form contains limited metadata.
     *
     * @param string $string Input string
     * @return array List of plural forms, or empty array if not plural
     */
    public static function parse_plurals($string)
    {
        if (strstr($string ?? '', '|') && strstr($string ?? '', '{count}')) {
            $keys = i18n::config()->uninherited('default_plurals');
            $values = explode('|', $string ?? '');
            if (count($keys ?? []) == count($values ?? [])) {
                return array_combine($keys ?? [], $values ?? []);
            }
        }
        return [];
    }

    /**
     * Convert CLDR array plural form to `|` pipe-delimited string.
     * Unlike parse_plurals, this supports all locale forms (not just en)
     *
     * @param array $plurals
     * @return string Delimited string, or null if not plurals
     */
    public static function encode_plurals($plurals)
    {
        // Validate against global plural list
        $forms = i18n::config()->uninherited('plurals');
        $forms = array_combine($forms ?? [], $forms ?? []);
        $intersect = array_intersect_key($plurals ?? [], $forms);
        if ($intersect) {
            return implode('|', $intersect);
        }
        return null;
    }

    /**
     * Matches a given locale with the closest translation available in the system
     *
     * @param string $locale locale code
     * @return string Locale of closest available translation, if available
     */
    public static function get_closest_translation($locale)
    {
        // Check if exact match
        $pool = i18n::getSources()->getKnownLocales();
        if (isset($pool[$locale])) {
            return $locale;
        }

        // Fallback to best locale for common language
        $localesData = static::getData();
        $lang = $localesData->langFromLocale($locale);
        $candidate = $localesData->localeFromLang($lang);
        if (isset($pool[$candidate])) {
            return $candidate;
        }
        return null;
    }

    /**
     * Gets a RFC 1766 compatible language code,
     * e.g. "en-US".
     *
     * @see http://www.ietf.org/rfc/rfc1766.txt
     * @see http://tools.ietf.org/html/rfc2616#section-3.10
     *
     * @param string $locale
     * @return string
     */
    public static function convert_rfc1766($locale)
    {
        return str_replace('_', '-', $locale ?? '');
    }

    /**
     * Set the current locale, used as the default for
     * any localized classes, such as {@link FormField} or {@link DBField}
     * instances. Locales can also be persisted in {@link Member->Locale},
     * for example in the {@link CMSMain} interface the Member locale
     * overrules the global locale value set here.
     *
     * @param string $locale Locale to be set. See
     *                       http://unicode.org/cldr/data/diff/supplemental/languages_and_territories.html for a list
     *                       of possible locales.
     */
    public static function set_locale($locale)
    {
        if ($locale) {
            i18n::$current_locale = $locale;
        }
    }

    /**
     * Temporarily set the locale while invoking a callback
     *
     * @param string $locale
     * @param callable $callback
     * @return mixed
     */
    public static function with_locale($locale, $callback)
    {
        $oldLocale = i18n::$current_locale;
        static::set_locale($locale);
        try {
            return $callback();
        } finally {
            static::set_locale($oldLocale);
        }
    }

    /**
     * Get the current locale.
     * Used by {@link Member::populateDefaults()}
     *
     * @return string Current locale in the system
     */
    public static function get_locale()
    {
        if (!i18n::$current_locale) {
            i18n::$current_locale = i18n::config()->uninherited('default_locale');
        }
        return i18n::$current_locale;
    }

    /**
     * Returns the script direction in format compatible with the HTML "dir" attribute.
     *
     * @see http://www.w3.org/International/tutorials/bidi-xhtml/
     * @param string $locale Optional locale incl. region (underscored)
     * @return string "rtl" or "ltr"
     */
    public static function get_script_direction($locale = null)
    {
        return static::getData()->scriptDirection($locale);
    }

    public static function get_template_global_variables()
    {
        return [
            'i18nLocale' => 'get_locale',
            'get_locale',
            'i18nScriptDirection' => 'get_script_direction',
        ];
    }

    /**
     * @return MessageProvider
     */
    public static function getMessageProvider()
    {
        return Injector::inst()->get(MessageProvider::class);
    }

    /**
     * Localisation data source
     *
     * @return Locales
     */
    public static function getData()
    {
        return Injector::inst()->get(Locales::class);
    }

    /**
     * Get data sources for localisation strings
     *
     * @return Sources
     */
    public static function getSources()
    {
        return Injector::inst()->get(Sources::class);
    }
}
