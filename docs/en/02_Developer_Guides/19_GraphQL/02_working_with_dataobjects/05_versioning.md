---
title: Versioned content
summary: A guide on how DataObjects with the Versioned extension behave in GraphQL schemas
---

# Versioned content

For the most part, if your DataObject has the `Versioned` extension applied, there is nothing you need to do
explicitly, but be aware that it will affect the operations and fields of your type.

## Versioned plugins

There are several plugins provided by the `silverstripe-versioned` module that affect how versioned DataObjects
appear in the schema. These include:

* The `versioning` plugin, applied to the DataObject type
* The `readVersion` plugin, applied to the queries for the DataObject
* The `unpublishOnDelete` plugin, applied to the delete mutation

Let's walk through each one.

### The "versioning" plugin

Defined in the `SilverStripe\Versioned\GraphQL\Plugins\VersionedDataObject` class, this plugin adds
several fields to the DataObject type, including:

#### The "version" field

The `version` field on your DataObject will include the following fields:

* author: Member (Object -- the author of the version)
* publisher: Member (Object -- the publisher of the version)
* published: Boolean (True if the version is published)
* liveVersion: Boolean (True if the version is the one that is currently live)
* latestDraftVersion: Boolean (True if the version is the latest draft version)

Let's look at it in context:

```graphql
query readSiteTrees {
  nodes {
    title
    version {
      author {
        firstname
      }
      published
    }
  }
}
```

#### The "versions" field

The `versions` field on your DataObject will return a list of the `version` objects described above.
The list is sortable by version number, using the `sort` parameter.

```graphql
query readSiteTrees {
  nodes {
    title
    versions(sort: { version: DESC }) {
      author {
        firstname
      }
      published
    }
  }
}
```

### The "readVersion" plugin

This plugin updates the `read` operation to include a `versioning` argument that contains the following
fields:

* mode: VersionedQueryMode (An enum of [ARCHIVE, LATEST, DRAFT, LIVE, STATUS, VERSION])
* archiveDate: String (The archive date to read from)
* status: VersionedStatus (An enum of [PUBLISHED, DRAFT, ARCHIVED, MODIFIED])
* version: Int (The exact version to read)

The query will automatically apply the settings from the `versioning` input type to the query and affect
the resulting `DataList`.


### The "unpublishOnDelete" plugin

This is mostly for internal use. It's an escape hatch for tidying up after a delete.

## Versioned operations

DataObjects with the `Versioned` extension applied will also receive four extra operations
by default. They include:

* `publish`
* `unpublish`
* `copyToStage`
* `rollback`

All of these identifiers can be used in the `operations` config for your versioned
DataObject. They will all be included if you use `operations: '*'`.

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      MyProject\Models\MyObject:
        fields: '*'
        operations:
          publish: true
          unpublish: true
          rollback: true
          copyToStage: true

```

### Using the operations

Let's look at a few examples:

**Publishing**
```graphql
mutation publishSiteTree(id: 123) {
  id
  title
}
```

**Unpublishing**
```graphql
mutation unpublishSiteTree(id: 123) {
  id
  title
}
```

**Rolling back**
```graphql
mutation rollbackSiteTree(id: 123, toVersion: 5) {
  id
  title
}
```

**Copying to stage**
```graphql
mutation copySiteTreeToStage(id: 123, fromStage: DRAFT, toStage: LIVE) {
  id
  title
}
```
