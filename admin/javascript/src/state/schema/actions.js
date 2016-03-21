import ACTION_TYPES from './action-types';

/**
 * Sets the schema being used to generate the curent layout.
 *
 * @param string schema - JSON schema for the layout.
 */
export function setSchema(schema) {
    return (dispatch, getState) => {
        return dispatch ({
            type: ACTION_TYPES.SET_SCHEMA,
            payload: schema
        });
    }
}
