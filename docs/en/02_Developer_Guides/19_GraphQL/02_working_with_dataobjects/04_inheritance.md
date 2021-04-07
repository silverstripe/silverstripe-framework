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

## DataObject inheritance

The inheritance pattern used in the ORM is a tricky thing to navigate in a GraphQL API, mostly owing
to the fact that there is no concept of inheritance in GraphQL types. The main tools we have at our
disposal are [interfaces](https://graphql.org/learn/schema/#interfaces) and [unions](https://graphql.org/learn/schema/#union-types) to deal with this type of architecture, and we leverage both of them when
working with dataobjects.

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

To solve this problem, the graphql module will automatically change these types of queries to return unions. Unions
require the special `... on` syntax provided by the graphql spec.

```graphql
query {
    readPages {
        nodes {
            ... on Page {
                title
                content
            }
            ... on BlogPage {
                date
            }
        }
    }
}
```

But now we have another problem -- when we get a `BlogPage` result, we won't get `title` and `content`, which we
would probably want. We could just add these fields to both `... on` blocks, but that gets really repetitive. A better
way to handle this is to use the common *interface* between `Page` and `BlogPage`.

```graphql
query {
    readPages {
        nodes {
            ... on PageInterface {
                title
                content
            }
            ... on BlogPage {
                date
            }
        }
    }
}
```
Now, `BlogPage` will hit on `PageInterface` and `BlogPage`. You can kind of think of interfaces in this context
as abstractions of *parent classes*.

[info]
A good way to determine whether you want an interface or a concrete type in your ...on block is to ask,
"Can this field appear on any other types in the query?" If the answer is yes, you want to use an interface, 
which is usually the parent class with the "Interface" suffix.
[/info]

### Inheritance: A deep dive

There are several components to the way inheritance is handled at build time:

* Implicit field / type exposure
* Interface generation and assignment to types
* Union generation and assignment to queries

We'll look at each of these in detail.

#### Inherited fields / implicit exposures

Here are the rules for how inheritance affects types and fields:

* Exposing a type implicitly exposes all of its ancestors.
* Ancestors receive any fields exposed by their descendants, if applicable.
* Exposing a type applies all of its fields to descendants only if they are explicitly exposed also.

All of this is serviced by: `SilverStripe\GraphQL\Schema\DataObject\InheritanceBuilder`

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

This results in these two types being exposed with the fields as shown, but also results in a `Page` type:

```
type Page {
  id: ID! # always exposed
  title: String
  content: String
  urlSegment: String
}
```

#### Interface generation and assignment to types

Any type that's part of an inheritance chain will generate interfaces. Each applicable ancestral interface is added 
to the type. Like the type inheritance pattern shown above, interfaces duplicate fields from their ancestors as well.

Additionally, a **base interface** is provided for all types containing common fields across the entire DataObject
schema.

All of this is serviced by: `SilverStripe\GraphQL\Schema\DataObject\InterfaceBuilder`

##### Example

```
Page:
  BlogPage extends Page
  EventsPage extends Page
    ConferencePage extends EventsPage
    WebinarPage extends EventsPage
```

This will create the following interfaces:

```
interface PageInterface {
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
  venueAddress: String
}

interface WebinarPageInterface {
  id: ID!
  title: String
  content: String
  numberOfTickets: Int
  zoomLink: String
}
```

Types then get these interfaces applied, like so:

```
type Page implements PageInterface {}
type BlogPage implements BlogPageInterface & PageInterface {}
type EventsPage implements EventsPageInterface & PageInterface {} 
type ConferencePage implements ConferencePageInterface & EventsPageInterface & PageInterface {} 
type WebinarPage implements WebinarPageInterface & EventsPageInterface & PageInterface {} 
```

Lastly, for good measure, we create a `DataObjectInterface` that applies to everything.

```
interface DataObjectInterface {
  id: ID!
  # Any other fields you've explicitly exposed in config.modelConfig.DataObject.base_fields
}
```

```
type Page implements PageInterface & DataObjectInterface {}
```

#### Union generation and assignment to queries

Models that have descendants will create unions that include themselves and all of their descendants. For queries that return those models, a union is put in its place.

Serviced by: `SilverStripe\GraphQL\Schema\DataObject\InheritanceUnionBuilder`

##### Example

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

"Leaf" models like `BlogPage`, `ConferencePage`, and `WebinarPage` that have no exposed descendants will not create unions, as they are functionally useless.

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


As mentioned above, a good way of negotiating whether to use interfaces or types in the `... on` block is to 
ask the question "Could this field appear on more than one type?" If the answer is yes, you want an interface.

#### Elemental

Almost by definition, content blocks are always abstractions. You're never going to query for a `BaseElement` type specifically. You're always asking for an assortment of its descendants, which adds a lot of polymorphism to 
the query.

```graphql
query {
  readElementalPages {
    nodes {
      elementalArea {
        elements {
          nodes {
            ... on BaseElementInterface {
              title
              id
            }
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

### Lookout for the footgun!

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

```
query {
    readProducts {
        nodes {
            price # Error: Field "price" not found on ProductInheritanceUnion
        }
    }
}
```

We need to revise it:

```
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



### Further reading

[CHILDREN]
