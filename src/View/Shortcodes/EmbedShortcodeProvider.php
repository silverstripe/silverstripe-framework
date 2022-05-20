<?php

namespace SilverStripe\View\Shortcodes;

use Embed\Http\NetworkException;
use Embed\Http\RequestException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Embed\Embeddable;
use SilverStripe\View\HTML;
use SilverStripe\View\Parsers\ShortcodeHandler;
use SilverStripe\View\Parsers\ShortcodeParser;
use SilverStripe\Control\Director;
use SilverStripe\Dev\Deprecation;
use SilverStripe\View\Embed\EmbedContainer;

/**
 * Provider for the [embed] shortcode tag used by the embedding service
 * in the HTML Editor field.
 * Provides the html needed for the frontend and the editor field itself.
 */
class EmbedShortcodeProvider implements ShortcodeHandler
{

    /**
     * Gets the list of shortcodes provided by this handler
     *
     * @return mixed
     */
    public static function get_shortcodes()
    {
        return ['embed'];
    }

    /**
     * Embed shortcode parser from Oembed. This is a temporary workaround.
     * Oembed class has been replaced with the Embed external service.
     *
     * @param array $arguments
     * @param string $content
     * @param ShortcodeParser $parser
     * @param string $shortcode
     * @param array $extra
     *
     * @return string
     */
    public static function handle_shortcode($arguments, $content, $parser, $shortcode, $extra = [])
    {
        // Get service URL
        if (!empty($content)) {
            $serviceURL = $content;
        } elseif (!empty($arguments['url'])) {
            $serviceURL = $arguments['url'];
        } else {
            return '';
        }

        $class = $arguments['class'] ?? '';
        $width = $arguments['width'] ?? '';
        $height = $arguments['height'] ?? '';

        // Try to use cached result
        $cache = static::getCache();
        $key = static::deriveCacheKey($serviceURL, $class, $width, $height);
        try {
            if ($cache->has($key)) {
                return $cache->get($key);
            }
        } catch (InvalidArgumentException $e) {
        }

        // See https://github.com/oscarotero/Embed#example-with-all-options for service arguments
        $serviceArguments = [];
        if (!empty($arguments['width'])) {
            $serviceArguments['min_image_width'] = $arguments['width'];
        }
        if (!empty($arguments['height'])) {
            $serviceArguments['min_image_height'] = $arguments['height'];
        }

        /** @var EmbedContainer $embeddable */
        $embeddable = Injector::inst()->create(Embeddable::class, $serviceURL);

        // Only EmbedContainer is currently supported
        if (!($embeddable instanceof EmbedContainer)) {
            throw new \RuntimeException('Emeddable must extend EmbedContainer');
        }

        if (!empty($serviceArguments)) {
            $embeddable->setOptions(array_merge($serviceArguments, (array) $embeddable->getOptions()));
        }

        // Process embed
        try {
            // this will trigger a request/response which will then be cached within $embeddable
            $embeddable->getExtractor();
        } catch (NetworkException | RequestException $e) {
            $message = (Director::isDev())
                ? $e->getMessage()
                : _t(__CLASS__ . '.INVALID_URL', 'There was a problem loading the media.');

            $attr = [
                'class' => 'ss-media-exception embed'
            ];

            $result = HTML::createTag(
                'div',
                $attr,
                HTML::createTag('p', [], $message)
            );
            return $result;
        }

        // Convert embed object into HTML
        $html = static::embeddableToHtml($embeddable, $arguments);
        // Fallback to link to service
        if (!$html) {
            $result = static::linkEmbed($arguments, $serviceURL, $serviceURL);
        }
        // Cache result
        if ($html) {
            try {
                $cache->set($key, $html);
            } catch (InvalidArgumentException $e) {
            }
        }
        return $html;
    }

    public static function embeddableToHtml(Embeddable $embeddable, array $arguments): string
    {
        // Only EmbedContainer is supported
        if (!($embeddable instanceof EmbedContainer)) {
            return '';
        }
        $extractor = $embeddable->getExtractor();
        $type = $embeddable->getType();
        if ($type === 'video' || $type === 'rich') {
            // Attempt to inherit width (but leave height auto)
            if (empty($arguments['width']) && $embeddable->getWidth()) {
                $arguments['width'] = $embeddable->getWidth();
            }
            return static::videoEmbed($arguments, $extractor->code->html);
        }
        if ($type === 'photo') {
            return static::photoEmbed($arguments, (string) $extractor->url);
        }
        if ($type === 'link') {
            return static::linkEmbed($arguments, (string) $extractor->url, $extractor->title);
        }
        return '';
    }

    /**
     * @param Adapter $embed
     * @param array $arguments Additional shortcode params
     * @return string
     * @deprecated 4.11..5.0 Use embeddableToHtml instead
     */
    public static function embedForTemplate($embed, $arguments)
    {
        Deprecation::notice('4.11', 'Use embeddableToHtml() instead');
        switch ($embed->getType()) {
            case 'video':
            case 'rich':
                // Attempt to inherit width (but leave height auto)
                if (empty($arguments['width']) && $embed->getWidth()) {
                    $arguments['width'] = $embed->getWidth();
                }
                return static::videoEmbed($arguments, $embed->getCode());
            case 'link':
                return static::linkEmbed($arguments, $embed->getUrl(), $embed->getTitle());
            case 'photo':
                return static::photoEmbed($arguments, $embed->getUrl());
            default:
                return null;
        }
    }

