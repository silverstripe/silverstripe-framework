<?php

namespace SilverStripe\i18n\Messages\Symfony;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Dev\Debug;
use SilverStripe\i18n\Messages\Reader;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\PluralizationRules;

/**
 * Loads yaml localisations across all modules simultaneously.
 * Note: This will also convert rails yml plurals into symfony standard format.
 * Acts as a YamlFileLoader, but across a list of modules
 */
class ModuleYamlLoader extends ArrayLoader
{
    use Configurable;

    /**
     * Map of rails plurals into symfony standard order
     *
     * @see PluralizationRules For symfony's implementation of this logic
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
     * Message reader
     *
     * @var Reader
     */
    protected $reader = null;

    public function load($resource, $locale, $domain = 'messages')
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
            if (is_array($value)) {
                $messages[$key] = $this->normalisePlurals($key, $value, $locale);
            }
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
     * @param array $map
     * @param string $locale
     * @return string
     */
    protected function normalisePlurals($key, $map, $locale)
    {
        $parts = [];
        foreach ($this->config()->get('plurals') as $form) {
            if (isset($map[$form])) {
                $parts[] = $map[$form];
            }
        }
        // Non-associative plural, just keep in same order
        if (empty($parts)) {
            return $parts = $map;
        }

        // Warn if mismatched plural forms
        if (count($map) !== count($parts)) {
            trigger_error("Plural form {$locale}.{$key} has invalid plural keys", E_USER_WARNING);
        }

        return implode('|', $parts);
    }
}
