title: Authentication
summary: Explains SilverStripe's Authentication options and custom authenticators. 

# Authentication

By default, SilverStripe provides a [MemberAuthenticator](api:SilverStripe\Security\MemberAuthenticator\MemberAuthenticator) class which hooks into its own internal
authentication system.

The main login system uses these controllers to handle the various security requests:

[Security](api:SilverStripe\Security\Security) - Which is the controller which handles most front-end security requests, including logging in, logging out, resetting password, or changing password. This class also provides an interface to allow configured [Authenticator](api:SilverStripe\Security\Authenticator) classes to each display a custom login form.	

[CMSSecurity](api:SilverStripe\Security\CMSSecurity) - Which is the controller which handles security requests within the CMS, and allows users to re-login without leaving the CMS.

## Member Authentication

The default member authentication system is implemented in the following classes:

[MemberAuthenticator](api:SilverStripe\Security\MemberAuthenticator) - Which is the default member authentication implementation. This uses the email and password stored internally for each member to authenticate them.	

[MemberLoginForm](api:SilverStripe\Security\MemberAuthenticator\MemberLoginForm) - Is the default form used by `MemberAuthenticator`, and is displayed on the public site at the url `Security/login` by default.

[CMSMemberLoginForm](api:SilverStripe\Security\MemberAuthenticator\CMSMemberLoginForm) - Is the secondary form used by `MemberAuthenticator`, and will be displayed to the	user within the CMS any time their session expires or they are logged out via an action. This form is	presented via a popup dialog, and can be used to re-authenticate that user automatically without them having	to lose their workspace. E.g. if editing a form, the user can login and continue to publish their content.

## Custom Authentication

Additional authentication methods (oauth, etc) can be implemented by creating custom implementations of each of the
following base classes:

[Authenticator](api:SilverStripe\Security\Authenticator) - The base class for authentication systems. This class also acts as the factory to generate various login forms for parts of the system. If an authenticator supports in-cms	reauthentication then it will be necessary to override the `supports_cms` and `get_cms_login_form` methods.

[LoginForm](api:SilverStripe\Security\LoginForm) - which is the base class for a login form which links to a specific authenticator. At the very least, it will be necessary to implement a form class which provides a default login interface. If in-cms re-authentication is desired, then a specialised subclass of this method may be necessary. For example, this form could be extended to require confirmation of username as well as password.

## Default Admin

When a new SilverStripe site is created for the first time, it may be necessary to create a default admin to provide
CMS access for the first time. SilverStripe provides a default admin configuration system, which allows a username
and password to be configured for a single special user outside of the normal membership system.

It is advisable to configure this user in your `.env` file inside of the web root, as below:
```
    # Configure a default username and password to access the CMS on all sites in this environment.
    SS_DEFAULT_ADMIN_USERNAME="admin"
    SS_DEFAULT_ADMIN_PASSWORD="password"
```
When a user logs in with these credentials, then a [Member](api:SilverStripe\Security\Member) with the Email 'admin' will be generated in
the database, but without any password information. This means that the password can be reset or changed by simply
updating the `.env` file.
