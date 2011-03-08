# Controller

Base controller class.  You will extend this to take granular control over the actions and url handling of aspects of
your SilverStripe site.


## Example

`mysite/code/Controllers/FastFood.php`

	:::php
	<?php
	
	class FastFood_Controller extends Controller {
	    function order($arguments) {
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


## URL Handling

In the above example the URLs were configured using the `[api:Director]` rules in the **_config.php** file. 
Alternatively you can specify these in your Controller class via the **$url_handlers** static array (which gets
processed by the `[api:RequestHandler]`).  

This is useful when you want to subvert the fixed action mapping of `fastfood/order/*` to the function **order**.  In
the case below we also want any orders coming through `/fastfood/drivethrough/` to use the same order function.

`mysite/code/Controllers/FastFood.php`

	:::php
	class FastFood_Controller extends Controller {
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
