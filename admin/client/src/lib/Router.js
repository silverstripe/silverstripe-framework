/**
 * Handles client-side routing.
 * See https://github.com/visionmedia/page.js
 */
import page from 'page.js';
import url from 'url';

/**
 * Add leading slash to base-relative urls, as required by Page.js
 *
 * If a url is unable to be resolved (because it has the wrong base url) then
 * it will be returned as an absolute url to break out of routing to do
 * a hard redirect.
 *
 * @param {string} path
 * @return {string} Normalised path
 */
function resolveURLToBase(path) {
  // Resolve path to base
  const absoluteBase = this.getAbsoluteBase();
  const absolutePath = url.resolve(absoluteBase, path);

  // Validate that this url belongs to this base; If not, normalise
  // to absolute url to force routing to do a page redirect.
  if (absolutePath.indexOf(absoluteBase) !== 0) {
    return absolutePath;
  }

  // Remove base url from absolute path, save for trailing `/` which Page.js requires
  return absolutePath.substring(absoluteBase.length - 1);
}

/**
 * Wrapper for `page.show()` with SilverStripe specific behaviour.
 *
 * @param {page} originalPage
 * @return {function} Replacement function for show
 */
function show(pageShow) {
  return (path, state, dispatch, push) => (
    pageShow(page.resolveURLToBase(path), state, dispatch, push)
  );
}

/**
 * Checks if the passed route applies to the current location.
 *
 * @param string route - The route to check.
 *
 * @return boolean
 */
function routeAppliesToCurrentLocation(route) {
  const r = new page.Route(route);
  return r.match(page.current, {});
}

/**
 * Find base url if available
 *
 * @returns {string}
 */
function getAbsoluteBase() {
  const baseTags = window.document.getElementsByTagName('base');
  if (baseTags && baseTags[0]) {
    return baseTags[0].href;
  }
  return null;
}

// Ensure that subsequent references to router.js don't nest page.show more than once
if (!page.oldshow) {
  page.oldshow = page.show;
}
page.getAbsoluteBase = getAbsoluteBase.bind(page);
page.resolveURLToBase = resolveURLToBase.bind(page);
page.show = show(page.oldshow);
page.routeAppliesToCurrentLocation = routeAppliesToCurrentLocation;

/*
 * We're assigning an instances to the `ss` namespace because singletons only
 * work within the context on a single Browserify bundle.
 *
 * For example - the `lib` bundle exposes a singleton called `router`.
 * If the `framework` imports `router`, as an external dependency, then
 * all modules in `framework` will get the same copy of `register` when importing it.
 *
 * Likewise if the `custom` bundle imports `router` as an external dependency,
 * all modules in `custom` will get the same copy of `router`.
 *
 * This works as expected within the context of one bundle, all modules in that bundle
 * importing `router` get the exact same copy, a singleton.
 *
 * However this is not true across bundles. While all modules in `framework` get a single
 * copy of `router` and all modules in `custom` get a single copy of `router`,
 * the copy of `router` in `framework` is not the same copy of `router`
 * available in `custom`.
 *
 * @TODO Look into SystemJS as a solution https://github.com/systemjs/systemjs
 */

window.ss = window.ss || {};
window.ss.router = window.ss.router || page;

export default window.ss.router;
