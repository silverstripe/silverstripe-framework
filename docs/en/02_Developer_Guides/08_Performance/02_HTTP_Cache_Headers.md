---
title: HTTP Cache Headers
summary: Set the correct HTTP cache headers for your responses.
---
# HTTP Cache Headers

## Overview

By default, SilverStripe sends headers which signal to HTTP caches
that the response should be not considered cacheable.
HTTP caches can either be intermediary caches (e.g. CDNs and proxies), or clients (e.g. browsers).
The cache headers sent are `Cache-Control: no-store, no-cache, must-revalidate`;

HTTP caching can be a great way to speed up your website, but needs to be properly applied.
Getting it wrong can accidentally expose draft pages or other protected content.
The [Google Web Fundamentals](https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching#public_vs_private)
are a great way to learn about HTTP caching.

## Cache Control Headers

### Overview

In order to support developers in making safe choices around HTTP caching,
we're using a `HTTPCacheControl` class to control if a response
should be considered public or private. This is an abstraction on existing
lowlevel APIs like `HTTP::add_cache_headers()` and `SS_HTTPResponse->addHeader()`.

The `HTTPCacheControl` API makes it easier to express your caching preferences
without running the risk of overriding essential core safety measures.
Most commonly, these APIs will prevent HTTP caching of draft content.

It will also prevent caching of content generated with an active session,
since the system can't tell whether session data was used to vary the output.
In this case, it's up to the developer to opt-in to caching,
after ensuring that certain execution paths are safe despite of using sessions.

The system behaviour does not guard against accidentally caching "private" content,
since there are too many variations under which output could be considered private
(e.g. a custom "approval" flag on a comment object). It is up to
the developer to ensure caching is used appropriately there.

The `HTTPCacheControl` class supplements the `HTTP` helper class.
It comes with methods which let developers safely interact with the `Cache-Control` header.

### disableCache()

Simple way to set cache control header to a non-cacheable state.
Use this method over `privateCache()` if you are unsure about caching details.
Takes precendence over unforced `enableCache()`, `privateCache()` or `publicCache()` calls.

Removes all state and replaces it with `no-cache, no-store, must-revalidate`. Although `no-store` is sufficient
the others are added under [recommendation from Mozilla](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control#Examples)

Does not set `private` directive, use `privateCache()` if this is explicitly required
([details](https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching#public_vs_private))

### enableCache()

Simple way to set cache control header to a cacheable state.
Use this method over `publicCache()` if you are unsure about caching details.

Removes `no-store` and `no-cache` directives; other directives will remain in place.
Use alongside `setMaxAge()` to indicate caching.

Does not set `public` directive. Usually, `setMaxAge()` is sufficient. Use `publicCache()` if this is explicitly required
([details](https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching#public_vs_private))

### privateCache()

Advanced way to set cache control header to a non-cacheable state.
Indicates that the response is intended for a single user and must not be stored by a shared cache.
A private cache (e.g. Web Browser) may store the response. Also removes `public` as this is a contradictory directive.

### publicCache()

Advanced way to set cache control header to a cacheable state.
Indicates that the response may be cached by any cache. (eg: CDNs, Proxies, Web browsers)
Also removes `private` as this is a contradictory directive

### Priority
    
Each of these highlevel methods has a boolean `$force` parameter which determines
their application priority regardless of execution order.
The priority order is as followed, sorted in descending order
(earlier items will overrule later items): 

 * `disableCache($force=true)`
 * `privateCache($force=true)`
 * `publicCache($force=true)`
 * `enableCache($force=true)`
 * `disableCache()`
 * `privateCache()`
 * `publicCache()`
 * `enableCache()`

## Cache Control Examples

### Global opt-in for page content 

Enable caching for all page content (through `Page_Controller`).

```php
class Page_Controller extends ContentController
{
    public function init()
    {
        HTTPCacheControl::singleton()
           ->enableCache()
           ->setMaxAge(60); // 1 minute

        
        parent::init();
    }
}
```

Note: SilverStripe will still override this preference when a session is active,
a [CSRF token](/developer_guides/forms/form_security) token is present,
or draft content has been requested.

### Opt-out for a particular controller action

If a controller output relies on session data, cookies,
permission checks or other triggers for conditional output,
you can disable caching either on a controller level
(through `init()`) or for a particular action.

```php
class MyPage_Controller extends Page_Controller
{
    public function myprivateaction($request)
    {
        $response = $this->myPrivateResponse();
        HTTPCacheControl::singleton()
           ->disableCache();
        
        return $response;
    }
}
```

Note: SilverStripe will still override this preference when a session is active,
a [CSRF token](/developer_guides/forms/form_security) token is present,
or draft content has been requested. 

### Global opt-in, ignoring session (advanced) 

This can be helpful in situations where forms are embedded on the website.
SilverStripe will still override this preference when draft content has been requested.
CAUTION: This mode relies on a developer examining each execution path to ensure
that no session data is used to vary output. 

Use case: By default, forms include a [CSRF token](/developer_guides/forms/form_security)
which starts a session with a value that's unique to the visitor, which makes the output uncacheable.
But any subsequent requests by this visitor will also carry a session, leading to uncacheable output
for this visitor. This is the case even if the output does not contain any forms,
and does not vary for this particular visitor.

```php
class Page_Controller extends ContentController
{
    public function init()
    {
        HTTPCacheControl::singleton()
           ->enableCache($force=true) // DANGER ZONE
           ->setMaxAge(60); // 1 minute
        
        parent::init();
    }
}
```

## Defaults

By default, PHP adds caching headers that make the page appear purely dynamic. This isn't usually appropriate for most 
sites, even ones that are updated reasonably frequently. SilverStripe overrides the default settings with the following 
headers:

  * The `Last-Modified` date is set to be most recent modification date of any database record queried in the generation 
  of the page.
  * The `Expiry` date is set by taking the age of the page and adding that to the current time.
  * `Cache-Control` is set to `max-age=86400, must-revalidate`
  * Since a visitor cookie is set, the site won't be cached by proxies.
  * Ajax requests are never cached.

## Max Age

The cache age determines the lifetime of your cache, in seconds.
It only takes effect if you instruct the cache control
that your response is public in the first place (via `enableCache()` or via modifying the `HTTP.cache_control` defaults).

	:::php
	HTTPCacheControl::singleton()
	    ->setMaxAge(60)

Note that `setMaxAge(0)` is NOT sufficient to disable caching in all cases.

### Last Modified

Used to set the modification date to something more recent than the default. [api:DataObject::__construct] calls 
[api:HTTP::register_modification_date(] whenever a record comes from the database ensuring the newest date is present.

	:::php
	HTTP::register_modification_date('2014-10-10');

### Vary

A `Vary` header tells caches which aspects of the response should be considered
when calculating a cache key, usually in addition to the full URL path.
By default, SilverStripe will output a `Vary` header with the following content: 

```
Vary: X-Forwarded-Protocol
```

To change the value of the `Vary` header, you can change this value by specifying the header in configuration.

```yml
HTTP:
  vary: ""
```

Note that if you use `Director::is_ajax()` on cached pages then you should add `X-Requested-With` to the vary
header.
