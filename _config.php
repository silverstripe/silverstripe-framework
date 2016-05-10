<?php

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
 *
 * @package framework
 * @subpackage core
 */

ShortcodeParser::get('default')
	->register('file_link', array('File', 'handle_shortcode'))
	->register('embed', array('Oembed', 'handle_shortcode'))
	->register('image', array('Image', 'handle_shortcode'));

// Shortcode parser which only regenerates shortcodes
ShortcodeParser::get('regenerator')
	->register('image', array('Image', 'regenerate_shortcode'));

// @todo
//	->register('dbfile_link', array('DBFile', 'handle_shortcode'))

// Zend_Cache temp directory setting
$_ENV['TMPDIR'] = TEMP_FOLDER; // for *nix
$_ENV['TMP'] = TEMP_FOLDER; // for Windows

SS_Cache::set_cache_lifetime('GDBackend_Manipulations', null, 100);

// If you don't want to see deprecation errors for the new APIs, change this to 3.2.0-dev.
Deprecation::notification_version('3.2.0');

// TODO Remove once new ManifestBuilder with submodule support is in place
require_once('admin/_config.php');
