<?php

/**
 * Sapphire configuration file
 *
 * Here you can make different settings for the Sapphire module (the core
 * module).
 *
 * For example you can register the authentication methods you wish to use
 * on your site, e.g. to register the OpenID authentication method type
 *
 * <code>
 * Authenticator::register_authenticator('OpenIDAuthenticator');
 * </code>
 *
 * @package sapphire
 * @subpackage core
 */

// Default director
Director::addRules(10, array(
	'Security//$Action/$ID/$OtherID' => 'Security',
	//'Security/$Action/$ID' => 'Security',
	'db//$Action' => 'DatabaseAdmin',
	'$Controller//$Action/$ID/$OtherID' => '*',
	'' => 'RootURLController',
	'api/v1/live' => 'VersionedRestfulServer',
	'api/v1' => 'RestfulServer',
	'soap/v1' => 'SOAPModelAccess',
	'dev' => 'DevelopmentAdmin',
	'interactive' => 'SapphireREPL',
));

Director::addRules(1, array(
	'$URLSegment//$Action/$ID/$OtherID' => 'ModelAsController',
));

/**
 * Register the default internal shortcodes.
 */
ShortcodeParser::get('default')->register('sitetree_link', array('SiteTree', 'link_shortcode_handler'));

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
define('MCE_ROOT', 'sapphire/thirdparty/tinymce/');

/**
 * The secret key that needs to be sent along with pings to /Email_BounceHandler
 *
 * Change this to something different for increase security (you can
 * override it in mysite/_config.php to ease upgrades).
 * For more information see:
 * {@link http://doc.silverstripe.org/doku.php?id=email_bouncehandler}
 */
if(!defined('EMAIL_BOUNCEHANDLER_KEY')) {
	define('EMAIL_BOUNCEHANDLER_KEY', '1aaaf8fb60ea253dbf6efa71baaacbb3');
}

PasswordEncryptor::register('none', 'PasswordEncryptor_None');
PasswordEncryptor::register('md5', 'PasswordEncryptor_LegacyPHPHash("md5")');
PasswordEncryptor::register('sha1','PasswordEncryptor_LegacyPHPHash("sha1")');
PasswordEncryptor::register('md5_v2.4', 'PasswordEncryptor_PHPHash("md5")');
PasswordEncryptor::register('sha1_v2.4','PasswordEncryptor_PHPHash("sha1")');

// Zend_Cache temp directory setting
$_ENV['TMPDIR'] = TEMP_FOLDER; // for *nix
$_ENV['TMP'] = TEMP_FOLDER; // for Windows
