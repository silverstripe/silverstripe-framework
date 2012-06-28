# Director

## Introduction

`[api:Director]` is the first step in the "execution pipeline". It parses the URL, matching it to one of a number of patterns,
and determines the controller, action and any argument to be used. It then runs the controller, which will finally run
the viewer and/or perform processing steps.

## Best Practices

*  Checking for an Ajax-Request: Use Director::is_ajax() instead of checking for $_REQUEST['ajax'].


## Request processing

The `[api:Director]` is the entry point in Silverstring Framework for processing a request. You can read through
the execution steps in `[api:Director]``::direct()`, but in short

* File uploads are first analysed to remove potentially harmful uploads (this will likely change!)
* The `[api:SS_HTTPRequest]` object is created
* The session object is created
* The `[api:Injector]` is first referenced, and asks the registered `[api:RequestProcessor]` to pre-process
  the request object. This allows for analysis of the current request, and allow filtering of parameters
  etc before any of the core of the application executes
* The request is handled and response checked
* The `[api:RequestProcessor]` is called to post-process the request to allow further filtering before
  content is sent to the end user. 
* The response is output

The framework provides the ability to hook into the request both before and after it is handled to allow
developers to bind in their own custom pre- or post- request logic; see the `[api:RequestFilter]` to see how
this can be used to authenticate the request before the request is handled. 

## Custom Rewrite Rules

You can influence the way URLs are resolved one of 2 ways

1.  Adding rules to `[api:Director]` in `<yourproject>/_config.php` (See Default Rewrite Rules below for examples)
2.  Adding rules in your extended `[api:Controller]` class via the *$url_handlers* static variable 

See [controller](/topics/controller) for examples and explanations on how the rules get processed for both 1 and 2 above. 

*  Static redirect for specific URL

	:::php
	Director::addRules(100, array(
	'myPermanentRedirect' => 'redirect:http://www.mysite.com'
	));


## Default Rewrite Rules

SilverStripe comes with certain rewrite rules (e.g. for *admin/assets*).

*  [framework/_config.php](https://github.com/silverstripe/sapphire/blob/master/_config.php)
*  [cms/_config.php](https://github.com/silverstripe/silverstripe-cms/blob/master/_config.php)


## Links

*  See `[api:ModelAsController]` class for details on controller/model-coupling
*  See [execution-pipeline](/reference/execution-pipeline) for custom routing

## API Documentation
`[api:Director]`
