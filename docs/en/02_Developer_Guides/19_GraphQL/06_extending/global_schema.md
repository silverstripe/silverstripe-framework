---
title: The global schema
summary: How to push modifications to every schema in the project
---

# Extending the schema

[CHILDREN asList]

[info]
You are viewing docs for silverstripe/graphql 4.x.
If you are using 3.x, documentation can be found
[in the github repository](https://github.com/silverstripe/silverstripe-graphql/tree/3)
[/info]

## The global schema

Developers of thirdparty modules that influence graphql schemas may want to take advantage
of the _global schema_. This is a pseudo-schema that will merge itself with all other schemas
that have been defined. A good use case is in the `silverstripe/versioned` module, where it
is critical that all schemas can leverage its schema modifications.

The global schema is named `*`.

**app/_config/graphql.yml**
```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    '*':
      enums:
        VersionedStage:
          DRAFT: DRAFT
          LIVE: LIVE
```

### Further reading

[CHILDREN]
