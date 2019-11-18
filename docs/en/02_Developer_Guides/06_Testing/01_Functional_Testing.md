---
title: Functional Testing
summary: Test controllers, forms and HTTP responses.
---
# Functional Testing

[api:FunctionalTest] test your applications `Controller` logic and anything else which requires a web request. The 
core idea of these tests is the same as `SapphireTest` unit tests but `FunctionalTest` adds several methods for 
creating [api:SS_HTTPRequest], receiving [api:SS_HTTPResponse] objects and modifying the current user session.

## Get
	
```php
	$page = $this->get($url);
	
```
of the response.

## Post
	
```php
	$page = $this->post($url);
	
```
of the response.

## Submit

```php
	$submit = $this->submitForm($formID, $button = null, $data = array());

```

## LogInAs

```php
	$this->logInAs($member);

```

```php
	$this->logInAs(null);

```

The `FunctionalTest` class also provides additional asserts to validate your tests.

### assertPartialMatchBySelector

```php
	$this->assertPartialMatchBySelector('p.good',array(
		'Test save was successful'
	));

```
selector will be applied to the HTML of the most recent page. The content of every matching tag will be examined. The 
assertion fails if one of the expectedMatches fails to appear.


### assertExactMatchBySelector

```php
	$this->assertExactMatchBySelector("#MyForm_ID p.error", array(
		"That email address is invalid."
	));

```
selector will be applied to the HTML of the most recent page. The full HTML of every matching tag will be examined. The 
assertion fails if one of the expectedMatches fails to appear. 

### assertPartialHTMLMatchBySelector
	
```php
	$this->assertPartialHTMLMatchBySelector("#MyForm_ID p.error", array(
		"That email address is invalid."
	));

```
selector will be applied to the HTML of the most recent page. The content of every matching tag will be examined. The 
assertion fails if one of the expectedMatches fails to appear.

[notice]
`&amp;nbsp;` characters are stripped from the content; make sure that your assertions take this into account.
[/notice]

### assertExactHTMLMatchBySelector
	
```php
	$this->assertExactHTMLMatchBySelector("#MyForm_ID p.error", array(
		"That email address is invalid."
	));

```
selector will be applied to the HTML of the most recent page.  The full HTML of every matching tag will be examined. The 
assertion fails if one of the expectedMatches fails to appear.

[notice]
`&amp;nbsp;` characters are stripped from the content; make sure that your assertions take this into account.
[/notice]

## Related Documentation

* [How to write a FunctionalTest](how_tos/write_a_functionaltest)

## API Documentation

* [api:FunctionalTest]
