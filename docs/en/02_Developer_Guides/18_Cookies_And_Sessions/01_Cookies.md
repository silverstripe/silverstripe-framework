title: Cookies
summary: A set of static methods for manipulating PHP cookies.

# Cookies

Cookies are a mechanism for storing data in the remote browser and thus tracking or identifying return users. 
SilverStripe uses cookies for remembering users preferences. Application code can modify a users cookies through
the [api:Cookie] class. This class mostly follows the PHP API.

## set

Sets the value of cookie with configuration.

	:::php
	Cookie::set($name, $value, $expiry = 90, $path = null, $domain = null, $secure = false, $httpOnly = false);

	// Cookie::set('MyApplicationPreference', 'Yes');

## get

Returns the value of cookie.

	:::php
	Cookie::get($name);

	// Cookie::get('MyApplicationPreference');
	// returns 'Yes'

## force_expiry

Clears a given cookie.

	:::php
	Cookie::force_expiry($name, $path = null, $domain = null);

	// Cookie::force_expiry('MyApplicationPreference')

## API Documentation

* [api:Cookie]