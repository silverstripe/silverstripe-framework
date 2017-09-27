title: Rate Limiting
summary: SilverStripe's in built rate limiting features

# Rate Limiting

SilverStripe Framework comes with a [Middleware](developer_guides/controllers/middlewares/) that provides rate limiting
for the Security controller. This provides added protection to a potentially vulnerable part of a SilverStripe application
where an attacker is free to bombard your login forms or other Security endpoints.

## Applying rate limiting to controllers

You can apply rate limiting to other specific controllers or your entire SilverStripe application. When applying rate
limiting to other controllers you can define custom limits for each controller.

First, you need to define your rate limit middleware with the required settings:

```yml
SilverStripe\Core\Injector\Injector:
  MyRateLimitMiddleware:
    class: SilverStripe\Control\Middleware\RateLimitMiddleware
    properties:
      ExtraKey: 'mylimiter' # this isolates your rate limiter from others
      MaxAttempts: 10 # how many attempts are allowed in a decay period
      Decay: 1 # how long the decay period is in minutes
```

Next, you need to define your request handler which will apply the middleware to the controller:

```yml
SilverStripe\Core\Injector\Injector:
  MyRateLimitedController:
    class: SilverStripe\Control\Middleware\RequestHandlerMiddlewareAdapter
    properties:
      RequestHandler: '%$MyController' # the fully qualified class name of your controller
      Middlewares:
        - '%$MyRateLimitMiddleware' # the rate limiter we just defined in the last step
```

Finally, you need to define the custom routing:

```yml
Director:
  rules:
    'MyController//$Action/$ID/$OtherID': '%$MyRateLimitedController'
```

## Applying rate limiting across an entire application

If you'd like to add rate limiting to an entire application (ie: across all routes) then you'll need to define your rate
limit middleware much like the first step outlined in the previous section and then you'll have to apply it to the entire
site as you would with any other middleware:

```yml
SilverStripe\Core\Injector\Injector:
  SilverStripe\Control\Director:
    properties:
      Middlewares:
        SiteWideRateLimitMiddleware: '%$SiteWideRateLimitMiddleware'
```

## Disabling the Rate Limiter

You may already solve the rate limiting problem on a server level and the built in rate limiting may well be redundant.
If this is the case you can turn off the rate limiting middleware by redefining the URL rules for the Security controller.

Add the following to your config.yml:

```yml
SilverStripe\Control\Director:
  rules:
    'Security//$Action/$ID/$OtherID': SilverStripe\Security\Security
```