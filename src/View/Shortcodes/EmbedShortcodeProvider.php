<?php

namespace SilverStripe\View\Shortcodes;

use Embed\Http\NetworkException;
use Embed\Http\RequestException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use RuntimeException;
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
use SilverStripe\Core\Config\Configurable;
use SilverStripe\View\Embed\EmbedContainer;

/**
 * Provider for the [embed] shortcode tag used by the embedding service
 * in the HTML Editor field.
 * Provides the html needed for the frontend and the editor field itself.
 */
class EmbedShortcodeProvider implements ShortcodeHandler
{
    use Configurable;

    /**
     * Domains to exclude from sandboxing content in an iframe
     * This will also exclude any subdomains
     * e.g. if 'example.com' is excluded then 'www.example.com' will also be excluded
     * Do not include the protocol in the domain i.e. exclude the leading https://
     */
    private static array $domains_excluded_from_sandboxing = [];

    /**
     * Attributes to add to the iframe when sandboxing
     * Note that the 'src' attribute cannot be set via config
     * If a style attribute is set via config, width and height values will be overriden by
     * any shortcode width and height arguments
     */
    private static array $sandboxed_iframe_attributes = [];

    /**
     * The url of the last extractor used
     * This is used instead of adding a new param to an existing method
     * which would be backwards incompatible
     *
     * @internal
     */
    private static string $extractorUrl = '';

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
        EmbedShortcodeProvider::$extractorUrl = (string) $extractor->url;
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

        if (!empty($arguments['caption'])) {
            $arguments['caption'] = htmlentities($arguments['caption'], ENT_QUOTES, 'UTF-8', false);
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

        $attributes = static::buildAttributeListFromArguments($arguments, ['width', 'height', 'url', 'caption']);
        if (array_key_exists('style', $arguments)) {
            $attributes->push(ArrayData::create([
                'Name' => 'style',
                'Value' => Convert::raw2att($arguments['style']),
            ]));
        }

        $content = EmbedShortcodeProvider::sandboxHtml($content, $arguments);
        $data = [
            'Arguments' => $arguments,
            'Attributes' => $attributes,
            'Content' => DBField::create_field('HTMLFragment', $content)
        ];

        return ArrayData::create($data)->renderWith(EmbedShortcodeProvider::class . '_video')->forTemplate();
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

        return ArrayData::create($data)->renderWith(EmbedShortcodeProvider::class . '_link')->forTemplate();
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

        return ArrayData::create($data)->renderWith(EmbedShortcodeProvider::class . '_photo')->forTemplate();
    }

    /**
     * Build a list of HTML attributes from embed arguments - used to preserve backward compatibility
     *
     * @param array $arguments List of embed arguments
     * @param array $exclude List of attribute names to exclude from the resulting list
     * @return ArrayList<ArrayData>
     */
    private static function buildAttributeListFromArguments(array $arguments, array $exclude = []): ArrayList
    {
        // A whitelist of shortcode attributes which are allowed in the resultant markup.
        // Note that the tinymce plugin restricts attributes on the client-side separately.
        $whitelist = [
            'url',
            'thumbnail',
            'class',
            'width',
            'height',
            'caption'
        ];
        // Clean out any empty arguments and anything not whitelisted
        $arguments = array_filter($arguments, function ($value, $key) use ($whitelist) {
            return in_array($key, $whitelist) && strlen(trim($value ?? ''));
        }, ARRAY_FILTER_USE_BOTH);

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
            EmbedShortcodeProvider::cleanKeySegment($url),
            EmbedShortcodeProvider::cleanKeySegment($class),
            EmbedShortcodeProvider::cleanKeySegment($width),
            EmbedShortcodeProvider::cleanKeySegment($height)
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

    /**
     * Wrap potentially dangerous html embeds in an iframe to sandbox them
     * Potentially dangerous html embeds would could be those that contain <script> or <style> tags
     * or html that contains an on*= attribute
     */
    private static function sandboxHtml(string $html, array $arguments)
    {
        // Do not sandbox if the domain is excluded
        if (EmbedShortcodeProvider::domainIsExcludedFromSandboxing()) {
            return $html;
        }
        // Do not sandbox if the html is already an iframe
        if (preg_match('#^<iframe[^>]*>#', $html) && preg_match('#</iframe\s*>$#', $html)) {
            // Prevent things like <iframe><script>alert(1)</script></iframe>
            // and <iframe></iframe><unsafe stuff here/><iframe></iframe>
            // If there's more than 2 HTML tags then sandbox it
            if (substr_count($html, '<') <= 2) {
                return $html;
            }
        }
        // Sandbox the html in an iframe
        $style = '';
        if (!empty($arguments['width'])) {
            $style .= 'width:' . intval($arguments['width']) . 'px;';
        }
        if (!empty($arguments['height'])) {
            $style .= 'height:' . intval($arguments['height']) . 'px;';
        }
        $attrs = array_merge([
            'frameborder' => '0',
        ], static::config()->get('sandboxed_iframe_attributes'));
        $attrs['src'] = 'data:text/html;charset=utf-8,' . rawurlencode($html);
        if (array_key_exists('style', $attrs)) {
            $attrs['style'] .= ";$style";
            $attrs['style'] = ltrim($attrs['style'], ';');
        } else {
            $attrs['style'] = $style;
        }
        $html = HTML::createTag('iframe', $attrs);
        return $html;
    }

    /**
     * Check if the domain is excluded from sandboxing based on config
     */
    private static function domainIsExcludedFromSandboxing(): bool
    {
        $domain = (string) parse_url(EmbedShortcodeProvider::$extractorUrl, PHP_URL_HOST);
        $config = static::config()->get('domains_excluded_from_sandboxing');
        foreach ($config as $excluded) {
            if (!is_string($excluded)) {
                throw new RuntimeException('domains_excluded_from_sandboxing must be an array of strings');
            }
            $excludedDomain = parse_url($excluded, PHP_URL_HOST);
            if (!$excludedDomain) {
                // Try adding a protocol so that parse_url can parse it
                $excludedDomain = parse_url('http://' . $excluded, PHP_URL_HOST);
            }
            if (!$excludedDomain) {
                throw new RuntimeException('Not a valid domain: ' . $excluded);
            }
            if (str_ends_with($domain, $excludedDomain)) {
                return true;
            }
        }
        return false;
    }
}
