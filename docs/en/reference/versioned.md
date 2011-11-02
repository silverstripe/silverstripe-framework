# Versioned

The Versioned class is a `[api:DataObject]` that adds versioning and staging capabilities to the objects.

## Trapping the publication event

Sometimes, you'll want to do something whenever a particular kind of page is published.  This example sends an email
whenever a blog entry has been published.

	:::php
	class Page extends SiteTree {
	  // ...
	  function onAfterPublish() {
	    mail("sam@silverstripe.com", "Blog published", "The blog has been published");
	    parent::onAfterPublish();
	  }
	}
	
