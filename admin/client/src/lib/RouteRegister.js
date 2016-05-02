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

export default RouteRegister;
