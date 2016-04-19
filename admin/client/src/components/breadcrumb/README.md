#NorthHeaderBreadcrumbs

The breadcrumbs for the current section of the CMS.

## Props

### Crumbs (array)

An array of objects, each object should have a `text` and `href` key.

```
let breadcrumbs = [
    {
        text: 'Pages',
        href: 'admin/pages'
    },
    {
        text: 'About us',
        href: 'admin/pages/show/2'
    }
];
<BreadcrumbComponent crumbs={breadcrumbs} />
}

...
```
