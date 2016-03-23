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

}

export default Config;
