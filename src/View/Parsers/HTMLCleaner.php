<?php

namespace SilverStripe\View\Parsers;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

/**
 * Base class for HTML cleaning implementations.
 */
abstract class HTMLCleaner
{
    use Configurable;
    use Injectable;

    /**
     * @var array
     */
    protected $defaultConfig = [];

    /**
     * Configuration variables for HTMLCleaners that support configuration (like Tidy)
     *
     * @var array
     */
    public $config;

    /**
     * @param array $config The configuration for the cleaner, if necessary
     */
    public function __construct($config = null)
    {
        if ($config) {
            $config = array_merge($this->defaultConfig, $config);
        } else {
            $config = $this->defaultConfig;
        }
        $this->setConfig($config);
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Passed a string, return HTML that has been tidied.
     *
     * @param string $content
     * @return string HTML, tidied
     */
    abstract public function cleanHTML($content);

    /**
     * Experimental inst class to create a default html cleaner class
     *
     * @return static
     */
    public static function inst()
    {
        if (class_exists('HTMLPurifier')) {
            return new PurifierHTMLCleaner();
        } elseif (class_exists('tidy')) {
            return new TidyHTMLCleaner();
        }
        return null;
    }
}
