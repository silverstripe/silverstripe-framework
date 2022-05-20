---
title: SearchFilter Modifiers
summary: Use suffixes on your ORM queries.
icon: search
---

# SearchFilter Modifiers

The `filter` and `exclude` operations specify exact matches by default. However, when filtering `DataList`s, there are a number of suffixes that
you can put on field names to change this behavior. These are represented as `SearchFilter` subclasses and include.

 * [ExactMatchFilter](api:SilverStripe\ORM\Filters\ExactMatchFilter)
 * [StartsWithFilter](api:SilverStripe\ORM\Filters\StartsWithFilter)
 * [EndsWithFilter](api:SilverStripe\ORM\Filters\EndsWithFilter) 
 * [PartialMatchFilter](api:SilverStripe\ORM\Filters\PartialMatchFilter)
 * [GreaterThanFilter](api:SilverStripe\ORM\Filters\GreaterThanFilter)
 * [GreaterThanOrEqualFilter](api:SilverStripe\ORM\Filters\GreaterThanOrEqualFilter)
 * [LessThanFilter](api:SilverStripe\ORM\Filters\LessThanFilter)
 * [LessThanOrEqualFilter](api:SilverStripe\ORM\Filters\LessThanOrEqualFilter)

An example of a `SearchFilter` in use:

```php
// fetch any player that starts with a S
$players = Player::get()->filter([
    'FirstName:StartsWith' => 'S',
    'PlayerNumber:GreaterThan' => '10'
]);

// to fetch any player that's name contains the letter 'z'
$players = Player::get()->filterAny([
    'FirstName:PartialMatch' => 'z',
    'LastName:PartialMatch' => 'z'
]);
```

Developers can define their own [SearchFilter](api:SilverStripe\ORM\Filters\SearchFilter) if needing to extend the ORM filter and exclude behaviors.

These suffixes can also take modifiers themselves. The modifiers currently supported are `":not"`, `":nocase"` and 
`":case"`. These negate the filter, make it case-insensitive and make it case-sensitive, respectively. The default
comparison uses the database's default. For MySQL and MSSQL, this is case-insensitive. For PostgreSQL, this is 
case-sensitive.

```php
// Fetch players that their FirstName is 'Sam'
// Caution: This might be case in-sensitive if MySQL or MSSQL is used
$players = Player::get()->filter([
    'FirstName:ExactMatch' => 'Sam'
]);

// Fetch players that their FirstName is 'Sam' (force case-sensitive)
$players = Player::get()->filter([
    'FirstName:ExactMatch:case' => 'Sam'
]);

// Fetch players that their FirstName is 'Sam' (force NOT case-sensitive)
$players = Player::get()->filter([
    'FirstName:ExactMatch:nocase' => 'Sam'
]);
```

By default the `:ExactMatch` filter is applied, hence why we can shorthand the above to:
```php
$players = Player::get()->filter('FirstName', 'Sam'); // Default DB engine behaviour
$players = Player::get()->filter('FirstName:case', 'Sam'); // case-sensitive
$players = Player::get()->filter('FirstName:nocase', 'Sam'); // NOT case-sensitive
```

Note that all search filters (e.g. `:PartialMatch`) refer to services registered with [Injector](api:SilverStripe\Core\Injector\Injector)
within the `DataListFilter.` prefixed namespace. New filters can be registered using the below yml
config:


```yaml
SilverStripe\Core\Injector\Injector:
  DataListFilter.CustomMatch:
    class: MyVendor\Search\CustomMatchFilter
```

The following is a query which will return everyone whose first name starts with "S", either lowercase or uppercase:

```php
$players = Player::get()->filter([
    'FirstName:StartsWith:nocase' => 'S'
]);

// use :not to perform a converse operation to filter anything but a 'W'
$players = Player::get()->filter([
    'FirstName:StartsWith:not' => 'W'
]);
```

## Related Lessons
* [Introduction to ModelAdmin](https://www.silverstripe.org/learn/lessons/v4/introduction-to-modeladmin-1)
* [Building a search form](https://www.silverstripe.org/learn/lessons/v4/building-a-search-form-1)

## API Documentation

* [SearchFilter](api:SilverStripe\ORM\Filters\SearchFilter)
