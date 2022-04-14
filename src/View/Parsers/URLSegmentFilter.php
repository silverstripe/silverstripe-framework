<?php

namespace SilverStripe\View\Parsers;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

/**
 * Filter certain characters from "URL segments" (also called "slugs"), for nicer (more SEO-friendly) URLs.
 * Uses {@link Transliterator} to convert non-ASCII characters to meaningful ASCII representations.
 * Use {@link $default_allow_multibyte} to allow a broader range of characters without transliteration.
 *
 * Caution: Should not be used on full URIs with domains or query parameters.
 * In order to retain forward slashes in a path, each individual segment needs to be filtered individually.
 *
 * See {@link FileNameFilter} for similar implementation for filesystem-based URLs.
 */
class URLSegmentFilter implements FilterInterface
{
    use Configurable;
    use Injectable;

    /**
     * @config
     * @var Boolean
     */
    private static $default_use_transliterator = true;

    /**
     * @config
     * @var array See {@link setReplacements()}.
     */
    private static $default_replacements = [
        '/&amp;/u' => '-and-',
        '/&/u' => '-and-',
        '/\s|\+/u' => '-', // remove whitespace/plus
        '/[_.]+/u' => '-', // underscores and dots to dashes
        '/[^A-Za-z0-9\-]+/u' => '', // remove non-ASCII chars, only allow alphanumeric and dashes
        '/[\/\?=#:]+/u' => '-', // remove forward slashes, question marks, equal signs, hashes and colons in case multibyte is allowed (and non-ASCII chars aren't removed)
        '/[\-]{2,}/u' => '-', // remove duplicate dashes
        '/^[\-]+/u' => '', // Remove all leading dashes
        '/[\-]+$/u' => '' // Remove all trailing dashes
    ];

    /**
     * Doesn't try to replace or transliterate non-ASCII filters.
     * Useful for character sets that have little overlap with ASCII (e.g. far eastern),
     * as well as better search engine optimization for URLs.
     * @see http://www.ietf.org/rfc/rfc3987
     *
     * @config
     * @var boolean
     */
    private static $default_allow_multibyte = false;

    /**
     * @var array See {@link setReplacements()}
     */
    public $replacements = [];

    /**
     * @var Transliterator
     */
    protected $transliterator;


    /**
     * @var boolean
     */
    protected $allowMultibyte;

    /**
     * Note: Depending on the applied replacement rules, this method might result in an empty string.
     *
     * @param string $name URL path (without domain or query parameters), in utf8 encoding
     * @return string A filtered path compatible with RFC 3986
     */
    public function filter($name)
    {
        if (!$this->getAllowMultibyte()) {
            // Only transliterate when no multibyte support is requested
            $transliterator = $this->getTransliterator();
            if ($transliterator) {
                $name = $transliterator->toASCII($name);
            }
        }

        $name = mb_strtolower($name ?? '');
        $replacements = $this->getReplacements();

        // Unset automated removal of non-ASCII characters, and don't try to transliterate
        if ($this->getAllowMultibyte() && isset($replacements['/[^A-Za-z0-9\-]+/u'])) {
            unset($replacements['/[^A-Za-z0-9\-]+/u']);
        }

        foreach ($replacements as $regex => $replace) {
            $name = preg_replace($regex ?? '', $replace ?? '', $name ?? '');
        }

        // Multibyte URLs require percent encoding to comply to RFC 3986.
        // Without this setting, the "remove non-ASCII chars" regex takes care of that.
        if ($this->getAllowMultibyte()) {
            $name = rawurlencode($name ?? '');
        }

        return $name;
    }

    /**
     * @param string[] $replacements Map of find/replace used for preg_replace().
     * @return $this
     */
    public function setReplacements($replacements)
    {
        $this->replacements = $replacements;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getReplacements()
    {
        return $this->replacements ?: (array)$this->config()->get('default_replacements');
    }

    /**
     * @return Transliterator|null
     */
    public function getTransliterator()
    {
        if ($this->transliterator === null && $this->config()->get('default_use_transliterator')) {
            $this->transliterator = Transliterator::create();
        }
        return $this->transliterator;
    }

    /**
     * @param Transliterator $transliterator
     * @return $this
     */
    public function setTransliterator($transliterator)
    {
        $this->transliterator = $transliterator;
        return $this;
    }

    /**
     * @param bool $bool
     */
    public function setAllowMultibyte($bool)
    {
        $this->allowMultibyte = $bool;
    }

    /**
     * @return boolean
     */
    public function getAllowMultibyte()
    {
        return ($this->allowMultibyte !== null) ? $this->allowMultibyte : $this->config()->default_allow_multibyte;
    }
}
