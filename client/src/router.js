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

page.getAbsoluteBase = getAbsoluteBase.bind(page);
page.resolveURLToBase = resolveURLToBase.bind(page);
page.show = show(page.show);
page.routeAppliesToCurrentLocation = routeAppliesToCurrentLocation;

export default page;
