---
title: DataObject operation permissions
summary: A look at how permissions work for DataObject queries and mutations
---

# DataObject operation permissions

Any of the operations that come pre-configured for DataObjects are secured by the appropriate `canXXX` permissions
by default.

## Mutation permssions

[info]
When mutations fail due to permission checks, they throw a `PermissionsException`.
[/info]

For `create`, if a singleton instance of the record being created doesn't pass a `canCreate($member)` check,
the mutation will throw.

For `update`, if the record matching the given ID doesn't pass a `canEdit($member)` check, the mutation will
throw.

For `delete`, if any of the given IDs don't pass a `canDelete($member)` check, the mutation will throw.

## Query permissions

Query permissions are a bit more complicated, because they can either be in list form, (paginated or not),
or a single item. Rather than throw, these permission checks work as filters.

[notice]
It is critical that you have a `canView()` method defined on your dataobjects. Without this, only admins are
assumed to have permission to view a record.
[/notice]

For `readOne`, a plugin called `canViewItem` is installed by default. If the permission check for `canView($member)`
fails, the result comes back `null`.

For `read`, a plugin called `canViewList` or `canViewPaginated` (for paginated lists) will filter the result
set by the `canView($memeber)` check.

[notice]
When paginated items fail a `canView()` check, the `pageInfo` field is not affected. This can result in pages
showing a smaller number of items than what the page should contain, but keeps the pagination calls consistent
for `limit` and `offset` parameters.
[/notice]

##@ Disabling query permissions

Though not recommended, you can disable query permissions by setting their plugins to `false`.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      Page:
        operations:
          read:
            plugins:
              canViewPaginated: false
          readOne:
            plugins:
              canViewItem: false
```
