<?php

namespace SilverStripe\View\Embed;

use Embed\Extractor;
use Embed\Embed;
use Psr\Http\Message\UriInterface;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Manifest\ModuleResourceLoader;

/**
 * This class acts as a wrapper around the third party requirement embed/embed v4
 */
class EmbedContainer implements Embeddable
{
    use Injectable;

    private static $dependencies = [
        'embed' => '%$' . Embed::class,
    ];

    public Embed $embed;

    private ?Extractor $extractor = null;

    private string $url;

    private array $options = [];

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        $code = $this->getExtractor()->code;
        return $code ? ($code->width ?: 100) : 100;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        $code = $this->getExtractor()->code;
        return $code ? ($code->height ?: 100) : 100;
    }

    /**
     * @return string
     */
    public function getPreviewURL()
    {
        $extractor = $this->getExtractor();

        // Use thumbnail url
        if ($extractor->image) {
            return (string) $extractor->image;
        }

        // Default media
        return ModuleResourceLoader::resourceURL(
            'silverstripe/asset-admin:client/dist/images/icon_file.png'
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        $extractor = $this->getExtractor();
        if ($extractor->title) {
            return $extractor->title;
        }
        if ($extractor->url instanceof UriInterface) {
            return basename($extractor->url->getPath() ?? '');
        }
        return '';
    }

    /**
     * @return string
     */
    public function getType()
    {
        $html = $this->getExtractor()->code->html ?? '';
        if (strpos($html ?? '', '<video') !== false) {
            return 'video';
        }
        if (strpos($html ?? '', '<audio') !== false) {
            return 'audio';
        }
        foreach (['iframe', 'blockquote', 'pre', 'script', 'style'] as $richTag) {
            if (strpos($html ?? '', "<{$richTag}") !== false) {
                return 'rich';
            }
        }
        if (strpos($html ?? '', '<img') !== false) {
            return 'photo';
        }
        return 'link';
    }

    /**
     * @return bool
     */
    public function validate()
    {
        return !empty($this->getExtractor()->code->html ?? '');
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): EmbedContainer
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Calling this method will trigger the HTTP call(s) to the remote url
     */
    public function getExtractor(): Extractor
    {
        if (!$this->extractor) {
            $this->extractor = $this->embed->get($this->url);
        }
        return $this->extractor;
    }
}
