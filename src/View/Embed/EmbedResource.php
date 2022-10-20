<?php

namespace SilverStripe\View\Embed;

use Embed\Adapters\Adapter;
use Embed\Embed;
use Embed\Http\DispatcherInterface;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Dev\Deprecation;

/**
 * This is a deprecated class that was compatible with embed/embed v3
 * This has been replaced with EmbedContainer which is embed/embed v4 compatible
 *
 * Encapsulation of an embed tag, linking to an external media source.
 *
 * @see Embed
 * @deprecated 4.11.0 Use EmbedContainer instead
 */
class EmbedResource implements Embeddable
{
    /**
     * Embed result
     *
     * @var Adapter
     */
    protected $embed;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var DispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param string $url
     */
    public function __construct($url)
    {
        Deprecation::notice('4.11.0', 'Use EmbedContainer instead', Deprecation::SCOPE_CLASS);
        $this->url = $url;
    }

    public function getWidth()
    {
        return $this->getEmbed()->getWidth() ?: 100;
    }

    public function getHeight()
    {
        return $this->getEmbed()->getHeight() ?: 100;
    }

    public function getPreviewURL()
    {
        // Use thumbnail url
        if ($this->getEmbed()->image) {
            return $this->getEmbed()->image;
        }

        // Use direct image type
        if ($this->getType() === 'photo' && !empty($this->getEmbed()->url)) {
            return $this->getEmbed()->url;
        }

        // Default media
        return ModuleResourceLoader::resourceURL(
            'silverstripe/asset-admin:client/dist/images/icon_file.png'
        );
    }

    /**
     * Get human readable name for this resource
     *
     * @return string
     */
    public function getName()
    {
        if ($this->getEmbed()->title) {
            return $this->getEmbed()->title;
        }

        return preg_replace('/\?.*/', '', basename($this->getEmbed()->getUrl() ?? ''));
    }

    public function getType()
    {
        return $this->getEmbed()->type;
    }

    public function validate()
    {
        return !empty($this->getEmbed()->code);
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param DispatcherInterface $dispatcher
     * @return $this
     */
    public function setDispatcher(DispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    /**
     * @return DispatcherInterface
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Returns a bootstrapped Embed object
     *
     * @return Adapter
     */
    public function getEmbed()
    {
        if (!$this->embed) {
            $this->embed = Embed::create($this->url, $this->getOptions(), $this->getDispatcher());
        }
        return $this->embed;
    }
}
