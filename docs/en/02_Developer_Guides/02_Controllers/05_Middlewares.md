title: HTTP Middlewares
summary: Create objects for modifying request and response objects across controllers.

# HTTP Middlewares

HTTP Middlewares allow you to put code that will run before or after. These might be used for
authentication, logging, caching, request processing, and many other purposes. Note this interface
replaces the SilverStripe 3 interface, [api:RequestFilter], which still works but is deprecated.

To create a middleware class, implement `SilverStripe\Control\HTTPMiddleware` and define the
`process(HTTPRequest $request, callbale $delegate)` method. You can do anything you like in this
method, but to continue normal execution, you should call `$response = $delegate($request)`
at some point in this method.

In addition, you should return an HTTPResponse object. In normal cases, this should be the
$response object returned by `$delegate`, perhaps with some modification. However, sometimes you
will deliberately return a different response, e.g. an error response or a redirection.

**mysite/code/CustomMiddleware.php**

	:::php
	<?php

    use SilverStripe\Control\HTTPMiddleware

	class CustomMiddleware implements HTTPMiddleware {

        public $Secret = 'SECRET';

		public function process(HTTPRequest $request, callable $delegate) {

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
            $response->addHeader('X-Middleware-Applied', 'CustomMiddleware')

            // Don't forget to the return the response!
            return $response;
		}
	}

Once you have created your middleware class, you must attach it to the Director config to make
use of it.

## Global middleware

By adding the service or class name to the SilverStripe\Control\Director.middlewares array, a
middleware will be executed on every request:

**mysite/_config/app.yml**


	:::yml
    ---
    Name: myrequestprocessors
    After:
      - requestprocessors
    ---
    SilverStripe\Core\Injector\Injector:
      SilverStripe\Control\HTTPMiddleware.director:
        class: SilverStripe\Control\CompositeHTTPMiddleware
        properties:
          Middlewares:
            - %$CustomMiddleware


Because these are service names, you can configure properties into a custom service if you would
like:

**mysite/_config/app.yml**

    :::yml
    SilverStripe\Core\Injector\Injector:
      SilverStripe\Control\HTTPMiddleware.director:
        class: SilverStripe\Control\CompositeHTTPMiddleware
        properties:
          Middlewares:
           - %$ConfiguredMiddleware
    SilverStripe\Core\Injector\Injector:
      ConfiguredMiddleware:
       class: 'CustomMiddleware'
       properties:
         Secret: "DIFFERENT-ONE"

## Route-specific middleware

Alternatively, you can apply middlewares to a specific route. These will be processed after the
global middlewares. You do this by specifying the "Middleware" property of the route rule:

**mysite/_config/app.yml**

    :::yml
    SilverStripe\Control\Director:
      rules:
        special\section:
          Controller: SpecialSectionController
          Middleware: 'CustomMiddleware'


If you need to apply multiple middleware you can use the `CompositeHTTPMiddleware`
wrapper.


    :::yml
    SilverStripe\Core\Injector\Injector:
      SpecialRouteMiddleware:
        class: SilverStripe\Control\CompositeHTTPMiddleware
        properties
          Middlewares:
            - %$CustomMiddleware
            - %$AnotherMiddleware
    SilverStripe\Control\Director:
      rules:
        special\section:
          Controller: SpecialSectionController
          Middleware: 'CustomMiddleware'


## API Documentation

* [api:SilverStripe\Control\HTTPMiddleware]

