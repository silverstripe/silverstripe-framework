---
title: Caching
summary: How template variables are cached.
icon: rocket
---

# Caching 

## Object caching

All functions that provide data to templates must have no side effects, as the value is cached after first access. For 
example, this controller method will not behave as you might imagine.

```php
private $counter = 0;

public function Counter() 
{
    $this->counter += 1;

    return $this->counter;
}
```


```ss
$Counter, $Counter, $Counter

// returns 1, 1, 1
```

When we render `$Counter` to the template we would expect the value to increase and output `1, 2, 3`. However, as 
`$Counter` is cached at the first access, the value of `1` is saved.


## Partial caching

Partial caching is a feature that allows caching of a portion of a page as a single string value. For more details read [its own documentation](partial_template_caching).

Example:
```ss
<% cached $CacheKey if $CacheCondition %>
    $CacheableContent
<% end_cached %>
```
