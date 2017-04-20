<?php

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Forms\HtmlEditor\EmbedShortcodeProvider;
use SilverStripe\View\Parsers\ShortcodeParser;

/**
 * Framework configuration file
 *
 * Here you can make different settings for the Framework module (the core
 * module).
 *
 * For example you can register the authentication methods you wish to use
 * on your site, e.g. to register the OpenID authentication method type
 *
 * <code>
 * Authenticator::register_authenticator('OpenIDAuthenticator');
 * </code>
 */

ShortcodeParser::get('default')
	->register('file_link', array(File::class, 'handle_shortcode'))
	->register('embed', array(EmbedShortcodeProvider::class, 'handle_shortcode'))
	->register('image', array(Image::class, 'handle_shortcode'));

// Shortcode parser which only regenerates shortcodes
ShortcodeParser::get('regenerator')
	->register('image', array(Image::class, 'regenerate_shortcode'));

// If you don't want to see deprecation errors for the new APIs, change this to 3.2.0-dev.
Deprecation::notification_version('3.2.0');
