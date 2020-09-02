---
title: Adding middleware
summary: Add middleware to to extend query execution
---
# Extending the schema

[CHILDREN asList]

## Adding middleware

Middleware is any piece of functionality that is interpolated into
a larger process. A key feature of middleware is that it can be used
with other middlewares in sequence and not have to worry about the order
of execution.

In `silverstripe-graphql`, middleware is used for query execution,
but could ostensibly be used elsewhere too if the API ever accomodates
such an expansion.

[notice]
The middleware API in the silverstripe-graphql module is separate from other common middleware
APIs in Silverstripe CMS, such as HTTPMiddleware.
[/notice]

The signature for middleware is pretty simple:

```php
public function process(array $params, callable $next)
```

`$params` is an arbitrary array of data, much like an event object
passed to an event handler. The `$next` parameter refers to the next
middleware in the chain.

Let's write a simple middleware that logs our queries as they come in.

```php
class LoggingMiddleware implements Middleware
{
    public function process(array $params, callable $next)
    {
        $query = $params['query'];
        Injector::inst()->get(LoggerInterface::class)
        	->info('Query executed: ' . $query);
        
        // Hand off execution to the next middleware
        return $next($params);
    }
}
```

Now we can register the middleware with our query handler:


```yaml
  SilverStripe\GraphQL\QueryHandler\QueryHandlerInterface.default:
    class: SilverStripe\GraphQL\QueryHandler\QueryHandler
    properties:
      Middlewares:
        logging: '%$MyProject\Middleware\LoggingMiddleware'
```
