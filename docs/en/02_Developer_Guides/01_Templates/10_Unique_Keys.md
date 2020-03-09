---
title: Generating Unique Keys
summary: Outputting unique keys in templates.
icon: code
---

# Unique Keys

There are several cases where you may want to generate a unique key. For example:

* populate `ID` attribute in your HTML output
* key for partial cache

This can be done simply by including following code in your template:

```ss
$DataObject.UniqueKey
```

`getUniqueKey` method is available on `DataObject` so you can use it on many object types like pages and blocks.

## Customisation

The unique key generation can be altered in two ways:

* you can provide extra data to be used when generating a key via an extension
* you can inject over the key generation service and write your own custom code

### Extension point

`cacheKeyComponent` extension point is located in `DataObject::getCacheKeyComponent`.
Use standard extension flow to define the  `cacheKeyComponent` method on your extension which is expected to return a `string`.
This value will be used when unique key is generated. Common cases are:

* versions - object in different version stages needs to have different unique keys
* locales - object in different locales needs to have different unique keys

### Custom service

`UniqueKeyService` is used by default but you can use injector to override it with your custom service. For example:

```yaml
SilverStripe\Core\Injector\Injector:
  SilverStripe\ORM\UniqueKey\UniqueKeyService:
    class: App\Service\MuCustomService
```

Your custom service has to implement `UniqueKeyInterface`.
