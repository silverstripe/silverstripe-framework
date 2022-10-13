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
 * @deprecated 4.0.1 Will be removed without equivalent functionality
 */
class TinyMCEGZIPGenerator implements TinyMCEScriptGenerator
{
    use Injectable;

    public function __construct()
    {
        Deprecation::notice('4.0.1', 'Will be removed without equivalent functionality', Deprecation::SCOPE_CLASS);
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
        return Controller::join_links($config->getTinyMCEResourceURL(), 'tinymce.min.js');
    }
}
