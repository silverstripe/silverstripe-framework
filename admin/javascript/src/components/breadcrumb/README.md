#NorthHeaderBreadcrumbs

The breadcrumbs for the current section of the CMS.

## Props

### Crumbs (array)

An array of objects, each object should have a `text` and `href` key.

```
import BreadcrumbComponent from 'breadcrumb';

...

getBreadcrumbs() {
    var breadcrumbs = [
        {
            text: 'Pages',
            href: 'admin/pages'
        },
        {
            text: 'About us',
            href: 'admin/pages/show/2'
        }
    ];
    
    return breadcrumbs;
}

render() {
    return <BreadcrumbComponent crumbs={this.getBreadcrumbs()} />
}

...
```