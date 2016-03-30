import deepFreeze from 'deep-freeze';
import ACTION_TYPES from './action-types';

const initialState = {
};

function recordsReducer(state = initialState, action) {
    let records;
    let recordType;

    switch (action.type) {

        case ACTION_TYPES.CREATE_RECORD:
            return deepFreeze(Object.assign({}, state, {

            }));

        case ACTION_TYPES.UPDATE_RECORD:
            return deepFreeze(Object.assign({}, state, {

            }));

        case ACTION_TYPES.DELETE_RECORD:
            return deepFreeze(Object.assign({}, state, {

            }));

        case ACTION_TYPES.FETCH_RECORDS_REQUEST:
            return state;

        case ACTION_TYPES.FETCH_RECORDS_FAILURE:
            return state;

        case ACTION_TYPES.FETCH_RECORDS_SUCCESS:
            recordType = action.payload.recordType;
            // TODO Automatic pluralisation from recordType
            records = action.payload.data._embedded[recordType + 's'];
            return deepFreeze(Object.assign({}, state, {
                [recordType]: records
            }));

        case ACTION_TYPES.DELETE_RECORD_REQUEST:
            return state;

        case ACTION_TYPES.DELETE_RECORD_FAILURE:
            return state;

        case ACTION_TYPES.DELETE_RECORD_SUCCESS:
            recordType = action.payload.recordType;
            records = state[recordType]
                .filter(record => record.ID != action.payload.id)

            return deepFreeze(Object.assign({}, state, {
                [recordType]: records
            }));

        default:
            return state;
    }

}

export default recordsReducer;
