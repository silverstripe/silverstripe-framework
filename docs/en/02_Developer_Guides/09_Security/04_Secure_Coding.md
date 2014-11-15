# Security

## Introduction

This page details notes on how to ensure that we develop secure SilverStripe applications. 
See our "[Release Process](/misc/release-process#security-releases) on how to report security issues.

## SQL Injection

The [coding-conventions](/misc/coding-conventions) help guard against SQL injection attacks but still require developer
diligence: ensure that any variable you insert into a filter / sort / join clause has been escaped.

See [http://shiflett.org/articles/sql-injection](http://shiflett.org/articles/sql-injection).

### Automatic escaping

SilverStripe automatically escapes data in SQL statements wherever possible,
through database-specific methods (see `[api:Database->addslashes()]`).
For `[api:MySQLDatabase]`, this will be `[mysql_real_escape_string()](http://de3.php.net/mysql_real_escape_string)`.

*  Most `[api:DataList]` accessors (see escaping note in method documentation)
*  DataObject::get_by_id()
*  DataObject::update()
*  DataObject::castedUpdate()
*  DataObject->Property = 'val', DataObject->setField('Property','val')
*  DataObject::write()
*  DataList->byID()
*  Form->saveInto()
*  FormField->saveInto()
*  DBField->saveInto()

Data is escaped when saving back to the database, not when writing to object-properties.

Example:
	
	:::php
	// automatically escaped/quoted
	$members = Member::get()->filter('Name', $_GET['name']); 
	// automatically escaped/quoted
	$members = Member::get()->filter(array('Name' => $_GET['name'])); 
	// needs to be escaped/quoted manually
	$members = Member::get()->where(sprintf('"Name" = \'%s\'', Convert::raw2sql($_GET['name']))); 

<div class="warning" markdown='1'>
It is NOT good practice to "be sure" and convert the data passed to the functions below manually. This might
result in *double escaping* and alters the actually saved data (e.g. by adding slashes to your content).
</div>

### Manual escaping

As a rule of thumb, whenever you're creating raw queries (or just chunks of SQL), you need to take care of escaping
yourself. See [coding-conventions](/misc/coding-conventions) and [datamodel](/topics/datamodel) for ways to cast and convert
your data.

*  `SQLQuery`
*  `DataObject::buildSQL()`
*  `DB::query()`
*  `Director::urlParams()`
*  `Controller->requestParams`, `Controller->urlParams`
*  `SS_HTTPRequest` data
*  GET/POST data passed to a form method

Example:

	:::php
	class MyForm extends Form {
	  public function save($RAW_data, $form) {
	    $SQL_data = Convert::raw2sql($RAW_data); // works recursively on an array
	    $objs = Player::get()->where("Name = '{$SQL_data[name]}'");
	    // ...
	  }
	}


*  `FormField->Value()`
*  URLParams passed to a Controller-method

Example:

	:::php
	class MyController extends Controller {
	  private static $allowed_actions = array('myurlaction');
	  public function myurlaction($RAW_urlParams) {
	    $SQL_urlParams = Convert::raw2sql($RAW_urlParams); // works recursively on an array
	    $objs = Player::get()->where("Name = '{$SQL_data[OtherID]}'");
	    // ...
	  }
	}


As a rule of thumb, you should escape your data **as close to querying as possible**.
This means if you've got a chain of functions passing data through, escaping should happen at the end of the chain.

	:::php
	class MyController extends Controller {
	  /**
	   * @param array $RAW_data All names in an indexed array (not SQL-safe)
	   */
	  public function saveAllNames($RAW_data) {
	    // $SQL_data = Convert::raw2sql($RAW_data); // premature escaping
	    foreach($RAW_data as $item) $this->saveName($item);
	  }
	
	  public function saveName($RAW_name) {
	    $SQL_name = Convert::raw2sql($RAW_name);
	    DB::query("UPDATE Player SET Name = '{$SQL_name}'");
	  }
	}

This might not be applicable in all cases - especially if you are building an API thats likely to be customized. If
you're passing unescaped data, make sure to be explicit about it by writing *phpdoc*-documentation and *prefixing* your
variables ($RAW_data instead of $data).


## XSS (Cross-Site-Scripting)

SilverStripe helps you guard any output against clientside attacks initiated by malicious user input, commonly known as
XSS (Cross-Site-Scripting). With some basic guidelines, you can ensure your output is safe for a specific use case (e.g.
displaying a blog post in HTML from a trusted author, or escaping a search parameter from an untrusted visitor before
redisplaying it).

<div class="notice" markdown='1'>
Note: SilverStripe templates do not remove tags, please use [strip_tags()](http://php.net/strip_tags) for this purpose
or [sanitize](http://htmlpurifier.org/) it correctly.
</div>

See [http://shiflett.org/articles/foiling-cross-site-attacks](http://shiflett.org/articles/foiling-cross-site-attacks)
for in-depth information about "Cross-Site-Scripting".

### What if I can't trust my editors?

The default configuration of SilverStripe assumes some level of trust is given to your editors who have access
to the CMS. Though the HTML WYSIWYG editor is configured to provide some control over the HTML an editor provides,
this is not enforced server side, and so can be bypassed by a malicious editor. A editor that does so can use an
XSS attack against an admin to perform any administrative action.

If you can't trust your editors, SilverStripe must be configured to filter the content so that any javascript is
stripped out

To enable filtering, set the HtmlEditorField::$sanitise_server_side [configuration](/topics/configuration) property to
true, e.g.

	HtmlEditorField::config()->sanitise_server_side = true

The built in sanitiser enforces the TinyMCE whitelist rules on the server side, and is sufficient to eliminate the
most common XSS vectors.

However some subtle XSS attacks that exploit HTML parsing bugs need heavier filtering. For greater protection
you can install the [htmlpurifier](https://github.com/silverstripe-labs/silverstripe-htmlpurifier) module which
will replace the built in sanitiser with one that uses the [HTML Purifier](http://htmlpurifier.org/) library.
In both cases, you must ensure that you have not configured TinyMCE to explicitly allow script elements or other
javascript-specific attributes.

For `HTMLText` database fields which aren't edited through `HtmlEditorField`, you also
have the option to explicitly whitelist allowed tags in the field definition, e.g. `"MyField" => "HTMLText('meta','link')"`.
The `SiteTree.ExtraMeta` property uses this to limit allowed input.

##### But I also need my editors to provide javascript

It is not currently possible to allow editors to provide javascript content and yet still protect other users
from any malicious code within that javascript.

We recommend configuring [shortcodes](/reference/shortcodes) that can be used by editors in place of using javascript directly.

### Escaping model properties

`[api:SSViewer]` (the SilverStripe template engine) automatically takes care of escaping HTML tags from specific
object-properties by [casting](/topics/datamodel#casting) its string value into a `[api:DBField]` object.

PHP:

	:::php
	class MyObject extends DataObject {
	  private static $db = array(
	    'MyEscapedValue' => 'Text', // Example value: <b>not bold</b>
	    'MyUnescapedValue' => 'HTMLText' // Example value: <b>bold</b>
	  );
	}


Template:

	:::php
	<ul>
	  <li>$MyEscapedValue</li> // output: &lt;b&gt;not bold&lt;b&gt;
	  <li>$MyUnescapedValue</li> // output: <b>bold</b>
	</ul>


The example below assumes that data wasn't properly filtered when saving to the database, but are escaped before
outputting through SSViewer.

### Overriding default escaping in templates

You can force escaping on a casted value/object by using an [escape type](/topics/datamodel) method in your template, e.g.
"XML" or "ATT". 

Template (see above):

	:::php
	<ul>
	  // output: <a href="#" title="foo &amp; &#quot;bar&quot;">foo &amp; "bar"</a>
	  <li><a href="#" title="$Title.ATT">$Title</a></li>
	  <li>$MyEscapedValue</li> // output: &lt;b&gt;not bold&lt;b&gt;
	  <li>$MyUnescapedValue</li> // output: <b>bold</b>
	  <li>$MyUnescapedValue.XML</li> // output: &lt;b&gt;bold&lt;b&gt;
	</ul>


### Escaping custom attributes and getters

Every object attribute or getter method used for template purposes should have its escape type defined through the
static *$casting* array. Caution: Casting only applies when using values in a template, not in PHP.

PHP:

	:::php
	class MyObject extends DataObject {
		public $Title = '<b>not bold</b>'; // will be escaped due to Text casting
	     
		$casting = array(
			"Title" => "Text", // forcing a casting
			'TitleWithHTMLSuffix' => 'HTMLText' // optional, as HTMLText is the default casting
		);
		
		public function TitleWithHTMLSuffix($suffix) {
			// $this->Title is not casted in PHP
			return $this->Title . '<small>(' . $suffix. ')</small>';
		}
	}


Template:

	:::php
	<ul>
	  <li>$Title</li> // output: &lt;b&gt;not bold&lt;b&gt;
	  <li>$Title.RAW</li> // output: <b>not bold</b>
	  <li>$TitleWithHTMLSuffix</li> // output: <b>not bold</b>: <small>(...)</small>
	</ul>


Note: Avoid generating HTML by string concatenation in PHP wherever possible to minimize risk and separate your
presentation from business logic.

### Manual escaping in PHP

When using *customise()* or *renderWith()* calls in your controller, or otherwise forcing a custom context for your
template, you'll need to take care of casting and escaping yourself in PHP. 

The `[api:Convert]` class has utilities for this, mainly *Convert::raw2xml()* and *Convert::raw2att()* (which is
also used by *XML* and *ATT* in template code).

PHP:

	:::php
	class MyController extends Controller {
		private static $allowed_actions = array('search');
		public function search($request) {
			$htmlTitle = '<p>Your results for:' . Convert::raw2xml($request->getVar('Query')) . '</p>';
			return $this->customise(array(
				'Query' => Text::create($request->getVar('Query')),
				'HTMLTitle' => HTMLText::create($htmlTitle)
			));
		}
	}


Template:

	:::php
	<h2 title="Searching for $Query.ATT">$HTMLTitle</h2>


Whenever you insert a variable into an HTML attribute within a template, use $VarName.ATT, no not $VarName.

You can also use the built-in casting in PHP by using the *obj()* wrapper, see [datamodel](/topics/datamodel)  .

### Escaping URLs

Whenever you are generating a URL that contains querystring components based on user data, use urlencode() to escape the
user data, not *Convert::raw2att()*.  Use raw ampersands in your URL, and cast the URL as a "Text" DBField:

PHP:

	:::php
	class MyController extends Controller {
		private static $allowed_actions = array('search');
		public function search($request) {
			$rssRelativeLink = "/rss?Query=" . urlencode($_REQUEST['query']) . "&sortOrder=asc";
			$rssLink = Controller::join_links($this->Link(), $rssRelativeLink);
			return $this->customise(array(
				"RSSLink" => Text::create($rssLink),
			));
		}
	}


Template:

	:::php
	<a href="$RSSLink.ATT">RSS feed</a>


Some rules of thumb:

*  Don't concatenate URLs in a template.  It only works in extremely simple cases that usually contain bugs.
*  Use *Controller::join_links()* to concatenate URLs.  It deals with query strings and other such edge cases.

### Filtering incoming HTML from TinyMCE

In some cases you may be particularly concerned about which HTML elements are addable to Content via the CMS.
By default, although TinyMCE is configured to restrict some dangerous tags (such as `script` tags), this restriction
is not enforced server-side. A malicious user with write access to the CMS might create a specific request to avoid
these restrictions.

To enable server side filtering using the same whitelisting controls as TinyMCE, set the
HtmlEditorField::$sanitise_server_side config property to true.

## Cross-Site Request Forgery (CSRF)

SilverStripe has built-in countermeasures against [CSRF](http://shiflett.org/articles/cross-site-request-forgeries) identity theft for all form submissions. A form object
will automatically contain a `SecurityID` parameter which is generated as a secure hash on the server, connected to the
currently active session of the user. If this form is submitted without this parameter, or if the parameter doesn't
match the hash stored in the users session, the request is discarded.
You can disable this behaviour through `[api:Form->disableSecurityToken()]`.

It is also recommended to limit form submissions to the intended HTTP verb (mostly `GET` or `POST`)
through `[api:Form->setStrictFormMethodCheck()]`. 

Sometimes you need to handle state-changing HTTP submissions which aren't handled through
SilverStripe's form system. In this case, you can also check the current HTTP request
for a valid token through `[api:SecurityToken::checkRequest()]`.

## Casting user input

When working with `$_GET`, `$_POST` or `Director::urlParams` variables, and you know your variable has to be of a
certain type, like an integer, then it's essential to cast it as one. *Why?* To be sure that any processing of your
given variable is done safely, with the assumption that it's an integer.

To cast the variable as an integer, place `(int)` or `(integer)` before the variable.

For example: a page with the URL paramaters *mysite.com/home/add/1* requires that ''Director::urlParams['ID']'' be an
integer. We cast it by adding `(int)` - ''(int)Director::urlParams['ID']''. If a value other than an integer is
passed, such as *mysite.com/home/add/dfsdfdsfd*, then it returns 0.

Below is an example with different ways you would use this casting technique:

	:::php
	public function CaseStudies() {
	
	   // cast an ID from URL parameters e.g. (mysite.com/home/action/ID)
	   $anotherID = (int)Director::urlParam['ID'];
	
	   // perform a calculation, the prerequisite being $anotherID must be an integer
	   $calc = $anotherID + (5 - 2) / 2;
	
	   // cast the 'category' GET variable as an integer
	   $categoryID = (int)$_GET['category'];
	
	   // perform a byID(), which ensures the ID is an integer before querying
	   return CaseStudy::get()->byID($categoryID);
	}


The same technique can be employed anywhere in your PHP code you know something must be of a certain type. A list of PHP
cast types can be found here:

*  `(int)`, `(integer)` - cast to integer
*  `(bool)`, `(boolean)` - cast to boolean
*  `(float)`, `(double)`, `(real)` - cast to float
*  `(string)` - cast to string
*  `(array)` - cast to array
*  `(object)` - cast to object

Note that there is also a 'SilverStripe' way of casting fields on a class, this is a different type of casting to the
standard PHP way. See [casting](/topics/datamodel#casting).




## Filesystem

### Don't allow script-execution in /assets

As all uploaded files are stored by default on the /assets-directory, you should disallow script-execution for this
folder. This is just an additional security-measure to making sure you avoid directory-traversal, check for filesize and
disallow certain filetypes.

Example configuration for Apache2:

	<VirtualHost *:80>
	  ...
	  <LocationMatch assets/>
	    php_flag engine off
	    Options -ExecCGI -Includes -Indexes
	  </LocationMatch>
	</VirtualHost>


If you are using shared hosting or in a situation where you cannot alter your Vhost definitions, you can use a .htaccess
file in the assets directory.  This requires PHP to be loaded as an Apache module (not CGI or FastCGI).

**/assets/.htaccess**

	php_flag engine off
	Options -ExecCGI -Includes -Indexes 

### Don't allow access to YAML files

YAML files are often used to store sensitive or semi-sensitive data for use by 
SilverStripe, such as configuration files. We block access to any files
with a `.yml` or `.yaml` extension through the default web server rewriting rules.
If you need users to access files with this extension,
you can bypass the rules for a specific directory.
Here's an example for a `.htaccess` file used by the Apache web server:

	<Files *.yml>
		Order allow,deny
		Allow from all
	</Files>

## Passwords

SilverStripe stores passwords with a strong hashing algorithm (blowfish) by default
(see [api:PasswordEncryptor]). It adds randomness to these hashes via
salt values generated with the strongest entropy generators available on the platform
(see [api:RandomGenerator]). This prevents brute force attacks with
[Rainbow tables](http://en.wikipedia.org/wiki/Rainbow_table).

Strong passwords are a crucial part of any system security.
So in addition to storing the password in a secure fashion,
you can also enforce specific password policies by configuring
a [api:PasswordValidator]:

	:::php
	$validator = new PasswordValidator();
	$validator->minLength(7);
	$validator->checkHistoricalPasswords(6);
	$validator->characterStrength(3, array("lowercase", "uppercase", "digits", "punctuation"));
	Member::set_password_validator($validator);

In addition, you can tighten password security with the following configuration settings:

 * `Member.password_expiry_days`: Set the number of days that a password should be valid for.
 * `Member.lock_out_after_incorrect_logins`: Number of incorrect logins after which
    the user is blocked from further attempts for the timespan defined in `$lock_out_delay_mins`
 * `Member.lock_out_delay_mins`: Minutes of enforced lockout after incorrect password attempts.
 		Only applies if `lock_out_after_incorrect_logins` is greater than 0.
 * `Security.remember_username`: Set to false to disable autocomplete on login form

## Clickjacking: Prevent iframe Inclusion

"[Clickjacking](http://en.wikipedia.org/wiki/Clickjacking)"  is a malicious technique
where a web user is tricked into clicking on hidden interface elements, which can
lead to the attacker gaining access to user data or taking control of the website behaviour.

You can signal to browsers that the current response isn't allowed to be 
included in HTML "frame" or "iframe" elements, and thereby prevent the most common
attack vector. This is done through a HTTP header, which is usually added in your
controller's `init()` method:

	:::php
	class MyController extends Controller {
		public function init() {
			parent::init();

			$this->response->addHeader('X-Frame-Options', 'SAMEORIGIN');
		}
	}
	

This is a recommended option to secure any controller which displays
or submits sensitive user input, and is enabled by default in all CMS controllers,
as well as the login form.

##  Related

 * [http://silverstripe.org/security-releases/](http://silverstripe.org/security-releases/)
 * [Best-practices for securing MySQL (securityfocus.com)](http://www.securityfocus.com/infocus/1726)
