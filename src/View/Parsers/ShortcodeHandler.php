<?php

namespace SilverStripe\View\Parsers;

/**
 * Abstract interface for a class which handles shortcodes
 */
interface ShortcodeHandler
{

    /**
     * Gets the list of shortcodes provided by this handler
     *
     * @return mixed
     */
    public static function get_shortcodes();

    /**
     * Generate content with a shortcode value
     *
     * @param array $arguments Arguments passed to the parser
     * @param string $content Raw shortcode
     * @param ShortcodeParser $parser Parser
     * @param string $shortcode Name of shortcode used to register this handler
     * @param array $extra Extra arguments
     * @return string Result of the handled shortcode
     */
    public static function handle_shortcode($arguments, $content, $parser, $shortcode, $extra = []);
}
