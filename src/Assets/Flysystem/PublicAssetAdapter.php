<?php

namespace SilverStripe\Assets\Flysystem;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;

class PublicAssetAdapter extends AssetAdapter implements PublicAdapter
{

    /**
     * Server specific configuration necessary to block http traffic to a local folder
     *
     * @config
     * @var array Mapping of server configurations to configuration files necessary
     */
    private static $server_configuration = array(
        'apache' => array(
            '.htaccess' => "SilverStripe\\Assets\\Flysystem\\PublicAssetAdapter_HTAccess"
        ),
        'microsoft-iis' => array(
            'web.config' => "SilverStripe\\Assets\\Flysystem\\PublicAssetAdapter_WebConfig"
        )
    );

    protected function findRoot($root)
    {
        if ($root) {
            return parent::findRoot($root);
        }

        // Empty root will set the path to assets
        return ASSETS_PATH;
    }

    /**
     * Provide downloadable url
     *
     * @param string $path
     * @return string|null
     */
    public function getPublicUrl($path)
    {
        $rootPath = realpath(BASE_PATH);
        $filesPath = realpath($this->pathPrefix);

        if (stripos($filesPath, $rootPath) === 0) {
            $dir = substr($filesPath, strlen($rootPath));
            return Controller::join_links(Director::baseURL(), $dir, $path);
        }

        // File outside of webroot can't be used
        return null;
    }
}
