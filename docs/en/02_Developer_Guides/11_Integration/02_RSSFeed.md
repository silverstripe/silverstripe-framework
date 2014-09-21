# RSS Feed

## Introduction

Generating RSS/Atom-feeds is a matter of rendering a `[api:SS_List]` through
the `[api:RSSFeed]` class.

The `[api:RSSFeed]` class doesn't limit you to generating article based feeds,
it is just as easy to create a feed of your current staff members, comments or
any other custom `[api:DataObject]` subclasses you have defined. The only
logical limitation here is that every item in the RSS-feed should be accessible
through a URL on your website, so its advisable to just create feeds from sub
classes of `[api:SiteTree]`.

If you wish to generate an RSS feed for `[api:DataObject]` instances, ensure they
define an AbsoluteLink() method.

## Usage

	:::php
	RSSFeed::linkToFeed($link, $title)

This line should go in your `[api:Controller]` subclass in the action you want
to include the HTML link. Not all arguments are required, see `[api:RSSFeed]` and example below.  Last Modified Time is expected in seconds like time().

	:::php
	$feed = new RSSFeed(
		$list,
		$link,
		$title,
		$description,
		$titleField,
		$descriptionField,
		$authorField,
		$lastModifiedTime,
		$etag
	);

Creates a new `[api:RSSFeed]` instance to be returned. The arguments notify
SilverStripe what values to include in the feed.

## Examples

### Showing latest blog posts

	:::php
	class Page_Controller extends ContentController {
		private static $allowed_actions = array('rss');
		public function init() {
			parent::init();
			// linkToFeed will add an appropriate HTML link tag to the website
			// <head> tag to notify web browsers that an RSS feed is available
			// for this page. You can include as many feeds on the page as you
			// wish as long as each as a different link. For example:
			// ('blog/rss', 'staff/rss').
			//
			// In this example $this->Link("rss") refers to the *rss* function
			// we define below.
			RSSFeed::linkToFeed($this->Link("rss"), "RSS feed of this blog");
		}
		public function rss() {
			// Creates a new RSS Feed list
			$rss = new RSSFeed(
				$list = $this->getBlogPosts(), // an SS_List containing your feed items
				$link = $this->Link("rss"), // a HTTP link to this feed
				$title = "My feed", // title for this feed, displayed in RSS readers
				$description = "This is an example feed." // description
			);
			// Outputs the RSS feed to the user.
			return $rss->outputToBrowser();
		}
		public function getBlogPosts() {
			return BlogPage::get()->limit(10);
		}
	}

### Showing the 10 most recently updated pages

You can use `[api:RSSFeed]` to easily create a feed showing your latest Page
updates. Update mysite/code/Page.php to something like this:

	:::php
	<?php
	class Page extends SiteTree {}
	class Page_Controller extends ContentController {

		private static $allowed_actions = array('rss');

		public function init() {
			parent::init();
			RSSFeed::linkToFeed($this->Link() . "rss", "10 Most Recently Updated Pages");
		}

		public function rss() {
			$rss = new RSSFeed($this->LatestUpdates(), $this->Link(), "10 Most Recently Updated Pages", "Shows a list of the 10 most recently updated pages.");
			return $rss->outputToBrowser();
		}

		public function LatestUpdates() {
			return Page::get()->sort("LastEdited", "DESC")->limit(10);
		}
	}

### Rendering DataObjects in a RSSFeed

DataObjects can be rendered in the feed as well, however, since they aren't explicitly
`[api:SiteTree]` subclasses we need to include a function `AbsoluteLink` to allow the
RSS feed to link through to the item.

If the items are all displayed on a single page you may simply hard code the link to
point to a particular page.

Take an example, we want to create an RSS feed of all the Students, a DataObject we
defined in the [fifth tutorial](/tutorials/5-dataobject-relationship-management).

	:::php
	<?php
	class Student extends DataObject {
		public function AbsoluteLink() {
			// see tutorial 5, students are assigned a project, so the 'link'
			// to view the student is based on their projects link.
			return $this->Project()->AbsoluteLink();
		}
	}

Then update the Page_Controller class in mysite/code/Page.php to include an RSSFeed
for all the students as we've seen before.

	:::php
	class Page_Controller extends ContentController {
		private static $allowed_actions = array('students');
		public function init() {
			parent::init();
			RSSFeed::linkToFeed($this->Link("students"), "Students feed");
		}
		public function students() {
			$rss = new RSSFeed(
				$list = $this->getStudents(),
				$link = $this->Link("students"),
				$title = "Students feed"
			);
			return $rss->outputToBrowser();
		}
		public function getStudents() {
			return Student::get()->sort("Created", "DESC")->limit(10);
		}
	}

### Customizing the RSS Feed template

The default template used is framework/templates/RSSFeed.ss and includes
displaying titles and links to the content. If you have a particular need
for customizing the XML produced (say for additional meta data) use `setTemplate`.

Taking that last example, we would rewrite the students function to include a
unique template (write your own XML in themes/yourtheme/templates/Students.ss)

	:::php
	public function students() {
		$rss = new RSSFeed(
			$list = $this->getStudents(),
			$link = $this->Link("students"),
			$title = "Students feed"
		);
		$rss->setTemplate('Students');
		return $rss->outputToBrowser();
	}

## External Sources

`[api:RSSFeed]` only creates feeds from your own data. We've included the [SimplePie](http://simplepie.org) RSS-parser for
accessing feeds from external sources.


## Related

*  [blog module](http://silverstripe.org/blog-module)

## API Documentation

* `[api:RSSFeed]`
