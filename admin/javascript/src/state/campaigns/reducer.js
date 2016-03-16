import deepFreeze from 'deep-freeze';
import ACTION_TYPES from './action-types';

const initialState = {
    isFetching: false,
    items: []
};

function campaignsReducer(state = initialState, action) {

    switch (action.type) {

        case ACTION_TYPES.CREATE_CAMPAIGN:
            return deepFreeze(Object.assign({}, state, {
                
            }));

        case ACTION_TYPES.UPDATE_CAMPAIGN:
            return deepFreeze(Object.assign({}, state, {
                
            }));

        case ACTION_TYPES.DELETE_CAMPAIGN:
            return deepFreeze(Object.assign({}, state, {
                
            }));

        case ACTION_TYPES.FETCH_CAMPAIGN_REQUEST:
            return deepFreeze(Object.assign({}, state, {
                isFetching: true
            }));

        case ACTION_TYPES.FETCH_CAMPAIGN_FAILURE:
            return deepFreeze(Object.assign({}, state, {
                isFetching: false
            }));

        case ACTION_TYPES.FETCH_CAMPAIGN_SUCCESS:
            return deepFreeze(Object.assign({}, state, {
                isFetching: false
            }));

        default:
            return state;
    }

}

export default campaignsReducer;
