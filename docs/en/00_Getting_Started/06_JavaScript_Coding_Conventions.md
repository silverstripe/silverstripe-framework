# JavaScript Coding Conventions

## Overview

This document provides guidelines for code formatting to developers contributing
to SilverStripe. It applies to all JavaScript files in the `framework/` and `cms/` modules.

In 2016, SilverStripe started a rewrite of its CMS interface in
[ES6](https://en.wikipedia.org/wiki/ECMAScript) and
[ReactJS](http://facebook.github.io/react/). Code written prior to this rewrite
might not follow these conventions, and is placed in a `legacy/` folder structure.

## Conventions

We follow the [AirBnB JavaScript Conventions](https://github.com/airbnb/javascript).
A lot of their rules can be validated via [ESLint](http://eslint.org/),
and can be checked locally via `npm run lint`.

## File and Folder Naming

- All frontend files (CSS, JavaScript, images) should be placed in
  a `client/` folder on the top level of the module
- Frontend files relating to the `framework` CMS UI should be placed in `admin/client`
- File names should follow the [AirBnB Naming Conventions](https://github.com/airbnb/javascript#naming-conventions)
- The `client/src/components` folder should contain only React
  [presentational components](https://medium.com/@dan_abramov/smart-and-dumb-components-7ca2f9a7c7d0#.r635clean).
  These components should be self-contained, and shouldn't know about Redux state.
- The `client/src/containers` folder should contain only React
  [container components](https://medium.com/@dan_abramov/smart-and-dumb-components-7ca2f9a7c7d0#.r635clean)
- React classes in `client/src/components` and `client/src/containers` should
  have one folder per component, alongside a `README.md` and SCSS files where applicable.
- The `client/src/state` folder should contain [Redux](http://redux.js.org/)
  actions, action types and reducers. Each set of these should be kept in a folder
  named the same as its [reducer state key](http://redux.js.org/docs/basics/Reducers.html).
- JavaScript tests should be named after the module/class they're testing,
  with their file name suffixed with `-tests.js`.
- JavaScript tests should be placed in a `tests/` subfolder alongside the module code.

## Related

* [PHP Coding Conventions](/getting_started/coding_conventions)
