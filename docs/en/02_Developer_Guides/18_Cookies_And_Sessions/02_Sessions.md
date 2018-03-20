title: Sessions
summary: A set of static methods for manipulating PHP sessions.

# Sessions

Session support in PHP consists of a way to preserve certain data across subsequent accesses such as logged in user
information and security tokens.

In order to support things like testing, the session is associated with a particular Controller.  In normal usage,
this is loaded from and saved to the regular PHP session, but for things like static-page-generation and
unit-testing, you can create multiple Controllers, each with their own session.

## Getting the session instance

If you're in a controller, the `Session` object will be bound to the `HTTPRequest` for your controller.

```php
use SilverStripe\Control\Controller;

class MyController extends Controller
{
    public function MySession()
    {
        return $this->getRequest()->getSession();
    }
}
```

Otherwise, if you're not in a controller, get the request as a service.

```php
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;

$request = Injector::inst()->get(HTTPRequest::class);
$session = $request->getSession();
```

## set


```php
$session->set('MyValue', 6);
```

Saves the value of to session data. You can also save arrays or serialized objects in session (but note there may be 
size restrictions as to how much you can save).


```php
// saves an array
$session->set('MyArrayOfValues', ['1','2','3']);

// saves an object (you'll have to unserialize it back)
$object = new Object();
$session->set('MyObject', serialize($object));

```

 
## get

Once you have saved a value to the Session you can access it by using the `get` function. Like the `set` function you 
can use this anywhere in your PHP files.


```php
echo $session->get('MyValue'); 
// returns 6

$data = $session->get('MyArrayOfValues'); 
// $data = array(1,2,3)

$object = unserialize($session->get('MyObject', $object)); 
// $object = Object()

```

## getAll

You can also get all the values in the session at once. This is useful for debugging.
```php
$session->getAll();
// returns an array of all the session values.
```

## clear

Once you have accessed a value from the Session it doesn't automatically wipe the value from the Session, you have
to specifically remove it.
```php
$session->clear('MyValue');
```

Or you can clear every single value in the session at once. Note SilverStripe stores some of its own session data
including form and page comment information. None of this is vital but `clear_all` will clear everything.
```php
$session->clearAll();
```

## Configuration

Sessions are backed by `symfony/http-foundation` internally, which is easily customisable and includes support for a number of different “save handlers” (which persist session data server-side between requests).

The default configuration (backed by PHP’s native session storage, usually file-based) can be customised by adjusting the default `Injector` service definition:

```yml
---
Name: mysitesession
After: '#coresession'
---
SilverStripe\Core\Injector\Injector:
  SymfonyNativeSessionStorage:
    class: Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage
    constructor:
      config:
        cookie_lifetime: 1200
        cookie_secure: true
        name: 'MYSESSIONID'
```

A full list of available configuration options is available here: [http://php.net/session.configuration](http://php.net/session.configuration). Note that the `session.` prefix is omitted for convenience.

You can also use a different save handler, for example to use Memcached as a save handler:


```yml
---
Name: mysitesession
After: '#coresession'
---
SilverStripe\Core\Injector\Injector:
  SymfonyNativeSessionStorage:
    class: Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage
    constructor:
      config:
        cookie_lifetime: 60
      handler: %$MemcachedSessionHandler
  MemcachedSessionHandler:
    class: Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler
    constructor:
      memcached: %$MemcachedClient
  MemcachedClient:
    class: 'Memcached'
    calls:
      - [ addServer, [ 'localhost', 11211 ] ]
```

## API Documentation

* [Session](api:SilverStripe\Control\Session)
