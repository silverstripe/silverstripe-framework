<?php

namespace SilverStripe\View\Shortcodes;

use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\HTML;
use SilverStripe\View\Parsers\ShortcodeHandler;
use Embed\Adapters\Adapter;
use Embed\Embed;
use SilverStripe\View\Parsers\ShortcodeParser;

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
        return array('embed');
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
    public static function handle_shortcode($arguments, $content, $parser, $shortcode, $extra = array())
    {
        // Get service URL
        if (!empty($content)) {
            $serviceURL = $content;
        } elseif (!empty($arguments['url'])) {
            $serviceURL = $arguments['url'];
        } else {
            return '';
        }

        // See https://github.com/oscarotero/Embed#example-with-all-options for service arguments
        $serviceArguments = [];
        if (!empty($arguments['width'])) {
            $serviceArguments['min_image_width'] = $arguments['width'];
        }
        if (!empty($arguments['height'])) {
            $serviceArguments['min_image_height'] = $arguments['height'];
        }

        // Allow resolver to be mocked
        $dispatcher = null;
        if (isset($extra['resolver'])) {
            $dispatcher = Injector::inst()->create(
                $extra['resolver']['class'],
                $serviceURL,
                $extra['resolver']['config']
            );
        }

        // Process embed
        $embed = Embed::create($serviceURL, $serviceArguments, $dispatcher);

        // Convert embed object into HTML
        if ($embed && $embed instanceof Adapter) {
            $result = static::embedForTemplate($embed, $arguments);
            if ($result) {
                return $result;
            }
        }

        // Fallback to link to service
        return static::linkEmbed($arguments, $serviceURL, $serviceURL);
    }

    /**
     * @param Adapter $embed
     * @param array $arguments Additional shortcode params
     * @return string
     */
    public static function embedForTemplate($embed, $arguments)
    {
        switch ($embed->getType()) {
            case 'video':
            case 'rich':
                // Attempt to inherit width (but leave height auto)
                if (empty($arguments['width']) && $embed->getWidth()) {
                    $arguments['width'] = $embed->getWidth();
                }
                return self::videoEmbed($arguments, $embed->getCode());
            case 'link':
                return self::linkEmbed($arguments, $embed->getUrl(), $embed->getTitle());
            case 'photo':
                return self::photoEmbed($arguments, $embed->getUrl());
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

        // Convert caption to <p>
        if (!empty($arguments['caption'])) {
            $xmlCaption = Convert::raw2xml($arguments['caption']);
            $content .= "\n<p class=\"caption\">{$xmlCaption}</p>";
        }
        unset($arguments['width']);
        unset($arguments['height']);
        unset($arguments['url']);
        unset($arguments['caption']);
        return HTML::createTag('div', $arguments, $content);
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
        $title = !empty($arguments['caption']) ? ($arguments['caption']) : $title;
        unset($arguments['caption']);
        unset($arguments['width']);
        unset($arguments['height']);
        unset($arguments['url']);
        $arguments['href'] = $href;
        return HTML::createTag('a', $arguments, Convert::raw2xml($title));
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
        $arguments['src'] = $src;
        unset($arguments['url']);
        return HTML::createTag('img', $arguments);
    }
}
