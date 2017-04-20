title: Redirection
summary: Move users around your site using automatic redirection.

# Redirection

Controllers can facilitate redirecting users from one place to another using `HTTP` redirection using the `Location` 
HTTP header.

**mysite/code/Page.php**

	:::php
	$this->redirect('goherenow');
	// redirect to Page::goherenow(), i.e on the contact-us page this will redirect to /contact-us/goherenow/

	$this->redirect('goherenow/');
	// redirect to the URL on yoursite.com/goherenow/. (note the trailing slash)

	$this->redirect('http://google.com');
	// redirect to http://google.com

	$this->redirectBack();
	// go back to the previous page.

## Status Codes

The `redirect()` method takes an optional HTTP status code, either `301` for permanent redirects, or `302` for 
temporary redirects (default).
	
	:::php
	$this->redirect('/', 302);
	// go back to the homepage, don't cache that this page has moved

## Redirection in URL Handling

Controllers can specify redirections in the `$url_handlers` property rather than defining a method by using the '~'
operator.

	:::php
	private static $url_handlers = array(
		'players/john' => '~>coach'
	);

For more information on `$url_handlers` see the [Routing](routing) documenation.

## API Documentation

* [api:Controller]