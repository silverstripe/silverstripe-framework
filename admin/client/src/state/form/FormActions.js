import { ACTION_TYPES } from './FormActionTypes';

/**
 * Removes a form from state.
 * This action should be called when a Redux managed Form component unmounts.
 *
 * @param {string} formId - ID of the form you want to remove.
 * @return {function}
 */
export function removeForm(formId) {
  return (dispatch) => {
    dispatch({
      type: ACTION_TYPES.REMOVE_FORM,
      payload: { formId },
    });
  };
}

/**
 * Sets one or more values on an existing form field.
 *
 * @param {string} formId - Id of the form where the field lives.
 * @param {object} updates - The values to update on the field.
 * @param {string} updates.id - Field ID.
 * @return {function}
 */
export function updateField(formId, updates) {
  return (dispatch) => {
    dispatch({
      type: ACTION_TYPES.UPDATE_FIELD,
      payload: { formId, updates },
    });
  };
}

/**
 * Adds a form to the store.
 *
 * @param {object} formState
 * @param {string} formState.id - The ID the form will be keyed as in state.
 * @return {function}
 */
export function addForm(formState) {
  return (dispatch) => {
    dispatch({
      type: ACTION_TYPES.ADD_FORM,
      payload: { formState },
    });
  };
}

/**
 * Submits a form and handles the response.
 *
 * @param {Function} submitApi
 * @param {String} formId
 */
export function submitForm(submitApi, formId, fieldValues) {
  return (dispatch) => {
    dispatch({
      type: ACTION_TYPES.SUBMIT_FORM_REQUEST,
      payload: { formId },
    });

    submitApi(Object.assign({ ID: formId }, fieldValues), { 'X-Formschema-Request': 'state' })
      .then((response) => {
        dispatch({
          type: ACTION_TYPES.SUBMIT_FORM_SUCCESS,
          payload: { response },
        });
      })
      .catch((error) => {
        dispatch({
          type: ACTION_TYPES.SUBMIT_FORM_FAILURE,
          payload: { formId, error },
        });
      });
  };
}
