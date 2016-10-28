import deepFreeze from 'deep-freeze-strict';
import ACTION_TYPES from './SchemaActionTypes';

const initialState = deepFreeze({});

export default function schemaReducer(state = initialState, action = null) {
  switch (action.type) {

    case ACTION_TYPES.SET_SCHEMA: {
      return deepFreeze(Object.assign({}, state, { [action.payload.id]: action.payload }));
    }

    case ACTION_TYPES.DESTROY_SCHEMA: {
      return deepFreeze(Object.assign({}, state, {
        [action.payload.id]: undefined,
      }));
    }

    default:
      return state;
  }
}
