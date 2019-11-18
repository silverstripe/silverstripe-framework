---
title: Customise the CMS tree
summary: Learn how to add custom UI elements to the CMS page navigation
icon: sitemap
---

# How to customise the CMS tree

## Overview

The CMS tree for viewing hierarchical structures (mostly pages) is powered
by the [jstree](http://jstree.com) library. It is configured through
`client/src/legacy/LeftAndMain.Tree.js` in the `silverstripe/admin` module, as well as some
HTML5 metadata generated on its container (see the `data-hints` attribute).

The tree is rendered through [LeftAndMain::getSiteTreeFor()](api:SilverStripe\Admin\LeftAndMain::getSiteTreeFor()),
which recursively collects all nodes based on various filtering criteria.
The node strictly just has to implement the [Hierarchy](api:SilverStripe\ORM\Hierarchy\Hierarchy) extension,
but in the CMS usually is a [SiteTree](api:SilverStripe\CMS\Model\SiteTree) object.

## Add status lozenges to tree nodes

A tree node in CMS could be rendered with lot of extra information but a node title, such as a
link that wraps around the node title, a node's id which is given as id attribute of the node
&lt;li&gt; tag, a extra checkbox beside the tree title, tree icon class or extra &lt;span&gt;
tags showing the node status, etc. SilverStripe tree node will be typically rendered into html
code like this:


```ss

...
<ul>
    ...
    <li id="record-15" class="class-Page closed jstree-leaf jstree-unchecked" data-id="15">
    <ins class="jstree-icon">&nbsp;</ins>
        <a class="" title="Page type: Page" href="{$AdminURL}page/edit/show/15">
            <ins class="jstree-checkbox">&nbsp;</ins>
            <ins class="jstree-icon">&nbsp;</ins>
            <span class="text">
                <span class="jstree-pageicon"></span>
                <span class="item" title="Deleted">New Page</span>
                <span class="badge deletedonlive">Deleted</span>
            </span>
        </a>
    </li>
    ...
</ul>
...
```

By applying the proper style sheet, the snippet html above could produce the look of:
![Page Node Screenshot](../../../_images/tree_node.png "Page Node")

SiteTree is a [DataObject](api:SilverStripe\ORM\DataObject) which is versioned by [Versioned](api:SilverStripe\Versioned\Versioned) extension.
Each node can optionally have publication status flags, e.g. "Removed from draft".
Each flag has a unique identifier, which is also used as a CSS class for easier styling.

Developers can easily add a new flag, delete or alter an existing flag on how it is looked
or changing the flag label. The customization of these lozenges could be done either through
inherited subclass or [DataExtension](api:SilverStripe\ORM\DataExtension). It is just really about how we change the return
value of function `SiteTree->getTreeTitle()` by two easily extendable methods
`SiteTree->getStatusClass()` and `SiteTree->getStatusFlags()`.

Note: Though the flag is not necessarily tie to its status of __publication__ and it could
be used for flagging anything you like, we should keep this lozenge to show version-related
status, while let `SiteTree->CMSTreeClasses()` to deal with other customised classes, which
will be used for the class attribute of &lt;li&gt; tag of the tree node.

### Add new flag
__Example: using a subclass__


```php
use SilverStripe\CMS\Model\SiteTree;

class Page extends SiteTree 
{
    public function getScheduledToPublish()
    {
        // return either true or false
    }

    public function getStatusFlags($cached = true) 
    {
        $flags = parent::getStatusFlags($cached);
        $flags['scheduledtopublish'] = "Scheduled To Publish";
        return $flags;
    }
}
```

The above subclass of [SiteTree](api:SilverStripe\CMS\Model\SiteTree) will add a new flag for indicating its
__'Scheduled To Publish'__ status. The look of the page node will be changed
from ![Normal Page Node](../../../_images/page_node_normal.png) to ![Scheduled Page Node](../../../_images/page_node_scheduled.png). The getStatusFlags has an `updateStatusFlags()`
extension point, so the flags can be modified through `DataExtension` rather than
inheritance as well. Deleting existing flags works by simply unsetting the array key.

## Customising page icons

The page tree in the CMS is a central element to manage page hierarchies, hence its display of pages can be customised as well. You can specify a custom page icon to make it easier for CMS authors to identify pages of this type, when navigating the tree or adding a new page:

```php
class HomePage extends Page
{
    private static $icon_class = 'font-icon-p-home';
}
```

The CMS uses an icon set from [Fontastic](http://fontastic.me/). New icons may be [requested](https://github.com/silverstripe/silverstripe-admin/issues/new) and added to the [core icon set](https://silverstripe.github.io/silverstripe-admin/?selectedKind=Admin%2FIcons&selectedStory=Icon%20reference&full=0&addons=1&stories=1&panelRight=0&addonPanel=storybook%2Factions%2Factions-panel). The benefit of having icons added to the core set is that you can use icons more consistently across different modules allowing every module to use a different icon with the same style.

You can also add your own icon by specifying an image path to override the Fontastic icon set:

```php
    private static $icon = 'app/images/homepage-icon.svg';
```
