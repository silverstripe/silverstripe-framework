<?php

namespace SilverStripe\Dev\i18n;

use SilverStripe\i18n\Messages\MessageProvider;
use Symfony\Component\Translation\MessageSelector;

class TestMessageProvider implements MessageProvider
{
    /**
     * @var MessageSelector
     */
    protected $selector;

    public function __construct()
    {
        $this->selector = new MessageSelector();
    }

    /**
     * Localise this message
     *
     * @param string $entity Identifier for this message in Namespace.key format
     * @param string $default Default message
     * @param array $injection List of injection variables
     * @return string Localised string
     */
    public function translate($entity, $default, $injection)
    {
        return $this->inject($default, $injection);
    }

    /**
     * Pluralise a message
     *
     * @param string $entity Identifier for this message in Namespace.key format
     * @param array|string $default Default message with pipe-separated delimiters, or array
     * @param array $injection List of injection variables
     * @param int $count Number to pluralise against
     * @return string Localised string
     */
    public function pluralise($entity, $default, $injection, $count)
    {
        // Choose the right "option" for injecting
        $default = $this->selector->choose($default, $count, 'en');

        return $this->inject($default, $injection);
    }

    protected function inject($default, $injection)
    {
        return strtr($default, array_combine(array_map(function($key) {
            return '{' . $key . '}';
        }, array_keys($injection)), array_values($injection)));
    }
}
