---
title: Writing a simple plugin
summary: In this tutorial, we add a simple plugin for string fields
---

# Writing a simple plugin

For this example, we want all `String` fields to have a `truncate` argument that will limit the length of the string
in the response.

Because it applies to fields, we'll want `FieldPlugin` for this.

```php
class Truncator implements FieldPlugin
{
    public function getIdentifier(): string
    {
        return 'truncate';
    }

    public function apply(Field $field, Schema $schema, array $config = [])
    {
        $field->addArg('truncate', 'Int');
    }
}
```

Now we've added an argument to any field that implements the `truncate` plugin. This is good, but it really
doesn't save us a whole lot of time. The real value here is that the field will automatically apply the truncation.

For that, we'll need to augment the resolver with some _afterware_.

```php
public function apply(Field $field, Schema $schema, array $config = [])
{
    // Sanity check
    Schema::invariant(
        $field->getType() === 'String',
        'Field %s is not a string. Cannot truncate.',
        $field->getName()
    );

    $field->addArg('truncate', 'Int');
    $field->addResolverAfterware([static::class, 'truncate']);
}

public static function truncate(string $result, array $args): string
{
    $limit = $args['truncate'] ?? null;
    if ($limit) {
        return substr($result, 0, $limit);
    }

    return $result;
}
```

Let's register the plugin:

```yaml
SilverStripe\Core\Injector\Injector:
  SilverStripe\GraphQL\Schema\Registry\PluginRegistry:
    constructor:
      truncate: '%$MyProject\Plugins\Truncator'
```

And now we can apply it to any string field we want:

```yaml
SilverStripe\GraphQL\Schema\Schema:
  schemas:
    default:
      types:
        Country:
          name:
            type: String
            plugins:
              truncate: true
```
