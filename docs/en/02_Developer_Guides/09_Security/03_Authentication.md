title: Authentication
summary: Explains SilverStripe's Authentication options and custom authenticators. 

# Authentication

By default, SilverStripe provides a `[api:MemberAuthenticator]` class which hooks into its own internal
authentication system.

The main login system uses these controllers to handle the various security requests:

`[api:Security]` Which is the controller which handles most front-end security requests, including 
	Logging in, logging out, resetting password, or changing password. This class also provides an interface
	to allow configured `[api:Authenticator]` classes to each display a custom login form.
	
`[api:CMSSecurity]` Which is the controller which handles security requests within the CMS, and allows
	users to re-login without leaving the CMS.

## Member Authentication

The default member authentication system is implemented in the following classes:

`[api:MemberAuthenticator]` Which is the default member authentication implementation. This uses the email
	and password stored internally for each member to authenticate them.
	
`[api:MemberLoginForm]` Is the default form used by `MemberAuthenticator`, and is displayed on the public site
	at the url `Security/login` by default.
	
`[api:CMSMemberLoginForm]` Is the secondary form used by `MemberAuthenticator`, and will be displayed to the
	user within the CMS any time their session expires or they are logged out via an action. This form is
	presented via a popup dialog, and can be used to re-authenticate that user automatically without them having
	to lose their workspace. E.g. if editing a form, the user can login and continue to publish their content.

## Custom Authentication

Additional authentication methods (oauth, etc) can be implemented by creating custom implementations of each of the
following base classes:

`[api:Authenticator]` The base class for authentication systems. This class also acts as the factory
	to generate various login forms for parts of the system. If an authenticator supports in-cms
	reauthentication then it will be necessary to override the `supports_cms` and `get_cms_login_form` methods.

`[api:LoginForm]` which is the base class for a login form which links to a specific authenticator. At the very
	least, it will be necessary to implement a form class which provides a default login interface. If in-cms
	re-authentication is desired, then a specialised subclass of this method may be necessary. For example, this form
	could be extended to require confirmation of username as well as password.

## Default Admin

When a new SilverStripe site is created for the first time, it may be necessary to create a default admin to provide
CMS access for the first time. SilverStripe provides a default admin configuration system, which allows a username
and password to be configured for a single special user outside of the normal membership system.

It is advisable to configure this user in your `_ss_environment.php` file outside of the web root, as below:

	:::php
	// Configure a default username and password to access the CMS on all sites in this environment.
	define('SS_DEFAULT_ADMIN_USERNAME', 'admin');
	define('SS_DEFAULT_ADMIN_PASSWORD', 'password');

When a user logs in with these credentials, then a `[api:Member]` with the Email 'admin' will be generated in
the database, but without any password information. This means that the password can be reset or changed by simply
updating the `_ss_environment.php` file.
