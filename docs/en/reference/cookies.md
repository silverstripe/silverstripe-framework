# Cookies

## Accessing and Manipulating Cookies

Cookies can be set/get/expired using the `Cookie` class and its static methods

setting:

```php
Cookie::set('CookieName', 'CookieValue');
```

getting:

```php
Cookie::get('CookieName'); //returns null if not set or the value if set
```

expiring / removing / clearing:

```php
Cookie::force_expiry('CookieName');
```


## The `Cookie_Backend`

The `Cookie` class manipulates and sets cookies using a `Cookie_Backend`. The backend is in charge of the logic
that fetches, sets and expires cookies. By default we use a the `CookieJar` backend which uses PHP's
(`setcookie`)[http://www.php.net/manual/en/function.setcookie.php] function.

The `CookieJar` keeps track of cookies that have been set by the current process as well as those that were recieved
from the browser.

By default the `Cookie` class will load the `$_COOKIE` superglobal into the `Cookie_Backend`. If you want to change
the initial state of the `Cookie_Backend` you can load your own backend into the `Cookie::$inst`.

eg:

```php
$myCookies = array(
	'cookie1' => 'value1',
);

$newBackend = new CookieJar($myCookies);

Cookie::set_inst($myCookies);

Cookie::get('cookie1'); //will return 'value1'
```


### Using your own Cookie_Backend

If you need to implement your own Cookie_Backend you can use the injector system to force a different class to be used.

example:

```yml
Injector:
  CookieJar:
    class: MyCookieJar
```

To be a valid backend your class must implement the `Cookie_Backend` interface.

## Advanced Usage

### Sent vs Received Cookies

Sometimes it's useful  to be able to tell if a cookie was set by the process (thus will be sent to the browser) or if it
came from the browser as part of the request.

Using the `Cookie_Backend` we can do this like such:

```php

Cookie::set('CookieName', 'CookieVal');

Cookie::get('CookieName'); //gets the cookie as we set it

//will return the cookie as it was when it was sent in the request
Cookie::get_inst()->get('CookieName', false);
```

### Accessing all the cookies at once

One can also access all of the cookies in one go using the `Cookie_Backend`

```php

Cookie::get_inst()->getAll(); //returns all the cookies including ones set during the current process

Cookie::get_inst()->getAll(false); //returns all the cookies in the request

```
