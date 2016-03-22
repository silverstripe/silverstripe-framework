import deepFreeze from 'deep-freeze';
import ACTION_TYPES from './action-types';

function configReducer(state = {}, action) {

    switch (action.type) {

        case ACTION_TYPES.SET_CONFIG:
            return deepFreeze(Object.assign({}, state, action.payload.config));

        default:
            return state;

    }

}

export default configReducer;
