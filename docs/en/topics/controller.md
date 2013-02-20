# Controller

Base controller class.  You will extend this to take granular control over the actions and url handling of aspects of
your SilverStripe site.

## Usage

`mysite/code/Controllers/FastFood.php`

	:::php
	<?php
	
	class FastFood_Controller extends Controller {
		public static $allowed_actions = array('order');
	    public function order(SS_HTTPRequest $request) {
	        print_r($arguments);
	    }
	}

	?>


`mysite/_config.php`

	:::php
	Director::addRules(50, array('fastfood/$Action/$ID/$Name' => 'FastFood_Controller'));


Request for `/fastfood/order/24/cheesefries` would result in the following to the $arguments above. If needed, use
`?flush=1` on the end of request after making any code changes to your controller.

	:::ss
	Array
	(
	    [Action] => order
	    [ID] => 24
	    [Name] => cheesefries
	)

<div class="warning" markdown='1'>
	SilverStripe automatically adds a URL routing entry based on the controller's class name,
	so a `MyController` class is accessible through `http://yourdomain.com/MyController`.
</div>

## Access Control

### Through $allowed_actions

All public methods on a controller are accessible by their name through the `$Action`
part of the URL routing, so a `MyController->mymethod()` is accessible at
`http://yourdomain.com/MyController/mymethod`. This is not always desireable,
since methods can return internal information, or change state in a way
that's not intended to be used through a URL endpoint.

SilverStripe strongly recommends securing your controllers
through defining a `$allowed_actions` array on the class,
which allows whitelisting of methods, as well as a concise
way to perform checks against permission codes or custom logic.

	:::php
	class MyController extends Controller {
		public static $allowed_actions = array(
			// someaction can be accessed by anyone, any time
			'someaction', 
			// So can otheraction
			'otheraction' => true, 
			// restrictedaction can only be people with ADMIN privilege
			'restrictedaction' => 'ADMIN', 
			// complexaction can only be accessed if $this->canComplexAction() returns true
			'complexaction' '->canComplexAction' 
		);
	}

There's a couple of rules guiding these checks:

 * Each controller is only responsible for access control on the methods it defines
 * If a method on a parent class is overwritten, access control for it has to be redefined as well
 * An action named "index" is whitelisted by default
 * A wildcard (`*`) can be used to define access control for all methods (incl. methods on parent classes)
 * Specific method entries in `$allowed_actions` overrule any `*` settings
 * Methods returning forms also count as actions which need to be defined
 * Form action methods (targets of `FormAction`) should NOT be included in `$allowed_actions`,
   they're handled separately through the form routing (see the ["forms" topic](/topics/forms))
 * `$allowed_actions` can be defined on `Extension` classes applying to the controller.


If the permission check fails, SilverStripe will return a "403 Forbidden" HTTP status.

### Through the action

Each method responding to a URL can also implement custom permission checks,
e.g. to handle responses conditionally on the passed request data.

	:::php
	class MyController extends Controller {
		public static $allowed_actions = array('myaction');
		public function myaction($request) {
			if(!$request->getVar('apikey')) {
				return $this->httpError(403, 'No API key provided');
			} 
				
			return 'valid';
		}
	}

Unless you transform the response later in the request processing,
it'll look pretty ugly to the user. Alternatively, you can use
`ErrorPage::response_for(<status-code>)` to return a more specialized layout.

Note: This is recommended as an addition for `$allowed_actions`, in order to handle
more complex checks, rather than a replacement.

### Through the init() method

After checking for allowed_actions, each controller invokes its `init()` method,
which is typically used to set up common state in the controller, and 
include JavaScript and CSS files in the output which are used for any action.
If an `init()` method returns a `SS_HTTPResponse` with either a 3xx or 4xx HTTP
status code, it'll abort execution. This behaviour can be used to implement
permission checks.

	:::php
	class MyController extends Controller {
		public static $allowed_actions = array();
		public function init() {
			parent::init();
			if(!Permission::check('ADMIN')) return $this->httpError(403);
		}
	}

## URL Handling

In the above example the URLs were configured using the `[api:Director]` rules in the **_config.php** file. 
Alternatively you can specify these in your Controller class via the **$url_handlers** static array (which gets
processed by the `[api:RequestHandler]`).  

This is useful when you want to subvert the fixed action mapping of `fastfood/order/*` to the function **order**.  In
the case below we also want any orders coming through `/fastfood/drivethrough/` to use the same order function.

`mysite/code/Controllers/FastFood.php`

	:::php
	class FastFood_Controller extends Controller {
	    static $allowed_actions = array('drivethrough');
	    public static $url_handlers = array(
	        'drivethrough/$Action/$ID/$Name' => 'order'
	    );



## URL Patterns

The `[api:RequestHandler]` class will parse all rules you specify against the following patterns.

**A rule must always start with alphabetical ([A-Za-z]) characters or a $Variable declaration**

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

## API Documentation

`[api:Controller]`

## Links

*  `[api:Director]` class
*  [execution-pipeline](/reference/execution-pipeline)
*  [URL Handling in Controllers](http://maetl.net/silverstripe-url-handling) by maetl
