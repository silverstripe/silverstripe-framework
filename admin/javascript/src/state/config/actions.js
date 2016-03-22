import ACTION_TYPES from './action-types';

/**
 * Sets global config for the application.
 *
 * @param object config
 */
export function setConfig(config) {
    return (dispatch, getState) => {
        return dispatch({
            type: ACTION_TYPES.SET_CONFIG,
            payload: { config }
        });
    }
}
