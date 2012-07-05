# RSS Feed

## Introduction

Generating RSS/Atom-feeds is just a matter of rendering a `[api:DataObject]` and the Page Comment Interface. 
Handled through the `[api:RSSFeed]` class.

`[api:RSSFeed]` doesn't limit you to generating "article-based" feeds, it is just as easy to create a feed of your current
staff-members. The only logical limitation here is that every item in the RSS-feed should be accessible through a URL on
your website, so its advisable to just create feeds from subclasses of `[api:SiteTree]`.

## Usage

### Showing latest Blog posts

*  The first part will add an appropriate link tag for autodetecting RSS feeds
*  The second part sets up /this-page/rss to return the RSS feed.  This one returns the children of the current page.

	:::php
		public function init() {
			RSSFeed::linkToFeed($this->Link() . "rss", "RSS feed of this blog");
			parent::init();
		}
		
		public function rss() {
			$rss = new RSSFeed($this->Children(), $this->Link(), "My feed", "This is an example feed.", "Title", "Content", "Author");
			$rss->outputToBrowser();
		}


### Example of showing the 10 most recently updated pages


You can use `[api:RSSFeed]` to easily create a feed showing your latest Page updates. Just change mysite/code/Page.php to
something like this:

	:::php
	<?php
	class Page extends SiteTree {
		static $db = array(
		);
		static $has_one = array(
	   );
	}
	
	class Page_Controller extends ContentController {
		
		public function init() {
			RSSFeed::linkToFeed($this->Link() . "rss", "10 Most Recently Updated Pages");
			parent::init();
		}
		
		public function rss() {
			$rss = new RSSFeed($this->LatestUpdates(), $this->Link(), "10 Most Recently Updated Pages", "Shows a list of the 10 most recently updated pages.", "Title", "Content", "Author");
			$rss->outputToBrowser();
		}
	
		public function LatestUpdates() {
			// 10 is the number of pages
			return Page::get()->sort("LastEdited", "DESC")->limit(10);
		} 
	}
	
	?>

## Viewing Comment RSS Feeds

You can view RSS feeds for comments for a certain page or for all comments on your site by visiting
http://www.yoursite.com/PageComment/rss . That produces a RSS Feed of the most recent comments to all of your site. You
can also do http://www.yoursite.com/PageComment/rss?pageid=46 where pageid is the id of the page you want to follow


## External Sources

`[api:RSSFeed]` only creates feeds from your own data. We've included the [SimplePie](http://simplepie.org) RSS-parser for
accessing feeds from external sources.


## Related

*  [blog module](http://silverstripe.org/blog-module)

## API Documentation
`[api:RSSFeed]`