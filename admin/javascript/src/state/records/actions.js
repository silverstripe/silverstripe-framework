import ACTION_TYPES from './action-types';
import fetch from 'isomorphic-fetch';
import backend from 'silverstripe-backend.js';

/**
 * Retrieves all records
 *
 * @param string recordType Type of record (the "class name")
 * @param string method HTTP methods
 * @param string url API endpoint
 */
export function fetchRecords(recordType, method, url) {
    return (dispatch, getState) => {
		dispatch ({type: ACTION_TYPES.FETCH_RECORDS_REQUEST, payload: {recordType: recordType}});
        return backend[method.toLowerCase()](url)
			.then(response => response.json())
			.then(json => dispatch({type: ACTION_TYPES.FETCH_RECORDS_SUCCESS, payload: {recordType: recordType, data: json}}))
			.catch((err) => {
				dispatch({type: ACTION_TYPES.FETCH_RECORDS_FAILURE, payload: {error: err, recordType: recordType}})
			});
    }
}
