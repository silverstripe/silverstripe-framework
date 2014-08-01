# Director

## Introduction

`[api:Director]` is the first step in the "execution pipeline". It parses the 
URL, matching it to one of a number of patterns, and determines the controller, 
action and any argument to be used. It then runs the controller, which will 
finally run the viewer and/or perform processing steps.

## Request processing

The `[api:Director]` is the entry point in Silverstring Framework for processing
a request. You can read through the execution steps in `[api:Director]``::direct()`,
but in short:

* File uploads are first analysed to remove potentially harmful uploads (this will likely change!)
* The `[api:SS_HTTPRequest]` object is created
* The session object is created
* The `[api:RequestProcessor]` pre-request pipeline is invoked for initiali filtering of the response. The pipeline
can short-circuit, meaning the controller invocation will be bypassed. See "Request filtering" chapter for more details.
* The request is handled and response checked
* The `[api:RequestProcessor]` post-request pipeline is invoked
* The response is output

## Request filtering

Request filtering API permits its consumers to hook into the processing pipeline to adjust incoming requests
and augment outgoing responses. This includes the capability to prevent the pipeline from continuing, to skip
the controller execution altogether, or to rewrite the responses in their entirety. Session can also be updated.

This implementation, sometimes described as pluggable "middleware", bears resemblance to the approach seen in the web
servers using [Rack](http://rack.github.io/), and in
[Slim Framework](http://docs.slimframework.com/#Middleware-Overview).

Framework `RequestFilter` objects combine inward (`preRequest`) and outward handlers (`postRequest`). Outward
handlers are applied in reverse, ensuring that all filters have a chance to clean up. So during a normal execution,
assuming that three filters A, B and C have been configured, the following path is taken:

	A > B > C > (controllers) > C > B > A

Filters have an option to short-circuit the pipeline on its inward side, bypassing the controllers altogether. To do
this, filter has to return a new `SS_HTTPResponse` object from `preRequest` handler, at which point the
`RequestProcessor` will set itself into the shorted state. The exact state can be inspected by calling
`getExecutedFilters` and `getShortedFilter` on the processor.

In case of a short the `RequestProcessor` will still invoke the already-executed filters in a reverse order, but will do
so via the `postShorted` handler instead. The early `SS_HTTPResponse` object returned by the shorting filter will be
passed on as a parameter.

If we take the example situation of the filter B shorting, the execution will look as follows:

	A > B (shorts) > A

Shorting is not permitted in the outgoing pipeline.

## Routing

You can influence the way URLs are resolved in the following ways

1. Adding rules to `[api:Director]` in `<yourproject>/_config/routes.yml` 
2. Adding rules to `[api:Director]` in `<yourproject>/_config.php (deprecated)
3. Adding rules in your extended `[api:Controller]` class via the *$url_handlers* 
static variable 

See [controller](/topics/controller) for examples and explanations on how the 
rules get processed for those methods.


### Routing Rules

SilverStripe comes with certain rules which map a URI to a `[api:Controller]`
class (e.g. *dev/* -> DevelopmentAdmin). These routes are either stored in 
a routes.yml configuration file located a `_config` directory or inside a 
`_config.php` file (deprecated). 

To add your own custom routes for your application create a routes.yml file 
in `<yourproject>/_config/routes.yml` with the following format:

	:::yaml
	---
	Name: customroutes
	After: framework/routes#coreroutes
	---
	Director:
  		rules:
    		'subscriptions/$Action' : 'SubscriptionController'

The [Controller](/topics/controller) documentation has a wide range of examples 
and explanations on how the rules get processed for those methods.

See:

*  [framework/_config/routes.yml](https://github.com/silverstripe/silverstripe-framework/blob/master/_config/routes.yml)
*  [cms/_config/routes.yml](https://github.com/silverstripe/silverstripe-cms/blob/master/_config/routes.yml)


## Best Practices

*  Checking for an Ajax-Request: Use Director::is_ajax() instead of checking 
for $_REQUEST['ajax'].


## Links

*  See `[api:ModelAsController]` class for details on controller/model-coupling
*  See [execution-pipeline](/reference/execution-pipeline) for custom routing

## API Documentation
`[api:Director]`
