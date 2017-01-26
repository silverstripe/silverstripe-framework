<?php

namespace SilverStripe\Forms;

use InvalidArgumentException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\i18n\i18n;
use SilverStripe\View\Requirements;

/**
 * Preliminary API to separate optional view properties
 * like calendar popups from the actual datefield logic.
 *
 * Caution: This API is highly volatile, and might change without prior deprecation.
 */
class DateField_View_JQuery
{
    use Injectable;
    use Configurable;

    /**
     * @var DateField
     */
    protected $field;

    /**
     * @var array Maps values from {@link i18n::$all_locales} to
     * localizations existing in jQuery UI.
     */
    private static $locale_map = array(
        'en_GB' => 'en-GB',
        'en_US' => 'en',
        'en_NZ' => 'en-GB',
        'fr_CH' => 'fr',
        'pt_BR' => 'pt-BR',
        'sr_SR' => 'sr-SR',
        'zh_CN' => 'zh-CN',
        'zh_HK' => 'zh-HK',
        'zh_TW' => 'zh-TW',
    );

    /**
     * @param DateField $field
     */
    public function __construct($field)
    {
        $this->field = $field;

        // Health check
        if (!$this->localePath('en')) {
            throw new InvalidArgumentException("Missing jquery config");
        }
    }

    /**
     * @return DateField
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Get path to localisation file for a given locale, if it exists
     *
     * @param string $lang
     * @return string Relative path to file, or null if it isn't available
     */
    protected function localePath($lang)
    {
        $path = ADMIN_THIRDPARTY_DIR . "/jquery-ui/datepicker/i18n/jquery.ui.datepicker-{$lang}.js";
        if (file_exists(BASE_PATH . '/' . $path)) {
            return $path;
        }
        return null;
    }

    public function onBeforeRender()
    {
    }

    /**
     * @param String $html
     * @return string
     */
    public function onAfterRender($html)
    {
        if ($this->getField()->getShowCalendar()) {
            // Load config for this locale if available
            $locale = $this->getLocale();
            $localeFile = $this->localePath($locale);
            if ($localeFile) {
                Requirements::javascript($localeFile);
            }
        }

        return $html;
    }

    /**
     * Determines which language to use for jQuery UI, which
     * can be different from the value set in i18n.
     *
     * @return string
     */
    public function getLocale()
    {
        $locale = $this->getField()->getClientLocale();

        // Check standard mappings
        $map = $this->config()->locale_map;
        if (array_key_exists($locale, $map)) {
            return $map[$locale];
        }

        // Fall back to default lang (meaning "en_US" turns into "en")
        return i18n::getData()->langFromLocale($locale);
    }

    /**
     * Convert iso to jquery UI date format.
     * Needs to be consistent with Zend formatting, otherwise validation will fail.
     * Removes all time settings like hour/minute/second from the format.
     * See http://docs.jquery.com/UI/Datepicker/formatDate
     * From http://userguide.icu-project.org/formatparse/datetime
     *
     * @param string $format
     * @return string
     */
    public static function convert_iso_to_jquery_format($format)
    {
        $convert = array(
            '/([^d])d([^d])/' => '$1d$2',
            '/^d([^d])/' => 'd$1',
            '/([^d])d$/' => '$1d',
            '/dd/' => 'dd',
            '/SS/' => '',
            '/eee/' => 'd',
            '/e/' => 'N',
            '/D/' => '',
            '/EEEE/' => 'DD',
            '/EEE/' => 'D',
            '/w/' => '',
            // make single "M" lowercase
            '/([^M])M([^M])/' => '$1m$2',
            // make single "M" at start of line lowercase
            '/^M([^M])/' => 'm$1',
            // make single "M" at end of line lowercase
            '/([^M])M$/' => '$1m',
            // match exactly three capital Ms not preceeded or followed by an M
            '/(?<!M)MMM(?!M)/' => 'M',
            // match exactly two capital Ms not preceeded or followed by an M
            '/(?<!M)MM(?!M)/' => 'mm',
            // match four capital Ms (maximum allowed)
            '/MMMM/' => 'MM',
            '/l/' => '',
            '/YYYY/' => 'yy',
            '/yyyy/' => 'yy',
            // See http://open.silverstripe.org/ticket/7669
            '/y{1,3}/' => 'yy',
            '/a/' => '',
            '/B/' => '',
            '/hh/' => '',
            '/h/' => '',
            '/([^H])H([^H])/' => '',
            '/^H([^H])/' => '',
            '/([^H])H$/' => '',
            '/HH/' => '',
            // '/mm/' => '',
            '/ss/' => '',
            '/zzzz/' => '',
            '/I/' => '',
            '/ZZZZ/' => '',
            '/Z/' => '',
            '/z/' => '',
            '/X/' => '',
            '/r/' => '',
            '/U/' => '',
        );
        $patterns = array_keys($convert);
        $replacements = array_values($convert);

        return preg_replace($patterns, $replacements, $format);
    }

    /**
     * Get client date format
     *
     * @return string
     */
    public function getDateFormat()
    {
        return static::convert_iso_to_jquery_format($this->getField()->getDateFormat());
    }
}
