---
title: How to Create a Google Maps Shortcode
summary: Learn how to embed a Google map in the WYSIWYG editor with a simple shortcode
icon: map
---
# How to Create a Google Maps Shortcode

To demonstrate how easy it is to build custom shortcodes, we'll build one to display a Google Map based on a provided 
address. We want our CMS authors to be able to embed the map using the following code:
	
```php
	[googlemap,width=500,height=300]97-99 Courtenay Place, Wellington, New Zealand[/googlemap]

```
We'll add defaults to those in our shortcode parser so they're optional.

**mysite/_config.php**
