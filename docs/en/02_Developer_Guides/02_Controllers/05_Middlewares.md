---
title: HTTP Middlewares
summary: Create objects for modifying request and response objects across controllers.
---

# HTTP Middlewares

HTTP Middlewares allow you to add code that will run before or after a request has been delegated to the router. These might be used for
authentication, logging, caching, request processing, and many other purposes.

[notice]
Note this interface replaces the Silverstripe CMS 3 interface [RequestFilter](api:SilverStripe\Control\RequestFilter), which still works but is deprecated.
[/notice]

To create a middleware class, implement `SilverStripe\Control\Middleware\HTTPMiddleware` and define the
`process(HTTPRequest $request, callable $delegate)` method. You can do anything you like in this
method, but to continue normal execution, you should call `$response = $delegate($request)`
at some point in this method.

In addition, you should return an `HTTPResponse` object. In normal cases, this should be the
`$response` object returned by `$delegate`, perhaps with some modification. However, sometimes you
will deliberately return a different response, e.g. an error response or a redirection.

**app/src/CustomMiddleware.php**

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

## Application middleware

Some use cases will require a middleware to run before the Silverstripe CMS has been fully bootstrapped (e.g.: Updating 
the HTTPRequest before Silverstripe CMS routes it to a controller). This can be achieved by editing the Silverstripe 
CMS entry point file.

This file will be located in your own codebase at `public/index.php`, or directly in your project root at `index.php` 
if your project doesn't use the public web root. Find the line that instantiate `HTTPApplication`. Call the 
`addMiddleware` method on the `HTTPApplication` instance and pass it an instance of your middleware. This must be done
before the request is handled.

```php
// Default application
$kernel = new CoreKernel(BASE_PATH);
$app = new HTTPApplication($kernel);

$app->addMiddleware(new MyApplicationMiddleware());

$response = $app->handle($request);
$response->output();
```

Beware that by this point, the Silverstripe framework features you normally rely on won't be 
available in your middleware or in `index.php` because they won't have been initialised yet. (e.g.: ORM, Injector, services configured by Injector)

For example, Silverstripe's autoloading functionality won't work in `index.php`. So you might have to take additional
steps to load your custom middleware class.

[Configuring autoloading in your `composer.json` file](https://getcomposer.org/doc/04-schema.md#autoload) is the best
way to achieve this. Remember to call `composer dump-autoload` to regenerate your autoloader. 

Alternatively, you can manually include the file containing your custom middleware with a `require` call. e.g.: 
`require __DIR__.'/../app/src/MyApplicationMiddleware.php';`

## API Documentation

* [Built-in Middleware](/developer_guides/controllers/builtin_middlewares)
* [HTTPMiddleware](api:SilverStripe\Control\Middleware\HTTPMiddleware)
