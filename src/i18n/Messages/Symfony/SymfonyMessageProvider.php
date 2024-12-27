<?php

namespace SilverStripe\i18n\Messages\Symfony;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\i18n\i18n;
use SilverStripe\i18n\Messages\MessageProvider;
use Symfony\Component\Translation\Translator;

/**
 * Implement message localisation using a symfony/translate backend
 */
class SymfonyMessageProvider implements MessageProvider
{
    use Injectable;
    use Configurable;

    /**
     * List of locales initialised
     *
     * @var array
     */
    protected $loadedLocales = [];

    /**
     * @var Translator
     */
    protected $translator = null;

    /**
     * List of source folder dirs to load yml localisations from
     *
     * @var array
     */
    protected $sourceDirs = [];

    /**
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @param Translator $translator
     * @return $this
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
        foreach ($translator->getFallbackLocales() as $locale) {
            $this->load($locale);
        }
        return $this;
    }

    /**
     * Load resources for the given locale
     *
     * @param string $locale
     */
    protected function load($locale)
    {
        if (isset($this->loadedLocales[$locale])) {
            return;
        }

        // Add full locale file. E.g. 'en_NZ'
        $this
            ->getTranslator()
            ->addResource('ss', $this->getSourceDirs(), $locale);

        // Add lang-only file. E.g. 'en'
        $lang = i18n::getData()->langFromLocale($locale);
        if ($lang !== $locale) {
            $this
                ->getTranslator()
                ->addResource('ss', $this->getSourceDirs(), $lang);
        }


        $this->loadedLocales[$locale] = true;
    }

    /**
     * Translate the given $entity key. If no translation is found in the current locale,
     * it will fallback to $default. Also performs variable replacement for both
     * {placeholders} and $variables, and can handle single-scalar injection.
     *
     * @param string $entity    The localisation key, e.g. "MyNamespace.MyKey"
     * @param mixed  $default   The default text or an array with ['default' => '...', 'comment' => '...']
     * @param mixed  $injection Either an associative array of replacements (e.g. ['type' => 'Office']),
     *                          or a single scalar which will replace '$type' by that scalar.
     * @return string           The resulting translated string after injections.
     */
    public function translate($entity, $default, $injection)
    {
        // Ensure localisation is ready
        $locale = i18n::get_locale();
        $this->load($locale);

        // Prepare arguments
        $arguments = $this->templateInjection($injection);

        // Pass to symfony translator
        $result = $this->getTranslator()->trans($entity, $arguments, 'messages', $locale);

        // Manually inject default if no translation found
        if ($entity === $result) {
            $result = $this->getTranslator()->trans($default, $arguments, 'messages', $locale);
        }

        // Perform $variable replacement if $injection is an array or a single or multi scalars
        if (is_array($injection) && !empty($injection)) {
            foreach ($injection as $k => $v) {
                $result = str_replace('$' . $k, (string)$v, $result);
            }
        } elseif (!is_array($injection) && !empty($injection)) {
            $scalars = [$injection];

            if (preg_match_all('/\$([a-zA-Z_]\w*)/', $result, $matches)) {
                $i = 0;
                foreach ($matches[1] as $varName) {
                    if (isset($scalars[$i])) {
                        $result = str_replace('$' . $varName, (string)$scalars[$i], $result);
                        $i++;
                    } else {
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Perform plural translation for the given $entity key. If no translation is found,
     * fallback to $default. Also performs variable injection for {placeholders} and $variables.
     *
     * @param string $entity    Localisation key, e.g. "MyNamespace.MyKey"
     * @param mixed  $default   A pipe-delimited string or array with plurals, or a fallback default
     * @param mixed  $injection Either an associative array of replacements or a single scalar
     * @param int    $count     Numeric value to determine which plural form to use
     * @return string           The resulting translated string after plural logic and injections
     */
    public function pluralise($entity, $default, $injection, $count)
    {
        if (is_array($default)) {
            $default = $this->normalisePlurals($default);
        }

        // Ensure localisation is ready
        $locale = i18n::get_locale();
        $this->load($locale);

        // Prepare arguments
        $arguments = $this->templateInjection($injection);
        $arguments['%count%'] = $count;

        // Pass to symfony translator
        $result = $this->getTranslator()->trans($entity, $arguments, 'messages', $locale);

        // Manually inject default if no translation found
        if ($entity === $result) {
            $result = $this->getTranslator()->trans($default, $arguments, 'messages', $locale);
        }

        // Perform $variable replacement if $injection is an array or a single or multi scalars
        if (is_array($injection) && !empty($injection)) {
            foreach ($injection as $k => $v) {
                $result = str_replace('$' . $k, (string)$v, $result);
            }
        } elseif (!is_array($injection) && !empty($injection)) {
            $scalars = [$injection];

            if (preg_match_all('/\$([a-zA-Z_]\w*)/', $result, $matches)) {
                $i = 0;
                foreach ($matches[1] as $varName) {
                    if (isset($scalars[$i])) {
                        $result = str_replace('$' . $varName, (string)$scalars[$i], $result);
                        $i++;
                    } else {
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get the list of /lang dirs to load localisations from
     *
     * @return array
     */
    public function getSourceDirs()
    {
        if (!$this->sourceDirs) {
            $this->setSourceDirs(i18n::getSources()->getLangDirs());
        }
        return $this->sourceDirs;
    }

    /**
     * Set the list of /lang dirs to load localisations from
     *
     * @param array $sourceDirs
     * @return $this
     */
    public function setSourceDirs($sourceDirs)
    {
        $this->sourceDirs = $sourceDirs;
        return $this;
    }

    /**
     * Generate template safe injection parameters
     *
     * @param array $injection
     * @return array Injection array with all keys surrounded with {} placeholders
     */
    protected function templateInjection($injection)
    {
        $injection = $injection ?: [];
        // Rewrite injection to {} surrounded placeholders
        $arguments = array_combine(
            array_map(function ($val) {
                return '{' . $val . '}';
            }, array_keys($injection ?? [])),
            $injection ?? []
        );
        return $arguments;
    }

    /**
     * Convert ruby i18n plural form to symfony pipe-delimited form.
     *
     * @param array $parts
     * @return array|string
     */
    protected function normalisePlurals($parts)
    {
        return implode('|', $parts);
    }
}
