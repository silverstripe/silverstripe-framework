title: Partial Caching
summary: Cache SilverStripe templates to reduce database queries.

# Partial Caching

Partial caching is a feature that allows the caching of just a portion of a page. 

	:::ss
	<% cached 'CacheKey' %>
	$DataTable
	...
	<% end_cached %>


Each cache block has a cache key. A cache key is an unlimited number of comma separated variables and quoted strings. 
Every time the cache key returns a different result, the contents of the block are recalculated. If the cache key is 
the same as a previous render, the cached value stored last time is used.

Since the above example contains just one argument as the cache key, a string (which will be the same every render) it
will invalidate the cache after a given amount of time has expired (default 10 minutes).

Here are some more complex examples:

	:::ss
	<% cached 'database', LastEdited %> 
		<!-- that updates every time the record changes. -->
	<% end_cached %>
	
	<% cached 'loginblock', CurrentMember.ID %>
		<!-- cached unique to the user. i.e for user 2, they will see a different cache to user 1 -->
	<% end_cached %>

	<% cached 'loginblock', LastEdited, CurrentMember.isAdmin %>
		<!-- recached when block object changes, and if the user is admin -->
	<% end_cached %>

An additional global key is incorporated in the cache lookup. The default value for this is 
`$CurrentReadingMode, $CurrentUser.ID`. This ensures that the current `[api:Versioned]` state and user ID are used. 
This may be configured by changing the config value of `SSViewer.global_key`. It is also necessary to flush the 
template caching when modifying this config, as this key is cached within the template itself.

For example, to ensure that the cache is configured to respect another variable, and if the current logged in
user does not influence your template content, you can update this key as below;

**mysite/_config/app.yml**

	:::yaml
	SSViewer:
		global_key: '$CurrentReadingMode, $Locale'
	

## Aggregates

Often you want to invalidate a cache when any object in a set of objects change, or when the objects in a relationship 
change. To do this, SilverStripe introduces the concept of Aggregates. These calculate and return SQL aggregates
on sets of [api:DataObject]s - the most useful for us being the `Max` aggregate.

For example, if we have a menu, we want that menu to update whenever _any_ page is edited, but would like to cache it
otherwise. By using aggregates, we do that like this:

	:::ss
	<% cached 'navigation', List(SiteTree).max(LastEdited), List(SiteTree).count() %>

The cache for this will update whenever a page is added, removed or edited.

If we have a block that shows a list of categories, we can make sure the cache updates every time a category is added 
or edited

	:::ss
	<% cached 'categorylist', List(Category).max(LastEdited), List(Category).count() %>

<div class="notice" markdown="1">
Note the use of both `.max(LastEdited)` and `.count()` - this takes care of both the case where an object has been 
edited since the cache was last built, and also when an object has been deleted since the cache was last built.
</div>

We can also calculate aggregates on relationships. A block that shows the current member's favorites needs to update
whenever the relationship `Member::$has_many = array('Favourites' => Favourite')` changes.

	:::ss
	<% cached 'favourites', CurrentMember.ID, CurrentMember.Favourites.max(LastEdited) %>

## Cache key calculated in controller

In the previous example the cache key is getting a bit large, and is complicating our template up. Better would be to 
extract that logic into the controller.

	:::php

	public function FavouriteCacheKey() {
	    $member = Member::currentUser();
	
	    return implode('_', array(
	        'favourites',
	        $member->ID,
	        $member->Favourites()->max('LastEdited')
	    ));
	}

Then using that function in the cache key:

	:::ss
	<% cached FavouriteCacheKey %>


## Cache blocks and template changes

In addition to the key elements passed as parameters to the cached control, the system automatically includes the
template name and a sha1 hash of the contents of the cache block in the key. This means that any time the template is
changed the cached contents will automatically refreshed.

## Purposely stale data

In some situations it's more important to be fast than to always be showing the latest data. By constructing the cache
key to invalidate less often than the data updates you can ensure rendering time is constant no matter how often the
data updates.

For instance, if we show some blog statistics, but are happy having them be slightly stale, we could do

	:::ss
	<% cached 'blogstatistics', Blog.ID %>


which will invalidate after the cache lifetime expires. If you need more control than that (cache lifetime is
configurable only on a site-wide basis), you could add a special function to your controller:

	:::php
	public function BlogStatisticsCounter() {
	    return (int)(time() / 60 / 5); // Returns a new number every five minutes
	}

 
and then use it in the cache key

	:::ss
	<% cached 'blogstatistics', Blog.ID, BlogStatisticsCounter %>


## Cache block conditionals

You may wish to conditionally enable or disable caching. To support this, in cached tags you may (after any key
arguments) specify 'if' or 'unless' followed by a standard template variable argument. If 'if' is used, the resultant
value must be true for that block to be cached. Conversely if 'unless' is used, the result must be false.

Following on from the previous example, you might wish to only cache slightly-stale data if the server is experiencing
heavy load:

	:::ss
	<% cached 'blogstatistics', Blog.ID if HighLoad %>


By adding a `HighLoad` function to your `Page_Controller`, you could enable or disable caching dynamically.

To cache the contents of a page for all anonymous users, but dynamically calculate the contents for logged in members,
 use something like:

	:::ss
	<% cached unless CurrentUser %>

## Uncached

The template tag 'uncached' can be used - it is the exact equivalent of a cached block with an if condition that always 
returns false. The key and conditionals in an uncached tag are ignored, so you can easily temporarily disable a 
particular cache block by changing just the tag, leaving the key and conditional intact.

	:::ss
	<% uncached %>


## Nested cache blocks

You can also nest independent cache blocks  Any nested cache blocks are calculated independently from their containing 
block, regardless of the cached state of that container.

This allows you to wrap an entire page in a cache block on the page's LastEdited value, but still keep a member-specific
portion dynamic, without having to include any member info in the page's cache key.

An example:

	:::ss
	<% cached LastEdited %>
	  Our wonderful site
	
	  <% cached Member.ID %>
	    Welcome $Member.Name
	  <% end_cached %>
	
	  $ASlowCalculation
	<% end_cached %>


This will cache the entire outer section until the next time the page is edited, but will display a different welcome
message depending on the logged in member.

Cache conditionals and the uncached tag also work in the same nested manner. Since Member.Name is fast to calculate, you
could also write the last example as:

	:::ss
	<% cached LastEdited %>
	  Our wonderful site
	
	  <% uncached %>
	    Welcome $Member.Name
	  <% end_uncached %>
	
	  $ASlowCalculation
	<% end_cached %>

<div class="warning" markdown="1">
Currently cached blocks can not be contained within if or loop blocks. The template engine will throw an error
letting you know if you've done this. You can often get around this using aggregates.
</div>

Failing example:

	:::ss
	<% cached LastEdited %>
	
	  <% loop $Children %>
	    <% cached LastEdited %>
	      $Name
	    <% end_cached %>
	  <% end_loop %>
	
	<% end_cached %>

Can be re-written as:

	:::ss
	<% cached LastEdited %>
	
	  <% cached AllChildren.max(LastEdited) %>
	    <% loop $Children %>
	      $Name
	    <% end_loop %>
	  <% end_cached %>
	
	<% end_cached %>
