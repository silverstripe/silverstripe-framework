---
title: Built-in Middleware
summary: Middleware components that come with SilverStripe Framework
---

# Built-in Middleware

SilverStripe Framework has a number of Middleware components.
You may find them in the [SilverStripe\Control\Middleware](api:SilverStripe\Control\Middleware) namespace.

| Name | Description |
| ---- | ----------- |
| [AllowedHostsMiddleware](api:SilverStripe\Control\Middleware\AllowedHostsMiddleware) | Secures requests by only allowing a whitelist of Host values |
| [CanonicalURLMiddleware](api:SilverStripe\Control\Middleware\CanonicalURLMiddleware) | URL normalisation and redirection |
| [ChangeDetectionMiddleware](api:SilverStripe\Control\Middleware\ChangeDetectionMiddleware) | Change detection via Etag / IfModifiedSince headers, conditionally sending a 304 not modified if possible. |\
| [ConfirmationMiddleware](api:SilverStripe\Control\Middleware\ConfirmationMiddleware) | Checks whether user manual confirmation is required for HTTPRequest |
| [ExecMetricMiddleware](api:SilverStripe\Control\Middleware\ExecMetricMiddleware) | Display execution metrics in DEV mode |
| [FlushMiddleware](api:SilverStripe\Control\Middleware\FlushMiddleware) | Triggers a call to flush() on all [Flushable](api:SilverStripe\Core\Flushable) implementors |
| [HTTPCacheControlMiddleware](api:SilverStripe\Control\Middleware\HTTPCacheControlMiddleware) | Controls HTTP response cache headers |
| [RateLimitMiddleware](api:SilverStripe\Control\Middleware\RateLimitMiddleware) | Access throttling, controls HTTP Retry-After header |
| [SessionMiddleware](api:SilverStripe\Control\Middleware\SessionMiddleware) | PHP Session initialisation |
| [TrustedProxyMiddleware](api:SilverStripe\Control\Middleware\TrustedProxyMiddleware) | Rewrites headers that provide IP and host details from upstream proxies |
| [URLSpecialsMiddleware](api:SilverStripe\Control\Middleware\URLSpecialsMiddleware) | Controls some of the [URL special variables](../../debugging/url_variable_tools) |
