---
name: Initiate a security release
about: Use this template to create an issue that schedules a new security release of the core recipe.
---

# Security release checklist

## Planning

- [ ] Internal comms have been made with intended release date
  - [ ] #cwp-and-oss-releases
  - [ ] Marketing
- [ ] All issues in milestone are closed or reassigned

## Preparation

*Start ~1 week before intended release date*

- [ ] All security fixes requiring pre-disclosure (CVSS >= 7.0) have been disclosed in the [security-preannounce](https://groups.google.com/a/silverstripe.com/forum/#!forum/security-preannounce) group.
- [ ] Security pre-annoucements are general enough to convey importance, but not specific enough to put affected sites at risk.
- [ ] Draft disclosures of each fix have been created on the [security releases page](https://www.silverstripe.org/download/security-releases/).
- [ ] Security pages are populated with the information from the [Github project board](https://github.com/silverstripe-security/security-issues/projects/1).
- [ ] Each disclosure has versions affected, description, and, if applicable, a CVE identifier and [CVSS score](https://nvd.nist.gov/vuln-metrics/cvss/v3-calculator)
- [ ] Changelog links to [security releases](https://www.silverstripe.org/download/security-releases/) detail page
- [ ] All security fixes in the release are in the "Awaiting release" column on the [project board](https://github.com/silverstripe-security/security-issues/projects/1)
- [ ] Security repos are up to date with their public counterparts (double checked on release day, but keeping them up to date will minimise surprises)

## Execution

- [ ] Push all affected public repositories to their respective private security repositories (e.g. `git pull origin 4 && git push security 4`).
- [ ] Review each pull request in the security repositories. Check that after syncing, there are:
  - [ ] No new merge conflicts
  - [ ] Tests are passing
  - [ ] No new comments that require a response
- [ ] Merge each pull request once the above criteria are met
- [ ] Wait for builds to go green
- [ ] When green, push security upstreams to their respective public repositories
- [ ] **I understand that the security issues have now been publicly disclosed**

- [ ] Merge up minor release branches and push directly to origin
- [ ] `$ cow release:create [version] silverstripe/installer`
- [ ] `$ cow release:plan [version] silverstripe/installer`
- [ ] Use the [cow-compare](https://gist.github.com/unclecheese/0683140b8d1300638131ba9e9b20ee78) command to determine what modules need new tags. If the compare is empty or includes only non-functional changes, use current tag. See [open PR](https://github.com/silverstripe/cow/pull/144) to merge this script into core cow functionality.
- [ ] `$ cow release:branch [version] silverstripe/installer`
- [ ] `$ cow release:translate [version] silverstripe/installer`
- [ ] `$ cow release:changelog [version] silverstripe/installer`
  - [ ] Review changelog:
    - [ ] Preamble is in publishable state:
      - [ ] Major changes announced
      - [ ] Upgrading notes included (if applicable)
      - [ ] All security fixes (if applicable) are included
      - [ ] No typos
    - [ ] No duplicates
    - [ ] No merge commits
- [ ] Smoke test release by running release webroot in localhost or VM
- [ ] `cow release:tag [version] silverstripe/installer`

## Publication
 
- [ ] All security issues are in the "done" column
- [ ] Minor release branches merged up
- [ ] New tag is on [releases](https://github.com/silverstripe/silverstripe-installer/releases) page
- [ ] Current milestone closed, new milestone created
- [ ] demo.silverstripe.org updated
  - [ ] Update composer.json to new minor release
  - [ ] Deploy
- [ ] docs.silverstripe.org updated:
  - [ ] New changelog showing
  - [ ] Links to changelogs in announcements work
- [ ] Announce in the ["Releases" forum](https://forum.silverstripe.org/c/releases)
- [ ] #ss4 Slack channel topic shows newest release with link to changelog
- [ ] Security release pages published
- [ ] Response to issue reporter with reference to the release on the same discussion thread (cc security@silverstripe.org) sent
- [ ] [CVE publication request](https://cveform.mitre.org/) submitted with link to disclosure on silverstripe.org
- [ ] Comms to OS teams and marketing