    /**
     * Build video embed tag
     *
     * @param array $arguments
     * @param string $content Raw HTML content
     * @return string
     */
    protected static function videoEmbed($arguments, $content)
    {
        // Ensure outer div has given width (but leave height auto)
        if (!empty($arguments['width'])) {
            $arguments['style'] = 'width: ' . intval($arguments['width']) . 'px;';
        }

        // override iframe dimension attributes provided by webservice with ones specified in shortcode arguments
        foreach (['width', 'height'] as $attr) {
            if (!($value = $arguments[$attr] ?? false)) {
                continue;
            }
            foreach (['"', "'"] as $quote) {
                $rx = "/(<iframe .*?)$attr=$quote([0-9]+)$quote([^>]+>)/";
                $content = preg_replace($rx ?? '', "$1{$attr}={$quote}{$value}{$quote}$3", $content ?? '');
            }
        }

        $data = [
            'Arguments' => $arguments,
            'Attributes' => static::buildAttributeListFromArguments($arguments, ['width', 'height', 'url', 'caption']),
            'Content' => DBField::create_field('HTMLFragment', $content)
        ];

        return ArrayData::create($data)->renderWith(self::class . '_video')->forTemplate();
    }

    /**
     * Build <a> embed tag
     *
     * @param array $arguments
     * @param string $href
     * @param string $title Default title
     * @return string
     */
    protected static function linkEmbed($arguments, $href, $title)
    {
        $data = [
            'Arguments' => $arguments,
            'Attributes' => static::buildAttributeListFromArguments($arguments, ['width', 'height', 'url', 'caption']),
            'Href' => $href,
            'Title' => !empty($arguments['caption']) ? ($arguments['caption']) : $title
        ];

        return ArrayData::create($data)->renderWith(self::class . '_link')->forTemplate();
    }

    /**
     * Build img embed tag
     *
     * @param array $arguments
     * @param string $src
     * @return string
     */
    protected static function photoEmbed($arguments, $src)
    {
        $data = [
            'Arguments' => $arguments,
            'Attributes' => static::buildAttributeListFromArguments($arguments, ['url']),
            'Src' => $src
        ];

        return ArrayData::create($data)->renderWith(self::class . '_photo')->forTemplate();
    }

    /**
     * Build a list of HTML attributes from embed arguments - used to preserve backward compatibility
     *
     * @deprecated 4.5.0 Use {$Arguments.name} directly in shortcode templates to access argument values
     * @param array $arguments List of embed arguments
     * @param array $exclude List of attribute names to exclude from the resulting list
     * @return ArrayList
     */
    private static function buildAttributeListFromArguments(array $arguments, array $exclude = []): ArrayList
    {
        $attributes = ArrayList::create();
        foreach ($arguments as $key => $value) {
            if (in_array($key, $exclude ?? [])) {
                continue;
            }

            $attributes->push(ArrayData::create([
                'Name' => $key,
                'Value' => Convert::raw2att($value)
            ]));
        }

        return $attributes;
    }

    /**
     * @param ShortcodeParser $parser
     * @param string $content
     */
    public static function flushCachedShortcodes(ShortcodeParser $parser, string $content): void
    {
        $cache = static::getCache();
        $tags = $parser->extractTags($content);
        foreach ($tags as $tag) {
            if (!isset($tag['open']) || $tag['open'] != 'embed') {
                continue;
            }
            $url = $tag['content'] ?? $tag['attrs']['url'] ?? '';
            $class = $tag['attrs']['class'] ?? '';
            $width = $tag['attrs']['width'] ?? '';
            $height = $tag['attrs']['height'] ?? '';
            if (!$url) {
                continue;
            }
            $key = static::deriveCacheKey($url, $class, $width, $height);
            try {
                if (!$cache->has($key)) {
                    continue;
                }
                $cache->delete($key);
            } catch (InvalidArgumentException $e) {
                continue;
            }
        }
    }

    /**
     * @return CacheInterface
     */
    private static function getCache(): CacheInterface
    {
        return Injector::inst()->get(CacheInterface::class . '.EmbedShortcodeProvider');
    }

    /**
     * @param string $url
     * @return string
     */
    private static function deriveCacheKey(string $url, string $class, string $width, string $height): string
    {
        return implode('-', array_filter([
            'embed-shortcode',
            self::cleanKeySegment($url),
            self::cleanKeySegment($class),
            self::cleanKeySegment($width),
            self::cleanKeySegment($height)
        ]));
    }

    /**
     * @param string $str
     * @return string
     */
    private static function cleanKeySegment(string $str): string
    {
        return preg_replace('/[^a-zA-Z0-9\-]/', '', $str ?? '');
    }
}
