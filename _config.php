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

/**
 * PHP 5.2 introduced a conflict with the Datetime field type, which was renamed to SSDatetime. This was later renamed
 * to SS_Datetime to be consistent with other namespaced classes.
 *
 * Overload both of these to support legacy code.
 */
Object::useCustomClass('SSDatetime', 'SS_Datetime', true);
Object::useCustomClass('Datetime',   'SS_Datetime', true);

/**
 * The root directory of TinyMCE
 */
define('MCE_ROOT', FRAMEWORK_DIR . '/thirdparty/tinymce/');

ShortcodeParser::get('default')->register('file_link', array('File', 'link_shortcode_handler'));
ShortcodeParser::get('default')->register('embed', array('Oembed', 'handle_shortcode'));

// Zend_Cache temp directory setting
$_ENV['TMPDIR'] = TEMP_FOLDER; // for *nix
$_ENV['TMP'] = TEMP_FOLDER; // for Windows

SS_Cache::set_cache_lifetime('GDBackend_Manipulations', null, 100);

// If you don't want to see deprecation errors for the new APIs, change this to 3.2.0-dev.
Deprecation::notification_version('3.2.0');

// TODO Remove once new ManifestBuilder with submodule support is in place
require_once('admin/_config.php');
