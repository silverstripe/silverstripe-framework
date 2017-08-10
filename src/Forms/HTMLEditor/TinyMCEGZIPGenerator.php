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
 * @deprecated 4.0.0:5.0.0
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
        return Controller::join_links($config->getTinyMCEResourceURL(), 'tinymce.min.js');
    }
}
