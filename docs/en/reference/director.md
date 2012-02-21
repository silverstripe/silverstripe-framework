# Director

## Introduction

`[api:Director]` is the first step in the "execution pipeline". It parses the URL, matching it to one of a number of patterns,
and determines the controller, action and any argument to be used. It then runs the controller, which will finally run
the viewer and/or perform processing steps.

## Best Practices

*  Checking for an Ajax-Request: Use Director::is_ajax() instead of checking for $_REQUEST['ajax'].

## Redirection

The `[api:Director]` class has a number of methods to facilitate 301 and 302 HTTP redirection.

*  **Director::redirect("action-name")**: If there's no slash in the URL passed to redirect, then it is assumed that you
want to go to a different action on the current controller.
*  **Director::redirect("relative/url")**: If there is a slash in the URL, it's taken to be a normal URL.  Relative URLs
will are assumed to be relative to the site-root; so Director::redirect("home/") will work no matter what the current
URL is.
*  **Director::redirect("http://www.absoluteurl.com")**: Of course, you can pass redirect() absolute URL s too.
*  **Director::redirectPerm("any-url")**: redirectPerm takes the same arguments as redirect, but it will send a 301
(permanent) instead of a 302 (temporary) header.  It improves search rankings, so this should be used whenever the
following two conditions are true:
    * Nothing happens server-side prior to the redirection
    * The redirection will always occur
*  **Director::redirectBack()**: This will return you to the previous page.  There's no permanent version of
redirectBack().


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

*  [sapphire/_config.php](http://open.silverstripe.org/browser/modules/sapphire/trunk/_config.php)
*  [cms/_config.php](http://open.silverstripe.org/browser/modules/cms/trunk/_config.php)


## Links

*  See `[api:ModelAsController]` class for details on controller/model-coupling
*  See [execution-pipeline](/reference/execution-pipeline) for custom routing

## API Documentation
`[api:Director]`
