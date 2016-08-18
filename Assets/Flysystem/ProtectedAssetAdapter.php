<?php

namespace SilverStripe\Assets\Flysystem;

use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;

class ProtectedAssetAdapter extends AssetAdapter implements ProtectedAdapter {

    /**
     * Name of default folder to save secure assets in under ASSETS_PATH.
     * This can be bypassed by specifying an absolute filesystem path via
     * the SS_PROTECTED_ASSETS_PATH environment definition.
     *
     * @config
     * @var string
     */
    private static $secure_folder = '.protected';

    private static $server_configuration = array(
        'apache' => array(
            '.htaccess' => __CLASS__ . "_HTAccess"
        ),
        'microsoft-iis' => array(
            'web.config' => __CLASS__ . "_WebConfig"
        )
    );

    protected function findRoot($root) {
        // Use explicitly defined path
        if($root) {
            return parent::findRoot($root);
        }

        // Use environment defined path
        if(defined('SS_PROTECTED_ASSETS_PATH')) {
            return SS_PROTECTED_ASSETS_PATH;
        }

        // Default location is under assets
        return ASSETS_PATH . '/' . Config::inst()->get(get_class($this), 'secure_folder');
    }

    /**
     * Provide secure downloadable
     *
     * @param string $path
     * @return string|null
     */
    public function getProtectedUrl($path) {
        // Public URLs are handled via a request handler within /assets.
        // If assets are stored locally, then asset paths of protected files should be equivalent.
        return Controller::join_links(Director::baseURL(), ASSETS_DIR, $path);
    }
}
