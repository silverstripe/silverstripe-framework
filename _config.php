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

/**
 * Add pear parser to include path
 */
$path = Director::baseFolder().'/sapphire/parsers/';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

/**
 * Register the {@link OpenIDAuthenticator OpenID authenticator}
 */
Authenticator::register_authenticator('MemberAuthenticator');
Authenticator::set_default_authenticator('MemberAuthenticator');

/**
 * Define a default language different than english
 */
//i18n::set_locale('ca_AD'); 


/**
 * The root directory of TinyMCE
 */
define('MCE_ROOT', 'jsparty/tiny_mce2/');

/**
 * Should passwords be encrypted (TRUE) or stored in clear text (FALSE)?
 */
Security::encrypt_passwords(true);


/**
 * Which algorithm should be used to encrypt? Should a salt be used to
 * increase the security?
 *
 * You can get a list of supported algorithms by calling
 * {@link Security::get_encryption_algorithms()}
 */
Security::set_password_encryption_algorithm('sha1', true);

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