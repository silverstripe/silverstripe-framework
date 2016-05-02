import deepFreeze from 'deep-freeze';

/**
 * The register of routes
 *
 * @private
 */
let register = deepFreeze({});

/**
 * RouteRegister is the iterface developers should use to register routes with
 * the main client application. Routes should not be registered with Page.js directly.
 *
 * Register routes using the `DOMContentLoaded` event in your controller file.
 *
 * __controller.js__
 * ```
 * import routeRegister from 'lib/RouteRegister';
 *
 * document.addEventListener('DOMContentLoaded', () => {
 *   routeRegister.add('', (ctx, next) => {
 *     // Do stuff.
 *   });
 * }
 * ```
 *
 * Any route callback you register will invoked _after_ passing through to top level wildcard route.
 * This route adds some custom properties to the `ctx` object which will be useful in your callback.
 *
 * `ctx.store` - [Redux store](http://redux.js.org/docs/api/Store.html) for the client application.
 *
 * All routes registered with `RouteRegister` are applied to Page.js by `appBoot()`
 * see `/admin/client/src/boot/index.js`.
 *
 * Page.js doesn't provide a way to inspect which routes are registered
 * so you can use `RouteRegister` to do this using the `get` or `getAll` methods.
 *
 * @class
 */
class RouteRegister {
  /**
   * Adds a route to the register.
   *
   * @param {string} route - The route to register.
   * @param {function} callback - Called when the route matches.
   * @return {object}
   */
  add(route, callback) {
    if (typeof register[route] !== 'undefined') {
      throw new Error(`Route callback already registered for '${route}'`);
    }

    register = deepFreeze(Object.assign({}, register, {
      [route]: callback,
    }));

    return register;
  }

  /**
   * Removes a route from the register.
   *
   * @param {string} - The route to remove.
   * @return {object}
   */
  remove(route) {
    register = deepFreeze(Object.keys(register).reduce((result, current) => {
      if (current === route) {
        return result;
      }
      return Object.assign({}, result, {
        [current]: register[current],
      });
    }, {}));

    return register;
  }

  /**
   * Removes all routes from the register.
   *
   * @return {object}
   */
  removeAll() {
    register = deepFreeze({});
    return register;
  }

  /**
   * Gets the callback for a route in the register.
   *
   * @param {string}
   * @return {object|null}
   */
  get(route) {
    return typeof register[route] !== 'undefined'
      ? { [route]: register[route] }
      : null;
  }

  /**
   * Gets all routes and their callbacks from the register.
   *
   * @return {object}
   */
  getAll() {
    return register;
  }
}

/*
 * We're assigning an instances to the `ss` namespace because singletons only
 * work within the context on a single Browserify bundle.
 *
 * For example - the `lib` bundle exposes a singleton called `routeRegister`.
 * If the `framework` imports `routeRegister`, as an external dependency, then
 * all modules in `framework` will get the same copy of `register` when importing it.
 *
 * Likewise if the `custom` bundle imports `routeRegister` as an external dependency,
 * all modules in `custom` will get the same copy of `routeRegister`.
 *
 * This works as expected within the context of one bundle, all modules in that bundle
 * importing `routeRegister` get the exact same copy, a singleton.
 *
 * However this is not true across bundles. While all modules in `framework` get a single
 * copy of `routeRegister` and all modules in `custom` get a single copy of `routeRegister`,
 * the copy of `routeRegister` in `framework` is not the same copy of `routeRegister`
 * available in `custom`.
 *
 * @TODO Look into SystemJS as a solution https://github.com/systemjs/systemjs
 */

window.ss = window.ss || {};
window.ss.routeRegister = window.ss.routeRegister || new RouteRegister();

export default window.ss.routeRegister;
