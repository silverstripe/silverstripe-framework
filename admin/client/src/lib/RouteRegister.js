import deepFreeze from 'deep-freeze';

/**
 * The register of routes
 * @private
 */
let register = deepFreeze({});

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
   * Revoves a route from the register.
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

const routeRegister = new RouteRegister();

export default routeRegister;
