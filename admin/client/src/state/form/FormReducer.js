import deepFreeze from 'deep-freeze-strict';
import { ACTION_TYPES } from './FormActionTypes';

const initialState = deepFreeze({});

function formReducer(state = initialState, action) {
  switch (action.type) {

    case ACTION_TYPES.SUBMIT_FORM_REQUEST:
      return deepFreeze(Object.assign({}, state, {
        [action.payload.formId]: { submitting: true },
      }));

    case ACTION_TYPES.REMOVE_FORM:
      return deepFreeze(Object.keys(state).reduce((previous, current) => {
        if (current === action.payload.formId) {
          return previous;
        }
        return Object.assign({}, previous, {
          [current]: state[current],
        });
      }, {}));

    case ACTION_TYPES.ADD_FORM:
      return deepFreeze(Object.assign({}, state, {
        [action.payload.formState.id]: {
          fields: action.payload.formState.fields,
          submitting: false,
        },
      }));

    case ACTION_TYPES.UPDATE_FIELD:
      return deepFreeze(Object.assign({}, state, {
        [action.payload.formId]: Object.assign({}, state[action.payload.formId], {
          fields: state[action.payload.formId].fields.map((field) => {
            if (field.id === action.payload.updates.id) {
              return Object.assign({}, field, action.payload.updates);
            }
            return field;
          }),
        }),
      }));

    case ACTION_TYPES.SUBMIT_FORM_SUCCESS:
      return deepFreeze(Object.assign({}, state, {
        [action.payload.response.id]: {
          fields: action.payload.response.state.fields,
          messages: action.payload.response.state.messages,
          submitting: false,
        },
      }));

    case ACTION_TYPES.SUBMIT_FORM_FAILURE:
      return deepFreeze(Object.assign({}, state, {
        [action.payload.formId]: { submitting: false },
      }));

    default:
      return state;

  }
}

export default formReducer;
