---
title: Disable Anchor Rewriting
summary: Get more control over how hash links are rendered.
---

# Disable Anchor Rewriting

Anchor links are links with a "#" in them. A frequent use-case is to use anchor links to point to different sections of 
the current page.  For example, we might have this in our template:

```ss
<ul>
    <li><a href="#section1">Section 1</a></li>
    <li><a href="#section2">Section 2</a></li>
</ul>
```

Things get tricky because of we have set our `<base>` tag to point to the root of the site.  So, when you click the 
first link you will be sent to http://yoursite.com/#section1 instead of http://yoursite.com/my-long-page/#section1

In order to prevent this situation, the SSViewer template renderer will automatically rewrite any anchor link that
doesn't specify a URL before the anchor, prefixing the URL of the current page.  For our example above, the following
would be created in the final HTML

```ss
<ul>
    <li><a href="my-long-page/#section1">Section 1</a></li>
    <li><a href="my-long-page/#section2">Section 2</a></li>
</ul>
```

There are cases where this can be unhelpful. HTML anchors created from Ajax responses are the most common. In these
situations, you can disable anchor link rewriting by setting the `SSViewer.rewrite_hash_links` configuration value to 
`false`.

**app/_config/app.yml**

```yml
SilverStripe\View\SSViewer:
  rewrite_hash_links: false
```

Alternatively, it's possible to disable anchor link rewriting for specific pages using the `SSViewer::setRewriteHashLinksDefault()` method in the page controller:

```php
namespace Example\HashLink;

use PageController;
use SilverStripe\View\SSViewer;

class ExamplePageController extends PageController
{
    protected function init()
    {
        parent::init();
        SSViewer::setRewriteHashLinksDefault(false);
    }
}
```
