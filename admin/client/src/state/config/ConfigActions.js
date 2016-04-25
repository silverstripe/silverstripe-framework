import ACTION_TYPES from './ConfigActionTypes';

/**
 * Sets global config for the application.
 *
 * @param object config
 */
export function setConfig(config) {
  return {
    type: ACTION_TYPES.SET_CONFIG,
    payload: { config },
  };
}
