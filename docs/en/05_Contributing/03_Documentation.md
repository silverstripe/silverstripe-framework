title: Documentation
summary: Developer and CMS User Help Documentation.

# Contributing Documentation

Documentation for a software project is a continued and collaborative effort, we encourage everybody to contribute, from 
simply fixing spelling mistakes, to writing recipes, reviewing existing documentation, and translating the whole thing.

Modifying documentation requires basic [PHPDoc](http://en.wikipedia.org/wiki/PHPDoc) and 
[Markdown](http://daringfireball.net/projects/markdown/) knowledge, and a GitHub user account.

## Editing online

The easiest way of making a change to the documentation is by clicking the "Edit this page" link at the bottom of the 
page you want to edit. Alternativly, you can find the appropriate .md file in the 
[github.com/silverstripe/silverstripe-framework](https://github.com/silverstripe/silverstripe-framework/tree/master/docs/) 
repository and press the "edit" button. **You will need a free GitHub account to do this**. 


 * After you have made your change, describe it in the "commit summary" and "extended description" fields below, and 
 press "Commit Changes".
 * After that you will see form to submit a Pull Request.  You should just be able to submit the form, and your changes 
 will be sent to the core team for approval.

<div class="warning" markdown='1'>
You should make the changes in the lowest branch they apply to. For instance, if you fix a spelling issue that you
found in the 3.1 documentation, submit your fix to that branch in Github and it'll be copied to the master (3.2)
version of the documentation automatically. *Don't submit multiple pull requests*.
</div>

## Editing on your computer

If you prefer to edit the content on your local machine, you can "[fork](http://help.github.com/forking/)" the 
[github.com/silverstripe/silverstripe-framework](http://github.com/silverstripe/silverstripe-framework) and 
[github.com/silverstripe/silverstripe-cms](http://github.com/silverstripe/silverstripe-cms) repositories and send us 
"[pull requests](http://help.github.com/pull-requests/)". If you have downloaded SilverStripe or a module, chances are 
that you already have these repositories on your machine.

The documentation is kept alongside the source code in the `docs/` subfolder of any SilverStripe module, framework or
CMS folder.

<div class="warning" markdown='1'>
If you submit a new feature or an API change, we strongly recommend that your patch includes updates to the necessary 
documentation.  This helps prevent our documentation from getting out of date.
</div>

## Repositories

*  End-user: [userhelp.silverstripe.org](http://userhelp.silverstripe.org).
*  Developer Guides: [doc.silverstripe.org](http://doc.silverstripe.org).
*  Developer API Docuumentation: [api.silverstripe.org](http://api.silverstripe.org).

## Source Control

In order to balance editorial control with effective collaboration, we keep documentation alongside the module source 
code, e.g. in `framework/docs/`, or as code comments within PHP code. Contributing documentation is the same process as 
providing any other patch (see [Contributing code](code)).

## What to write

See [What to write (jacobian.org)](http://jacobian.org/writing/great-documentation/what-to-write/) for an excellent
introduction to the different types of documentation, and 
[Producing OSS: "Documentation"](http://producingoss.com/en/getting-started.html#documentation) for good rules of thumb 
for documenting open source software.

## Structure

* Keep documentation lines to 120 characters.
* Don't duplicate: Search for existing places to put your documentation. Do you really require a new page, or just a new paragraph
of text somewhere?
* Use PHPDoc in source code: Leave lowlevel technical documentation to code comments within PHP, in [PHPDoc](http://en.wikipedia.org/wiki/PHPDoc) format. 
* API and Developer Guides complement each other: Both forms of documenting source code (API and Developer Guides) are valuable resources.
* Provide context: Give API documentation the "bigger picture" by referring to Developer Guides inside your PHPDoc.
* Make your documentation findable: Documentation lives by interlinking content, so please make sure your contribution doesn't become an
inaccessible island. Your page should at least be linked on the index page in the same folder. It can also appear
as "related content" on other resource (e.g. `/topics/search` might link to `howto/search-dataobjects`).

## Writing Style

* Write in second plural form: Use "we" instead of "I". It gives the text an instructive and collaborative style.
* It's okay to address the reader: For example "First you'll install a webserver" is good style.
* Write in an active and direct voice.
* Mark up correctly: Use preformatted text, emphasis and bold to make technical writing more "scannable".
* Avoid FAQs: FAQs are not a replacement of a coherent, well explained documentation. If you've done a good job
documenting, there shouldn't be any "frequently asked questions" left ;)

## Highlighted blocks

There are several built-in block styles for highlighting a paragraph of text. Please use these graphical elements 
sparingly.

<div class="hint" markdown='1'>
"Tip box": Adds, deepens or accents information in the main text. Can be used for background knowledge, or "see also" 
links.
</div>

Code:

	<div class="hint" markdown='1'>
	...
	</div>

<div class="notice" markdown='1'>
"Notification box": Technical notifications relating to the main text. For example, notifying users about a deprecated 
feature.
</div>

Code:

	<div class="notice" markdown='1'>
	...
	</div>

<div class="warning" markdown='1'>
"Warning box": Highlight a severe bug or technical issue requiring a users attention. For example, a code block with 
destructive functionality might not have its URL actions secured to keep the code shorter.
</div>

Code:

	<div class="warning" markdown='1'>
	...
	</div>

See [Markdown Extra Documentation](http://michelf.com/projects/php-markdown/extra/#html) for more restriction
on placing HTML blocks inside Markdown.

## Translating Documentation

Documentation is kept alongside the source code, typically in a module subdirectory like `framework/docs/en/`. Each 
language has its own subfolder, which can duplicate parts or the whole body of documentation. German documentation 
would for example live in `framework/docs/de/`. The 
[docsviewer](https://github.com/silverstripe/silverstripe-docsviewer) module that drives 
[doc.silverstripe.org](http://doc.silverstripe.org) automatically resolves these subfolders into a language dropdown.

## Further reading

* [Writing great documentation (jacobian.org)](http://jacobian.org/writing/great-documentation/)
* [How tech writing sucks: Five Sins](http://www.slash7.com/articles/2006/11/15/tech-writing-the-five-sins)
* [What is good documentation?](http://www.techscribe.co.uk/techw/whatis.htm)
