import ACTION_TYPES from './SchemaActionTypes';

/**
 * Sets the schema being used to generate the current form layout.
 * Note that the `state` key just determines the initial form field values,
 * and is overruled by redux-form behaviour (stored in separate reducer key)
 *
 * @param string schema - JSON schema for the layout.
 */
export function setSchema(schema) {
  return {
    type: ACTION_TYPES.SET_SCHEMA,
    payload: schema,
  };
}

export function clearMessage(id) {
  if (id) {
    return {
      type: ACTION_TYPES.CLEAR_MESSAGE,
      payload: { id },
    };
  }
  return null;
}
