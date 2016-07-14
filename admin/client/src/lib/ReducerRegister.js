/**
 * The register of Redux reducers.
 * @private
 */
const register = {};

/**
 * The central register of Redux reducers for the CMS.
 * All registered reducers are combined when the application boots.
 */
class ReducerRegister {

  /**
   * Adds a reducer to the register.
   *
   * @param string key - The key to register the reducer against.
   * @param object reducer - Redux reducer.
   */
  add(key, reducer) {
    if (typeof register[key] !== 'undefined') {
      throw new Error(`Reducer already exists at '${key}'`);
    }

    register[key] = reducer;
  }

  /**
   * Gets all reducers from the register.
   *
   * @return object
   */
  getAll() {
    return register;
  }

  /**
   * Gets a reducer from the register.
   *
   * @param string [key] - The key the reducer is registered against.
   *
   * @return object|undefined
   */
  getByKey(key) {
    return register[key];
  }

  /**
   * Removes a reducer from the register.
   *
   * @param string key - The key the reducer is registered against.
   */
  remove(key) {
    delete register[key];
  }

}

// TODO Fix registration across modules, see lib/Router.js for details
window.ss = window.ss || {};
window.ss.reducerRegister = window.ss.reducerRegister || new ReducerRegister();

export default window.ss.reducerRegister;
