---
title: CSS coding conventions
summary: The CSS style guidelines we follow in our open source software
iconBrand: css3
---
# CSS and SCSS Coding Conventions

## Overview

This document provides guidelines for code formatting to developers contributing
to SilverStripe. It applies to all CSS/Sass files in the SilverStripe core modules.

In 2016, SilverStripe started a rewrite of the styles of the CMS interface.
This rewrite is work-in-progress so code written prior to this
rewrite might not follow these conventions, and is placed in a `legacy/` folder structure.

## Browser support

Check our [requirements](/getting_started/server_requirements) documentation.

## Tools and libraries

Styles are written in the [SCSS language](http://sass-lang.com/).
We use [Bootstrap 4](https://getbootstrap.com/) styles where possible.

## Conventions

We follow the [AirBnB CSS Conventions](https://github.com/airbnb/css)
and the [BEM](http://getbem.com/) methodology (block-element-modifier).

Because we use [Bootstrap 4](https://getbootstrap.com/) which 
does not follow [BEM](http://getbem.com/) naming convention there will be 
a lot of places where class names voilate BEM. 
However, please note that they are not a indicator of how to name classes. 
Use BEM conventions where possible.

## Linting

We use [sass-lint](https://github.com/sasstools/sass-lint) to ensure all new SCSS
written complies with the rules below. It will be provided as an npm dev dependency.
There are also quite a few [sass-lint IDE integrations](https://github.com/sasstools/sass-lint#ide-integration) 
which highlight any linting errors right in your code.

We strongly recommend installing one of these into the editor of your choice, to
avoid the frustration of failed pull requests. You can run the checks on console
via `yarn run lint`.

## File and Folder Naming

- All frontend files (CSS, JavaScript, images) should be placed in
  a `client/` folder on the top level of the module
- Frontend files relating to the `framework` CMS UI should be placed in `admin/client`
- The `client/src/components` folder should contain only reusable components
  (e.g. Button, Accordion). Presentation of these components should not rely on
  the markup context they're embedded in.
- The `client/src/containers` folder should contain use-case dependent styles only
  (e.g. CampaignAdmin). Styles in here should be kept at a minimum.
- The file name of styles nested within components and containers should inherit their
  respective folder name for easy reference.
  For example, a `components/FormAction` component has styles named `FormAction.scss`).
- The `client/src/styles` folder contains base styles (reset, typography, variables)
  as well as layout-related styles which arranges components together.

## Icons and Graphics

Most graphics used in the CMS are vector based, and stored as generated
webfonts in `admin/client/src/font`, which also contains a HTML reference. 
The webfonts are generated through the [Fontastic](http://app.fontastic.me) service.
If you need new icons to be added, please ping us on Github.  

## Legacy conventions

CSS written prior to SilverStripe 4.0 is not following the conventions outlined above.
It is contained in a `legacy/` folder structure. If modifying these styles,
consider porting them over into the new structure. Otherwise, follow these conventions:

- Class naming: Use the `cms-` class prefix for major components in the cms interface,
  and the `ss-ui-` prefix for extensions to jQuery UI. Don't use the `ui-` class prefix, its reserved for jQuery UI built-in styles.
- Use jQuery UI's built-in styles where possible, e.g. `ui-widget` for a generic container, or `ui-state-highlight`
  to highlight a specific component. See the [jQuery UI Theming API](https://api.jqueryui.com/category/theming/) for a full list.

## Related

* [PHP Coding Conventions](/contributing/php_coding_conventions)
* [JavaScript Coding Conventions](/contributing/javascript_coding_conventions)
* [Browser support](/getting_started/server_requirements/)
