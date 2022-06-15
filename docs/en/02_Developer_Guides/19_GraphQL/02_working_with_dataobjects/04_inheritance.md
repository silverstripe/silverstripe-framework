---
title: DataObject inheritance
summary: Learn how inheritance is handled in DataObject types
---

# Working with DataObjects

[CHILDREN asList]

[alert]
You are viewing docs for a pre-release version of silverstripe/graphql (4.x).
Help us improve it by joining #graphql on the [Community Slack](https://www.silverstripe.org/blog/community-slack-channel/),
and report any issues at [github.com/silverstripe/silverstripe-graphql](https://github.com/silverstripe/silverstripe-graphql). 
Docs for the current stable version (3.x) can be found
[here](https://github.com/silverstripe/silverstripe-graphql/tree/3)
[/alert]

## `DataObject` inheritance

The inheritance pattern used in the ORM is a tricky thing to navigate in a GraphQL API, mostly owing
to the fact that there is no concept of inheritance in GraphQL types. The main tools we have at our
disposal are [interfaces](https://graphql.org/learn/schema/#interfaces) and [unions](https://graphql.org/learn/schema/#union-types)
to deal with this type of architecture, and we leverage both of them when working with DataObjects.

### Key concept: Querying types that have descendants

When you query a type that has descendant classes, you are by definition getting a polymorphic return. There
is no guarantee that each result will be of one specific type. Take this example:

```graphql
query {
  readPages {
    nodes {
      title
      content
    }
  }
}
```

This is fine when the two fields are common to across the entire inheritance chain, but what happens
when we need the `date` field on `BlogPage`?

```graphql
query {
  readPages {
    nodes {
      title
      content
      date # fails!
    }
  }
}
```

To solve this problem, the graphql module will automatically change these types of queries to return interfaces.

```graphql
query {
  readPages { 
    nodes { # <--- [PageInterface]
      title
      content
    }
  }
}
```

Now, in order to query fields that are specific to `BlogPage`, we need to use an
[inline fragment](https://graphql.org/learn/queries/#inline-fragments) to select them.

In the below example, we are querying `title` and `content` on all page types, but we only query `heroImage`
on `HomePage` objects, and we query `date` and `author` only for `BlogPage` objects.

```graphql
query {
  readPages { 
    nodes { 
      title # Common field
      content # Common field
      ... on HomePage {
        heroImage {
          url
        }
      }
      ... on BlogPage {
        date
        author {
          firstName
        }
      }
    }
  }
}
```

So the fields that are common to every possible type in the result set can be directly selected (with no `...on`
syntax), because they're part of the common interface. They're guaranteed to exist on every type. But for fields
that only appear on some types, we need to be explicit.

Now let's take this a step further. What if there's another class in between? Imagine this ancestry:

```
Page
  -> EventPage extends Page
     -> ConferencePage extends EventPage
     -> WebinarPage extends EventPage
```

We can use the intermediary interface `EventPageInterface` to consolidate fields that are unique to
`ConferencePage` and `WebinarPage`.

```graphql
query {
  readPages { 
    nodes { 
      title # Common to all types
      content # Common to all types
      ... on EventPageInterface {
        # Common fields for WebinarPage, ConferencePage, EventPage
        numberOfTickets
        featuredSpeaker {
          firstName
          email
        }
      }
      ... on WebinarPage {
        zoomLink
      }
      ... on ConferencePage {
        venueSize
      }
      ... on BlogPage {
        date
        author {
          firstName
        }
      }
    }
  }
}
```

You can think of interfaces in this context as abstractions of *parent classes* - and the best part is
they're generated automatically. We don't need to manually define or implement the interfaces.

[info]
A good way to determine whether you need an inline fragment is to ask
"can this field appear on any other types in the query?" If the answer is yes, you want to use an interface,
which is usually the parent class with the "Interface" suffix.
[/info]

### Inheritance: A deep dive

There are several ways inheritance is handled at build time:

* Implicit field / type exposure
* Interface generation
* Assignment of generated interfaces to types
* Assignment of generated interfaces to queries

We'll look at each of these in detail.

#### Inherited fields / implicit exposures

Here are the rules for how inheritance affects types and fields:

* Exposing a type implicitly exposes all of its ancestors.
* Ancestors receive any fields exposed by their descendants, if applicable.
* Exposing a type applies all of its fields to descendants only if they are explicitly exposed also.

All of this is serviced by: [`InheritanceBuilder`](api:SilverStripe\GraphQL\Schema\DataObject\InheritanceBuilder)

##### Example:

```yaml
BlogPage:
  fields: 
    title: true
    content: true
    date: true
GalleryPage:
  fields:
    images: true
    urlSegment: true
```

This results in those two types being exposed with the fields as shown, but also results in a `Page` type:

```graphql
type Page {
  id: ID! # always exposed
  title: String
  content: String
  urlSegment: String
}
```

#### Interface generation

Any type that's part of an inheritance chain will generate interfaces. Each applicable ancestral interface is added
to the type. Like the type inheritance pattern shown above, interfaces duplicate fields from their ancestors as well.

Additionally, a **base interface** is provided for all types containing common fields across the entire `DataObject`
schema.

All of this is serviced by: [`InterfaceBuilder`](api:SilverStripe\GraphQL\Schema\DataObject\InterfaceBuilder)

##### Example

```
Page
  -> BlogPage extends Page
  -> EventsPage extends Page
     -> ConferencePage extends EventsPage
     -> WebinarPage extends EventsPage
```

This will create the following interfaces (assuming the fields below are exposed):

```graphql
interface PageInterface {
  id: ID!
  title: String
  content: String
}

interface BlogPageInterface {
  id: ID!
  title: String
  content: String
  date: String
}

interface EventsPageInterface {
  id: ID!
  title: String
  content: String
  numberOfTickets: Int
}

interface ConferencePageInterface {
  id: ID!
  title: String
  content: String
  numberOfTickets: Int
  venueSize: Int
  venurAddress: String
}

interface WebinarPageInterface {
  id: ID!
  title: String
  content: String
  numberOfTickets: Int
  zoomLink: String
}
```

#### Interface assignment to types

The generated interfaces then get applied to the appropriate types, like so:

```graphql
type Page implements PageInterface {}
type BlogPage implements BlogPageInterface & PageInterface {}
type EventsPage implements EventsPageInterface & PageInterface {} 
type ConferencePage implements ConferencePageInterface & EventsPageInterface & PageInterface {} 
type WebinarPage implements WebinarPageInterface & EventsPageInterface & PageInterface {} 
```

Lastly, for good measure, we create a `DataObjectInterface` that applies to everything.

```graphql
interface DataObjectInterface {
  id: ID!
  # Any other fields you've explicitly exposed in config.modelConfig.DataObject.base_fields
}
```

```graphql
type Page implements PageInterface & DataObjectInterface {}
```

#### Interface assignment to queries

Queries, both at the root and nested as fields on types, will have their types
updated if they refer to a type that has had any generated interfaces added to it.

```graphql
type Query {
  readPages: [Page]
}

type BlogPage {
  download: File   
}
```

Becomes:

```graphql
type Query {
  readPages: [PageInterface]
}

type BlogPage {
  download: FileInterface
}
```

All of this is serviced by: [`InterfaceBuilder`](api:SilverStripe\GraphQL\Schema\DataObject\InterfaceBuilder)

#### Elemental
This section refers to types added via `dnadesign/silverstripe-elemental`.

Almost by definition, content blocks are always abstractions. You're never going to query for a `BaseElement` type
specifically. You're always asking for an assortment of its descendants, which adds a lot of polymorphism to
the query.

```graphql
query {
  readElementalPages {
    nodes {
      elementalArea {
        elements {
          nodes {            
            title
            id            
            ... on ContentBlock {
              html
            }
            ... on CTABlock {
              link
              linkText
            }
          }
        }
      }
    }
  }
}
```

[info]
The above example shows a query for elements on all elemental pages - but for most situations you will
probably only want to query the elements on one page at a time.
[/info]

### Optional: Use unions instead of interfaces

You can opt out of using interfaces as your return types for queries and instead use a union of all the concrete
types. This comes at a cost of potentially breaking your API unexpectedly (described below), so it is not enabled by
default. There is no substantive advantage to using unions over interfaces for your query return types. It would
typically only be done for conceptual purposes.

To use unions, turn on the `useUnionQueries` setting.

**app/_graphql/config.yml**
```yaml
modelConfig:
  DataObject:
    plugins:
      inheritance:
        useUnionQueries: true
```

This means that models that have descendants will create unions that include themselves and all of their descendants.
For queries that return those models, a union is put in its place.

Serviced by: [`InheritanceUnionBuilder`](api:SilverStripe\GraphQL\Schema\DataObject\InheritanceUnionBuilder)

#### Example

```
type Page implements PageInterface {}
type BlogPage implements BlogPageInterface & PageInterface {}
type EventsPage implements EventsPageInterface & PageInterface {} 
type ConferencePage implements ConferencePageInterface & EventsPageInterface & PageInterface {} 
type WebinarPage implements WebinarPageInterface & EventsPageInterface & PageInterface {} 
```

Creates the following unions:

```
union PageInheritanceUnion = Page | BlogPage | EventsPage | ConferencePage | WebinarPage
union EventsPageInheritanceUnion = EventsPage | ConferencePage | WebinarPage
```

"Leaf" models like `BlogPage`, `ConferencePage`, and `WebinarPage` that have no exposed descendants will not create
unions, as they are functionally useless.

This means that queries for `readPages` and `readEventsPages` will now return unions.

```graphql
query {
  readPages {
    nodes {
      ... on PageInterface {
        id # in theory, this common field could be done on DataObjectInterface, but that gets a bit verbose
        title
        content
      }
      ... on EventsPageInterface {
        numberOfTickets
      }
      ... on BlogPage {
        date
      }
      ... on WebinarPage {
        zoomLink
      }
    }
  }
}
```

#### Lookout for the footgun!

Because unions are force substituted for your queries when a model has exposed descendants, it is possible that adding
a subclass to a model will break your queries without much warning to you.

For instance:

```php
class Product extends DataObject
{
    private static $db = ['Price' => 'Int'];
}
```

We might query this with:

```graphql
query {
  readProducts {
    nodes {
      price
    }
  }
}
```

But if we create a subclass for product and expose it to graphql:

```php
class DigitalProduct extends Product
{
    private static $db = ['DownloadURL' => 'Varchar'];
}
```

Now our query breaks:

```graphql
query {
  readProducts {
    nodes {
      price # Error: Field "price" not found on ProductInheritanceUnion
    }
  }
}
```

We need to revise it:

```graphql
query {
  readProducts {
    nodes {
      ... on ProductInterface {
        price
      }
      ... on DigitalProduct {
        downloadUrl
      }
    }
  }
}
```

If we use interfaces, this won't break because the `price` field will be on `ProductInterface`
which makes it directly queryable (without requiring the inline fragment).

### Further reading

[CHILDREN]
