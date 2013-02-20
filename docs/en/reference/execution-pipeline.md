# Execution Pipeline

## Introduction

This page documents all the steps from an URL request to the delivered page. 

## .htaccess and RewriteRule

Silverstripe uses **[mod_rewrite](http://httpd.apache.org/docs/2.0/mod/mod_rewrite.html)** to deal with page requests.
So instead of having your normal everyday `index.php` file which tells all, you need to look elsewhere. 

The basic .htaccess file after installing SilverStripe look like this:

	<file>
	### SILVERSTRIPE START ###

	<Files *.ss>
	Order deny,allow
	Deny from all
	Allow from 127.0.0.1
	</Files>

	<IfModule mod_rewrite.c>
	RewriteEngine On

	RewriteCond %{REQUEST_URI} !(\.gif$)|(\.jpg$)|(\.png$)|(\.css$)|(\.js$)

	RewriteCond %{REQUEST_URI} ^(.*)$
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule .* sapphire/main.php?url=%1&%{QUERY_STRING} [L]
	</IfModule>
	### SILVERSTRIPE END ###

	</file>

The `<Files>` section denies direct access to the template files from anywhere but the server itself.

The next section enables the rewriting engine and rewrites requests to `sapphire/main.php` if they meet the following
criteria:

*  URI doesn't end in .gif, .jpg, .png, .css, or .js
*  The requested file doesn't exist on the filesystem `sapphire/main.php` is called with the REQUEST_FILENAME (%1) as the `url` parameter and also appends the original
QUERY_STRING.

See the [mod_rewrite documentation](http://httpd.apache.org/docs/2.0/mod/mod_rewrite.html) for more information on how
mod_rewrite works.


## main.php

All requests go through main.php, which sets up the environment and then hands control over to Director. 

**See:** The API documentation of `[api:Main]` for information about how main.php processes requests.
## Director and URL patterns

main.php relies on `[api:Director]` to work out which controller should handle this request.  `[api:Director]` will instantiate that
controller object and then call `[api:Controller::run()]`.

**See:** The API documentation of `[api:Director]` for information about how Director parses URLs and hands control over to a controller object.

In general, the URL is build up as follows: page/action/ID/otherID - e.g. http://www.mysite.com/mypage/addToCart/12. 
This will add an object with ID 12 to the cart.

When you create a function, you can access the ID like this:

	:::php
	 function addToCart ($request) {
	  $param = $r->allParams();
	  echo "my ID = ".$param["ID"];
	  $obj = DataObject::get("myProduct", $param["ID"]);
	  $obj->addNow();
	 }

## Controllers and actions

`[api:Controller]`s are the building blocks of your application.

**See:** The API documentation for `[api:Controller]`

You can access the following controller-method with /team/signup

	:::php
	class Team extends DataObject {}
	
	class Team_Controller extends Controller {
	  static $allowed_actions = array('signup');
	  public function signup($id, $otherId) {
	    return $this->renderWith('MyTemplate');
	  }
	}

## SSViewer template rendering

See [templates](/topics/templates) for information on the SSViewer template system.
