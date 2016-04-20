/**
 * Provides methods for interacting with the client config.
 * The client config is defined using the YAML/PHP config system.
 *
 * @class
 */
class Config {

  /**
   * Gets the the config for a specific section.
   *
   * @param string key - The section config key.
   *
   * @return object|undefined
   */
  static getSection(key) {
    return window.ss.config.sections[key];
  }

  /**
   * Gets a de-duped list of routes for top level controllers. E.g. 'assets', 'pages', etc.
   *
   * @return array
   */
  static getTopLevelRoutes() {
    const topLevelRoutes = [];

    Object.keys(window.ss.config.sections).forEach((key) => {
      const route = window.ss.config.sections[key].route;
      const isTopLevelRoute = route.indexOf('/') === -1;
      const isUnique = topLevelRoutes.indexOf(route) === -1;

      if (isTopLevelRoute && isUnique) {
        topLevelRoutes.push(route);
      }
    });

    return topLevelRoutes;
  }

}

export default Config;
