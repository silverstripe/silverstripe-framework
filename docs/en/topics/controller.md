# Controller

Base controller class.  You will extend this to take granular control over the 
actions and url handling of aspects of your SilverStripe site.

## Example

The following example is for a simple `[api:Controller]` class. If you're using
the cms module and looking at Page_Controller instances you won't need to setup
your own routes since the cms module handles these routes.

`mysite/code/Controllers/FastFood.php`

	:::php
	<?php
	
	class FastFood_Controller extends Controller {
	    public function order($arguments) {
	        print_r($arguments);
	    }
	}

`mysite/_config/routes.yml`

	:::yaml
	---
	Name: myroutes
	After: framework/routes#coreroutes
	---
	Director:
	  rules:
	    'fastfood/$Action/$ID/$Name': 'FastFood_Controller'


Request for `/fastfood/order/24/cheesefries` would result in the following to 
the $arguments above. If needed, use `?flush=1` on the end of request after 
making any code changes to your controller.

	:::ss
	Array
	(
	    [Action] => order
	    [ID] => 24
	    [Name] => cheesefries
	)


## URL Handling

In the above example the URLs were configured using the `[api:Director]` rules 
in the **routes.yml** file. Alternatively you can specify these in your 
Controller class via the **$url_handlers** static array (which gets processed 
by the `[api:RequestHandler]`).  

This is useful when you want to subvert the fixed action mapping of `fastfood/order/*` 
to the function **order**. In the case below we also want any orders coming 
through `/fastfood/drivethrough/` to use the same order function.

`mysite/code/Controllers/FastFood.php`

	:::php
	class FastFood_Controller extends Controller {
	    
	    public static $url_handlers = array(
	        'drivethrough/$Action/$ID/$Name' => 'order'
	    );

## URL Patterns

The `[api:RequestHandler]` class will parse all rules you specify against the 
following patterns.

**A rule must always start with alphabetical ([A-Za-z]) characters or a $Variable 
declaration**

 | Pattern     | Description | 
 | ----------- | --------------- | 
 | `$`         | **Param Variable** - Starts the name of a paramater variable, it is optional to match this unless ! is used | 
 | `!`         | **Require Variable** - Placing this after a parameter variable requires data to be present for the rule to match | 
 | `//`        | **Shift Point** - Declares that only variables denoted with a $ are parsed into the $params AFTER this point in the regex | 

## Examples

See maetl's article in the Links below of a detailed explanation. 

`$Action/$ID/$OtherID` - Standard URL handler for a Controller.  Take whatever `URLSegment` it is set to, find
the Action to match a function in the controller, and parse two optional `$param` variables that will be named `ID` and
`OtherID`.


`admin/help//$Action/$ID` - Match an url starting with `/admin/help/`, but don't include `/help/` as part of the
action (the shift point is set to start parsing variables and the appropriate controller action AFTER the `//`)


`tag/$Tag!` - Match an URL starting with `/tag/` after the controller's `URLSegment` and require it to have something
after it.  If the URLSegment is **order** then `/order/tag/34` and `/order/tag/asdf` match but `/order/tag/` will not


You can use the `debug_request=1` switch from the [urlvariabletools](/reference/urlvariabletools) to see these in action.

## Redirection

Controllers facilitate HTTP redirection.

Note: These methods have been formerly located on the `[api:Director]` class.

*  `redirect("action-name")`: If there's no slash in the URL passed to redirect, then it is assumed that you want to go to a different action on the current controller.
*  `redirect("relative/url")`: If there is a slash in the URL, it's taken to be a normal URL.  Relative URLs
will are assumed to be relative to the site-root.
*  `redirect("http://www.absoluteurl.com")`: Of course, you can pass `redirect()` absolute URLs too.
*  `redirectBack()`: This will return you to the previous page.

The `redirect()` method takes an optional HTTP status code,
either `301` for permanent redirects, or `302` for temporary redirects (default).

## Access control

You can also limit access to actions on a controller using the static `$allowed_actions` array. This allows you to always allow an action, or restrict it to a specific permission or to call a method that checks if the action is allowed.

For example, the default `Controller::$allowed_actions` is
	static $allowed_actions = array(
		'handleAction',
		'handleIndex',
	);
which allows the `handleAction` and `handleIndex` methods to be called via a URL.

To allow any action on your controller to be called you can either leave your `$allowed_actions` array empty or not have one at all. This is the default behaviour, however it is not recommended as it allows anything on your controller to be called via a URL, including view-specific methods.

The recommended approach is to explicitly state the actions that can be called via a URL. Any action not in the `$allowed_actions` array, excluding the default `index` method, is then unable to be called.

To always allow an action to be called, you can either add the name of the action to the array or add a value of `true` to the array, using the name of the method as its index. For example
	static $allowed_actions = array(
		'MyAwesomeAction',
		'MyOtherAction' => true
	);


To require that the current user has a certain permission before being allowed to call an action you add the action to the array as an index with the value being the permission code that user must have. For example
	static $allowed_actions = array(
		'MyAwesomeAction',
		'MyOtherAction' => true,
		'MyLimitedAction' => 'CMS_ACCESS_CMSMain',
		'MyAdminAction' => 'ADMIN'
	);

If neither of these are enough to decide if an action should be called, you can have the check use a method. The method must be on the controller class and return true if the action is allowed or false if it isn't. To do this add the action to the array as an index with the value being the name of the method to called preceded by '->'. You are able to pass static arguments to the method in much the same way as you can with extensions. Strings are enclosed in quotes, numeric values are written as numbers and true and false are written as true and false. For example
	static $allowed_actions = array(
		'MyAwesomeAction',
		'MyOtherAction' => true,
		'MyLimitedAction' => 'CMS_ACCESS_CMSMain',
		'MyAdminAction' => 'ADMIN',
		'MyRestrictedAction' => '->myCheckerMethod("MyRestrictedAction", false, 42)',
		'MyLessRestrictedAction' => '->myCheckerMethod'
	);

In this example, `MyAwesomeAction` and `MyOtherAction` are always allowed, `MyLimitedAction` requires access to the CMS for the current user and `MyAdminAction` requires the current user to be an admin. `MyRestrictedAction` calls the method `myCheckerMethod`, passing in the string "MyRestrictedAction", the boolean false and the number 42. `MyLessRestrictedAction` simply calls the method `myCheckerMethod` with no arguments.

## API Documentation

`[api:Controller]`

## Links

*  `[api:Director]` class
*  [execution-pipeline](/reference/execution-pipeline)
*  [URL Handling in Controllers](http://maetl.net/silverstripe-url-handling) by maetl
