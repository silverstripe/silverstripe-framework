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
Our [module list on silverstripe.org](http://silverstripe.org/modules) lists the repository locations alongside
the archive downloads, typically using a version control system like "git" or "[subversion](subversion)". 

General guidelines:

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

The core team is responsible for reviewing patches and deciding if they will make it into core.  If
there are any problems they will follow up with you, so please ensure they have a way to contact you! 

### Sending pull requests (for git)

The SilverStripe core (`sapphire` and `cms`), as well as some of the more popular modules are in
git version control. SilverStripe hosts its modules on [github.com/silverstripe](http://github.com/silverstripe).

After [installing git](http://help.github.com/git-installation-redirect) and creating a [free github.com account](https://github.com/signup/free), you can "fork" a module,
which creates a copy that you can commit to (see github's [guide to "forking"](http://help.github.com/forking/)).

Example: [Fork the blog module](https://github.com/silverstripe/silverstripe-blog)

Now you have two choices: Smaller fixes (e.g. typos) can be edited directly in the github.com web interface
(every file view has an "edit this file" link). More commonly, you will work on a working copy on your own computer. After committing your fix, you can send the module authors a so called ["pull request"](http://help.github.com/pull-requests/).
The module authors will get notified automatically, review your patch, and merge it back as appropriate.
For new features, we recommend creating a ["feature branch"](http://progit.org/book/ch3-3.html) rather than a really big patch.

On github, you can review outstanding [sapphire pull requests](https://github.com/silverstripe/sapphire/pulls) and [cms pull requests](https://github.com/silverstripe/silverstripe-cms/pulls)).

If you want to learn more about git, please have a look at the [free online git book](http://progit.org/book/) and the [git crash course](http://gitref.org/).

### Submitting patches (for subversion)

Some modules will be hosted on [subversion](http://subversion.tigris.org), in which case you have to
package your changes as a "patch" file. Please read the [official Subversion book](http://svnbook.red-bean.com/) (available free online) for a general introduction to subversion. 

Please don't submit patch files for modules on git, as they cause us more work.

To submit a patch, [register](http://open.silverstripe.com/register) or [login](http://open.silverstripe.org/login) on open.silverstripe.org, and attach the patch to an appropriate ticket. 
Please include in the comment the revision number that the patch is applicable for and a brief outline of what you fixed and how. 

[Submit a patch (requires account on open.silverstripe.org)](http://open.silverstripe.com/newticket?field_type=patch&field_owner=ischommer&attachment=1)

You can create a patch file through the svn diff-command on the command-line. 
More info in the [svn redbook](http://svnbook.red-bean.com/en/1.1/ch03s05.html#svn-ch-3-sect-5.3.2). 
Your code editor might have a GUI for creating patches.

	# in a working copy folder (e.g /myproject)
	svn diff sapphire/ > ~/patch.diff

Some gotchas when using subversion and the patch format:

*  Submit your patch in *diff -u* or *diff -c format*. 
*  If your patch involves new files, create a compressed archive for them (including any required directory-structures)
*  Create patches relative to the working copy (*sapphire/main.php* instead of */Users/myuser/sapphire/main.php*)
*  Remember the shortcomingsof *svn diff*: Please document moved files and created/deleted directories separately

The "[Patches: Core](http://open.silverstripe.com/report/101)" and "[Patches: Modules](http://open.silverstripe.com/report/102)" reports will let you see where all the patches are at.

### Commit Messages

We try to maintain a consistent record of descriptive commit messages. As we automatically generate changelogs from them, we need a way to categorize and filter. Please prefix **all** commit messages with one of the following tags: 

* `API CHANGE`: You've added or modified the functions available to developers writing custom PHP.
* `ENHANCEMENT`: You've added something to the user-visible aspects of SilverStripe.
* `BUGFIX`: You've fixed something that was broken.
* `MINOR` Mark things that are so trivial they're not even worth telling users about; specifically, to prevent adding
clutter to our automatically generated changelogs. MINOR is not used to mark a minor bugfix or feature, see above.
Some examples:
	* a subsequent commit to a bugfix/feature that you committed earlier that day
	* adding unit tests (that are more interesting to developers of SilverStripe than users of it)
	* subversion/codebase plumbing (changing externals, blocking revisions, moving files around, etc)
	* In summary: if it's worth including in the changelog, it's not `MINOR`.

Further guidelines:

* Each commit should form a logical unit - if you fix two unrelated bugs, commit each one separately
* If you are fixing a ticket from our [bugtracker](http://open.silverstripe.com), please append `(fixes #<ticketnumber>)`
* If your change is related to another changeset, reference it with `r<revisionnumber>`. 
* Please mention the changed classes and methods in your comment - the message should be understandable on its own without browsing any sourcecode

Example: Bad commit message

	finally fixed this dumb rendering bug that Joe talked about ... LOL
	also added another form field for password validation

Example: Good commit message

	ENHANCEMENT Added prepValueForDB() which is called on DBField->writeToManipulation() to ensure formatting of value before insertion to DB on a per-DBField type basis (see #1234)
	MINOR Added documentation for DBField->writeToManipulation() (see r55555)
	
<div class="hint" markdown="1">
Note: By supplying code in patches, tickets and pull requests, 
you agree that is can be used in distributions and derivative works of SilverStripe CMS and associated modules, under the BSD license.
</div>

## Reporting Security Issues

Report security issues to [security@silverstripe.com](mailto:security@silverstripe.com). Please don't file security
issues in our [bugtracker](http://open.silverstripe.org). In the event of a confirmed vulnerability in SilverStripe
core, we will take the following actions:

*  Acknowledge to the reporter that we’ve received the report and that a fix is forthcoming. We’ll give a rough
timeline and ask the reporter to keep the issue confidential until we announce it.
*  Halt all other development as long as is needed to develop a fix, including patches against the current and one
previous major release (if applicable).
*  We will inform you about resolution and [announce](http://groups.google.com/group/silverstripe-announce) a [new
release](http://silverstripe.org/security-releases/) publically.

You can help us determine the problem and speed up responses by providing us with more information on how to reproduce
the issue: SilverStripe version (incl. any installed modules), PHP/webserver version and configuration, anonymized
webserver access logs (if a hack is suspected), any other services and web packages running on the same server.

## Writing Documentation

Documentation for a software project is a continued and collaborative effort,
we encourage everybody to contribute, from simply fixing spelling mistakes, to writing recipes/howtos,
reviewing existing documentation, and translating the whole thing.
Modifying documentation requires basic [PHPDoc](http://en.wikipedia.org/wiki/PHPDoc) and
[Markdown](http://daringfireball.net/projects/markdown/)/[SSMarkdown](ss-markdown) knowledge. 
If you have downloaded SilverStripe or a module, chances
are that you already have the documentation files - they are kept alongside the source code (in the `docs/` subfolder).

In general, you have to "[fork](http://help.github.com/forking/)" the [github.com/silverstripe/sapphire](http://github.com/silverstripe/sapphire)
and [github.com/silverstripe/silverstripe-cms](http://github.com/silverstripe/silverstripe-cms) repositories
and send us "[pull requests](http://help.github.com/pull-requests/)". 
Note: Smaller edits can be performed in the github.com web interface on your fork,
every page view should have an "edit this file" button.

The [doc.silverstripe.org](http://doc.silverstripe.org) website itself is powered by a
SilverStripe project that uses the ["sapphiredocs" module](https://github.com/silverstripe/silverstripe-sapphiredocs)
to convert Markdown formatted files into searchable HTML pages with index lists.

### Repositories

*  End-user: [userhelp.silverstripe.org](http://userhelp.silverstripe.org)
*  Developer Guides: [doc.silverstripe.org](http://doc.silverstripe.org)
*  Developer API Docuumentation: [api.silverstripe.org](http://api.silverstripe.org)

### Source Control

In order to balance editorial control with effective collaboration, we keep
documentation alongside the module source code, e.g. in `sapphire/docs/`,
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

Documentation is kept alongside the source code, typically in a module subdirectory like `sapphire/docs/en/`.
Each language has its own subfolder, which can duplicate parts or the whole body of documentation.
German documentation would for example live in `sapphire/docs/de/`.
The [sapphiredocs](https://github.com/silverstripe/silverstripe-sapphiredocs) module that drives
[doc.silverstripe.org](http://doc.silverstripe.org) automatically resolves these subfolders into a language dropdown.

### Further reading

* [Writing great documentation (jacobian.org)](http://jacobian.org/writing/great-documentation/)
* [How tech writing sucks: Five Sins](http://www.slash7.com/articles/2006/11/15/tech-writing-the-five-sins)
* [What is good documentation?](http://www.techscribe.co.uk/techw/whatis.htm)

## Translating the User Interface

The content for UI elements (button labels, field titles) and instruction texts shown in the CMS and
elsewhere is stored in the PHP code for a module (see [i18n](/topics/i18n)). All content can be extracted as a "language file"
which is then uploaded to [translate.silverstripe.org](http://translate.silverstripe.org). This website provides
an online editor for translators (like you!). Every now and then, translations will be merged back into
the codebase from there, and released alongside other PHP code.

SilverStripe is already translated in over 60 languages, and we're relying on native speakers
to keep these up to date, and of course add new languages. Please [register](http://translate.silverstripe.org/apply)
a free translator account to get started, even if you just feel like fixing up a few sentences.
