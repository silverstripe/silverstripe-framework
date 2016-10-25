import deepFreeze from 'deep-freeze-strict';
import ACTION_TYPES from './SchemaActionTypes';
import merge from 'merge';

const initialState = deepFreeze({});

export default function schemaReducer(state = initialState, action = null) {
  switch (action.type) {

    case ACTION_TYPES.SET_SCHEMA: {
      return deepFreeze(Object.assign({}, state, { [action.payload.id]: action.payload }));
    }

    case ACTION_TYPES.CLEAR_MESSAGE: {
      return deepFreeze(merge.recursive(true, {}, state, {
        [action.payload.id]: {
          state: {
            message: null,
          }
        }
      }));
    }

    default:
      return state;
  }
}
