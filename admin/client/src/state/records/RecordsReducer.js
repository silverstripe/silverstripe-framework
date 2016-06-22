import deepFreeze from 'deep-freeze-strict';
import ACTION_TYPES from './RecordsActionTypes';

const initialState = {};

function recordsReducer(state = initialState, action) {
  let records;
  let recordType;
  let record;

  switch (action.type) {

    case ACTION_TYPES.CREATE_RECORD:
      return deepFreeze(Object.assign({}, state, {}));

    case ACTION_TYPES.UPDATE_RECORD:
      return deepFreeze(Object.assign({}, state, {}));

    case ACTION_TYPES.DELETE_RECORD:
      return deepFreeze(Object.assign({}, state, {}));

    case ACTION_TYPES.FETCH_RECORDS_REQUEST:
      return state;

    case ACTION_TYPES.FETCH_RECORDS_FAILURE:
      return state;

    case ACTION_TYPES.FETCH_RECORDS_SUCCESS:
      recordType = action.payload.recordType;
      if (!recordType) {
        throw new Error('Undefined record type');
      }
      records = action.payload.data._embedded[recordType] || {};
      records = records.reduce((prev, val) => Object.assign({}, prev, { [val.ID]: val }), {});
      return deepFreeze(Object.assign({}, state, {
        [recordType]: records,
      }));

    case ACTION_TYPES.FETCH_RECORD_REQUEST:
      return state;

    case ACTION_TYPES.FETCH_RECORD_FAILURE:
      return state;

    case ACTION_TYPES.FETCH_RECORD_SUCCESS:
      recordType = action.payload.recordType;
      record = action.payload.data;

      if (!recordType) {
        throw new Error('Undefined record type');
      }
      return deepFreeze(Object.assign({}, state, {
        [recordType]: Object.assign({}, state[recordType], { [record.ID]: record }),
      }));

    case ACTION_TYPES.DELETE_RECORD_REQUEST:
      return state;

    case ACTION_TYPES.DELETE_RECORD_FAILURE:
      return state;

    case ACTION_TYPES.DELETE_RECORD_SUCCESS:
      recordType = action.payload.recordType;
      records = state[recordType];
      records = Object.keys(records)
        .reduce((result, key) => {
          if (parseInt(key, 10) !== parseInt(action.payload.id, 10)) {
            return Object.assign({}, result, { [key]: records[key] });
          }
          return result;
        }, {});

      return deepFreeze(Object.assign({}, state, {
        [recordType]: records,
      }));

    default:
      return state;
  }
}

export default recordsReducer;
