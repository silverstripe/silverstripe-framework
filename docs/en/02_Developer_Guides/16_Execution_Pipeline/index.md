---
summary: An overview of the steps involved in delivering a SilverStripe web page.
---

# Execution Pipeline

## Introduction

In order to transform a HTTP request or a commandline exeuction into a response,
SilverStripe needs to boot its core and run through several stages of processing.

## Request Rewriting

The first step in most environments is a rewrite of a request path into parameters passed to a PHP script.
This allows writing friendly URLs instead of linking directly to PHP files.
The implementation depends on your web server; we'll show you the most common one here: 
Apache with [mod_rewrite](http://httpd.apache.org/docs/2.0/mod/mod_rewrite.html).
Check our [installation guides](/getting_started/installation) on how other web servers like IIS or nginx handle rewriting.

The standard SilverStripe project ships with a `.htaccess` file in your webroot for this purpose.
By default, requests will be passed through for files existing on the filesystem.
Some access control is in place to deny access to potentially sensitive files in the webroot, such as YAML configuration files.
If no file can be directly matched, control is handed off to `index.php`.

## Bootstrap

The `constants.php` file is included automatically in any project which requires silverstripe/framework.
This is included automatically when the composer `vendor/autoload.php` is included, and performs its
tasks silently in the background.

  * Tries to locate an `.env` 
   [configuration file](/getting_started/environment_management) in the webroot.
  * Sets constants based on the filesystem structure (e.g. `BASE_URL`, `BASE_PATH` and `TEMP_PATH`)

All requests go through `index.php`, which sets up the core [Kernel](api:SilverStripe\Core\Kernel) and [HTTPApplication](api:SilverStripe\Control\HTTPApplication)
objects. See [/developer_guides/execution_pipeline/app_object_and_kernel] for details on this.
The main process follows:

 
 * Include `autoload.php`
 * Construct [HTTPRequest](api:SilverStripe\Control\HTTPRequest) object from environment.
 * Construct a `Kernel` instance
 * Construct a `HTTPApplication` instance
 * Add any necessary middleware to this application
 * Pass the request to the application, and request a response
 

While you usually don't need to modify the bootstrap on this level, some deeper customizations like
adding your own manifests or a performance-optimized routing might require it.
An example of this can be found in the ["staticpublisher" module](https://github.com/silverstripe-labs/silverstripe-staticpublisher/).

## Routing and Request Handling

The `index.php` script relies on [Director](api:SilverStripe\Control\Director) to work out which [controller](../controllers/)
should handle this request. It parses the URL, matching it to one of a number of patterns, 
and determines the controller, action and any argument to be used ([Routing](../controllers/routing)).

 * Creates a [HTTPRequest](api:SilverStripe\Control\HTTPRequest) object containing all request and environment information
 * The [session](../cookies_and_sessions/sessions) holds an abstraction of PHP session
 * Instantiates a [controller](../controllers/) object
 * The [Injector](api:SilverStripe\Core\Injector\Injector) is first referenced, and asks the registered 
   [RequestFilter](../controllers/requestfilters)
   to pre-process the request object (see below)
 * The `Controller` executes the actual business logic and populates an [HTTPResponse](api:SilverStripe\Control\HTTPResponse)
 * The `Controller` can optionally hand off control to further nested controllers
 * The `Controller` optionally renders a response body through `SSViewer` [templates](../templates)
 * The [RequestProcessor](api:SilverStripe\Control\RequestProcessor) is called to post-process the request to allow 
further filtering before content is sent to the end user
 * The response is output to the client

## Request Preprocessing and Postprocessing

The framework provides the ability to hook into the request both before and 
after it is handled to allow binding custom logic. This can be used
to transform or filter request data, instantiate helpers, execute global logic,
or even short-circuit execution (e.g. to enforce custom authentication schemes).
The ["Request Filters" documentation](../controllers/requestfilters) shows you how.

## Flushing Manifests

If a `?flush=1` query parameter is added to a URL, a call to `flush()` will be triggered
on any classes that implement the [Flushable](flushable) interface.
This enables developers to clear [manifest caches](manifests),
for example when adding new templates or PHP classes.
Note that you need to be in [dev mode](/getting_started/environment_management)
or logged-in as an administrator for flushing to take effect.

[CHILDREN]
