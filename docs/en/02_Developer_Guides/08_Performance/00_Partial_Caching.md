---
title: Partial Caching
summary: Cache SilverStripe templates to reduce database queries.
icon: tachometer-alt
---

# Partial Caching

[Partial template caching](../templates/partial_template_caching) is a feature that allows caching of rendered portions a template.


## Cache block conditionals

Use conditions whenever possible. The cache tag supports defining conditions via either `if` or `unless` keyword.
Those are optional, however is highly recommended.

[warning]
Avoid performing heavy computations in conditionals, as they are evaluated for every template rendering.
[/warning]

If you cache without conditions:
  - your cache backend will always be queried for the cache block (on every template render)
  - your cache may be cluttered with heaps of redundant and useless data (especially the default filesystem backend)

As an example, if you use `$DataObject->ID` as a key for the block, consider adding a condition that ID is greater than zero:

```ss
<% cached $MenuItem.ID if $MenuItem.ID > 0 %>
```

To cache the contents of a page for all anonymous users, but dynamically calculate the contents for logged in members,
 use something like:

```ss
<% cached unless $CurrentUser %>
```


## Aggregates

Sometimes you may want to invalidate cache when any object in a set changes, or when objects in a relationship
change. To do this, you may use [DataList](api:SilverStripe\ORM\DataList) aggregate methods (which we call Aggregates).
These perform SQL aggregate queries on sets of [DataObject](api:SilverStripe\ORM\DataObject)s.

Here are some useful methods of the [DataList](api:SilverStripe\ORM\DataList) class:
  - `int count()` : Return the number of items in this DataList
  - `mixed max(string $fieldName)` : Return the maximum value of the given field in this DataList
  - `mixed min(string $fieldName)` : Return the minimum value of the given field in this DataList
  - `mixed avg(string $fieldName)` : Return the average value of the given field in this DataList
  - `mixed sum(string $fieldName)` : Return the sum of the values of the given field in this DataList

To construct a `DataList` over a `DataObject`, we have a global template variable called `$List`.

For example, if we have a menu, we may want that menu to update whenever _any_ page is edited, but would like to cache it
otherwise. By using aggregates, we do that like this:

```ss
<% cached
     'navigation',
     $List('SilverStripe\CMS\Model\SiteTree').max('LastEdited'),
     $List('SilverStripe\CMS\Model\SiteTree').count()
%>
```

The cache for this will update whenever a page is added, removed or edited.

[note]
The use of the fully qualified classname is necessary.
[/note]

[note]
The use of both `.max('LastEdited')` and `.count()` makes sure we check for any object
edited or deleted since the cache was last built.
[/note]

[warning]
Be careful using aggregates. Remember that the database is usually one of the performance bottlenecks.
Keep in mind that every key of every cached block is recalculated for every template render, regardless of caching
result. Aggregating SQL queries are usually produce more load on the database than simple select queries,
especially if you query records by Primary Key or join tables using database indices properly.

Sometimes it may be cheaper to not cache altogether, rather than cache a block using a bunch of heavy aggregating SQL
queries.

Let us consider two versions:

```ss
# Version 1 (bad)

<% cached
    $List('SilverStripe\CMS\Model\SiteTree').max('LastEdited'),
    $List('SilverStripe\CMS\Model\SiteTree').count()
%>
    Parent title is: $Me.Parent.Title
<% end_cached %>
```

```ss
# Version 2 (better performance than Version 1)

Parent title is: $Me.Parent.Title
```

`Version 1` always generates two heavy aggregating SQL queries for the database on every
template render.  
`Version 2` always generates a single and more performant SQL query fetching the record by its Primary Key.

[/warning]


## Purposely stale data

In some situations it's more important to be fast than to always be showing the latest data. By constructing the cache
key to invalidate less often than the data updates you can ensure rendering time is constant no matter how often the
data updates.

For instance, if we show some blog statistics, but are happy having them be slightly stale, we could do


```ss
<% cached 'blogstatistics', $Blog.ID %>
```

which will invalidate after the cache lifetime expires. If you need more control than that (cache lifetime is
configurable only on a site-wide basis), you could add a special function to your controller:


```php
public function BlogStatisticsCounter() 
{
    return (int)(time() / 60 / 5); // Returns a new number every five minutes
}
```


and then use it in the cache key


```ss
<% cached 'blogstatistics', $Blog.ID, $BlogStatisticsCounter %>
```


## Cache backend

The template engine uses [Injector](../extending/injector) service `Psr\SimpleCache\CacheInterface.cacheblock` as
caching backend. The default definition of that service is very conservative and relies on the server filesystem.  
This is the most common denominator for most of the applications out there. However,
this is not the most robust neither performant cache implementation. If you have a better solution
available on your platform, you should consider tuning that setting for your application.  
All you need to do to swap the cache backend for partial template cache blocks is to redefine this service for the Injector.

Here's an example of how it could be done:

```yml
# app/_config/cache.yml

---
Name: app-cache
After:
  - 'corecache'
---
SilverStripe\Core\Injector\Injector:
  Psr\SimpleCache\CacheInterface.cacheblock: '%$App\Cache\Service.memcached'
```

[note]
For the above example to work it is necessary to have the Injector service `App\Cache\Service.memcached` defined somewhere in the configs.
[/note]

[warning]
The default filesystem cache backend does not support auto cleanup of the residual files with expired cache records.
If your project relies on Template Caching heavily (e.g. thousands of cache records daily), you may want to keep en eye on the
filesystem storage. Sooner or later its capacity may be exhausted.
[/warning]
