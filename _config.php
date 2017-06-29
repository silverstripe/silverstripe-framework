<?php

use SilverStripe\Dev\Deprecation;
use SilverStripe\View\Shortcodes\EmbedShortcodeProvider;
use SilverStripe\Assets\Shortcodes\FileShortcodeProvider;
use SilverStripe\Assets\Shortcodes\ImageShortcodeProvider;
use SilverStripe\View\Parsers\ShortcodeParser;

/**
 * Framework configuration file
 *
 * Here you can make different settings for the Framework module (the core
 * module).
 *
 */

ShortcodeParser::get('default')
	->register('file_link', [FileShortcodeProvider::class, 'handle_shortcode'])
	->register('embed', [EmbedShortcodeProvider::class, 'handle_shortcode'])
	->register('image', [ImageShortcodeProvider::class, 'handle_shortcode']);

// Shortcode parser which only regenerates shortcodes
ShortcodeParser::get('regenerator')
	->register('image', [ImageShortcodeProvider::class, 'regenerate_shortcode']);

// If you don't want to see deprecation errors for the new APIs, change this to 3.2.0-dev.
Deprecation::notification_version('3.2.0');
