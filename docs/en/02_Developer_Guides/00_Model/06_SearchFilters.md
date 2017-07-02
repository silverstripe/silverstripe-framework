title: SearchFilter Modifiers
summary: Use suffixes on your ORM queries.

# SearchFilter Modifiers

The `filter` and `exclude` operations specify exact matches by default. However, there are a number of suffixes that
you can put on field names to change this behavior. These are represented as `SearchFilter` subclasses and include.

 * [api:SilverStripe\ORM\Filters\StartsWithFilter]
 * [api:SilverStripe\ORM\Filters\EndsWithFilter] 
 * [api:SilverStripe\ORM\Filters\PartialMatchFilter]
 * [api:SilverStripe\ORM\Filters\GreaterThanFilter]
 * [api:SilverStripe\ORM\Filters\GreaterThanOrEqualFilter]
 * [api:SilverStripe\ORM\Filters\LessThanFilter]
 * [api:SilverStripe\ORM\Filters\LessThanOrEqualFilter]

An example of a `SearchFilter` in use:
	
	:::php
	// fetch any player that starts with a S
	$players = Player::get()->filter(array(
		'FirstName:StartsWith' => 'S',
		'PlayerNumber:GreaterThan' => '10'
	));

	// to fetch any player that's name contains the letter 'z'
	$players = Player::get()->filterAny(array(
		'FirstName:PartialMatch' => 'z',
		'LastName:PartialMatch' => 'z'
	));

Developers can define their own [api:SilverStripe\ORM\Filters\SearchFilter] if needing to extend the ORM filter and exclude behaviors.

These suffixes can also take modifiers themselves. The modifiers currently supported are `":not"`, `":nocase"` and 
`":case"`. These negate the filter, make it case-insensitive and make it case-sensitive, respectively. The default
comparison uses the database's default. For MySQL and MSSQL, this is case-insensitive. For PostgreSQL, this is 
case-sensitive.

Note that all search filters (e.g. `:PartialMatch`) refer to services registered with [api:SilverStripe\Core\Injector\Injector]
within the `DataListFilter.` prefixed namespace. New filters can be registered using the below yml
config:


	:::yaml
	SilverStripe\Core\Injector\Injector:
	  DataListFilter.CustomMatch:
	    class: MyVendor/Search/CustomMatchFilter


The following is a query which will return everyone whose first name starts with "S", either lowercase or uppercase:

	:::php
	$players = Player::get()->filter(array(
		'FirstName:StartsWith:nocase' => 'S'
	));

	// use :not to perform a converse operation to filter anything but a 'W'
	$players = Player::get()->filter(array(
		'FirstName:StartsWith:not' => 'W'
	));

## API Documentation

* [api:SilverStripe\ORM\Filters\SearchFilter]
