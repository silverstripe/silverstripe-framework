---
name: Initiate a minor release
about: Use this template to create an issue that schedules a new minor release of the core recipe.
---
# Minor release checklist

## Planning

- [ ] Internal comms have been made with intended release date
  - [ ] #cwp-and-oss-releases
  - [ ] Marketing
- [ ] Blog post has no pending changes and is in draft state on silverstripe.org
- [ ] All issues in milestone are closed or reassigned

## Execution

- [ ] Merge up minor release branches and push directly to origin
- [ ] `$ cow release:create [version] silverstripe/installer`
- [ ] `$ cow release:plan [version] silverstripe/installer`
- [ ] Use the [cow-compare](https://gist.github.com/unclecheese/0683140b8d1300638131ba9e9b20ee78) command to determine what modules need new tags. If the compare is empty or includes only non-functional changes, use current tag. (Gist will be deprecated once this is core funcionality. See [open PR](https://github.com/silverstripe/cow/pull/144) to merge this script into core).
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

- [ ] Minor release branches merged up
- [ ] All composer `branch-alias`es are updated
- [ ] New tag is on [releases](https://github.com/silverstripe/silverstripe-installer/releases) page
- [ ] Current milestone closed, new milestone created
- [ ] docs.silverstripe.org updated
  - [ ] Update `app/_config/docs-repositories.yml` to show new branch
  - [ ] Update `.htaccess` rewrite rule for "Contributing" `RewriteRule ^(.*)/(.*)/contributing/?(.*)?$ ...)`
  - [ ] Deploy
  - [ ] New changelog showing
  - [ ] Links to changelogs in announcements work   
- [ ] demo.silverstripe.org updated
  - [ ] Update composer.json to new minor release
  - [ ] Deploy
- [ ] Announce in the ["Releases" forum](https://forum.silverstripe.org/c/releases)
- [ ] Check for EOLs:
  - [ ] Announce EOLs in the ["Releases" forum](https://forum.silverstripe.org/c/releases)
  - [ ] Retarget any PRs to EOL branches
  - [ ] Update the [roadmap](https://www.silverstripe.org/admin/pages/edit/EditForm/3103/field/TableComponentItems/item/670/edit) to reflect new EOLs
- [ ] #ss4 Slack channel topic shows newest release with link to changelog
- [ ] Help marketing get the blog post out
