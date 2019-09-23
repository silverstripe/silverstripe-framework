title: Making a SilverStripe core release
summary: Development guide for core contributors to build and publish a new release

# Making a SilverStripe core release

## Introduction

This guide is intended to be followed by core contributors, allowing them to take
the latest development branch of each of the core modules, and building a release.
The artifacts for this process are typically:

 * A published announcement
 * A new composer installable stable tag for silverstripe/installer

While this document is not normally applicable to normal silverstripe contributors,
it is still useful to have it available in a public location so that these users
are aware of these processes.

## First time setup

As a core contributor it is necessary to have installed the following set of tools:

### First time setup: Standard releases

* PHP 5.6+
* Python 2.7 / 3.5
* [cow release tool](https://github.com/silverstripe/cow#install). This should typically
  be installed in a global location via the below command. Please see the installation
  docs on the cow repo for more setup details.
  `composer global require silverstripe/cow ^2`
* [satis repository tool](https://github.com/composer/satis). This should be installed
  globally for minimum maintenance.
  `composer global require composer/satis ^1`
* [transifex client](http://docs.transifex.com/client/).
  `pip install transifex-client`
  If you're on OSX 10.10+, the standard Python installer is locked down.
  Use `brew install python; sudo easy_install pip` instead
* A good `.env` setup in your localhost webroot.

Example `.env`:

```
# Environent
SS_TRUSTED_PROXY_IPS="*"
SS_ENVIRONMENT_TYPE="dev"

# DB Credentials
SS_DATABASE_CLASS="MySQLDatabase"
SS_DATABASE_SERVER="127.0.0.1"
SS_DATABASE_USERNAME="root"
SS_DATABASE_PASSWORD=""

# Each release will have its own DB
SS_DATABASE_CHOOSE_NAME=1

# So you can test releases
SS_DEFAULT_ADMIN_USERNAME="admin"
SS_DEFAULT_ADMIN_PASSWORD="password"

# Basic CLI request url default
SS_BASE_URL="http://localhost/"
```

You will also need to be assigned the following permissions. Contact one of the SilverStripe staff from
the [core committers](core_committers), who will assist with setting up your credentials.

* Write permissions on the [silverstripe](https://github.com/silverstripe) organisation.
* Admin permissions on [transifex](https://www.transifex.com/silverstripe/).
  Set up a [~/.transifexrc](https://docs.transifex.com/client/client-configuration) with your credentials.
* Moderator permissions in the [Slack channel](https://www.silverstripe.org/community/slack-signup/)
* Administrator account on [docs.silverstripe.org](https://docs.silverstripe.org) and
  [userhelp.silverstripe.org](https://userhelp.silverstripe.org).

### First time setup: Security releases

For doing security releases the following additional setup tasks are necessary:

* Write permissions on the [silverstripe-security](https://github.com/silverstripe-security)
  organisation.
* Permissions to write to the [security releases page](http://www.silverstripe.org/download/security-releases)
  and the [silverstripe.org CMS](http://www.silverstripe.org/admin).
* Permission on [security pre-announcement mailing list](https://groups.google.com/a/silverstripe.com/forum/#!forum/security-preannounce).

## Initiating a core release

Releases are documented as issues in the [silverstripe/framework Github repository](https://github.com/silverstripe/silverstripe-framework). Create an issue
using one of the three release templates. This will create a checklist for you to follow that will guide you through the release and ensure you don't miss
any steps.

Please note that there is a lot of duplication across the lists, so when making changes, be sure to make the update to all release types to which it applies.

If a minor release contains security fixes, you will need to either create two issues, or simply reference the security checklist in parallel with the minor release checklist.

When the release is complete, close the issue.

## Security release process

### Overview

When doing a security release, typically one or more (or sometimes all) of the below
steps will need to be performed manually. As such, this guide should not be followed
exactly the same for these.

Standard practice is to produce a pre-release for any patched modules on the security 
forks, e.g. for cms and framework (see [silverstripe-security](https://github.com/silverstripe-security)).

<div class="warning" markdown="1">
Security issues are never disclosed until a public stable release containing this fix
is available, or within a reasonable period of time of such a release.
</div>

### When receiving a report

   * Perform initial criticality assessment, and ensure that the reporter is given a justification for all issues we classify or demote as non-security vulnerabilities.
   * If encrypted information is provided, add pass phrases into the SilverStripe Ltd. LastPass account. Keep encrypted documents in Google Drive and only share directly with relevant participants
   * Add a new issue in the "Backlog" on the [project board](https://github.com/silverstripe-security/security-issues/projects/1).
     Add a link to the [Google Groups](https://groups.google.com/a/silverstripe.com/forum/#!forum/security) discussion thread so it's easy to review follow up messages.
   * Use the [CVSS Calculator](https://nvd.nist.gov/vuln-metrics/cvss/v3-calculator) to determine the issue severity
   * Once the issue is confirmed, [request a CVE identifier](https://cveform.mitre.org/) under the security@silverstripe.org contact email (see "Acknowledgement and disclosure").
   * Once a CVE has been assigned, respond to issue reporter and add it to the Github issue 
   * Clarify who picks up and owns the issue (assign in Github).
     The owner can be separate from the developer resolving the issue,
     their primary responsibility is to ensure the issue keeps moving through the process correctly.

### When developing a fix

   * Ensure you're working on the oldest supported minor release branch of every supported major release (see [Supported Versions](#supported-versions))
   * Move the issue into "In Progress" on the [project board](https://github.com/silverstripe-security/security-issues/projects/1)
   * Add fixes on the [http://github.com/silverstripe-security](http://github.com/silverstripe-security) repo. Don't forget to update branches from the upstream repo.
   * Ensure that all security commit messages are prefixed with the CVE. E.g. "[CVE-2019-001] Fixed invalid XSS"
   * Get them peer reviewed by posting on security@silverstripe.org with a link to the Github issue

### Setting up satis for hosting private security releases

When installing a project from protected repositories, it's necessary prior to creating your project
to override the public repository URLs with the private repositories containing undisclosed fixes. For
this we use [satis](https://github.com/composer/satis).

To setup a Satis project for a release:

* Ensure Satis is installed globally: `composer global require composer/satis ^1` 
* `cd ~/Sites/` (or wherever your web-root is located)
* `mkdir satis-security && cd satis-security` (or some directory specific to your release)
* Create a config file (e.g. config.json) of the given format (add only those repositories necessary).

Note:
- The homepage path should match the eventual location of the package content
- You should add the root repository (silverstripe/installer) to ensure
 `create-project` works (even if not a private security fork).
- You should add some package version constraints to prevent having to parse
 all legacy tags and all branches.

```json
{
    "name": "SilverStripe Security Repository",
    "homepage": "http://localhost/satis-security/public",
    "repositories": {
        "installer": {
            "type": "vcs",
            "url": "https://github.com/silverstripe/silverstripe-installer.git"
        },
        "framework": {
            "type": "vcs",
            "url": "https://github.com/silverstripe-security/silverstripe-framework.git"
        }
    },
    "require": {
		"silverstripe/installer": "^3.5 || ^4",
		"silverstripe/framework": "^3.5 || ^4"
	},
    "require-all": true
}
```

* Build the repository:
  `satis build config.json ./public`
* Test you can view the satis home page at `http://localhost/satis-security/public/`
* When performing the release ensure you use `--repository=http://localhost/satis-security/public` (below)

<div class="warning" markdown="1">
It's important that you re-run `satis build` step after EVERY change that is pushed upstream; E.g. between
each release, if making multiple releases.
</div>

### Detailed CVE and CVSS Guidance

 * In the [CVE Request Form](https://cveform.mitre.org/), we follow certain conventions on fields:
   * Request with the `security@silverstripe.org` group email
   * **Vendor of the product(s):** SilverStripe
   * **Affected product(s)/code base - Product:** Composer module name (e.g. `silverstripe/framework`).
     Indirectly affected dependencies of the module should not be listed here.
   * **Affected product(s)/code base - Version:** Use Composer constraint notation,
     with one entry per major release line.
     Example for an issue affecting all framework releases: First line `^3.0`, second line `^4.0`.
     We don't know the target release version at this point, so can't set an upper constraint yet.
     It should include all affected versions, including any unsupported release lines.
   * **Affected component(s):** E.g. ORM, Template layer
   * **Suggested description of the vulnerability:** Keep this short, usually the issue title only.
     We want to retain control over editing the description in our own systems without going
     through lengthy CVE change processes.
   * **Reference(s):** Leave this blank. We'll send through a "Link to the advisory" as part of the publication request

## Standard release process

The release process, at a high level, involves creating a release, publishing it, and
reviewing the need for either another pre-release or a final stable tag within a short period
(normally within 3-5 business days).

### Using cow, the release tool

Releases are instantiated by running `cow release` in the format as below:

`cow release <version> [recipe] -vvv`

E.g.

`cow release 4.0.1 -vvv`

* `<version>` The recipe version that is to be released. E.g. `4.1.4` or `4.3.0-rc1`
* `<recipe>` `Optional: the recipe that is being released (default: "silverstripe/installer")

This command has these options (note that --repository option is critical for security releases):

* `-vvv` to ensure all underlying commands are echoed
* `--directory <directory>` to specify the folder to create or look for this project in. If you don't specify this,
it will install to the path specified by `./release-<version>` in the current directory.
* `--repository <repository>` will allow a custom composer package url to be specified. E.g. `http://packages.cwp.govt.nz`
  See the above section "Setting up satis for hosting private security releases" on how to prepare a custom
  repository for a security release.
* `--branching <type>` will specify a branching strategy. This allows these options:
  * `auto` - Default option, will branch to the minor version (e.g. 1.1) unless doing a non-stable tag (e.g. rc1)
  * `major` - Branch all repos to the major version (e.g. 1) unless already on a more-specific minor version.
  * `minor` - Branch all repos to the minor semver branch (e.g. 1.1)
  * `none` - Release from the current branch and do no branching.
* `--skip-tests` to skip tests
* `--skip-i18n` to skip updating localisations

This can take between 5-15 minutes, and will invoke the following steps,
each of which can also be run in isolation (in case the process stalls
and needs to be manually advanced):

* `release:create` The release version will be created in the `release-<version>`
  folder directly underneath the folder this command was invoked in. Cow
  will look at the available versions and branch-aliases of silverstripe/installer
  to determine the best version to install from. E.g. installing 4.0.0 will
  know to install dev-master, and installing 3.3.0 will install from 3.x-dev.
  If installing pre-release versions for stabilisation, it will use the correct
  temporary release branch.
* `release:plan` The release planning will take place, this reads the various dependencies of the recipe being released
  and determines what new versions of those dependencies need to be tagged to create the final release. Note that
  the patch version numbers of each module may differ. This step requires the latest versions to be released are
  determined and added to the plan. The conclusion of the planning step is output to the screen and requires user
  confirmation.
* `release:branch` If release:create installed from a non-rc branch, it will
  create the new temporary release branch (via `--branch-auto`). You can also customise this branch
  with `--branch=<branchname>`, but it's best to use the standard.
* `release:translate` All upstream transifex strings will be pulled into the
  local master strings, and then the [i18nTextCollector](api:SilverStripe\i18n\TextCollection\i18nTextCollector)
  task will be invoked and will merge these strings together, before pushing all
  new master strings back up to transifex to make them available for translation.
  Changes to these files will also be automatically committed to git.
* `release:test` Will run all unit tests on this release. Make sure that you
  setup your `.env` correctly (as above) so that this will work.
* `release:changelog` Will compare the current branch head with `--from` parameter
  version in order to generate a changelog file. This wil be placed into the
  `./framework/docs/en/04_Changelogs/` folder. If an existing file named after
  this version is already in that location, then the changes will be automatically
  regenerated beneath the automatically added line:
  `<!--- Changes below this line will be automatically regenerated -->`.
  It may be necessary to edit this file to add details of any upgrading notes
  or special considerations. If this is a security release, make sure that any
  links to the security registrar (http://www.silverstripe.org/download/security-releases)
  match the pages saved in draft.

#### Publishing with cow

Publication of the release is initiated with:

`cow release:publish 4.0.1 silverstripe/installer`

This command has these options:

* `-vvv` to ensure all underlying commands are echoed
* `--directory <directory>` to specify the folder to look for the project created in the prior step. As with
  above, it will be guessed if omitted. You can run this command in the `./release-<version>` directory and
  omit this option.


## See also

* [Release Process](release_process)
* [Translation Process](translation_process)
* [Core committers](core_committers)

If at any time a release runs into an unsolveable problem contact the
core committers on the [discussion group](https://groups.google.com/forum/#!forum/silverstripe-committers)
to ask for support.
