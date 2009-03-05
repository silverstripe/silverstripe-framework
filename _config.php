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
	'images' => 'Image_Uploader',
	'' => 'RootURLController',
	'api/v1/live' => 'VersionedRestfulServer',
	'api/v1' => 'RestfulServer',
	'soap/v1' => 'SOAPModelAccess',
	'dev' => 'DevelopmentAdmin'
));

Director::addRules(1, array(
	'$URLSegment//$Action/$ID/$OtherID' => 'ModelAsController',
));

/**
 * PHP 5.2 has a namespace conflict with our datetime class,
 * for legacy support, we use this overload method.
 * // ENFORCE STRONG_CREATE
 */
Object::useCustomClass('Datetime','SSDatetime',true);

/**
 * Add pear parser to include path
 */
$path = Director::baseFolder().'/sapphire/parsers/';
set_include_path(str_replace('.' . PATH_SEPARATOR, '.' . PATH_SEPARATOR . $path . PATH_SEPARATOR, get_include_path())); 

/**
 * Define a default language different than english
 */
//i18n::set_locale('ca_AD'); 


/**
 * The root directory of TinyMCE
 */
define('MCE_ROOT', 'jsparty/tiny_mce2/');

/**
 * The secret key that needs to be sent along with pings to /Email_BounceHandler
 *
 * Change this to something different for increase security (you can
 * override it in mysite/_config.php to ease upgrades).
 * For more information see:
 * {@link http://doc.silverstripe.com/doku.php?id=email_bouncehandler}
 */
define('EMAIL_BOUNCEHANDLER_KEY', '1aaaf8fb60ea253dbf6efa71baaacbb3');

?>
