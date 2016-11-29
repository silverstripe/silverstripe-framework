<?php

namespace SilverStripe\Forms\HtmlEditor;

use SilverStripe\View\Parsers\ShortcodeHandler;
use Embed\Adapters\Adapter;
use Embed\Embed;

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
     * @param $arguments
     * @param $content
     * @param $parser
     * @param $shortcode
     * @param array $extra
     *
     * @return string
     */
    public static function handle_shortcode($arguments, $content, $parser, $shortcode, $extra = array())
    {
        $embed = Embed::create($content, $arguments);
        if ($embed && $embed instanceof Adapter) {
            return self::embedForTemplate($embed);
        } else {
            return '<a href="' . $content . '">' . $content . '</a>';
        }
    }

    /**
     * @param Adapter $embed
     *
     * @return string
     */
    public static function embedForTemplate($embed)
    {
        switch ($embed->type) {
            case 'video':
            case 'rich':
                if ($embed->extraClass) {
                    return "<div class='media $embed->extraClass'>$embed->code</div>";
                } else {
                    return "<div class='media'>$embed->code</div>";
                }
                break;
            case 'link':
                return '<a class="' . $embed->extraClass . '" href="' . $embed->origin . '">' . $embed->title . '</a>';
                break;
            case 'photo':
                return "<img src='$embed->url' width='$embed->width' height='$embed->height' class='$embed->extraClass' />";
                break;
        }
        return null;
    }
}
