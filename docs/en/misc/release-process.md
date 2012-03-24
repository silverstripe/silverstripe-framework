# Release Process

Describes the process followed for "core" releases (mainly the `framework` and `cms` modules).
For other modules, we've compiled a helpful guide for a good [module release process](module-release-process).

## Release Maintainer

The current maintainer responsible for planning and performing releases is Ingo Schommer (ingo at silverstripe dot com).

## Release Planning

Our most up-to-date release plans are typically in the [roadmap](http://open.silverstripe.com/roadmap).
New features and API changes are typically discussed on the [core
mailinglist](http://groups.google.com/group/silverstripe-dev). They are prioritized by the core team as tickets on 
[open.silverstripe.org](http://open.silverstripe.com/). 

Release dates are usually not published prior to the release, but you can get a good idea of the release status by
reviewing the [release milestone](http://open.silverstripe.com/roadmap) on open.silverstripe.org. Releases will be
announced on the [release announcements mailing list](http://groups.google.com/group/silverstripe-announce).

Releases of the *cms* and *framework* modules are coupled at the moment, they follow the same numbering scheme. Module
releases are documented separately in [module-release-process](module-release-process).

## Release Numbering

*  Versions are numbered by major version number, minor version number, and micro version number, in the form *A.B.C*
(e.g. *2.4.1*)
*  *A* is the *major version number*, which is only incremented for major changes and core rewrites, lots of them won't
be backwards compatible. 
*  *B* is the *minor version number*. It is incremented for our typical releases with new features and bugfixes. We
strive for few changes to be backwards incompatible, and will deprecate any APIs before removing them.
*  *C* is the *micro version number*, incremented for bugfixes, minor enhancements and security fixes. Unless
security-related, all changes will be fully backwards compatible to the minor version number.
*  Major and minor releases have an *alpha* cycle, which is a preview developer release which that see major changes
until release. It is followed by a *beta* cycle, which is feature complete and used by the wider development community
for stability and regression testing. Naming convention is *A.B.C-alpha* and *A.B.C-beta*.
*  Major, minor and micro releases can have one or more *release candidates (RC)*, to be used by the wider community. A
release candidate signifies that the core team thinks the release is ready without further changes. The actual release
should be a identical copy of the latest RC. Naming convention is *A.B.C-rc1* (and further increments).
* Major releases may have a *preview* cycle which is a early snapshot of the codebase for developers before 
going into the *alpha* cycle. Preview releases are named *A.B.C-pr1* (and further increments).

### Major releases

So far, major releases have happened every couple of years. Most new releases are *minor version number* or *micro
version number* increments.
So far, we only had one major release, from the *1.x* to the *2.x* line.

### Minor releases

Minor releases have happened about once every 18 months. 
For example, *2.3* was released in February 2009, followed by *2.4* in May 2010.

These releases will contain new features, general enhancements and bugfixes. APIs from previous minor releases can be
*deprecated*, but will stay available for one more minor release. So, if an API is deprecated in *A.B*, it will continue
to work in *A.B+1*, and removed in *A.B+2*. 

An example: Say we'd want to rename *BasicAuth::requireLogin()* to follow our coding conventions, which is
*BasicAuth::require_login()*. The method was introduced in *2.1*, we've made the change in *2.3*?

*  *2.3* would've marked the method as *@deprecated*, and documents it as an *API CHANGE* in our
[changelog](/changelogs). The old method continues to work, but will throw an *E_USER_NOTICE*.
*  *2.4* would've removed the method, also documenting it as an *API CHANGE*, and mentioning it in the
[upgrading](/installation/upgrading) guidelines.

Exceptions to the deprecation cycle are APIs that have been moved into their own module, and continue to work with the
new minor release. These changes can be performed in a single minor release without a deprecation period.

### Micro releases

Micro releases are issued about every two months for the latest release, typically for security reasons.
You can safely upgrade to those releases (after reading the [upgrading](/installation/upgrading) guidelines).
For example, *2.3.6* was released in February 2010, followed by *2.3.7* in March 2010.

### Supported versions

At any point in time, the core development team will support a set of releases to varying levels:

*  The current *development trunk* will get new features and bug fixes that might require major refactoring before going
into a release (Note: At the moment, bugfixing and feature development might happen on the current release branch, to be
merged back to trunk regularly).
*  Applicable bugfixes on trunk will also be merged back to the last minor release branch, to be released as the next
micro release.
*  Security fixes will be applied to the current trunk and the previous two minor releases (e.g. *2.3.8* and *2.4.1*).

## Deprecation

Needs of developers (both on core framework and custom projects) might outgrow the capabilities
of a certain API. Existing APIs might turn out to be hard to understand, maintain, test or stabilize.
In these cases, it is best practice to "refactor" these APIs into something more useful.
SilverStripe acknowledges that developers have built a lot of code on top of existing APIs,
so we strive for giving ample warning on any upcoming changes through a "deprecation cycle".

How to deprecate an API:

*  Add a `@deprecated` item to the docblock tag, with a `{@link <class>}` item pointing to the new API to use.
*  Update the deprecated code to throw an `E_USER_NOTICE` error, with a message starting with the string 'DEPRECATED:'.  
In time, we may use that string to identify deprecation errors, so please ensure that you add this string to the notice level error.
*  Make sure that the old deprecated function works by calling the new function - don't have duplicated code!
*  Mark in which release the function was deprecated (find out next release in the [roadmap](http://open.silverstripe.com/roadmap)), so we can determine when to finally remove it.
Here's an example for replacing `Director::isDev()` with a (theoretical) `Env::is_dev()`:

	:::php
	/**
	 * Returns true if your are in development mode
	 * @deprecated (since 2.2.2) Use {@link Env::is_dev()} instead.
	 */
	public function isDev() {
		user_error("DEPRECATED: Use Env::is_dev() instead.", E_USER_NOTICE);
		return Env::is_dev();
	}
* Deprecated APIs can be removed after developers had a chance to react to the changes. As a rule of thumb, leave the code with the deprecation warning in for at least three micro releases. Only remove code in a minor or major release. For example:
   * Deprecated as of in 2.2.2
   * Still deprecated in 2.2.3
   * Still deprecated in 2.2.4
   * Removed from 2.3.0

## Security Releases

### Reporting an issue

Report security issues to [security@silverstripe.com](mailto:security@silverstripe.com). Please don't file security
issues in our [bugtracker](http://open.silverstripe.org). 

### Acknowledgement and disclosure

In the event of a confirmed vulnerability in SilverStripe core, we will take the following actions:

*  Acknowledge to the reporter that we’ve received the report and that a fix is forthcoming. We’ll give a rough
timeline and ask the reporter to keep the issue confidential until we announce it.
*  Halt all other development as long as is needed to develop a fix, including patches against the current and one
previous major release (if applicable).
*  We will inform you about resolution and [announce](http://groups.google.com/group/silverstripe-announce) a 
[new release](http://silverstripe.org/security-releases/) publically.

You can help us determine the problem and speed up responses by providing us with more information on how to reproduce
the issue: SilverStripe version (incl. any installed modules), PHP/webserver version and configuration, anonymized
webserver access logs (if a hack is suspected), any other services and web packages running on the same server.

### Severity rating

Each [security release](http://www.silverstripe.org/security-releases/) includes an overall severity rating and one for each vulnerability. The rating indicates how important an update is:

| Severity      | Description |
|---------------|-------------|
| **Critical**  | Critical releases require immediate actions. Such vulnerabilities allow attackers to take control of your site and you should upgrade on the day of release. *Example: Directory traversal, privilege escalation* |
| **Important** | Important releases should be evaluated immediately. These issues allow an attacker to compromise a site's data and should be fixed within days. *Example: SQL injection.* |
| **Moderate**  | Releases of moderate severity should be applied as soon as possible. They allow the unauthorized editing or creation of content. *Examples: Cross Site Scripting (XSS) in template helpers.* |
| **Low**       | Low risk releases fix information disclosure and read-only privilege escalation vulnerabilities. These updates should also be applied as soon as possible, but with an impact-dependent priority. *Example: Exposure of the core version number, Cross Site Scripting (XSS) limited to the admin interface.* |
