---
title: Managing Security Issues
summary: This document highlights how Silverstripe CMS team handles security issue.
iconBrand: github-alt
---

# Managing Security Issues

This document aims to provide a high level overview of how the Silverstripe CMS team handles security issues. Only members of the Silverstripe CMS security team can perform the actions outline in this document.

## Identifying/reporting security issues confidentially 

This process is relevant when a potential vulnerability is reported confidentially and the Silverstripe CMS development team is in a position to prepare a patch prior to the public disclosure of the vulnerability.

This process is usually started once someone [reports a security issue](Issues-and-Bugs/##security-issue).

### When receiving a report

   * An automated response is sent back to the reporter to acknowledge receipt of their vulnerability report.
   * Perform an initial assessment of the report.
   * [Create a issue in our private security repository](https://github.com/silverstripe-security/security-issues/issues/new) unless to report is obviously invalid. e.g. SPAM
   * If encrypted information is provided, attach it to the private security issue.
   * Reply to [security@silverstripe.org](mailto:security@silverstripe.org) only with a link to the private security issue. Keep most of the discussion on GitHub.
   * Perform initial criticality assessment. Validate assessment with another member of the security team before replying to the reporter with your conclusion. Ensure the reporter is given a justification for all issues we classify or demote as non-security vulnerabilities. You may need to seek additional information from the reporter before completing the criticality assessment.
   * Add a new issue in the "Backlog" on the [project board](https://github.com/silverstripe-security/security-issues/projects/1).
   * Use the [CVSS Calculator](https://nvd.nist.gov/vuln-metrics/cvss/v3-calculator) to determine the issue severity
   * Once the issue is confirmed, create a _draft security advisory_ against the relevant GitHub repository. This will allow you to request a CVE.
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

### Before release (or release candidate)

   * For issues rated "high" or "critical" (CVSS of >=7.0), post a pre-announcement to the [security pre-announcement list](https://groups.google.com/a/silverstripe.com/forum/#!forum/security-preannounce).
     It should include a basic "preannouncement description" which doesn't give away too much,
     the CVSS score as well as the CVE identifier.
   * Create a draft page under [Open Source > Download > Security Releases](https://www.silverstripe.org/admin/pages/edit/show/794).
     Populate it with the information from the [Github project board](https://github.com/silverstripe-security/security-issues/projects/1).
   * Link to silverstripe.org security release page in the changelog.
   * Move the issue to "Awaiting Release" in the [project board](https://github.com/silverstripe-security/security-issues/projects/1)

### Perform release

   * Public disclosure of security vulnerabilities need to happen in stable releases (not pre-releases)
   * Merge back from [http://github.com/silverstripe-security](http://github.com/silverstripe-security) repos shortly at the release (minimise early disclosure through source code)
   * Merge up to newer minor release branches (see [Supported Versions](#supported-versions))
   * Setup a temporary [satis](https://github.com/composer/satis) repository which points to all relevant repositories
  containing security fixes. See below for setting up a temporary satis repository.
   * Once release testing is completed and the release is ready for stabilisation, then these fixes
  can then be pushed to the upstream module fork, and the release completed as per normal.
   * Follow the steps for [making a core release](making_a_silverstripe_core_release)
 
### After release

   * Publish silverstripe.org security release page
   * Respond to issue reporter with reference to the release on the same discussion thread (cc security@silverstripe.org)
   * File a [CVE Publication Request](https://cveform.mitre.org/), and add a link to the security release
     through the "Link to the advisory" field. Note on the security issue thread
     that you've requested publication (to avoid double ups)
   * Move the issue to "Done" in the [project board](https://github.com/silverstripe-security/security-issues/projects/1)



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

## See also

* [Release Process](release_process)
* [Translation Process](translation_process)
* [Core committers](core_committers)

If at any time a release runs into an unsolveable problem contact the
core committers on the [discussion group](https://groups.google.com/forum/#!forum/silverstripe-committers)
to ask for support.
