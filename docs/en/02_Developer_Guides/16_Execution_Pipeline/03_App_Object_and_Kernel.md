title: App Object and Kernel
summary: Provides bootstrapping and entrypoint to the SilverStripe application

# Kernel

The [Kernel](api:SilverStripe\Core\Kernel) object provides a container for the various manifests, services, and components
which a SilverStripe application must have available in order for requests to be executed.

This can be accessed in user code via Injector


```php

    $kernel = Injector::inst()->get(Kernel::class);
    echo "Current environment: " . $kernel->getEnvironment();
```

## Kernel services

Services accessible from this kernel include:

  * getContainer() -> Current [Injector](api:SilverStripe\Core\Injector\Injector) service
  * getThemeResourceLoader() -> [ThemeResourceLoader](api:SilverStripe\View\ThemeResourceLoader) Service for loading of discovered templates.
    Also used to contain nested theme sets such as the `$default` set for all root module /templates folders.
  * getEnvironment() -> String value for the current environment. One of 'dev', 'live' or 'test'

Several meta-services are also available from Kernel (which are themselves containers for
other core services) but are not commonly accessed directly:

  * getClassLoader() -> [ClassLoader](api:SilverStripe\Core\Manifest\ClassLoader) service which handles the class manifest
  * getModuleLoader() -> [Manifest](api:SilverStripe\Core\Manifest) service which handles module registration
  * getConfigLoader() -> [ConfigLoader](api:SilverStripe\Core\Config\ConfigLoader) Service which assists with nesting of [Config](api:SilverStripe\Core\Config\Config) instances
  * getInjectorLoader() -> [InjectorLoader](api:SilverStripe\Core\Injector\InjectorLoader) Service which assists with nesting of [Injector](api:SilverStripe\Core\Injector\Injector) instances

## Kernel nesting

As with Config and Injector the Kernel can be nested to safely modify global application state,
and subsequently restore state. Unlike those classes, however, there is no `::unnest()`. Instead
you should call `->activate()` on the kernel instance you would like to unnest to.


```php

    $oldKernel = Injector::inst()->get(Kernel::class);
    try {
        // Injector::inst() / Config::inst() are automatically updated to the new kernel
        $newKernel = $oldKernel->nest();
        Config::modify()->set(Director::class, 'alternate_base_url', '/myurl');
    }
    finally {
        // Any changes to config (or other application state) have now been reverted
        $oldKernel->activate();
    }
```

# Application

An application represents a basic execution controller for the top level application entry point.
The role of the application is to:

 - Control bootstrapping of a provided kernel instance
 - Handle errors raised from an application
 - Direct requests to the request handler, and return a valid response

## HTTPApplication

The HTTPApplication provides a specialised application implementation for handling HTTP Requests.
This class provides basic support for HTTP Middleware, such as [ErrorControlChainMiddleware](api:SilverStripe\Core\Startup\ErrorControlChainMiddleware).

`main.php` contains the default application implementation.


```php

    <?php
    
    use SilverStripe\Control\HTTPApplication;
    use SilverStripe\Control\HTTPRequestBuilder;
    use SilverStripe\Core\CoreKernel;
    use SilverStripe\Core\Startup\ErrorControlChainMiddleware;
    
    require __DIR__ . '/src/includes/autoload.php';
    
    // Build request and detect flush
    $request = HTTPRequestBuilder::createFromEnvironment();
    
    // Default application
    $kernel = new CoreKernel(BASE_PATH);
    $app = new HTTPApplication($kernel);
    $app->addMiddleware(new ErrorControlChainMiddleware($app));
    $response = $app->handle($request);
    $response->output();
```

Users can customise their own application by coping the above to a file in `mysite/main.php`, and
updating their `.htaccess` to point to the new file.

    :::
    <IfModule mod_rewrite.c>
    # ...
    RewriteCond %{REQUEST_URI} ^(.*)$
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule .* mysite/main.php?url=%1 [QSA]
    # ...
    </IfModule>


Note: This config must also be duplicated in the below template which provide asset routing:

`silverstripe-assets/templates/SilverStripe/Assets/Flysystem/PublicAssetAdapter_HTAccess.ss`:


```ss

    <IfModule mod_rewrite.c>
        # ...
        # Non existant files passed to requesthandler
        RewriteCond %{REQUEST_URI} ^(.*)$
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule .* ../mysite/main.php?url=%1 [QSA]
    </IfModule>
```

## Custom application actions

If it's necessary to boot a SilverStripe kernel and application, but not do any
request processing, you can use the Application::execute() method to invoke a custom
application entry point.

This may be necessary if using SilverStripe code within the context of a non-SilverStripe
application.

For example, the below will setup a request, session, and current controller,
but will leave the application in a "ready" state without performing any
routing.


```php

    $request = CLIRequestBuilder::createFromEnvironment();
    $kernel = new TestKernel(BASE_PATH);
    $app = new HTTPApplication($kernel);
    $app->execute($request, function (HTTPRequest $request) {
        // Start session and execute
        $request->getSession()->init();
        
        // Set dummy controller
        $controller = Controller::create();
        $controller->setRequest($request);
        $controller->pushCurrent();
        $controller->doInit();
    }, true);
```

 
