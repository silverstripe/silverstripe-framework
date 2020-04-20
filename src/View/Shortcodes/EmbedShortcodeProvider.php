<?php

namespace SilverStripe\View\Shortcodes;

use Embed\Http\DispatcherInterface;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Embed\Embeddable;
use SilverStripe\View\Embed\EmbedResource;
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

        // See https://github.com/oscarotero/Embed#example-with-all-options for service arguments
        $serviceArguments = [];
        if (!empty($arguments['width'])) {
            $serviceArguments['min_image_width'] = $arguments['width'];
        }
        if (!empty($arguments['height'])) {
            $serviceArguments['min_image_height'] = $arguments['height'];
        }

        /** @var EmbedResource $embed */
        $embed = Injector::inst()->create(Embeddable::class, $serviceURL);
        if (!empty($serviceArguments)) {
            $embed->setOptions(array_merge($serviceArguments, (array) $embed->getOptions()));
        }

        // Allow resolver to be mocked
        $dispatcher = null;
        if (isset($extra['resolver'])) {
            $dispatcher = Injector::inst()->create(
                $extra['resolver']['class'],
                $serviceURL,
                $extra['resolver']['config']
            );
        } elseif (Injector::inst()->has(DispatcherInterface::class)) {
            $dispatcher = Injector::inst()->get(DispatcherInterface::class);
        }

        if ($dispatcher) {
            $embed->setDispatcher($dispatcher);
        }

        // Process embed
        $embed = $embed->getEmbed();

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
            if (in_array($key, $exclude)) {
                continue;
            }

            $attributes->push(ArrayData::create([
                'Name' => $key,
                'Value' => Convert::raw2att($value)
            ]));
        }

        return $attributes;
    }
}
