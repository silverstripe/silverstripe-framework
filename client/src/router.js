/**
 * Handles client-side routing.
 * See https://github.com/visionmedia/page.js
 */
import page from 'page.js';

/**
 * Wrapper for `page.show()` with SilverStripe specific behaviour.
 */
function show(pageShow) {
  return (path, state, dispatch, push) => {
    // Normalise `path` so that pattern matching is more robust.
    // For example if your route is '/pages' it should match when `path` is
    // 'http://foo.com/admin/pages', '/pages', and 'pages'.
    const el = document.createElement('a');
    let pathWithSearch;
    el.href = path;
    pathWithSearch = el.pathname;
    if (el.search) {
      pathWithSearch += el.search;
    }

    return pageShow(pathWithSearch, state, dispatch, push);
  };
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

page.show = show(page.show);
page.routeAppliesToCurrentLocation = routeAppliesToCurrentLocation;

export default page;
