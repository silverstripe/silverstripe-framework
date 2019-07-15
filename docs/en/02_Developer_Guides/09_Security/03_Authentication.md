title: Authentication
summary: Explains SilverStripe's Authentication options and custom authenticators. 

# Authentication

By default, SilverStripe provides a [MemberAuthenticator](api:SilverStripe\Security\MemberAuthenticator\MemberAuthenticator) class which hooks into its own internal
authentication system.

## User Interface

SilverStripe comes with a default login form interface,
that's embedded into your page templates through the `$Form` placeholder.
Since it's embedded into your own site styling and behaviour,
it can require adjustments to your particular context. 

Starting with SilverStripe 4.5, the view logic may be handled through the
[silverstripe/login-forms](https://github.com/silverstripe/silverstripe-login-forms) module (if present).

## Controllers

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

## Registering a new Authenticator

```yaml
SilverStripe\Core\Injector\Injector:
  SilverStripe\Security\Security:
    properties:
      Authenticators:
        myauthenticator: %$MyVendor\MyProject\Authenticator\MyAuthenticator
```
If there is no authenticator registered, `Authenticator` will try to fall back on the default provided authenticator (`default`), which can be changed using the following config, replacing the MemberAuthenticator with your authenticator:
```yaml
---
Name: MyAuth
After:
  - '#coresecurity'
---
SilverStripe\Core\Injector\Injector:
  SilverStripe\Security\Security:
    properties:
      Authenticators:
        default: %$MyVendor\MyProject\Authenticator\MyAuthenticator
```

By default, the `SilverStripe\Security\MemberAuthenticator\MemberAuthenticator` is seen as the default authenticator until it's explicitly set in the config.

Every Authenticator is expected to handle services. The `Authenticator` Interface provides the available services:

```php
const LOGIN = 1;
const LOGOUT = 2;
const CHANGE_PASSWORD = 4;
const RESET_PASSWORD = 8;
const CMS_LOGIN = 16;

/**
 * Returns the services supported by this authenticator
 *
 * The number should be a bitwise-OR of 1 or more of the following constants:
 * Authenticator::LOGIN, Authenticator::LOGOUT, Authenticator::CHANGE_PASSWORD,
 * Authenticator::RESET_PASSWORD, or Authenticator::CMS_LOGIN
 *
 * @return int
 */
public function supportedServices();
```

If there is no available authenticator for the required action (either one of the constants above), an error will be thrown.

Custom Authenticators are expected to have the following methods implemented:
* `getLoginHandler()`
* `getLogoutHandler()`
* `getChangePasswordHandler()`
* `getLostPasswordHandler()`

All expect a `$link` variable, to handle the request.
Further, there is 
* `authenticate()`
Which expects the data to be used for authentication as an array and a nullable variable `$result` by reference, which returns a `ValidationResult`.

If only a subset of the supportedServices() will be provided by the custom Authenticator, it is advised to extend `SilverStripe\Security\MemberAuthenticator\MemberAuthenticator`, as that default contains all required methods already and only an override or follow up needs to be written.

An example of how to write a multi-factor authentication [can be found here](https://gist.github.com/sminnee/bc646147f3941a764d0410f2044433c7).

## IdentityStore

A new IdentityStore, e.g. an LDAP IdentityStore can be registered as follows in a `security.yml` file (Not an actual valid LDAP configuration):
```yaml
SilverStripe\Core\Injector\Injector:
  MyProject\LDAP\Authenticator\LDAPAuthenticator:
    properties:
      LDAPSettings:
        - URL: https://my-ldap-location.com
      CascadeInTo: %$SilverStripe\Security\MemberAuthenticator\SessionAuthenticationHandler
  SilverStripe\Security\AuthenticationHandler:
    class: SilverStripe\Security\RequestAuthenticationHandler
    properties:
      Handlers:
        ldap: %$MyProject\LDAP\Authenticator\LDAPAuthenticator
```

CascadeInTo is used to defer login or logout actions to other authenticators, after the first one has been logged in. In the example of LDAP authenticator, this is useful to check e.g. the validity of the Session (is the user still logged in?) and if not, or it's LDAP login period has expired, only then validate against the external service again, limiting the amount of requests to the external service.

Upon request, the Member is authenticated against the given AuthenticatorHandlers. To override an Authenticator, override it's name in the `YML` to your own Handler.

To get applicable Authenticators for a certain request, refer to [API:Security:getApplicableAuthenticators()].

To register `CMS` authenticators, use the same procedure as above, only replace `SilverStripe\Security\Security` with `SilverStripe\Security\CMSSecurity`. 
