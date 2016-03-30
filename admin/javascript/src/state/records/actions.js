import ACTION_TYPES from './action-types';
import fetch from 'isomorphic-fetch';
import backend from 'silverstripe-backend.js';

/**
 * Populate strings based on a whitelist.
 * Not using ES6 string interpolation because its too powerful
 * for user supplied data.
 *
 * Example: populate("foo/bar/:id", {id: 123}) => "foo/bar/123"
 *
 * @param string str A template string with ":<name>" notation.
 * @param object Map of names to values
 * @return string
 */
function populate(str, params) {
    let names = ['id'];
    return names.reduce((str, name) => str.replace(`:${name}`, params[name]), str);
}

/**
 * Retrieves all records
 *
 * @param string recordType Type of record (the "class name")
 * @param string method HTTP method
 * @param string url API endpoint
 */
export function fetchRecords(recordType, method, url) {
    let payload = {recordType: recordType};
    url = populate(url, payload);
    return (dispatch, getState) => {
		dispatch ({type: ACTION_TYPES.FETCH_RECORDS_REQUEST, payload: payload});
        return backend[method.toLowerCase()](url)
			.then(response => response.json())
			.then(json => {
                dispatch({type: ACTION_TYPES.FETCH_RECORDS_SUCCESS, payload: {recordType: recordType, data: json}})
            })
			.catch((err) => {
				dispatch({type: ACTION_TYPES.FETCH_RECORDS_FAILURE, payload: {error: err, recordType: recordType}})
			});
    }
}

/**
 * Deletes a record
 *
 * @param string recordType Type of record (the "class name")
 * @param number id Database identifier
 * @param string method HTTP method
 * @param string url API endpoint
 */
export function deleteRecord(recordType, id, method, url) {
    let payload = {recordType: recordType, id: id};
    url = populate(url, payload);
    return (dispatch, getState) => {
		dispatch ({type: ACTION_TYPES.DELETE_RECORD_REQUEST, payload: payload});
        return backend[method.toLowerCase()](url)
			.then(json => {
                dispatch({type: ACTION_TYPES.DELETE_RECORD_SUCCESS, payload: {recordType: recordType, id: id}})
            })
			.catch((err) => {
				dispatch({type: ACTION_TYPES.DELETE_RECORD_FAILURE, payload: {error: err, recordType: recordType, id: id}})
			});
    }
}
