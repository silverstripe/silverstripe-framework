<?php

namespace SilverStripe\Filesystem\Flysystem;

use League\Flysystem\AwsS3v2\AwsS3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;
use Oneup\FlysystemBundle\Adapter\LocalWithHost;


/**
 * Allows urls for files to be exposed
 *
 * Credit to https://github.com/SmartestEdu/FlysystemPublicUrlPlugin
 *
 * @package framework
 * @subpackage filesystem
 */
class FlysystemUrlPlugin implements PluginInterface {
	
    /**
     * @var Filesystem adapter
     */
    protected $adapter;

    public function setFilesystem(FilesystemInterface $filesystem) {
        $this->adapter = $filesystem->getAdapter();
    }

    public function getMethod() {
        return 'getPublicUrl';
    }

    /**
	 * Generate public url
	 * 
     * @param string $path
     * @return string The full url to the file
     */
    public function handle($path) {
		// Default adaptor
		if($this->adapter instanceof AssetAdapter) {
			return $this->adapter->getPublicUrl($path);
		}

		// Check S3 adaptor
        if (class_exists('League\Flysystem\AwsS3v2\AwsS3Adapter')
			&& $this->adapter instanceof AwsS3Adapter
		) {
            return sprintf(
                'https://s3.amazonaws.com/%s/%s',
                $this->adapter->getBucket(),
                $path
            );
        }

		// Local with host
        if (class_exists('Oneup\FlysystemBundle\Adapter\LocalWithHost')
			&& $this->adapter instanceof LocalWithHost
		) {
            return sprintf(
                '%s/%s/%s',
                $this->adapter->getBasePath(),
                $this->adapter->getWebpath(),
                $path
            );
        }

		// no url available
		return null;
    }
}