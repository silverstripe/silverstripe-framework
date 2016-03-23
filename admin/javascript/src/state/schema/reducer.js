import deepFreeze from 'deep-freeze';
import ACTION_TYPES from './action-types';

const initialState = deepFreeze({});

export default function schemaReducer(state = initialState, action = null) {

    switch (action.type) {

        case ACTION_TYPES.SET_SCHEMA:
            const id = action.payload.schema.schema_url;
            return deepFreeze(Object.assign({}, state, {[id]: action.payload}));

        default:
            return state;
    }

}
