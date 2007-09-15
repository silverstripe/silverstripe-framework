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
 * Authenticator::registerAuthenticator('OpenIDAuthenticator');
 * </code>
 */



/**
 * Add the security folder to the include path so that the
 * {http://www.openidenabled.com/ PHP OpenID library} finds it files
 */
$path_extra = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'security';
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
Authenticator::registerAuthenticator('MemberAuthenticator');


/**
 * Register the {@link OpenIDAuthenticator OpenID authenticator}
 */
Authenticator::registerAuthenticator('OpenIDAuthenticator');

/**
 * Define a default language different than english
 */
//LocaleAPI::setLocale('ca_AD'); 
?>