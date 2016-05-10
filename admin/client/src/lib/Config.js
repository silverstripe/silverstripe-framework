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
      let route = window.ss.config.sections[key].route;

      // Check if this is a top level route
      const topLevelMatch = route.match(/^admin\/[^\/]+(\/?)$/);
      if (!topLevelMatch) {
        return;
      }

      // Remove trailing slash
      route = route.replace(/\/$/, '');

      // Check uniqueness and save
      const isUnique = topLevelRoutes.indexOf(route) === -1;
      if (isUnique) {
        topLevelRoutes.push(route);
      }
    });

    return topLevelRoutes;
  }

}

export default Config;
