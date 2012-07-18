# Director

## Introduction

`[api:Director]` is the first step in the "execution pipeline". It parses the 
URL, matching it to one of a number of patterns, and determines the controller, 
action and any argument to be used. It then runs the controller, which will 
finally run the viewer and/or perform processing steps.

## Request processing

The `[api:Director]` is the entry point in Silverstring Framework for processing 
a request. You can read through the execution steps in `[api:Director]``::direct()`, 
but in short

* File uploads are first analysed to remove potentially harmful uploads (this 
will likely change!)
* The `[api:SS_HTTPRequest]` object is created
* The session object is created
* The `[api:Injector]` is first referenced, and asks the registered `[api:RequestProcessor]` 
to pre-process the request object. This allows for analysis of the current 
request, and allow filtering of parameters etc before any of the core of the 
application executes.
* The request is handled and response checked
* The `[api:RequestProcessor]` is called to post-process the request to allow 
further filtering before content is sent to the end user
* The response is output

The framework provides the ability to hook into the request both before and 
after it is handled to allow developers to bind in their own custom pre- or 
post- request logic; see the `[api:RequestFilter]` to see how this can be used 
to authenticate the request before the request is handled. 

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

*  [framework/_config/routes.yml](https://github.com/silverstripe/sapphire/blob/master/_config/routes.yml)
*  [cms/_config/routes.yml](https://github.com/silverstripe/silverstripe-cms/blob/master/_config/routes.yml)


## Best Practices

*  Checking for an Ajax-Request: Use Director::is_ajax() instead of checking 
for $_REQUEST['ajax'].


## Links

*  See `[api:ModelAsController]` class for details on controller/model-coupling
*  See [execution-pipeline](/reference/execution-pipeline) for custom routing

## API Documentation
`[api:Director]`
