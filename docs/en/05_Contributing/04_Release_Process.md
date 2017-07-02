summary: Describes the process followed for "core" releases.

# Release Process

This page describes the process followed for "core" releases (mainly the `framework` and `cms` modules).

## Release Planning

Our most up-to-date release plans are typically in the ["framework" milestone](https://github.com/silverstripe/silverstripe-framework/milestones) and ["cms" milestone](https://github.com/silverstripe/silverstripe-cms/milestones).
New features and API changes are discussed on the [core mailinglist](http://groups.google.com/group/silverstripe-dev). They are prioritised by the core team as tickets on 
github.com. In addition, we collect community feedback on [silverstripe.uservoice.com](https://silverstripe.uservoice.com).
Any feature ideas we're planning to implement will be flagged there.

Release dates are usually not published prior to the release, but you can get a good idea of the release status by
reviewing the release milestone on github.com. Releases will be
announced on the [release announcements mailing list](http://groups.google.com/group/silverstripe-announce).

Releases of the *cms* and *framework* modules are coupled at the moment, and they follow the same numbering scheme.

## Release Numbering

SilverStripe follows [Semantic Versioning](http://semver.org).

Note: Until November 2014, the project didn't adhere to Semantic Versioning. Instead, a "minor release" in semver terminology
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
of a certain API. Existing APIs might turn out to be hard to understand, maintain, test or stabilise.
In these cases, it is best practice to "refactor" these APIs into something more useful.
SilverStripe acknowledges that developers have built a lot of code on top of existing APIs,
so we strive for giving ample warning on any upcoming changes through a "deprecation cycle".

How to deprecate an API:

*  Add a `@deprecated` item to the docblock tag, with a `{@link <class>}` item pointing to the new API to use.
*  Update the deprecated code to throw a [api:SilverStripe\Dev\Deprecation::notice()] error.
*  Both the docblock and error message should contain the **target version** where the functionality is removed.
   So, if you're committing the change to a *3.1* minor release, the target version will be *4.0*. 
*  Deprecations should not be committed to patch releases
*  Deprecations should only be committed to pre-release branches, ideally before they enter the "beta" phase.
   If deprecations are introduced after this point, their target version needs to be increased by one.
*  Make sure that the old deprecated function works by calling the new function - don't have duplicated code!
*  The commit message should contain an `API` prefix (see ["commit message format"](code#commit-messages))
*  Document the change in the [changelog](/changelogs) for the next release
*  Deprecated APIs can be removed only after developers have had sufficient time to react to the changes. Hence,     deprecated APIs should be removed in MAJOR releases only. Between MAJOR releases, leave the code in place with    a deprecation warning. 
*  Exceptions to the deprecation cycle are APIs that have been moved into their own module, and continue to work     with the new minor release. These changes can be performed in a single minor release without a deprecation        period.

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

This change could be committed to a minor release like *3.2.0*, and remains deprecated in all subsequent minor releases
(e.g. *3.3.0*, *3.4.0*), until a new major release (e.g. *4.0.0*), at which point it gets removed from the codebase. 

Deprecation notices are enabled by default on dev environment, but can be
turned off via either `.env` or in your _config.php. Deprecation
notices are always disabled on both live and test.


`mysite/_config.php`


    :::php
    Deprecation::set_enabled(false);


`.env`


    SS_DEPRECATION_ENABLED="0"


## Security Releases

### Reporting an issue

Report security issues to [security@silverstripe.com](mailto:security@silverstripe.com). 
Please don't file security issues in our [bugtracker](issues_and_bugs). 

### Acknowledgment and disclosure

In the event of a confirmed vulnerability in SilverStripe core, we will take the following actions:

*  Acknowledge to the reporter that we’ve received the report and that a fix is forthcoming. We’ll give a rough
timeline and ask the reporter to keep the issue confidential until we announce it.
*  Assign a unique identifier to the issue in the format `SS-<year>-<count>`, 
   where `<count>` is a padded three digit number counting issues for the year. 
   Example: `SS-2013-001` would be the first of the year `2013`.
   Additionally, [CVE](http://cve.mitre.org) numbers are accepted.
*  Halt all other development as long as is needed to develop a fix, including patches against the current and one
previous major release (if applicable).
* Pre-announce the upcoming security release to a private mailing list of important stakeholders (see below).
*  We will inform you about resolution and [announce](http://groups.google.com/group/silverstripe-announce) a 
[new release](http://silverstripe.org/security-releases/) publically.

You can help us determine the problem and speed up responses by providing us with more information on how to reproduce
the issue: SilverStripe version (incl. any installed modules), PHP/webserver version and configuration, anonymised
webserver access logs (if a hack is suspected), any other services and web packages running on the same server.

### Severity rating

Each [security release](http://www.silverstripe.org/security-releases/) includes an overall severity rating and one for 
each vulnerability. The rating indicates how important an update is:

| Severity      | Description |
|---------------|-------------|
| **Critical**  | Critical releases require immediate action. Such vulnerabilities allow attackers to take control of your site and you should upgrade on the day of release. *Example: Directory traversal, privilege escalation* |
| **Important** | Important releases should be evaluated immediately. These issues allow an attacker to compromise a site's data and should be fixed within days. *Example: SQL injection.* |
| **Moderate**  | Releases of moderate severity should be applied as soon as possible. They allow the unauthorized editing or creation of content. *Examples: Cross Site Scripting (XSS) in template helpers.* |
| **Low**       | Low risk releases fix information disclosure and read-only privilege escalation vulnerabilities. These updates should also be applied as soon as possible, but with an impact-dependent priority. *Example: Exposure of the core version number, Cross Site Scripting (XSS) limited to the admin interface.* |

### Internal Security Process

Follow these instructions in sequence as much as possible:

 * When receiving a report:
   * Perform initial criticality assessment, and ensure that the reporter is given a justification for all issues we classify or demote as non-security vulnerabilities.
   * Assign a unique identifier (see "Acknowledgement and disclosure").
     Identifiers are based on reported year and order reported in JIRA (Example: `SS-2017-001`)
   * Respond to issue reporter with this identifier on the same discussion thread (cc security@silverstripe.org). Clarify issue if required.
   * If encrypted information is provided, add pass phrases into the SilverStripe Ltd. LastPass account. Keep encrypted documents in Google Drive and only share directly with relevant participants
   * Add a new bug on our [Open Source Security JIRA board](https://silverstripe.atlassian.net/secure/RapidBoard.jspa?rapidView=198&view=detail). Add a link to the [Google Groups](https://groups.google.com/a/silverstripe.com/forum/#!forum/security) discussion thread so it's easy to review follow up messages.
   * Create a draft page under [Open Source > Download > Security Releases](https://www.silverstripe.org/admin/pages/edit/show/794) on silverstripe.org. Describe the issue in a readable way, make the impact clear. Credit the author if applicable. 
   * Clarify who picks up owns the issue resolution
 * When developing a fix:
   * Add fixes on the [http://github.com/silverstripe-security](http://github.com/silverstripe-security) repo
   * Ensure that all security commit messages are prefixed with the CVE. E.g. "[ss-2015-001] Fixed invalid XSS"
   * Get them peer reviewed by posting on security@silverstripe.org with a link to the JIRA issue
 * Before release (or release candidate)
   * Merge back from [http://github.com/silverstripe-security](http://github.com/silverstripe-security) repos shortly at the release (minimise early disclosure through source code)
   * Send out a note on the pre-announce list with a highlevel description of the issue and impact (usually a copy of the yet unpublished security release page on silverstripe.org)
   * Link to silverstripe.org security release page in the changelog.
 * After release
   * Publish silverstripe.org security release page
   * Respond to issue reporter with reference to the release on the same discussion thread (cc security@silverstripe.org)

### Pre-announce Mailinglist

In addition to our public disclosure process, we maintain a private mailinglist
where upcoming security releases will be pre-announced. Members in this list will receive a security 
pre-announcement as soon as it has been sufficiently researched,
alongside a timeline for the upcoming release. This will happen a few days before 
the announcement goes public alongside new release, and most likely before a patch has been developed.

Since we’ll distribute sensitive info on unpatched vulnerabilities in this list,
the selection criteria for joining naturally has to be strict.
Applicants should provide references within the community,
as well as a demonstrated need for this level of information (e.g. a large website with sensitive customer data).
You don’t need to be a client of SilverStripe Ltd to get on board, 
but we will need to perform some low-touch background checks to ensure identity.
Please contact security@silverstripe.org for details.

## Quality Assurance and Testing

The quality of our software is important to us, and we continously test it for regressions
through a broad suite of unit and integration tests. Most of these run on 
[Travis CI](http://travis-ci.com), and results are publicly available
for the [framework](https://travis-ci.org/silverstripe/silverstripe-framework) and
[cms](https://travis-ci.org/silverstripe/silverstripe-cms) modules.
In addition, some build configurations (e.g. running on Windows) are tested
through a [TeamCity](http://www.jetbrains.com/teamcity/) instance hosted at
[teamcity.silverstripe.com](http://teamcity.silverstripe.com) (click "Login as guest").

## Releasing to modules to NPM

As we're progressing to include NPM modules in our development process, we have created a `@silverstripe` organisation for modules built specifically for SilverStripe.

These are the steps involved to publish a new version to NPM for that module, similar steps apply for creating a new module under the `@silverstripe` organisation.
 
1) Make your changes, pull from upstream if applicable
2) Change to the relevant container folder with the package.json file.
3) Run `npm login` and make sure you’re part of the `@silverstripe` organisation
4) Make sure the `name` property of the package.json file matches to the right module name with organisation name prefix, e.g. `"name": "@silverstripe/webpack-config"`
5) Update the `version` property of the package.json file with a new version number, following semver where possible.
6) run `npm publish`
 
_IMPORTANT NOTE_: You cannot publish the same or lower version number.
