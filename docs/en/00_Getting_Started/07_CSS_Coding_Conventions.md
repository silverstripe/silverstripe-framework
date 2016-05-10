# CSS and SCSS Coding Conventions

## Overview

This document provides guidelines for code formatting to developers contributing
to SilverStripe. It applies to all CSS/Sass files in the `framework/` and `cms/` modules.

In 2016, SilverStripe started a rewrite of the styles of the CMS interface.
This rewrite is work-in-progress so code written prior to this
rewrite might not follow these conventions, and is placed in a `legacy/` folder structure.

## Browser support

Check our [requirements](/getting_started/server_requirements) documentation.

## Tools and libraries

Styles are written in the [SCSS language](http://sass-lang.com/).
We use [Bootstrap 4](http://v4-alpha.getbootstrap.com/) styles where possible.

## Conventions

We follow the [AirBnB CSS Conventions](https://github.com/airbnb/css)
and the [BEM](http://getbem.com/) methodology (block-element-modifier).
File naming and style include ordering is inspired by
[ITCSS](https://www.xfive.co/blog/itcss-scalable-maintainable-css-architecture/).

## Linting

We use [SCSSLint](https://github.com/brigade/scss-lint) to ensure all new SCSS
written complies with the rules below. Please consider installing it
in your development environment (you'll need Ruby). There's also
quite a few [SCSSLint IDE integrations](https://github.com/brigade/scss-lint#editor-integration)
which highlight any linting errors right in your code.

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
  Naming and conventions in this folder follow
  [ITCSS](https://www.xfive.co/blog/itcss-scalable-maintainable-css-architecture/).

## Legacy conventions

CSS written prior to SilverStripe 4.0 is not following the conventions outlined above.
It is contained in a `legacy/` folder structure. If modifying these styles,
consider porting them over into the new structure. Otherwise, follow these conventions:

- Class naming: Use the `cms-` class prefix for major components in the cms interface,
  and the `ss-ui-` prefix for extensions to jQuery UI. Don't use the `ui-` class prefix, its reserved for jQuery UI built-in styles.
- Use jQuery UI's built-in styles where possible, e.g. `ui-widget` for a generic container, or `ui-state-highlight`
  to highlight a specific component. See the [jQuery UI Theming API](http://jqueryui.com/docs/Theming/API) for a full list.

## Related

* [PHP Coding Conventions](/getting_started/coding_conventions)
* [JavaScript Coding Conventions](/getting_started/javascript_coding_conventions)
* [Browser support](/getting_started/server_requirements/)
