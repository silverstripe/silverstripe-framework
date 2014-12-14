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


## Cookie_Backend

The [api:Cookie] class manipulates and sets cookies using a [api:Cookie_Backend]. The backend is in charge of the logic
that fetches, sets and expires cookies. By default we use a [api:CookieJar] backend which uses PHP's 
[setcookie](http://www.php.net/manual/en/function.setcookie.php) function.

The [api:CookieJar] keeps track of cookies that have been set by the current process as well as those that were received
from the browser.

	:::php
	$myCookies = array(
		'cookie1' => 'value1',
	);

	$newBackend = new CookieJar($myCookies);

	Injector::inst()->registerService($newBackend, 'Cookie_Backend');

	Cookie::get('cookie1');

## Resetting the Cookie_Backend state

Assuming that your application hasn't messed around with the `$_COOKIE` superglobal, you can reset the state of your
`Cookie_Backend` by simply unregistering the `CookieJar` service with `Injector`. Next time you access `Cookie` it'll
create a new service for you using the `$_COOKIE` superglobal.

	:::php
	Injector::inst()->unregisterNamedObject('Cookie_Backend');

	Cookie::get('cookiename'); // will return $_COOKIE['cookiename'] if set


Alternatively, if you know that the superglobal has been changed (or you aren't sure it hasn't) you can attempt to use
the current `CookieJar` service to tell you what it was like when it was registered.

	:::php
	//store the cookies that were loaded into the `CookieJar`
	$recievedCookie = Cookie::get_inst()->getAll(false);

	//set a new `CookieJar`
	Injector::inst()->registerService(new CookieJar($recievedCookie), 'CookieJar');


### Using your own Cookie_Backend

If you need to implement your own Cookie_Backend you can use the injector system to force a different class to be used.

	:::yml
	---
	Name: mycookie
	After: '#cookie'
	---
	Injector:
	  Cookie_Backend:
		class: MyCookieJar

To be a valid backend your class must implement the [api:Cookie_Backend] interface.


## API Documentation

* [api:Cookie]
* [api:CookieJar]
* [api:CookieBackend]