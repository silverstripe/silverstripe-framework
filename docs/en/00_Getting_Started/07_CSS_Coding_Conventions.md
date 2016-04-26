# CSS/Sass Coding Conventions

## Overview

This document provides guidelines for code formatting to developers contributing
to SilverStripe. It applies to all CSS/Sass files in the `framework/` and `cms/` modules.

In 2016, SilverStripe started a rewrite of the styles of the CMS interface in
[Bootstrap 4](http://v4-alpha.getbootstrap.com/) for its base styles,
[BEM](http://getbem.com/) to structure our custom components and styles and to help promote best practices, and
[ITCSS](http://itcss.io/) to define the CSS architecture. ITCSS is still in its early stages of documentation so the [following article](https://www.xfive.co/blog/itcss-scalable-maintainable-css-architecture/) might be useful. This rewrite is work-in-progress so code written prior to this rewrite might not follow these conventions, and is placed in a `legacy/` folder structure.

## Browser support

In 2016 SilverStripe 4 dropped support for IE7, IE8, and IE9. We have not as yet dropped IE10 officially as of 26 April 2016. We now have the ability to use Flexbox which can be used for layout purposes.

## Conventions

We use [Bootstrap 4](http://v4-alpha.getbootstrap.com/) out of the box styles as much as possible, but for any newly written styles we follow the [AirBnB CSS Conventions](https://github.com/airbnb/css) and [BEM](http://getbem.com/).

## File and Folder Naming

- All frontend files (CSS, JavaScript, images) should be placed in
  a `client/` folder on the top level of the module
- Frontend files relating to the `framework` CMS UI should be placed in `admin/client`
- The `client/src/components` folder should contain only React and its related
  [presentational components](https://medium.com/@dan_abramov/smart-and-dumb-components-7ca2f9a7c7d0#.r635clean).
  Components should be self-contained, and include styles which it relies on to be displayed.
- The `client/src/containers` folder should contain only React and its related
  [container components](https://medium.com/@dan_abramov/smart-and-dumb-components-7ca2f9a7c7d0#.r635clean).
  Containers should be self-contained, and include styles which it relies on to be displayed.
- The file name of styles nested within components and containers should inherit the component/container folder name for easy reference (eg. FormAction component has a styles named FormAction.scss).

## Related

* [BEM](https://en.bem.info/) documentation, but we find https://en.bem.info/ to be easier to digest.

## Todo

Known SilverStripe 4.0 documentation does not currently align with these standards mentioned above.
- [CMS Layout](https://docs.silverstripe.org/en/4.0/developer_guides/customising_the_admin_interface/cms_layout/)
- [Layout and panels](https://docs.silverstripe.org/en/4.0/developer_guides/customising_the_admin_interface/cms_architecture#layout-and-panels)
- [Extend CMS interface](https://docs.silverstripe.org/en/4.0/developer_guides/customising_the_admin_interface/how_tos/extend_cms_interface/)
- [Browser support and server requirements](https://docs.silverstripe.org/en/4.0/getting_started/server_requirements/)
- [CMS architecture](https://docs.silverstripe.org/en/4.0/developer_guides/customising_the_admin_interface/cms_architecture/)
- [Buttons](https://docs.silverstripe.org/en/4.0/developer_guides/customising_the_admin_interface/cms_architecture//#buttons)
