---
title: Flushable
summary: Allows a class to define it's own flush functionality.
---

# Flushable

## Introduction

Allows a class to define it's own flush functionality, which is triggered when `flush=1` is requested in the URL.
[FlushMiddleware](api:SilverStripe\Control\Middleware\FlushMiddleware) is run before a request is made, calling `flush()` statically on all
implementors of [Flushable](api:SilverStripe\Core\Flushable).


<div class="notice">
Flushable implementers might also be triggered automatically on deploy if you have `SS_FLUSH_ON_DEPLOY` [environment
variable](../configuration/environment_variables) defined. In that case even if you don't manually pass `flush=1` parameter, the first request after deploy
will still be calling `Flushable::flush` on those entities.
</div>


## Usage

To use this API, you need to make your class implement [Flushable](api:SilverStripe\Core\Flushable), and define a `flush()` static function,
this defines the actions that need to be executed on a flush request.

### Using with Cache

This example uses [Cache](api:Cache) in some custom code, and the same cache is cleaned on flush:


```php
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Flushable;
use Psr\SimpleCache\CacheInterface;

class MyClass extends DataObject implements Flushable
{

    public static function flush()
    {
        Injector::inst()->get(CacheInterface::class . '.mycache')->clear();
    }

    public function MyCachedContent()
    {
        $cache = Injector::inst()->get(CacheInterface::class . '.mycache')
        $something = $cache->get('mykey');
        if(!$something) {
            $something = 'value to be cached';
            $cache->set('mykey', $something);
        }
        return $something;
    }

}
```

### Using with filesystem

Another example, some temporary files are created in a directory in assets, and are deleted on flush. This would be
useful in an example like `GD` or `Imagick` generating resampled images, but we want to delete any cached images on
flush so they are re-created on demand.

```php
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Flushable;

class MyClass extends DataObject implements Flushable
{

    public static function flush()
    {
        foreach(glob(ASSETS_PATH . '/_tempfiles/*.jpg') as $file) {
            unlink($file);
        }
    }

}
```
