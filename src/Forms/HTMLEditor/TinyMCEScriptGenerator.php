<?php

namespace SilverStripe\Forms\HTMLEditor;

/**
 * Declares a service which can generate a script URL for a given HTMLEditor config
 * @deprecated 5.4.0 Will be replaced with SilverStripe\TinyMCE\TinyMCEScriptGenerator in a future major release
 */
interface TinyMCEScriptGenerator
{
    /**
     * Generate a script URL for the given config
     *
     * @param TinyMCEConfig $config
     * @return string
     */
    public function getScriptURL(TinyMCEConfig $config);
}
