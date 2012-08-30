# Contributing

Any open source product is only as good as the community behind it. You can participate by sharing 
code, ideas, or simply helping others. No matter what your skill level is, every contribution counts.

See our [high level overview on silverstripe.org](http://silverstripe.org/contributing-to-silverstripe) on how you can help out.

## Sharing your Opinion

*  [silverstripe.org/forums](http://silverstripe.org/forums): Forums on silverstripe.org
*  [silverstripe-dev](http://groups.google.com/group/silverstripe-dev): Core development mailinglist
*  [silverstripe-documentation](http://groups.google.com/group/silverstripe-documentation): Documentation team mailing list

## Reporting Bugs

If you have discovered a bug in SilverStripe, we'd be glad to hear about it -
well written bug reports can be half of the solution already!
Our bugtracker is located on [open.silverstripe.org](http://open.silverstripe.org/) (create a [new ticket](http://open.silverstripe.org/newticket)).

## Submiting Bugfixes and Enhancements

We're not perfect, and need your help - for example in the form of patches for our modules and core codebase.

### Setup your project for contributions

In contrast to running a SilverStripe website, you can't use the standard download archive for this purpose.
The ["Installing from source"](../installation/from-source) guide explains how to get started.
For other modules, our [module list on silverstripe.org](http://silverstripe.org/modules) lists the repository locations, 
typically using a version control system like "git" or "[subversion](subversion)". 

### Check List

*  Adhere to our [coding conventions](coding-conventions)
*  If your patch is extensive, discuss it first on the [silverstripe forum](http///www.silverstripe.org/forums/) (ideally before doing any serious coding)
*  When working on existing tickets, assign them to you and provide status updates through ticket comments
*  Check your patches against the latest "trunk" or "master", as well as the latest release. 
Please not that the latest stable release will often not be sufficient! (of all modules)
*  Provide complete [unit test coverage](/topics/testing) - depending on the complexity of your work, this is a required
step.
*  Do not set milestones. If you think your patch should be looked at with priority, mark it as "critical".
*  Describe specifics on how to test the effects of the patch
*  It's better to submit multiple patches with separate bits of functionality than a big patch containing lots of
changes
*  Document your code inline through [PHPDoc](http://en.wikipedia.org/wiki/PHPDoc) syntax. See our 
[API documentation](http://api.silverstripe.org/trunk) for good examples.
* Also check and update documentation on [doc.silverstripe.org](http://doc.silverstripe.org). Check for any references to functionality deprecated or extended through your patch. Documentation changes should be included in the patch.
* We will attribute the change to you whereever possible (git does this automatically for pull requests)
* If you get stuck, please post to the [forum](http://silverstripe.org/forum) or for deeper core problems, to the [core mailinglist](https://groups.google.com/forum/#!forum/silverstripe-dev)
* When working with the CMS, please read the ["CMS Architecture Guide"](/reference/cms-architecture) first

The core team is responsible for reviewing patches and deciding if they will make it into core.  If
there are any problems they will follow up with you, so please ensure they have a way to contact you! 

### Sending git pull requests

The SilverStripe core modules (`framework` and `cms`), as well as some of the more popular modules are in
git version control. SilverStripe hosts its modules on [github.com/silverstripe](http://github.com/silverstripe) and [github.com/silverstripe-labs](http://github.com/silverstripe-labs).
After [installing git](http://help.github.com/git-installation-redirect) and creating a [free github.com account](https://github.com/signup/free), you can "fork" a module,
which creates a copy that you can commit to (see github's [guide to "forking"](http://help.github.com/forking/)).

Now you have two choices: Smaller fixes (e.g. typos) can be edited directly in the github.com web interface
(every file view has an "edit this file" link). More commonly, you will deal with a working copy on your own computer. After committing your fix, you can send the module authors a so called ["pull request"](http://help.github.com/pull-requests/).
The module authors will get notified automatically, review your patch, and merge it back as appropriate.
For new features, we recommend creating a ["feature branch"](http://progit.org/book/ch3-3.html) rather than a really big patch.
Note that we no longer accept patch file attachments ("diffs") as commonly created through subversion.

It is important that you use git branching properly in order to avoid messy and time-consuming merges
later on, so please read our in-depth ["Collaboration on Git"](collaboration-on-git) guide.
If you want to learn more about git, please have a look at the [free online git book](http://progit.org/book/) and the [git crash course](http://gitref.org/).

### Commit Messages

We try to maintain a consistent record of descriptive commit messages. 
Most importantly: Keep the first line short, and add more detail below.
This ensures commits are easy to browse, and look nice on github.com
(more info about [proper git commit messages](http://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html)).

As we automatically generate [changelogs](http://doc.silverstripe.org/sapphire/en/trunk/changelogs/) from them, we need a way to categorize and filter. 
Please prefix **noteworthy** commit messages with one of the following tags: 

* `NEW`: New feature or major enhancement (both for users and developers)
* `API`: Addition of a new API, or modification/removal/deprecation of an existing API.
  Includes any change developers should be aware of when upgrading.
* `BUG`: Bugfix or minor enhancement on something developers or users are likely to encounter.

All other commits should not be tagged if they are so trivial that most developers
can ignore them during upgrades or when reviewing changes to the codebase.
For example, adding unit tests or documentation would not be considered "noteworthy".
Same goes for version control plumbing like merges, file renames or reverts.

Further guidelines:

* Each commit should form a logical unit - if you fix two unrelated bugs, commit each one separately
* If you are fixing a ticket from our [bugtracker](http://open.silverstripe.com), please append `(fixes #<ticketnumber>)`
* If your change is related to another commit, reference it with its abbreviated commit hash. 
* Mention important changed classes and methods in the commit summary.

Example: Bad commit message

	finally fixed this dumb rendering bug that Joe talked about ... LOL
	also added another form field for password validation

Example: Good commit message

	BUG Formatting through prepValueForDB() 

	Added prepValueForDB() which is called on DBField->writeToManipulation() 
	to ensure formatting of value before insertion to DB on a per-DBField type basis (fixes #1234).
	Added documentation for DBField->writeToManipulation() (related to a4bd42fd).
	
<div class="hint" markdown="1">
Note: By supplying code in patches, tickets and pull requests, 
you agree that is can be used in distributions and derivative works of SilverStripe CMS and associated modules, under the BSD license.
</div>

## Reporting Security Issues

Report security issues to [security@silverstripe.com](mailto:security@silverstripe.com). See our "[Release Process](release-process)" documentation for more info, and read our guide on [how to write secure code](/topics/security).

## Writing Documentation

Documentation for a software project is a continued and collaborative effort,
we encourage everybody to contribute, from simply fixing spelling mistakes, to writing recipes/howtos, 
reviewing existing documentation, and translating the whole thing.

Modifying documentation requires basic [PHPDoc](http://en.wikipedia.org/wiki/PHPDoc) and
[Markdown](http://daringfireball.net/projects/markdown/)/[SSMarkdown](ss-markdown) knowledge,
and a GitHub user account.

### Editing online

The easiest way of making a change the the documentation is to find the appropriate .md 
file in the [github.com/silverstripe/sapphire](https://github.com/silverstripe/sapphire/edit/3.0/docs/) repository
and press the "edit" button.  You will need a GitHub account to do this.

 * After you have made your change, describe it in the "commit summary" and "extended description" fields below, and press "Commit Changes".
 * After that you will see form to submit a Pull Request.  You should just be able to submit the form, and your changes will be sent to the core team for approval.

**Coming soon:** each documentation page will have an "edit" link, to make it easier for you to find this feature.

### Editing on your computer

If you prefer to edit the content on your local machine, you can "[fork](http://help.github.com/forking/)" 
the [github.com/silverstripe/sapphire](http://github.com/silverstripe/sapphire)
and [github.com/silverstripe/silverstripe-cms](http://github.com/silverstripe/silverstripe-cms) 
repositories and send us "[pull requests](http://help.github.com/pull-requests/)".   If you have 
downloaded SilverStripe or a module, chances are that you already have these checkouts.

The documentation is kept alongside the source code in the `docs/` subfolder.

**Note:** If you submit a new feature or an API change, we strongly recommend that your patch
includes updates to the necessary documentation.  This helps prevent our documentation from 
getting out of date.

### Repositories

*  End-user: [userhelp.silverstripe.org](http://userhelp.silverstripe.org) - a custom SilverStripe project (not open sourced at the moment).
*  Developer Guides: [doc.silverstripe.org](http://doc.silverstripe.org) - powered by a
SilverStripe project that uses the ["sapphiredocs" module](https://github.com/silverstripe/silverstripe-sapphiredocs)
to convert Markdown formatted files into searchable HTML pages with index lists.
Its contents are fetched from different releases automatically every couple of minutes.
*  Developer API Docuumentation: [api.silverstripe.org](http://api.silverstripe.org) - powered by a customized
[phpDocumentor](http://www.phpdoc.org/) template, and is regenerated automatically every night.

### Source Control

In order to balance editorial control with effective collaboration, we keep
documentation alongside the module source code, e.g. in `framework/docs/`,
or as code comments within PHP code.
Contributing documentation is the same process as providing any other patch
(see [Patches and Bugfixes](contributing#submitting-patches-bugfixes-and-enhancements)).

### What to write

* **API Docs**: Written alongside source code and displayed on [api.silverstripe.com](http://api.silverstripe.org). 
  This documents the low-level, technical usage of a class, method or property.
  Not suited for longer textual descriptions, due to the limited support of PHPDoc formatting for headlines. 
* **Tutorials**: The first contact for new users, guiding them step-by-step through achievable projects, in a book-like style.
  *Example: Building a basic site*
* **Topics**: Provides an overview on how things fit together, the "conceptual glue" between APIs and features. 
  This is where most documentation should live, and is the natural "second step" after finishing the tutorials.
  *Example: Templates, Testing, Datamodel*
* **Howto**: Recipes that solve a specific task or problem, rather than describing a feature.
  *Example: Export DataObjects as CSV, Customizing TinyMCE in the CMS*
* **Reference**: Complements API docs in providing deeper introduction into a specific API. Most documentation
  should fit elsewhere. *Example: ModelAdmin*
* **Misc**: "Meta" documentation like coding conventions that doesn't directly relate to a feature or API. 

See [What to write (jacobian.org)](http://jacobian.org/writing/great-documentation/what-to-write/) for an excellent
introduction to the different types of documentation, and [Producing OSS: "Documentation"](http://producingoss.com/en/getting-started.html#documentation)
for good rules of thumb for documenting opensource software.

### Structure

* Don't duplicate: Search for existing places to put your documentation. Do you really require a new page, or just a new paragraph
of text somewhere?
* Use PHPDoc in source code: Leave lowlevel technical documentation to code comments within PHP, in [PHPDoc](http://en.wikipedia.org/wiki/PHPDoc) format. 
* Use Markdown in Developer Guides: We have a slightly customized version of Markdown called [SSMarkdown](ss-markdown)
* API and Developer Guides complement each other: Both forms of documenting sourcecode (API and Developer Guides) are valueable ressources.
* Provide context: Give API documentation the "bigger picture" by referring to Developer Guides inside your PHPDoc.
* Make your documentation findable: Documentation lives by interlinking content, so please make sure your contribution doesn't become an
inaccessible island. Your page should at least be linked on the index page in the same folder. It can also appear
as "related content" on other resource (e.g. `/topics/search` might link to `howto/search-dataobjects`).
* Avoid FAQs: FAQs are not a replacement of a coherent, well explained documentation. If you've done a good job
documenting, there shouldn't be any "frequently asked questions" left ;)
* Commit early and often: You don't need to completely finish documentation, as long as you mark areas needing refinement.
* Every file should have exactly one `<h1>` headline, roughly matching the filename. It should be short enough to be
used in table of content lists.

### Writing Style

* Write in second plural form: Use "we" instead of "I". It gives the text an instructive and collaborative style.
* Its okay to address the reader: For example "First you'll install a webserver" is good style.
* Write in an active and direct voice
* Mark up correctly: Use preformatted text, emphasis and bold to make technical writing more "scannable".

### Highlighted blocks ###

There are several built-in block styles for highlighting a paragraph of text. 
Please use these graphical elements sparingly.

<div class="hint" markdown='1'>
"Tip box": Adds, deepens or accents information in the main text.
Can be used for background knowledge, or "see also" links.
</div>

Code:

	<div class="hint" markdown='1'>
	...
	</div>

<div class="notice" markdown='1'>
"Notification box": Technical notifications relating to the main text.
For example, notifying users about a deprecated feature.
</div>

Code:

	<div class="notice" markdown='1'>
	...
	</div>

<div class="warning" markdown='1'>
"Warning box": Highlight a severe bug or technical issue requiring
a users attention. For example, a code block with destructive functionality 
might not have its URL actions secured to keep the code shorter.
</div>

Code:

	<div class="warning" markdown='1'>
	...
	</div>

See [Markdown Extra Documentation](http://michelf.com/projects/php-markdown/extra/#html) for more restriction
on placing HTML blocks inside Markdown.

### Translating Documentation

Documentation is kept alongside the source code, typically in a module subdirectory like `framework/docs/en/`.
Each language has its own subfolder, which can duplicate parts or the whole body of documentation.
German documentation would for example live in `framework/docs/de/`.
The [sapphiredocs](https://github.com/silverstripe/silverstripe-sapphiredocs) module that drives
[doc.silverstripe.org](http://doc.silverstripe.org) automatically resolves these subfolders into a language dropdown.

### Further reading

* [Writing great documentation (jacobian.org)](http://jacobian.org/writing/great-documentation/)
* [How tech writing sucks: Five Sins](http://www.slash7.com/articles/2006/11/15/tech-writing-the-five-sins)
* [What is good documentation?](http://www.techscribe.co.uk/techw/whatis.htm)

## Translating the User Interface

The content for UI elements (button labels, field titles) and instruction texts shown in the CMS and
elsewhere is stored in the PHP code for a module (see [i18n](/topics/i18n)). 
All content can be extracted as a "language file", and uploaded to an online translation editor interface.
SilverStripe is already translated in over 60 languages, and we're relying on native speakers
to keep these up to date, and of course add new languages. 
Please [register](translation-process) a free translator account to get started, 
even if you just feel like fixing up a few sentences.
See [our translation workflow](translation-process) for more details.

## Related

 * [Installation: From Source](/installation/from-source)
 * [Collaboration on Git](/misc/collaboration-on-git)
