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
 */



/**
 * Add Pear (pear.php.net)
 */
$path_extra = PATH_SEPARATOR . realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'pear';
$path = ini_get('include_path');
$path = $path_extra . PATH_SEPARATOR . $path;
ini_set('include_path', $path);


/**
 * Define the random number generator for the OpenID library
 *
 * To set a source of randomness, define {@link Auth_OpenID_RAND_SOURCE}
 * to the path to the randomness source. If your platform does not provide a
 * secure randomness source, the library can operate in pseudorandom mode,
 * but it is then vulnerable to theoretical attacks.
 * If you wish to operate in pseudorandom mode, define
 * {@link Auth_OpenID_RAND_SOURCE} to null.
 * On a Unix-like platform  (including MacOS X), try "/dev/random" and
 * "/dev/urandom".
 */
define('Auth_OpenID_RAND_SOURCE', null);



/**
 * Register the {@link OpenIDAuthenticator OpenID authenticator}
 */
Authenticator::register_authenticator('MemberAuthenticator');


/**
 * Register the {@link OpenIDAuthenticator OpenID authenticator}
 */
Authenticator::register_authenticator('OpenIDAuthenticator');

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