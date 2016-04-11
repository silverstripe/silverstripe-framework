import ACTION_TYPES from './action-types';
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
  const names = ['id'];
  return names.reduce((acc, name) => acc.replace(`:${name}`, params[name]), str);
}

/**
 * Retrieves all records
 *
 * @param string recordType Type of record (the "class name")
 * @param string method HTTP method
 * @param string url API endpoint
 */
export function fetchRecords(recordType, method, url) {
  const payload = { recordType };
  const headers = { Accept: 'text/json' };
  return (dispatch) => {
    dispatch({
      type: ACTION_TYPES.FETCH_RECORDS_REQUEST,
      payload,
    });
    const args = method.toLowerCase() === 'get'
      ? [populate(url, payload), headers]
      : [populate(url, payload), {}, headers];

    return backend[method.toLowerCase()](...args)
    .then(response => response.json())
    .then(json => {
      dispatch({
        type: ACTION_TYPES.FETCH_RECORDS_SUCCESS,
        payload: { recordType, data: json },
      });
    })
    .catch((err) => {
      dispatch({
        type: ACTION_TYPES.FETCH_RECORDS_FAILURE,
        payload: { error: err, recordType },
      });
    });
  };
}


/**
 * Fetches a single record
 *
 * @param string recordType Type of record (the "class name")
 * @param string method HTTP method
 * @param string url API endpoint
 */
export function fetchRecord(recordType, method, url) {
  const payload = { recordType };
  const headers = { Accept: 'text/json' };
  return (dispatch) => {
    dispatch({
      type: ACTION_TYPES.FETCH_RECORD_REQUEST,
      payload,
    });
    const args = method.toLowerCase() === 'get'
      ? [populate(url, payload), headers]
      : [populate(url, payload), {}, headers];

    return backend[method.toLowerCase()](...args)
    .then(response => response.json())
    .then(json => {
      dispatch({
        type: ACTION_TYPES.FETCH_RECORD_SUCCESS,
        payload: { recordType, data: json },
      });
    })
    .catch((err) => {
      dispatch({
        type: ACTION_TYPES.FETCH_RECORD_FAILURE,
        payload: { error: err, recordType },
      });
    });
  };
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
  const payload = { recordType, id };
  return (dispatch) => {
    dispatch({
      type: ACTION_TYPES.DELETE_RECORD_REQUEST,
      payload,
    });
    return backend[method.toLowerCase()](populate(url, payload))
      .then(() => {
        dispatch({
          type: ACTION_TYPES.DELETE_RECORD_SUCCESS,
          payload: { recordType, id },
        });
      })
      .catch((err) => {
        dispatch({
          type: ACTION_TYPES.DELETE_RECORD_FAILURE,
          payload: { error: err, recordType, id },
        });
      });
  };
}
