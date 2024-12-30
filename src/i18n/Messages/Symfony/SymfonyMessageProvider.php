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

        return $result;
    }

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
