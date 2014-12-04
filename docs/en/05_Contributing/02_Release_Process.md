summary: Describes the process followed for "core" releases.

# Release Process

Describes the process followed for "core" releases (mainly the `framework` and `cms` modules).

## Release Maintainer

The current maintainer responsible for planning and performing releases is Ingo Schommer (ingo at silverstripe dot com).

## Release Planning

Our most up-to-date release plans are typically in the ["framework" milestone](https://github.com/silverstripe/silverstripe-framework/issues/milestones) and ["cms" milestone](https://github.com/silverstripe/silverstripe-cms/issues/milestones).
New features and API changes are discussed on the [core mailinglist](http://groups.google.com/group/silverstripe-dev). They are prioritised by the core team as tickets on 
github.com. In addition, we collect community feedback on [silverstripe.uservoice.com](https://silverstripe.uservoice.com).
Any feature ideas we're planning to implement will be flagged there.

Release dates are usually not published prior to the release, but you can get a good idea of the release status by
reviewing the release milestone on github.com. Releases will be
announced on the [release announcements mailing list](http://groups.google.com/group/silverstripe-announce).

Releases of the *cms* and *framework* modules are coupled at the moment, they follow the same numbering scheme.

## Release Numbering

SilverStripe follows [Semantic Versioning](http://semver.org).

Note: Until November 2014, the project didn't adhere to Semantic Versioning. Instead. a "minor release" in semver terminology
was treated as a "major release" in SilverStripe. For example, the *3.1.0* release contained API breaking changes, and
the *3.1.1* release contained new features rather than just bugfixes.

## Supported versions

At any point in time, the core development team will support a set of releases to varying levels:

*  The current *master* will get new features, bug fixes and API changes that might require major refactoring before going
into a release. At the moment, bugfixing and feature development might happen on the current major release branch (e.g. *3*), to be
merged forward to master regularly.
*  Applicable bugfixes on master will also be merged back to the last major release branch, to be released as the next
patch release
*  Security fixes will be applied to the current master and the previous two major releases (e.g. *4.0*, *3.2* and *3.1*).

## Deprecation

Needs of developers (both on core framework and custom projects) can outgrow the capabilities
of a certain API. Existing APIs might turn out to be hard to understand, maintain, test or stabilize.
In these cases, it is best practice to "refactor" these APIs into something more useful.
SilverStripe acknowledges that developers have built a lot of code on top of existing APIs,
so we strive for giving ample warning on any upcoming changes through a "deprecation cycle".

How to deprecate an API:

*  Add a `@deprecated` item to the docblock tag, with a `{@link <class>}` item pointing to the new API to use.
*  Update the deprecated code to throw a `[api:Deprecation::notice()]` error.
*  Both the docblock and error message should contain the **target version** where the functionality is removed.
   So if you're committing the change to a *3.1* minor release, the target version will be *4.0*. 
*  Deprecations should not be committed to patch releases
*  Deprecations should just be committed to pre-release branches, ideally before they enter the "beta" phase.
   If deprecations are introduced after this point, their target version needs to be increased by one.
*  Make sure that the old deprecated function works by calling the new function - don't have duplicated code!
*  The commit message should contain an `API` prefix (see ["commit message format"](code#commit-messages))
*  Document the change in the [changelog](/changelogs) for the next release
*  Deprecated APIs can be removed after developers had a chance to react to the changes. As a rule of thumb, leave the 
code with the deprecation warning in for at least three micro releases. Only remove code in a minor or major release. 
*  Exceptions to the deprecation cycle are APIs that have been moved into their own module, and continue to work with the
new minor release. These changes can be performed in a single minor release without a deprecation period.

Here's an example for replacing `Director::isDev()` with a (theoretical) `Env::is_dev()`:

	:::php
	/**
	 * Returns true if your are in development mode
	 * @deprecated 4.0 Use {@link Env::is_dev()} instead.
	 */
	public function isDev() {
		Deprecation::notice('4.0', 'Use Env::is_dev() instead');
		return Env::is_dev();
	}

This change could be committed to a minor release like *3.2.0*, and stays deprecated in all following minor releases
(e.g. *3.3.0*, *3.4.0*), until a new major release (e.g. *4.0.0*) where it gets removed from the codebase. 

## Security Releases

### Reporting an issue

Report security issues to [security@silverstripe.com](mailto:security@silverstripe.com). 
Please don't file security issues in our [bugtracker](issues_and_bugs). 

### Acknowledgment and disclosure

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

Each [security release](http://www.silverstripe.org/security-releases/) includes an overall severity rating and one for 
each vulnerability. The rating indicates how important an update is:

| Severity      | Description |
|---------------|-------------|
| **Critical**  | Critical releases require immediate actions. Such vulnerabilities allow attackers to take control of your site and you should upgrade on the day of release. *Example: Directory traversal, privilege escalation* |
| **Important** | Important releases should be evaluated immediately. These issues allow an attacker to compromise a site's data and should be fixed within days. *Example: SQL injection.* |
| **Moderate**  | Releases of moderate severity should be applied as soon as possible. They allow the unauthorized editing or creation of content. *Examples: Cross Site Scripting (XSS) in template helpers.* |
| **Low**       | Low risk releases fix information disclosure and read-only privilege escalation vulnerabilities. These updates should also be applied as soon as possible, but with an impact-dependent priority. *Example: Exposure of the core version number, Cross Site Scripting (XSS) limited to the admin interface.* |
