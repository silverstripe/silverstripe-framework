<?php

use SilverStripe\Dev\Deprecation;
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

// If you don't want to see deprecation errors for the new APIs, change this to 3.2.0-dev.
Deprecation::notification_version('3.2.0');
