# Security

## Introduction

This page details notes on how to ensure that we develop secure SilverStripe applications. 
See our "[Release Process](/contributing/release_process#security-releases) on how to report security issues.

## SQL Injection

The [coding-conventions](/contributing/coding_conventions) help guard against SQL injection attacks but still require developer
diligence: ensure that any variable you insert into a filter / sort / join clause is either parameterised, or has been
escaped.

See [http://shiflett.org/articles/sql-injection](http://shiflett.org/articles/sql-injection).

### Parameterised queries

Parameterised queries, or prepared statements, allow the logic around the query and its structure to be separated from
the parameters passed in to be executed. Many DB adaptors support these as standard including [PDO](http://php.net/manual/en/pdo.prepare.php),
[MySQL](http://php.net/manual/en/mysqli.prepare.php), [SQL Server](http://php.net/manual/en/function.sqlsrv-prepare.php),
[SQLite](http://php.net/manual/en/sqlite3.prepare.php), and [PostgreSQL](http://php.net/manual/en/function.pg-prepare.php).

The use of parameterised queries whenever possible will safeguard your code in most cases, but care
must still be taken when working with literal values or table/column identifiers that may 
come from user input.

Example:

```php
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLSelect;

$records = DB::prepared_query('SELECT * FROM "MyClass" WHERE "ID" = ?', [3]);
$records = MyClass::get()->where(['"ID" = ?' => 3]);
$records = MyClass::get()->where(['"ID"' => 3]);
$records = DataObject::get_by_id('MyClass', 3);
$records = DataObject::get_one('MyClass', ['"ID" = ?' => 3]);
$records = MyClass::get()->byID(3);
$records = SQLSelect::create()->addWhere(['"ID"' => 3])->execute();
```

Parameterised updates and inserts are also supported, but the syntax is a little different


```php
use SilverStripe\ORM\Queries\SQLInsert;
use SilverStripe\ORM\DB;

SQLInsert::create('"MyClass"')
    ->assign('"Name"', 'Daniel')
    ->addAssignments([
        '"Position"' => 'Accountant',
        '"Age"' => [
            'GREATEST(0,?,?)' => [24, 28]
        ]
    ])
    ->assignSQL('"Created"', 'NOW()')
    ->execute();
DB::prepared_query(
    'INSERT INTO "MyClass" ("Name", "Position", "Age", "Created") VALUES(?, ?, GREATEST(0,?,?), NOW())'
    ['Daniel', 'Accountant', 24, 28]
);
```

### Automatic escaping

SilverStripe internally will use parameterised queries in SQL statements wherever possible.

If necessary Silverstripe performs any required escaping through database-specific methods (see [Database::addslashes()](api:SilverStripe\ORM\Connect\Database::addslashes())).
For [MySQLDatabase](api:SilverStripe\ORM\Connect\MySQLDatabase), this will be `[mysql_real_escape_string()](http://de3.php.net/mysql_real_escape_string)`.

*  Most [DataList](api:SilverStripe\ORM\DataList) accessors (see escaping note in method documentation)
*  DataObject::get_by_id()
*  DataObject::update()
*  DataObject::castedUpdate()
*  DataObject->Property = 'val', DataObject->setField('Property','val')
*  DataObject::write()
*  DataList->byID()
*  Form->saveInto()
*  FormField->saveInto()
*  DBField->saveInto()

Data is not escaped when writing to object-properties, as inserts and updates are normally
handled via prepared statements.

Example:

```php
use SilverStripe\Security\Member;

// automatically escaped/quoted
$members = Member::get()->filter('Name', $_GET['name']); 
// automatically escaped/quoted
$members = Member::get()->filter(['Name' => $_GET['name']]); 
// parameterised condition
$members = Member::get()->where(['"Name" = ?' => $_GET['name']]); 
// needs to be escaped and quoted manually (note raw2sql called with the $quote parameter set to true)
$members = Member::get()->where(sprintf('"Name" = %s', Convert::raw2sql($_GET['name'], true))); 
```

<div class="warning" markdown='1'>
It is NOT good practice to "be sure" and convert the data passed to the functions above manually. This might
result in *double escaping* and alters the actually saved data (e.g. by adding slashes to your content).
</div>

### Manual escaping

As a rule of thumb, whenever you're creating SQL queries (or just chunks of SQL) you should use parameterisation,
but there may be cases where you need to take care of escaping yourself. See [coding-conventions](/getting_started/coding-conventions)
and [datamodel](/developer_guides/model) for ways to parameterise, cast, and convert your data.

*  `SQLSelect`
*  `DB::query()`
*  `DB::prepared_query()`
*  `Director::urlParams()`
*  `Controller->requestParams`, `Controller->urlParams`
*  `HTTPRequest` data
*  GET/POST data passed to a form method

Example:


```php
use SilverStripe\Core\Convert;
use SilverStripe\Forms\Form;

class MyForm extends Form 
{
    public function save($RAW_data, $form) 
    {
        // Pass true as the second parameter of raw2sql to quote the value safely
        $SQL_data = Convert::raw2sql($RAW_data, true); // works recursively on an array
        $objs = Player::get()->where("Name = " . $SQL_data['name']);
        // ...
    }
}

```

*  `FormField->Value()`
*  URLParams passed to a Controller-method

Example:

```php
use SilverStripe\Core\Convert;
use SilverStripe\Control\Controller;

class MyController extends Controller 
{
    private static $allowed_actions = ['myurlaction'];
    public function myurlaction($RAW_urlParams) 
    {
        // Pass true as the second parameter of raw2sql to quote the value safely
        $SQL_urlParams = Convert::raw2sql($RAW_urlParams, true); // works recursively on an array
        $objs = Player::get()->where("Name = " . $SQL_data['OtherID']);
        // ...
    }
}
```

As a rule of thumb, you should escape your data **as close to querying as possible**
(or preferably, use parameterised queries). This means if you've got a chain of functions
passing data through, escaping should happen at the end of the chain.


```php
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DB;
use SilverStripe\Control\Controller;

class MyController extends Controller 
{
    /**
    * @param array $RAW_data All names in an indexed array (not SQL-safe)
    */
    public function saveAllNames($RAW_data) 
    {
        // $SQL_data = Convert::raw2sql($RAW_data); // premature escaping
        foreach($RAW_data as $item) $this->saveName($item);
    }

    public function saveName($RAW_name) 
    {
        $SQL_name = Convert::raw2sql($RAW_name, true);
        DB::query("UPDATE Player SET Name = {$SQL_name}");
    }
}
```

This might not be applicable in all cases - especially if you are building an API thats likely to be customised. If
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

To enable filtering, set the HtmlEditorField::$sanitise_server_side [configuration](/developer_guides/configuration/configuration) property to
true, e.g.

```
HtmlEditorField::config()->sanitise_server_side = true
```

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

We recommend configuring [shortcodes](/developer_guides/extending/shortcodes) that can be used by editors in place of using javascript directly.

### Escaping model properties

[SSViewer](api:SilverStripe\View\SSViewer) (the SilverStripe template engine) automatically takes care of escaping HTML tags from specific
object-properties by [casting](/developer_guides/model/data_types_and_casting) its string value into a [DBField](api:SilverStripe\ORM\FieldType\DBField) object.

PHP:


```php
use SilverStripe\ORM\DataObject;

class MyObject extends DataObject 
{
    private static $db = [
        'MyEscapedValue' => 'Text', // Example value: <b>not bold</b>
        'MyUnescapedValue' => 'HTMLText' // Example value: <b>bold</b>
    ];
}

```

Template:


```php
<ul>
    <li>$MyEscapedValue</li> // output: &lt;b&gt;not bold&lt;b&gt;
    <li>$MyUnescapedValue</li> // output: <b>bold</b>
</ul>
```

The example below assumes that data wasn't properly filtered when saving to the database, but are escaped before
outputting through SSViewer.

### Overriding default escaping in templates

You can force escaping on a casted value/object by using an [escape type](/developer_guides/model/data_types_and_casting) method in your template, e.g.
"XML" or "ATT". 

Template (see above):


```php
<ul>
    // output: <a href="#" title="foo &amp; &#quot;bar&quot;">foo &amp; "bar"</a>
    <li><a href="#" title="$Title.ATT">$Title</a></li>
    <li>$MyEscapedValue</li> // output: &lt;b&gt;not bold&lt;b&gt;
    <li>$MyUnescapedValue</li> // output: <b>bold</b>
    <li>$MyUnescapedValue.XML</li> // output: &lt;b&gt;bold&lt;b&gt;
</ul>
```

### Escaping custom attributes and getters

Every object attribute or getter method used for template purposes should have its escape type defined through the
static *$casting* array. Caution: Casting only applies when using values in a template, not in PHP.

PHP:

```php
use SilverStripe\ORM\DataObject;

class MyObject extends DataObject 
{
    public $Title = '<b>not bold</b>'; // will be escaped due to Text casting
     
    $casting = [
        "Title" => "Text", // forcing a casting
        'TitleWithHTMLSuffix' => 'HTMLText' // optional, as HTMLText is the default casting
    ];
    
    public function TitleWithHTMLSuffix($suffix) 
    {
        // $this->Title is not casted in PHP
        return $this->Title . '<small>(' . $suffix. ')</small>';
    }
}
```

Template:


```php
<ul>
    <li>$Title</li> // output: &lt;b&gt;not bold&lt;b&gt;
    <li>$Title.RAW</li> // output: <b>not bold</b>
    <li>$TitleWithHTMLSuffix</li> // output: <b>not bold</b>: <small>(...)</small>
</ul>
```

Note: Avoid generating HTML by string concatenation in PHP wherever possible to minimize risk and separate your
presentation from business logic.

### Manual escaping in PHP

When using *customise()* or *renderWith()* calls in your controller, or otherwise forcing a custom context for your
template, you'll need to take care of casting and escaping yourself in PHP. 

The [Convert](api:SilverStripe\Core\Convert) class has utilities for this, mainly *Convert::raw2xml()* and *Convert::raw2att()* (which is
also used by *XML* and *ATT* in template code).

PHP:

```php
use SilverStripe\Core\Convert;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\ORM\FieldType\DBHTMLText;

class MyController extends Controller 
{
    private static $allowed_actions = ['search'];
    public function search($request) 
    {
        $htmlTitle = '<p>Your results for:' . Convert::raw2xml($request->getVar('Query')) . '</p>';
        return $this->customise([
            'Query' => DBText::create($request->getVar('Query')),
            'HTMLTitle' => DBHTMLText::create($htmlTitle)
        ]);
    }
}
```

Template:

```php
<h2 title="Searching for $Query.ATT">$HTMLTitle</h2>
```

Whenever you insert a variable into an HTML attribute within a template, use $VarName.ATT, no not $VarName.

You can also use the built-in casting in PHP by using the *obj()* wrapper, see [datamodel](/developer_guides/model/data_types_and_casting).

### Escaping URLs

Whenever you are generating a URL that contains querystring components based on user data, use urlencode() to escape the
user data, not *Convert::raw2att()*.  Use raw ampersands in your URL, and cast the URL as a "Text" DBField:

PHP:


```php
use SilverStripe\Control\Controller;
use SilverStripe\ORM\FieldType\DBText;

class MyController extends Controller 
{
    private static $allowed_actions = ['search'];
    public function search($request) 
    {
        $rssRelativeLink = "/rss?Query=" . urlencode($_REQUEST['query']) . "&sortOrder=asc";
        $rssLink = Controller::join_links($this->Link(), $rssRelativeLink);
        return $this->customise([
            "RSSLink" => DBText::create($rssLink),
        ]);
    }
}
```

Template:


```php
<a href="$RSSLink.ATT">RSS feed</a>
```

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
You can disable this behaviour through [Form::disableSecurityToken()](api:SilverStripe\Forms\Form::disableSecurityToken()).

It is also recommended to limit form submissions to the intended HTTP verb (mostly `GET` or `POST`)
through [Form::setStrictFormMethodCheck()](api:SilverStripe\Forms\Form::setStrictFormMethodCheck()). 

Sometimes you need to handle state-changing HTTP submissions which aren't handled through
SilverStripe's form system. In this case, you can also check the current HTTP request
for a valid token through [SecurityToken::checkRequest()](api:SilverStripe\Security\SecurityToken::checkRequest()).

## Casting user input

When working with `$_GET`, `$_POST` or `Director::urlParams` variables, and you know your variable has to be of a
certain type, like an integer, then it's essential to cast it as one. *Why?* To be sure that any processing of your
given variable is done safely, with the assumption that it's an integer.

To cast the variable as an integer, place `(int)` or `(integer)` before the variable.

For example: a page with the URL paramaters *myapp.com/home/add/1* requires that ''Director::urlParams['ID']'' be an
integer. We cast it by adding `(int)` - ''(int)Director::urlParams['ID']''. If a value other than an integer is
passed, such as *myapp.com/home/add/dfsdfdsfd*, then it returns 0.

Below is an example with different ways you would use this casting technique:


```php
public function CaseStudies() 
{

    // cast an ID from URL parameters e.g. (myapp.com/home/action/ID)
    $anotherID = (int)Director::urlParam['ID'];
    
    // perform a calculation, the prerequisite being $anotherID must be an integer
    $calc = $anotherID + (5 - 2) / 2;
    
    // cast the 'category' GET variable as an integer
    $categoryID = (int)$_GET['category'];
    
    // perform a byID(), which ensures the ID is an integer before querying
    return CaseStudy::get()->byID($categoryID);
}
```

The same technique can be employed anywhere in your PHP code you know something must be of a certain type. A list of PHP
cast types can be found here:

*  `(int)`, `(integer)` - cast to integer
*  `(bool)`, `(boolean)` - cast to boolean
*  `(float)`, `(double)`, `(real)` - cast to float
*  `(string)` - cast to string
*  `(array)` - cast to array
*  `(object)` - cast to object

Note that there is also a 'SilverStripe' way of casting fields on a class, this is a different type of casting to the
standard PHP way. See [casting](/developer_guides/model/data_types_and_casting).


## Filesystem

### Don't script-execution in /assets

Please refer to the article on [file security](/developer_guides/files/file_security)
for instructions on how to secure the assets folder against malicious script execution.

### Don't allow access to YAML files

YAML files are often used to store sensitive or semi-sensitive data for use by 
SilverStripe, such as configuration files. We block access to any files
with a `.yml` or `.yaml` extension through the default web server rewriting rules.
If you need users to access files with this extension,
you can bypass the rules for a specific directory.
Here's an example for a `.htaccess` file used by the Apache web server:

```
<Files *.yml>
    Order allow,deny
    Allow from all
</Files>
```

### User uploaded files

Certain file types are by default excluded from user upload. html, xhtml, htm, and xml files may have embedded,
or contain links to, external resources or scripts that may hijack browser sessions and impersonate that user.
Even if the uploader of this content may be a trusted user, there is no safeguard against these users being
deceived by the content source.

Flash files (swf) are also prone to a variety of security vulnerabilities of their own, and thus by default are
disabled from file upload. As a standard practice, any users wishing to allow flash upload to their sites should
take the following precautions:

 * Only allow flash uploads from trusted sources, preferably those with available source.
 * Make use of the [AllowScriptAccess](http://helpx.adobe.com/flash/kb/control-access-scripts-host-web.html)
   parameter to ensure that any embedded Flash file is isolated from its environments scripts. In an ideal
   situation, all flash content would be served from another domain, and this value is set to "sameDomain". If this
   is not feasible, this should be set to "never". For trusted flash files you may set this to "sameDomain" without
   an isolated domain name, but do so at your own risk.
 * Take note of any regional cookie legislation that may affect your users. See
   [Cookie Law and Flash Cookies](http://eucookiedirective.com/cookie-law-and-flash-cookies/).

See [the Adobe Flash security page](http://www.adobe.com/devnet/flashplayer/security.html) for more information.

ADMIN privileged users may be allowed to override the above upload restrictions if the
`File.apply_restrictions_to_admin` config is set to false. By default this is true, which enforces these
restrictions globally.

Additionally, if certain file uploads should be made available to non-privileged users, you can add them to the
list of allowed extensions by adding these to the `File.allowed_extensions` config.

## Passwords

SilverStripe stores passwords with a strong hashing algorithm (blowfish) by default
(see [PasswordEncryptor](api:SilverStripe\Security\PasswordEncryptor)). It adds randomness to these hashes via
salt values generated with the strongest entropy generators available on the platform
(see [RandomGenerator](api:SilverStripe\Security\RandomGenerator)). This prevents brute force attacks with
[Rainbow tables](http://en.wikipedia.org/wiki/Rainbow_table).

Strong passwords are a crucial part of any system security.
So in addition to storing the password in a secure fashion,
you can also enforce specific password policies by configuring
a [PasswordValidator](api:SilverStripe\Security\PasswordValidator):


```php
use SilverStripe\Security\Member;
use SilverStripe\Security\PasswordValidator;

$validator = new PasswordValidator();
$validator->minLength(7);
$validator->checkHistoricalPasswords(6);
$validator->characterStrength(3, ["lowercase", "uppercase", "digits", "punctuation"]);
Member::set_password_validator($validator);
```

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


```php
use SilverStripe\Control\Controller;

class MyController extends Controller 
{
    public function init() 
    {
        parent::init();
        $this->getResponse()->addHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
```

This is a recommended option to secure any controller which displays
or submits sensitive user input, and is enabled by default in all CMS controllers,
as well as the login form.

## Request hostname forgery

To prevent a forged hostname appearing being used by the application, SilverStripe
allows the configure of a whitelist of hosts that are allowed to access the system. By defining
this whitelist in your `.env` file, any request presenting a `Host` header that is
_not_ in this list will be blocked with a HTTP 400 error:

```
SS_ALLOWED_HOSTS="www.myapp.com,myapp.com,subdomain.myapp.com"
```

Please note that if this configuration is defined, you _must_ include _all_ subdomains (eg www.)
that will be accessing the site.

When SilverStripe is run behind a reverse proxy, it's normally necessary for this proxy to
use the `X-Forwarded-Host` request header to tell the webserver which hostname was originally
requested. However, when SilverStripe is not run behind a proxy, this header can still be
used by attackers to fool the server into mistaking its own identity.

The risk of this kind of attack causing damage is especially high on sites which utilise caching
mechanisms, as rewritten urls could persist between requests in order to misdirect other users
into visiting external sites.

In order to prevent this kind of attack, it's necessary to whitelist trusted proxy
server IPs using the SS_TRUSTED_PROXY_IPS define in your `.env`.

```
SS_TRUSTED_PROXY_IPS="127.0.0.1,192.168.0.1"
```

If you wish to change the headers that are used to find the proxy information, you should reconfigure the
TrustedProxyMiddleware service:


```yml
SilverStripe\Control\TrustedProxyMiddleware:
  properties:
    ProxyHostHeaders: X-Forwarded-Host
    ProxySchemeHeaders: X-Forwarded-Protocol
    ProxyIPHeaders: X-Forwarded-Ip
```

```
SS_TRUSTED_PROXY_HOST_HEADER="HTTP_X_FORWARDED_HOST"
SS_TRUSTED_PROXY_IP_HEADER="HTTP_X_FORWARDED_FOR"
SS_TRUSTED_PROXY_PROTOCOL_HEADER="HTTP_X_FORWARDED_PROTOCOL"
```

At the same time, you'll also need to define which headers you trust from these proxy IPs. Since there are multiple ways through which proxies can pass through HTTP information on the original hostname, IP and protocol, these values need to be adjusted for your specific proxy. The header names match their equivalent `$_SERVER` values.

If there is no proxy server, 'none' can be used to distrust all clients.
If only trusted servers will make requests then you can use '*' to trust all clients.
Otherwise a comma separated list of individual IP addresses should be declared.

This behaviour is enabled whenever `SS_TRUSTED_PROXY_IPS` is defined, or if the
`BlockUntrustedIPs` environment variable is declared. It is advisable to include the
following in your .htaccess to ensure this behaviour is activated.

```
<IfModule mod_env.c>
    # Ensure that X-Forwarded-Host is only allowed to determine the request
    # hostname for servers ips defined by SS_TRUSTED_PROXY_IPS in your .env
    # Note that in a future release this setting will be always on.
    SetEnv BlockUntrustedIPs true
</IfModule>
```

In a future release this behaviour will be changed to be on by default, and this environment
variable will be no longer necessary, thus it will be necessary to always set
`SS_TRUSTED_PROXY_IPS` if using a proxy.

## Secure Sessions, Cookies and TLS (HTTPS)

SilverStripe recommends the use of TLS(HTTPS) for your application, and you can easily force the use through the 
director function `forceSSL()` 

```php
use SilverStripe\Control\Director;

if (!Director::isDev()) {
    Director::forceSSL();
}
```

Forcing HTTPS so requires a certificate to be purchased or obtained through a vendor such as 
[lets encrypt](https://letsencrypt.org/) and configured on your web server.

Note that by default enabling SSL will also enable `CanonicalURLMiddleware::forceBasicAuthToSSL` which will detect
and automatically redirect any requests with basic authentication headers to first be served over HTTPS. You can
disable this behaviour using `CanonicalURLMiddleware::singleton()->setForceBasicAuthToSSL(false)`, or via Injector
configuration in YAML.

We also want to ensure cookies are not shared between secure and non-secure sessions, so we must tell SilverStripe to 
use a [secure session](https://docs.silverstripe.org/en/3/developer_guides/cookies_and_sessions/sessions/#secure-session-cookie). 
To do this, you may set the `cookie_secure` parameter to `true` in your `config.yml` for `Session`

```yml
SilverStripe\Control\Session:
  cookie_secure: true
```

For other cookies set by your application we should also ensure the users are provided with secure cookies by setting 
the "Secure" and "HTTPOnly" flags. These flags prevent them from being stolen by an attacker through javascript. 

 - The `Secure` cookie flag instructs the browser not to send the cookie over an insecure HTTP connection. If this 
flag is not present, the browser will send the cookie even if HTTPS is not in use, which means it is transmitted in 
clear text and can be intercepted and stolen by an attacker who is listening on the network.

- The `HTTPOnly` flag lets the browser know whether or not a cookie should be accessible by client-side JavaScript 
code. It is best practice to set this flag unless the application is known to use JavaScript to access these cookies 
as this prevents an attacker who achieves cross-site scripting from accessing these cookies.

```php
use SilverStripe\Control\Cookie;

Cookie::set('cookie-name', 'chocolate-chip', $expiry = 30, $path = null, $domain = null, $secure = true, 
    $httpOnly = false
);
```

## Security Headers

In addition to forcing HTTPS browsers can support additional security headers which can only allow access to a website 
via a secure connection. As browsers increasingly provide negative feedback regarding unencrypted HTTP connections, 
ensuring an HTTPS connection will provide a better and more secure user experience.  

- The `Strict-Transport-Security` header instructs the browser to record that the website and assets on that website 
MUST use a secure connection. This prevents websites from becoming insecure in the future from stray absolute links 
or references without https from external sites. Check if your browser supports [HSTS](https://hsts.badssl.com/)
- `max-age` can be configured to anything in seconds: `max-age=31536000` (1 year), for roll out, consider something 
  lower
- `includeSubDomains` to ensure all present and future sub domains will also be HTTPS

For sensitive pages, such as members areas, or places where sensitive information is present, adding cache control
 headers can explicitly instruct browsers not to keep a local cached copy of content and can prevent content from 
 being cached throughout the infrastructure (e.g. Proxy, caching layers, WAF etc). 
 
- The headers `Cache-control: no-store` and `Pragma: no-cache` along with expiry headers of `Expires: <current date>` 
and `Date: <current date>` will ensure that sensitive content is not stored locally or able to be retrieved by 
unauthorised local persons. SilverStripe adds the current date for every request, and we can add the other cache 
 headers to the request for our secure controllers:
 
```php
use SilverStripe\Control\HTTP;
use SilverStripe\Control\Controller;

class MySecureController extends Controller 
{
    
    public function init() 
    {
        parent::init();
        
        // Add cache headers to ensure sensitive content isn't cached.
        $this->response->addHeader('Cache-Control', 'max-age=0, must-revalidate, no-transform');
        $this->response->addHeader('Pragma', 'no-cache'); // for HTTP 1.0 support

        HTTP::set_cache_age(0);
        HTTP::add_cache_headers($this->response);
        
        // Add HSTS header to force TLS for document content
        $this->response->addHeader('Strict-Transport-Security', 'max-age=86400; includeSubDomains');
    }
}
```

## HTTP Caching Headers

Caching is hard. If you get it wrong, private or draft content might leak
to unauthenticated users. We have created an abstraction which allows you to express
your intent around HTTP caching without worrying too much about the details.
See [/developer_guides/performances/http_cache_headers](Developer Guides > Performance > HTTP Cache Headers)
for details on how to apply caching safely, and read Google's
[Web Fundamentals on Caching](https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching).

##  Related

 * [http://silverstripe.org/security-releases/](http://silverstripe.org/security-releases/)
 * [Best-practices for securing MySQL (securityfocus.com)](http://www.securityfocus.com/infocus/1726)
