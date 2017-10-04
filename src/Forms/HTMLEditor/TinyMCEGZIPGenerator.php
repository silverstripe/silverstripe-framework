<?php

namespace SilverStripe\Forms\HTMLEditor;

use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\Deprecation;
use TinyMCE_Compressor;

/**
 * Uses the default tiny_mc_gzip.php handler
 *
 * @deprecated 4.0..5.0
 */
class TinyMCEGZIPGenerator implements TinyMCEScriptGenerator
{
    use Injectable;

    public function __construct()
    {
        Deprecation::notice('5.0', 'Legacy tiny_mce_gzip compressor is deprecated');
    }

    /**
     * Generate a script URL for the given config
     *
     * @param TinyMCEConfig $config
     * @return string
     * @throws Exception
     */
    public function getScriptURL(TinyMCEConfig $config)
    {
        // If gzip is disabled just return core script url
        $useGzip = HTMLEditorField::config()->get('use_gzip');
        if (!$useGzip) {
            return Controller::join_links($config->getTinyMCEResourceURL(), 'tinymce.min.js');
        }

        // tinyMCE JS requirement - use the original module path,
        // don't assume the PHP file is copied alongside the resources
        $gzipPath = $config->getTinyMCEResourcePath() . '/tiny_mce_gzip.php';
        if (!file_exists($gzipPath)) {
            throw new Exception("HTMLEditorField.use_gzip enabled, but file $gzipPath does not exist!");
        }

        require_once $gzipPath;

        $tag = TinyMCE_Compressor::renderTag(array(
            'url' => $config->getTinyMCEResourceURL() . '/tiny_mce_gzip.php',
            'plugins' => implode(',', $config->getInternalPlugins()),
            'themes' => $config->getTheme(),
            'languages' => $config->getOption('language')
        ), true);
        preg_match('/src="([^"]*)"/', $tag, $matches);

        return html_entity_decode($matches[1]);
    }
}
