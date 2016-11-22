import ACTION_TYPES from './SchemaActionTypes';

/**
 * Sets the schema being used to generate the current form layout.
 * Note that the `state` key just determines the initial form field values,
 * and is overruled by redux-form behaviour (stored in separate reducer key)
 *
 * @param {int} id
 * @param {object} schema - JSON schema for the layout.
 */
export function setSchema(id, schema) {
  return {
    type: ACTION_TYPES.SET_SCHEMA,
    payload: Object.assign({ id }, schema),
  };
}

/**
 * For setting the stateOverride of a formData in redux store
 *
 * @param {int} id
 * @param {object} stateOverride
 * @returns {object}
 */
export function setSchemaStateOverrides(id, stateOverride) {
  return {
    type: ACTION_TYPES.SET_SCHEMA_STATE_OVERRIDES,
    payload: {
      id,
      stateOverride,
    },
  };
}

/**
 * Tracks loading of schema for a form
 *
 * @param id
 * @returns {object}
 */
export function setSchemaLoading(id, loading) {
  return {
    type: ACTION_TYPES.SET_SCHEMA_LOADING,
    payload: {
      id,
      loading,
    },
  };
}
