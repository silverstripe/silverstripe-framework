title: HTTP Middlewares
summary: Create objects for modifying request and response objects across controllers.

# HTTP Middlewares

HTTP Middlewares allow you to put code that will run before or after. These might be used for
authentication, logging, caching, request processing, and many other purposes. Note this interface
replaces the SilverStripe 3 interface [RequestFilter](api:SilverStripe\Control\RequestFilter), which still works but is deprecated.

To create a middleware class, implement `SilverStripe\Control\Middleware\HTTPMiddleware` and define the
`process(HTTPRequest $request, callable $delegate)` method. You can do anything you like in this
method, but to continue normal execution, you should call `$response = $delegate($request)`
at some point in this method.

In addition, you should return an `HTTPResponse` object. In normal cases, this should be the
`$response` object returned by `$delegate`, perhaps with some modification. However, sometimes you
will deliberately return a different response, e.g. an error response or a redirection.

**app/code/CustomMiddleware.php**

```php
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Control\HTTPRequest;

class CustomMiddleware implements HTTPMiddleware
{
    public $Secret = 'SECRET';

    public function process(HTTPRequest $request, callable $delegate)
    {
        // You can break execution by not calling $delegate.
        if ($request->getHeader('X-Special-Header') !== $this->Secret) {
            return new HTTPResponse('You missed the special header', 400);
        }

        // You can modify the request before 
        // For example, this might force JSON responses
        $request->addHeader('Accept', 'application/json');

        // If you want normal behaviour to occur, make sure you call $delegate($request)
        $response = $delegate($request);

        // You can modify the response after it has been generated
        $response->addHeader('X-Middleware-Applied', 'CustomMiddleware');

        // Don't forget to the return the response!
        return $response;
    }
}
```

Once you have created your middleware class, you must attach it to the `Director` config to make
use of it.

## Global middleware

By adding the service or class name to the `Director.Middlewares` property via injector,
array, a middleware will be executed on every request:

**app/_config/app.yml**


```yaml
---
Name: myrequestprocessors
After:
  - requestprocessors
---
SilverStripe\Core\Injector\Injector:
  SilverStripe\Control\Director:
    properties:
      Middlewares:
        CustomMiddleware: %$CustomMiddleware
```


Because these are service names, you can configure properties into a custom service if you would
like:

**app/_config/app.yml**

```yaml
SilverStripe\Core\Injector\Injector:
  SilverStripe\Control\Director:
    properties:
      Middlewares:
        CustomMiddleware: %$ConfiguredMiddleware
  ConfiguredMiddleware:
   class: 'CustomMiddleware'
   properties:
     Secret: "DIFFERENT-ONE"
```

## Route-specific middleware

Alternatively, you can apply middlewares to a specific route. These will be processed after the
global middlewares. You can do this by using the `RequestHandlerMiddlewareAdapter` class
as a replacement for your controller, and register it as a service with a `Middlewares`
property. The controller which does the work should be registered under the
`RequestHandler` property.

**app/_config/app.yml**

```yaml
SilverStripe\Core\Injector\Injector:
  SpecialRouteMiddleware:
    class: SilverStripe\Control\Middleware\RequestHandlerMiddlewareAdapter
    properties:
      RequestHandler: %$MyController
      Middlewares:
        - %$CustomMiddleware
        - %$AnotherMiddleware
SilverStripe\Control\Director:
  rules:
    special\section:
      Controller: %$SpecialRouteMiddleware
```

## API Documentation

* [HTTPMiddleware](api:SilverStripe\Control\Middleware\HTTPMiddleware)
