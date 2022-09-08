<?php

namespace SilverStripe\i18n\Messages\Symfony;

use SilverStripe\i18n\i18n;
use SilverStripe\i18n\Messages\Reader;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Loads yaml localisations across all modules simultaneously.
 * Note: This will also convert rails yml plurals into symfony standard format.
 * Acts as a YamlFileLoader, but across a list of modules
 */
class ModuleYamlLoader extends ArrayLoader
{
    /**
     * Message reader
     *
     * @var Reader
     */
    protected $reader = null;

    public function load(mixed $resource, string $locale, string $domain = 'messages'): MessageCatalogue
    {
        $messages = [];
        foreach ($resource as $path) {
            // Note: already-loaded messages have higher priority
            $messages = array_merge(
                $this->loadMessages($path, $locale),
                $messages
            );
        }
        ksort($messages);
        $catalog = parent::load($messages, $locale, $domain);

        // Ensure this catalog is invalidated on flush
        $catalog->addResource(new FlushInvalidatedResource());
        return $catalog;
    }

    /**
     * @return Reader
     */
    public function getReader()
    {
        return $this->reader;
    }

    /**
     * @param Reader $reader
     * @return $this
     */
    public function setReader(Reader $reader)
    {
        $this->reader = $reader;
        return $this;
    }


    /**
     * Load messages
     *
     * @param string $path
     * @param string $locale
     * @return array
     */
    protected function loadMessages($path, $locale)
    {
        $filePath = $path . $locale . '.yml';
        $messages = $this->getReader()->read($locale, $filePath);
        return $this->normaliseMessages($messages, $locale);
    }

    /**
     * Normalises plurals in messages from rails-yaml format to symfony.
     *
     * @param array $messages List of messages
     * @param string $locale
     * @return array Normalised messages
     */
    protected function normaliseMessages($messages, $locale)
    {
        foreach ($messages as $key => $value) {
            $messages[$key] = $this->normaliseMessage($key, $value, $locale);
        }
        return $messages;
    }

    /**
     * Normalise rails-yaml plurals into pipe-separated rules
     *
     * @link http://www.unicode.org/cldr/charts/latest/supplemental/language_plural_rules.html
     * @link http://guides.rubyonrails.org/i18n.html#pluralization
     * @link http://symfony.com/doc/current/components/translation/usage.html#component-translation-pluralization
     *
     * @param string $key
     * @param mixed $value Input value
     * @param string $locale
     * @return string
     */
    protected function normaliseMessage($key, $value, $locale)
    {
        if (!is_array($value)) {
            return $value;
        }
        if (isset($value['default'])) {
            return $value['default'];
        }
        // Plurals
        $pluralised = i18n::encode_plurals($value);
        if ($pluralised) {
            return $pluralised;
        }

        // Warn if mismatched plural forms
        trigger_error("Localisation entity {$locale}.{$key} is invalid", E_USER_WARNING);
        return null;
    }
}
