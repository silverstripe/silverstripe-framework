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

/*
 * We're assigning an instances to the `ss` namespace because singletons only
 * work within the context on a single Browserify bundle.
 *
 * For example - the `lib` bundle exposes a singleton called `reducerRegister`.
 * If the `framework` imports `reducerRegister`, as an external dependency, then
 * all modules in `framework` will get the same copy of `register` when importing it.
 *
 * Likewise if the `custom` bundle imports `reducerRegister` as an external dependency,
 * all modules in `custom` will get the same copy of `reducerRegister`.
 *
 * This works as expected within the context of one bundle, all modules in that bundle
 * importing `reducerRegister` get the exact same copy, a singleton.
 *
 * However this is not true across bundles. While all modules in `framework` get a single
 * copy of `reducerRegister` and all modules in `custom` get a single copy of `reducerRegister`,
 * the copy of `reducerRegister` in `framework` is not the same copy of `reducerRegister`
 * available in `custom`.
 *
 * @TODO Look into SystemJS as a solution https://github.com/systemjs/systemjs
 */

window.ss = window.ss || {};
window.ss.reducerRegister = window.ss.reducerRegister || new ReducerRegister();

export default window.ss.reducerRegister;
