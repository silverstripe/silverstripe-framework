title: Functional Testing
summary: Test controllers, forms and HTTP responses.

# Functional Testing

[api:FunctionalTest] test your applications `Controller` logic and anything else which requires a web request. The 
core idea of these tests is the same as `SapphireTest` unit tests but `FunctionalTest` adds several methods for 
creating [api:SS_HTTPRequest], receiving [api:SS_HTTPResponse] objects and modifying the current user session.

## Get
	
	:::php
	$page = $this->get($url);
	
Performs a GET request on $url and retrieves the [api:SS_HTTPResponse]. This also changes the current page to the value
of the response.

## Post
	
	:::php
	$page = $this->post($url);
	
Performs a POST request on $url and retrieves the [api:SS_HTTPResponse]. This also changes the current page to the value
of the response.

## Submit

	:::php
	$submit = $this->submitForm($formID, $button = null, $data = array());

Submits the given form (`#ContactForm`) on the current page and returns the [api:SS_HTTPResponse].

## LogInAs

	:::php
	$this->logInAs($member);

Logs a given user in, sets the current session. To log all users out pass `null` to the method.

	:::php
	$this->logInAs(null);

## Assertions

The `FunctionalTest` class also provides additional asserts to validate your tests.

### assertPartialMatchBySelector

	:::php
	$this->assertPartialMatchBySelector('p.good',array(
		'Test save was successful'
	));

Asserts that the most recently queried page contains a number of content tags specified by a CSS selector. The given CSS 
selector will be applied to the HTML of the most recent page. The content of every matching tag will be examined. The 
assertion fails if one of the expectedMatches fails to appear.


### assertExactMatchBySelector

	:::php
	$this->assertExactMatchBySelector("#MyForm_ID p.error", array(
		"That email address is invalid."
	));

Asserts that the most recently queried page contains a number of content tags specified by a CSS selector. The given CSS 
selector will be applied to the HTML of the most recent page. The full HTML of every matching tag will be examined. The 
assertion fails if one of the expectedMatches fails to appear. 

### assertPartialHTMLMatchBySelector
	
	:::php
	$this->assertPartialHTMLMatchBySelector("#MyForm_ID p.error", array(
		"That email address is invalid."
	));

Assert that the most recently queried page contains a number of content tags specified by a CSS selector. The given CSS 
selector will be applied to the HTML of the most recent page. The content of every matching tag will be examined. The 
assertion fails if one of the expectedMatches fails to appear.

<div class="notice" markdown="1">
`&amp;nbsp;` characters are stripped from the content; make sure that your assertions take this into account.
</div>

### assertExactHTMLMatchBySelector
	
	:::php
	$this->assertExactHTMLMatchBySelector("#MyForm_ID p.error", array(
		"That email address is invalid."
	));

Assert that the most recently queried page contains a number of content tags specified by a CSS selector. The given CSS 
selector will be applied to the HTML of the most recent page.  The full HTML of every matching tag will be examined. The 
assertion fails if one of the expectedMatches fails to appear.

<div class="notice" markdown="1">
`&amp;nbsp;` characters are stripped from the content; make sure that your assertions take this into account.
</div>

## Related Documentation

* [How to write a FunctionalTest](how_tos/write_a_functionaltest)

## API Documentation

* [api:FunctionalTest]
