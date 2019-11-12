---
title: Release process
summary: Describes the process followed for "core" releases.
iconBrand: git-alt
---

# Release Process

This page describes the process followed for "core" releases
(the modules included by [silverstripe/recipe-core](https://github.com/silverstripe/recipe-core)
and [silverstripe/recipe-cms](https://github.com/silverstripe/recipe-cms)).

## Release Planning

Releases are planned by assigning issues to GitHub milestones,
according to our [roadmap](http://silverstripe.org/roadmap).
SilverStripe is split up into many modules listed on [github.com/silverstripe](https://github.com/silverstripe).
Each of them will have its own milestones and issues, and different release lines (e.g. 1.x vs. 4.x).
There are high-level "recipe" milestones on the [framework repository](https://github.com/silverstripe/silverstripe-framework/milestones)
to combine individual module milestones into a larger release that's eventually available on
[silverstripe.org/download](https://silverstripe.org/download).

New features and API changes are discussed as
[GitHub issues](https://docs.silverstripe.org/en/contributing/issues_and_bugs/)),
as well as the [forum](https://forum.silverstripe.org). 
They are prioritised by the core team as tickets on 
github.com. In addition, we collect community feedback as [feature ideas](https://forum.silverstripe.org/c/feature-ideas) on the forum.
Any feature ideas we're planning to implement will be flagged there.

Release dates are usually not published prior to the release, but you can get a good idea of the release status by
reviewing the release milestone on github.com. Releases will be
announced on the ["releases" forum category](https://forum.silverstripe.org/c/releases).

## Release numbering {#release-numbering}

SilverStripe follows [Semantic Versioning](http://semver.org).

All SilverStripe modules (including silverstripe/framework) may have patch releases created at any time, and will
not necessarily match other core module patch release numbers. For example, your project may be using
silverstripe/cms 4.3.1 with silverstripe/versioned 4.3.9.

All SilverStripe recipes are released in lock step with each other. For example, silverstripe/installer 4.3.1 will
contain silverstripe/recipe-cms 4.3.1 and silverstripe/recipe-core 4.3.1. These recipes may contain various patch
versions of its modules within the same minor release line (4.3 in this example).

## Supported versions {#supported-versions}

At any point in time, the core development team will support a set of releases to varying levels:

 * The status of major releases is determined by the [roadmap](http://silverstripe.org/roadmap)
 * Minor releases of major releases in "active development" or "full support" are released roughly every three months, and their End-of-Life (EOL) is announced at least six months in advance
 * The latest minor release is supported as long as the underlying major release
 * API changes and major new features are applied to the master branch, to be included in the next major release
 * New APIs can be applied to the current minor release of major releases in "active development", but should usually be marked as "internal" APIs until they're considered stable
 * Enhancements are applied to the next minor release of major releases in "active development"
 * Non-critical bugfixes and all security fixes are applied to all supported minor releases of major releases in "active development" or "full support"
 * Critical bugfixes and [critical security fixes](#severity-rating) are applied to the all minor releases of major releases in "active development", "full support" or "limited support"
 * [Non-critical security fixes](#severity-rating) are backported to releases in "limited support" on a best effort basis
 * Any patches applied to older minor releases are merged up regularly to newer minor releases (in the same major release)
 * Any patches applied to older major releases are merged up regularly to newer major releases

Note that this only applies to the "core" recipe
(the modules included by [silverstripe/recipe-core](https://github.com/silverstripe/recipe-core)
and [silverstripe/recipe-cms](https://github.com/silverstripe/recipe-cms)).
For [supported modules](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/) outside of this recipe,
please refer to our [supported modules definition](https://www.silverstripe.org/software/addons/supported-modules-definition/).


## Deprecation

Needs of developers (both on core framework and custom projects) can outgrow the capabilities
of a certain API. Existing APIs might turn out to be hard to understand, maintain, test or stabilise.
In these cases, it is best practice to "refactor" these APIs into something more useful.
SilverStripe acknowledges that developers have built a lot of code on top of existing APIs,
so we strive for giving ample warning on any upcoming changes through a "deprecation cycle".

How to deprecate an API:

*  Add a `@deprecated` item to the docblock tag, with a `{@link <class>}` item pointing to the new API to use.
*  Update the deprecated code to throw a [Deprecation::notice()](api:SilverStripe\Dev\Deprecation::notice()) error.
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


```php
/**
 * Returns true if your are in development mode
 * @deprecated 4.0 Use {@link Env::is_dev()} instead.
 */
public function isDev() 
{
    Deprecation::notice('4.0', 'Use Env::is_dev() instead');
    return Env::is_dev();
}
```

This change could be committed to a minor release like *3.2.0*, and remains deprecated in all subsequent minor releases
(e.g. *3.3.0*, *3.4.0*), until a new major release (e.g. *4.0.0*), at which point it gets removed from the codebase. 

Deprecation notices are enabled by default on dev environment, but can be
turned off via either `.env` or in your _config.php. Deprecation
notices are always disabled on both live and test.


`app/_config.php`



```php
Deprecation::set_enabled(false);
```

`.env`

```
SS_DEPRECATION_ENABLED="0"
```


## Security Releases

A security release is a [SilverStripe Core Release](making_a_silverstripe_core_release#standard-release-process) with patches for one or more security issues.

### Reporting an issue

Report security issues in our [commercially supported modules](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)
to [security@silverstripe.com](mailto:security@silverstripe.org). 
Please don't file security issues in our [bugtracker](issues_and_bugs). 

### Acknowledgment and disclosure

In the event of a confirmed vulnerability in our
[supported modules](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/),
we will take the following actions:

* Acknowledge to the reporter that we’ve received the report and that a fix is forthcoming.
  We’ll give a rough timeline and ask the reporter to keep the issue confidential until we announce it.
* Assign a [CVE identifier](https://cve.mitre.org) to the issue.
* For "high" and "critical" issues (CVSS of >=7.0): Pre-announce the upcoming security release to aprivate pre-announcement mailing list of important stakeholders (see below).
* We will inform you about resolution and [announce](https://forum.silverstripe.org/c/releases) a 
[new release](http://silverstripe.org/security-releases/) publically.

You can help us determine the problem and speed up responses by providing us with more information on how to reproduce
the issue: SilverStripe version (incl. any installed modules), PHP/webserver version and configuration, anonymised
webserver access logs (if a hack is suspected), any other services and web packages running on the same server.

### Severity rating

Each [security release](http://www.silverstripe.org/security-releases/) includes an overall severity rating and one for 
each vulnerability. The rating indicates how important an update is.
It follows the [Common Vulnerability Scoring System (CVSS)](https://www.first.org/cvss). 
This rating determines which release lines are targetd with security fixes.

| Severity      | CVSS | Description |
|---------------|------|-------------|
| **Critical**  | 9.0 to 10.0 | Critical releases require immediate action. Such vulnerabilities allow attackers to take control of your site and you should upgrade on the day of release. *Example: Directory traversal, privilege escalation* |
| **High**      | 7.0 to 8.9 | Important releases should be evaluated immediately. These issues allow an attacker to compromise a site's data and should be fixed within days. *Example: SQL injection.* |
| **Medium**    | 4.0 to 6.9 | Releases of moderate severity should be applied as soon as possible. They allow the unauthorized editing or creation of content. *Examples: Cross Site Scripting (XSS) in template helpers.* |
| **Low**       | 0.1 to 3.9 | Low risk releases fix information disclosure and read-only privilege escalation vulnerabilities. These updates should also be applied as soon as possible, but with an impact-dependent priority. *Example: Exposure of the core version number, Cross Site Scripting (XSS) limited to the admin interface.* |

### Internal Security Process

See [SilverStripe Core Release Process](making_a_silverstripe_core_release).

### Pre-announcement mailing list

In addition to our public disclosure process, we maintain a private mailing list where upcoming
"high" or "critical" security releases are pre-announced.
Members of this list will receive a security pre-announcement, as soon as it has been
sufficiently researched, with a timeline for the upcoming release. 
This will happen a few days before the announcement goes public alongside a new release, 
and most likely before a patch has been developed.

Since we’ll distribute sensitive information on unpatched vulnerabilities in this list, 
the selection criteria for joining naturally has to be strict. 
Applicants should provide references within the community, 
as well as a demonstrated need for this level of information 
(e.g. involvement with a large website with sensitive customer data). 
You don’t need to be a client of SilverStripe Ltd to get on board, 
but we will need to perform some low-touch background checks to verify your identity. 
Please contact security@silverstripe.org for details.


## Quality Assurance and Testing

The quality of our software is important to us, and we continously test it for regressions
through a broad suite of unit and integration tests. Most of these run on 
[Travis CI](http://travis-ci.com), and results are publicly available.
Check the badges on the various modules available on [github.com/silverstripe](http://github.com/silverstripe).
There's also a [build matrix](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)
for our commercially supported modules (only showing build status for the default branch).

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
