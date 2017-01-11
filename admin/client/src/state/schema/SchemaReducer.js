import deepFreeze from 'deep-freeze-strict';
import ACTION_TYPES from './SchemaActionTypes';

const initialState = deepFreeze({});

export default function schemaReducer(state = initialState, action = null) {
  switch (action.type) {
    case ACTION_TYPES.SET_SCHEMA: {
      return deepFreeze(Object.assign({}, state, {
        [action.payload.id]: Object.assign({}, state[action.payload.id], action.payload),
      }));
    }

    case ACTION_TYPES.SET_SCHEMA_STATE_OVERRIDES: {
      return deepFreeze(Object.assign({}, state, {
        [action.payload.id]: Object.assign({}, state[action.payload.id], {
          stateOverride: action.payload.stateOverride,
        }),
      }));
    }

    case ACTION_TYPES.SET_SCHEMA_LOADING: {
      return deepFreeze(Object.assign({}, state, {
        [action.payload.id]: Object.assign({}, state[action.payload.id], {
          metadata: Object.assign({},
            state[action.payload.id] && state[action.payload.id].metadata,
            { loading: action.payload.loading }
          ),
        }),
      }));
    }

    default:
      return state;
  }
}
