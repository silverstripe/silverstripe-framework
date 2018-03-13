title: Logging and Error Handling
summary: Trap, fire and report diagnostic logs, user exceptions, warnings and errors.

# Logging and Error Handling

SilverStripe uses Monolog for both error handling and logging. It comes with two default configurations: one for
development environments, and another for test or live environments. On development environments, SilverStripe will
deal harshly with any warnings or errors: a full call-stack is shown and execution stops for anything, giving you early
warning of a potential issue to handle.

## Raising errors and logging diagnostic information.

For informational and debug logs, you can use the Logger directly. The Logger is a PSR-3 compatible LoggerInterface and
can be accessed via the `Injector`:

```php
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;
use SilverStripe\Security\Security;

Injector::inst()->get(LoggerInterface::class)->info('User has logged in: ID #' . Security::getCurrentUser()->ID);
Injector::inst()->get(LoggerInterface::class)->debug('Query executed: ' . $sql);
```

Although you can raise more important levels of alerts in this way, we recommend using PHP's native error systems for
these instead.

For notice-level and warning-level issues, you should use [user_error](http://www.php.net/user_error) to throw errors
where appropriate. These will not halt execution but will send a message to the PHP error log.

```php
public function delete()
{
    if ($this->alreadyDelete) {
        user_error("Delete called on already deleted object", E_USER_NOTICE);
        return;
    }
    // ...
}
    
public function getRelatedObject()
{
    if (!$this->RelatedObjectID) {
        user_error("Can't find a related object", E_USER_WARNING);
        return;
    }
    // ...
}
```

For errors that should halt execution, you should use Exceptions. Normally, Exceptions will halt the flow of executuion,
but they can be caught with a try/catch clause.

```php
throw new \LogicException("Query failed: " . $sql);
```

### Accessing the logger via dependency injection.

It can be quite verbose to call `Injector::inst()->get(LoggerInterface::class)` all the time. More importantly,
it also means that you're coupling your code to global state, which is a bad design practise. A better
approach is to use depedency injection to pass the logger in for you. The [Injector](../extending/Injector)
can help with this. The most straightforward is to specify a `dependencies` config setting, like this:

```php
use SilverStripe\Control\Controller;

class MyController extends Controller
{
    private static $dependencies = [
        'logger' => '%$Psr\Log\LoggerInterface',
    ];

    // This will be set automatically, as long as MyController is instantiated via Injector
    public $logger;

    protected function init()
    {
        $this->logger->debug("MyController::init() called");
        parent::init();
    }
}
```

In other contexts, such as testing or batch processing, logger can be set to a different value by the code calling
MyController.

### Error Levels

*  **E_USER_WARNING:** Err on the side of over-reporting warnings. Throwing warnings provides a means of ensuring that 
developers know:
    * Deprecated functions / usage patterns
    * Strange data formats
    * Things that will prevent an internal function from continuing.  Throw a warning and return null.

*  **E_USER_ERROR:** Throwing one of these errors is going to take down the production site.  So you should only throw
E_USER_ERROR if it's going to be **dangerous** or **impossible** to continue with the request.

## Configuring error logging

You can configure your logging using Monolog handlers. The handlers should be provided int the `Logger.handlers`
configuration setting. Below we have a couple of common examples, but Monolog comes with [many different handlers](https://github.com/Seldaek/monolog/blob/master/doc/02-handlers-formatters-processors.md#handlers)
for you to try.

### Sending emails

To send emails, you can use Monolog's [NativeMailerHandler](https://github.com/Seldaek/monolog/blob/master/src/Monolog/Handler/NativeMailerHandler.php#L74), like this:

```yaml
SilverStripe\Core\Injector\Injector:
  Psr\Log\LoggerInterface: 
    calls:
      MailHandler: [ pushHandler, [ %$MailHandler ] ]
  MailHandler:
      class: Monolog\Handler\NativeMailerHandler
      constructor:
        - me@example.com
        - There was an error on your test site
        - me@example.com
        - error
      properties:
        ContentType: text/html
        Formatter: %$SilverStripe\Logging\DetailedErrorFormatter
```

The first section 4 lines passes a new handler to `Logger::pushHandler()` from the named service `MailHandler`. The
next 10 lines define what the service is.

The calls key, `MailHandler`, can be anything you like: its main purpose is to let other configuration disable it
(see below).

### Logging to a file

To log to a file, you can use Monolog's [StreamHandler](https://github.com/Seldaek/monolog/blob/master/src/Monolog/Handler/StreamHandler.php#L74), like this:

```yaml
SilverStripe\Core\Injector\Injector:
  Psr\Log\LoggerInterface: 
    calls:
      LogFileHandler: [ pushHandler, [ %$LogFileHandler ] ]
  LogFileHandler:
    class: Monolog\Handler\StreamHandler
    constructor:
      - "../silverstripe.log"
      - "info"
```

The log file will be relative to the framework/ path, so "../silverstripe.log" will create a file in your project root.

### Disabling the default handler

You can disable a handler by removing its pushHandlers call from the calls option of the Logger service definition.
The handler key of the default handler is `DisplayErrorHandler`, so you can disable it like this:

```yaml
SilverStripe\Core\Injector\Injector:
  Psr\Log\LoggerInterface:
    calls:
      DisplayErrorHandler:  %%remove%%
```

### Setting a different configuration for dev

In order to set different logging configuration on different environment types, we rely on the environment-specific
configuration features that the config system proviers. For example, here we have different configuration for dev and
non-dev.

```yaml
---
Name: dev-errors
Only:
  environment: dev
---
SilverStripe\Core\Injector\Injector:
  Psr\Log\LoggerInterface:
    calls:
      pushDisplayErrorHandler: [ pushHandler, [ %$DisplayErrorHandler ]] 
  DisplayErrorHandler:
    class: SilverStripe\Logging\HTTPOutputHandler
    constructor:
      - "notice"
    properties:
      Formatter: %$SilverStripe\Logging\DetailedErrorFormatter
      CLIFormatter: %$SilverStripe\Logging\DetailedErrorFormatter
---
Name: live-errors
Except:
  environment: dev
---
SilverStripe\Core\Injector\Injector:
  Psr\Log\LoggerInterface:
    calls:
      pushFileLogHandler: [ pushHandler, [ %$LogFileHandler ]] 
      pushDisplayErrorHandler: [ pushHandler, [ %$DisplayErrorHandler ]] 
  LogFileHandler:
    class: Monolog\Handler\StreamHandler
    constructor:
      - "../silverstripe.log"
      - "notice"
    properties:
      Formatter: %$Monolog\Formatter\HtmlFormatter
      ContentType: text/html
  DisplayErrorHandler:
    class: SilverStripe\Logging\HTTPOutputHandler
    constructor:
      - "error"
    properties:
      Formatter: %$SilverStripe\Logging\DebugViewFriendlyErrorFormatter
  SilverStripe\Logging\DebugViewFriendlyErrorFormatter:
    class: SilverStripe\Logging\DebugViewFriendlyErrorFormatter
    properties:
      Title: "There has been an error"
      Body: "The website server has not been able to respond to your request"
```

<div class="info" markdown="1">
In addition to SilverStripe-integrated logging, it is advisable to fall back to PHPs native logging functionality. A
script might terminate before it reaches the SilverStripe error handling, for example in the case of a fatal error. Make
sure `log_errors` and `error_log` in your PHP ini file are configured.
</div>

## Replacing default implementations

For most application, Monolog and its default error handler should be fine, as you can get a lot of flexibility simply
by changing that handlers that are used. However, some situations will call for replacing the default components with
others.

### Replacing the logger

Monolog comes by default with SilverStripe, but you may use another PSR-3 compliant logger, if you wish. To do this,
set the `Injector.Logger` configuration parameter, providing a new injector definition. For example:

```yaml
SilverStripe\Core\Injector\Injector:
  ErrorHandler:
    class: Logging\Logger
    constructor:
     - 'alternative-logger'
```

If you do this, you will need to supply your own handlers, and the `Logger.handlers` configuration parameter will
be ignored.

### Replacing the error handler

The Injector service `ErrorHandler` is responsible for initialising the error handler. By default it 

 * Create a `SilverStripe\Logging\MonologErrorHandler` object.
 * Attach the registered service `Psr\Log\LoggerInterface` to it, to start the error handler.
 
Core.php will call `start()` on this method, to start the error handler.

This error handler is flexible enough to work with any PSR-3 logging implementation, but sometimes you will want to use
another. To replace this, you should registered a new service, `ErrorHandlerLoader`.  For example:

```yaml
SilverStripe\Core\Injector\Injector:
  ErrorHandler: 
    class: MyApp\CustomErrorHandlerLoader
```

You should register something with a `start()` method.

## Differences from SilverStripe 3

In SilverStripe 3, logging was based on the Zend Log module. Customisations were added using `SS_Log::add_writer()`.
This function no longer works, and any Zend Log writers will need to be replaced with Monolog handlers. Fortunately,
a range of handlers are available, both in the core package and in add-ons. See the
[Monolog documentation](https://github.com/Seldaek/monolog/blob/master/doc/01-usage.md) for more information.

## Related Lessons
* [Advanced environment configuration](https://www.silverstripe.org/learn/lessons/v4/advanced-environment-configuration-1)
