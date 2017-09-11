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

// Set to 5.0.0 to show APIs marked for removal at that version
Deprecation::notification_version('4.0.0');
