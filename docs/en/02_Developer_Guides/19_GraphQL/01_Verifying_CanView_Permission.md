---
title: Controlling CanView access to DataObjects returned by GraphQL 
summary: Your GraphQL service should honour the CanView permission when fetching DataObjects. Learn how to customise this access control check.   
icon: cookie-bite
---

# Controlling who can view results in a GraphQL result set

The [Silverstripe ORM provides methods to control permissions on DataObject](Developer_Guides/Model/Permissions). In 
most cases, you'll want to extend this permission model to any GraphQL service you implement that returns a DataObject.

## The QueryPermissionChecker interface

The GraphQL module includes a `QueryPermissionChecker` interface. This interface can be used to specify how GraphQL 
services should validate that users have access to the DataObjects they are requesting.

The default implementation of `QueryPermissionChecker` is `CanViewPermissionChecker`. `CanViewPermissionChecker` directly calls the `CanView` method of each DataObject in your result set and filters out the entries not visible to the current user.

Out of the box, the `CanView` permission of your DataObjects are honoured when Scaffolding GraphQL queries.

## Customising how the results are filtered

`CanViewPermissionChecker` has some limitations. It's rather simplistic and will load each entry in your results set to perform a CanView call on it. It will also convert the results set to an `ArrayList` which can be inconvenient if you need to alter the underlying query after the _CanView_ check.

Depending on your exact use case, you may want to implement your own `QueryPermissionChecker` instead of relying 
on `CanViewPermissionChecker`.

Some of the reasons you might consider this are:
* the access permissions on your GraphQL service differ from the ones implemented directly on your DataObject
* you want to speed up your request by filtering out results the user doesn't have access to directly in the query
* you would rather have a `DataList` be returned.

### Implementing your own QueryPermissionChecker class

The `QueryPermissionChecker` requires your class to implement two methods:
* `applyToList` which filters a `Filterable` list based on whether a provided `Member` can view the results
* `checkItem` which checks if a provided object can be viewed by a specific `Member`.

#### Filtering results based on the user's permissions 

In some context, whether a user can view an object is entirely determined on their permissions. When that's the
case, you don't even need to get results to know if the user will be able to see them or not.

```php
<?php
use SilverStripe\GraphQL\Permission\QueryPermissionChecker;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\Filterable;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
* This implementation assumes that only users with the ADMIN permission can view results.
*/
class AdminPermissionChecker implements QueryPermissionChecker
{

    public function applyToList(Filterable $list, Member $member = null)
    {
        return Permission::check('ADMIN', 'any', $member) ?
        $list :
        ArrayList::create([]);
    }

    public function checkItem($item, Member $member = null)
    {
        return Permission::check('ADMIN', 'any', $member);
    }
}
```

#### Filtering results based on the user's permissions 

Some times, whether a user can view an object is determined by some information on the record. If that's the case,
you can filter out results the user can not see by altering the query. This has some performance advantage, because 
the results are filtered directly by the query.

```php
<?php
use SilverStripe\GraphQL\Permission\QueryPermissionChecker;
use SilverStripe\ORM\Filterable;
use SilverStripe\Security\Member;

/**
 * This implementation assumes that the results are assigned an owner and that only the owner can view a record.
 */
class OwnerPermissionChecker implements QueryPermissionChecker
{

    public function applyToList(Filterable $list, Member $member = null)
    {
        return $list->filter('OwnerID', $member ? $member->ID : -1);
    }

    public function checkItem($item, Member $member = null)
    {
        return $member && $item->OwnerID === $member->ID;
    }
}
```

### Using a custom QueryPermissionChecker implementation

There's three classes that expect a `QueryPermissionChecker`:
* `SilverStripe\GraphQL\Scaffolding\Scaffolders\ItemQueryScaffolder`
* `SilverStripe\GraphQL\Scaffolding\Scaffolders\ListQueryScaffolder`
* `SilverStripe\GraphQL\Pagination\Connection`

Those classes all implement the `SilverStripe\GraphQL\Permission\PermissionCheckerAware` and receive the default
`QueryPermissionChecker` from the Injector. They also have a `setPermissionChecker` method that can be use to provide a custom `QueryPermissionChecker`.

#### Scaffolding types with a custom QueryPermissionChecker implementation 

There's not any elegant way of defining a custom `QueryPermissionChecker` when scaffolding types at this time. If 
you need the ability to use a custom `QueryPermissionChecker`, you'll have to build your query manually.

#### Overriding the QueryPermissionChecker for a class extending ListQueryScaffolder

If you've created a GraphQL query by creating a subclass of `ListQueryScaffolder`, you can use the injector to
override `QueryPermissionChecker`.

```yaml
---
Name: custom-graphqlconfig
After: graphqlconfig
---
SilverStripe\Core\Injector\Injector:
  SilverStripe\GraphQL\Permission\QueryPermissionChecker.my-custom:
    class: App\Project\CustomQueryPermissionChecker
  App\Project\CustomListQueryScaffolder:
    properties:
      permissionChecker: '%$SilverStripe\GraphQL\Permission\QueryPermissionChecker.my-custom'
```

#### Manually specifying a QueryPermissionChecker on a Connection

If you're manually instantiating an instance of `SilverStripe\GraphQL\Pagination\Connection` to resolve your results,
you can pass an instance of your own custom `QueryPermissionChecker`.

```php
$childrenConnection = Connection::create('Children')
    ->setConnectionType($this->manager->getType('Children'))
    ->setSortableFields([
        'id' => 'ID',
        'title' => 'Title',
        'created' => 'Created',
        'lastEdited' => 'LastEdited',
    ])
    ->setPermissionChecker(new CustomQueryPermissionChecker());
```

## API Documentation

* [CanViewPermissionChecker](api:SilverStripe\GraphQL\Permission\CanViewPermissionChecker)
* [Connection](api:SilverStripe\GraphQL\Pagination\Connection)
* [ItemQueryScaffolder](api:SilverStripe\GraphQL\Scaffolding\Scaffolders\ItemQueryScaffolder)
* [ListQueryScaffolder](api:SilverStripe\GraphQL\Scaffolding\Scaffolders\ListQueryScaffolder)
* [PermissionCheckerAware](api:SilverStripe\GraphQL\Permission\PermissionCheckerAware)
* [QueryPermissionChecker](api:SilverStripe\GraphQL\Permission\QueryPermissionChecker)
