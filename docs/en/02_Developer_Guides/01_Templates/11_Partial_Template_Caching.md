---
title: Partial Template Caching
summary: Cache a section of a template Reduce rendering time with cached templates and understand the limitations of the ViewableData object caching.
icon: tags
---

## Partial template caching

Partial template caching is a feature that allows caching of rendered portions of templates. Cached content
is fetched from a [cache backend](../performance/caching), instead of being regenerated repeatedly.


### Base syntax

```ss
<% cached $CacheKey if $CacheCondition %>
  $CacheableContent
<% end_cached %>
```

This is not a definitive example of the syntax, but it shows the most common use case.

[note]
See also [Complete Syntax definition](#complete-syntax-defintition) section
[/note]

The key parts are `$CacheKey`, `$CacheCondition` and `$CacheableContent`.  
The following sections explain every one of them in more detail.


#### $CacheKey

Defines a unique key for the cache storage.

[warning]
Avoid heavy computations in `$CacheKey` as it is evaluated for every template render.
[/warning]

The formal definition is
 - Optional list of template expressions delimited by comma

The syntax is

```ss
<% cached [$key1[, $key2[, ...[, $keyN]]]] ... %>
```

The final value is concatenated by the Template Engine into a string. When doing so, Template Engine
adds some extra values to the mix to make it more unique and prevent clashing between cache keys from
different templates.

Here is how it works in detail:

1. `SilverStripe\View\SSViewer::$global_key` hash

   With the current template context, value of the `$global_key` variable is rendered into a string and hashed.

   `$global_key` content is inserted into the template "as is" at the compilation stage. Changing its value
   won't have any effect until template recompilation (e.g. on cache flush).

   By default it equals to `'$CurrentReadingMode, $CurrentUser.ID'`.
   This ensures the current [Versioned](api:SilverStripe\Versioned\Versioned) state and user ID are used.
   At runtime that will become something like `'LIVE, 0'` (for unauthenticated users in live mode).

   As usual, you may override its value via YAML configs. For example:

   ```yml
   # app/_config/view.yml
   SilverStripe\View\SSViewer:
     global_key: '$CurrentReadingMode, $CurrentUser.ID, $Locale'
   ```

2. Block hash

   Everything between the `<% cached %> ... <% end_cached %>` is taken as text (with no rendering) and hashed.

   This is done at the template compilation stage, so
   the compiled version of the template contains the hash precalculated.

   `Block hash` main purpose is to invalidate cache when template itself changes.

3. `$CacheKey` hash

   All keys of `$CacheKey` are processed, concatenated and the final value is hashed.  
   If there are no values defined, this step is skipped.

4. Make the final key vaule

   A string produced by concatenation of all the values mentioned above is used as the final value.

   Even if `$CacheKey` is omitted, `SilverStripe\View\SSViewer::$global_key` and `Block hash` values are still
   getting used to generate cache key for the caching backend storage.  

[note]
##### Cache key calculated in controller

If your caching logic is complex or re-usable, you can define a method on your controller to generate a cache key 
fragment.

For example, a block that shows a collection of rotating slides needs to update whenever the relationship 
`Page::$many_many = ['Slides' => 'Slide']` changes. In `PageController`:


```php
public function SliderCacheKey() 
{
    $fragments = [
        'Page-Slides',
        $this->ID,
        // identify which objects are in the list and their sort order
        implode('-', $this->Slides()->Column('ID')),
        // works for both has_many and many_many relationships
        $this->Slides()->max('LastEdited')
    ];
    return implode('-_-', $fragments);
}
```

Then reference that function in the cache key:


```ss
<% cached $SliderCacheKey if ... %>
```
[/note]


#### $CacheCondition

Defines if caching is required for the block.

Condition is optional and if omitted, `true` is implied.

If the value is `false`, the block skips `$CacheKey` evaluation completely, does not lookup
the data in the cache storage, neither preserve any data in the storage.
The template within the block keeps working as is, same as it would do without
`<% cached %>` block surrounding it.

Although `$CacheCondition` is optional, it is highly recommended. For example,
if you use `$DataObject->ID` as your `$CacheKey`, you may use
`$DataObject->ID > 0` as the condition.

Without it:
  - your cache backend will always be queried for cache (for every template render)
  - your cache backend may be cluttered with redundant and useless data


[warning]
The `$CacheCondition` value is evaluated on every template render and should be as lightweight as possible.
[/warning]


#### $CacheableContent

The content block may contain any usual template syntax.


### Cache storage

The cache storage may be re-configured via `Psr\SimpleCache\CacheInterface.cacheblock` key for [Injector](../extending/injector).  
By default, it is initialised by `SilverStripe\Core\Cache\DefaultCacheFactory` with the following parameters:

- `namespace: "cacheblock"`
- `defaultLifetime: 600`

[note]
The defaultLifetime 600 means every cache record expires in 10 minutes.  
If you have good `$CacheKey` and `$CacheCondition` implementations, you may want to tune these settings to
improve performance.
[/note]


### Nested cached blocks

Every nested cache block is processed independently.

Let's consider the following example:
```ss
<% cached $PageKey %>
  <!-- Header -->
  <% cached $BodyKey %> <!-- Body --> <% end_cached %>
  <!-- Footer -->
<% end_cached %>
```

The template processor will transparently flatten the structure into something similar to the following pseudo-code:

```ss
<% cached $PageKey %><!-- Header --><% end_block %>
<% cached $BodyKey %><!-- Body --><% end_cached %>
<% cached $PageKey %><!-- Footer --><% end_cached %>
```

[note]
`$PageKey` is used twice, but evaluated only once per render because of [template object caching](caching/#object-caching).
[/note]


### Uncached

The tag `<% uncached %> ... <% end_uncached %>` disables caching for its content.  

```ss
<% cached $PageKey %>
  <!-- Header -->
  <% uncached %><!-- Body --><% end_uncached %>
  <!-- Footer -->
<% end_cached %>
```

Because of the nested block flattening (see above), it works seamlessly on any level of depth.  

[warning]
The `uncached` block only works on the lexical level.
If you have a template that caches content rendering another template with included uncached blocks,
those will not have any effect on the parent template caching blocks.
[/warning]


### Nesting in LOOP and IF blocks

Currently, a cache block cannot be included in `if` and `loop` blocks.  
The template engine will throw an error letting you know if you've done this.

[note]
You may often get around this using aggregates or by un-nesting the block.

E.g.

```
<% cached $LastEdited %>
  <% loop $Children %>
      <% cached $LastEdited %>
          $Name
      <% end_cached %>
  <% end_loop %>
<% end_cached %>
```

Might be re-written as something like that:

```
<% cached $LastEdited %>
    <% cached $AllChildren.max('LastEdited') %>
        <% loop $Children %>
            $Name
        <% end_loop %>
    <% end_cached %>
<% end_cached %>
```
[/note]


### Unless (syntax sugar)

`if` keyword may be swapped with keyword `unless`, which inverts the boolean value evaluation.

The two following forms produce the same result

```ss
<% cached unless $Key %>
  "unless $Cond" === "if not $Cond"
<% end_cached %>
```

```ss
<% cached if not $Key %>
  "unless $Cond" === "if not $Cond"
<% end_cached %>
```


### Complete Syntax definition

```ss
<% [un]cached [$CacheKey[, ...]] [(if|unless) $CacheCondition] %>
  $CacheContent
<% end_[un]cached %>
```


### Examples

```ss
<% cached %>
  The key is: hash of the template code within the block with $global_key.
  This content is always cached.
<% end_cache %>
```

```ss
<% cached $Key %>
    Cached separately for every distinct $Key value
<% end_cached %>
```

```ss
<% cached $KeyA, $KeyB %>
    Cached separately for every combination of $KeyA and $KeyB
<% end_cached %>
```

```ss
<% cached $Key if $Cond %>
    Cached only if $Cond == true
<% end_cached %>
```

```ss
<% cached $Key unless $Cond %>
    Cached only if $Cond == false
<% end_cached %>
```

```ss
<% cached $Key if not $Cond %>
    Cached only if $Cond == false
<% end_cached %>
```

```ss
<% cached 'contentblock', $LastEdited, $CurrentMember.ID if $CurrentMember && not $CurrentMember.isAdmin %>
  <!--
       Hash of this content block is also included
       into the final Cache Key value along with
       SilverStripe\View\SSViewer::$global_key
  -->
  <% uncached %>
      This text is always dynamic (never cached)
  <% end_uncached %>
  <!--
       This bit is cached again
  -->
<% end_cached %>
```
