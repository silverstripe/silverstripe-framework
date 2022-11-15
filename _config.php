<?php

use SilverStripe\View\Shortcodes\EmbedShortcodeProvider;
use SilverStripe\View\Parsers\ShortcodeParser;

/**
 * Framework configuration file
 *
 * Here you can make different settings for the Framework module (the core
 * module).
 *
 */

ShortcodeParser::get('default')
    ->register('embed', [EmbedShortcodeProvider::class, 'handle_shortcode']);
