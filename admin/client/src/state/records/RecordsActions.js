import ACTION_TYPES from './RecordsActionTypes';
import backend from 'lib/Backend.js';

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
  const methodToLowerCase = method.toLowerCase();

  return (dispatch) => {
    dispatch({
      type: ACTION_TYPES.FETCH_RECORDS_REQUEST,
      payload,
    });

    const args = methodToLowerCase === 'get'
      ? [populate(url, payload), headers]
      : [populate(url, payload), {}, headers];

    return backend[methodToLowerCase](...args)
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
        throw err;
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
  const methodToLowerCase = method.toLowerCase();

  return (dispatch) => {
    dispatch({
      type: ACTION_TYPES.FETCH_RECORD_REQUEST,
      payload,
    });

    const args = methodToLowerCase === 'get'
      ? [populate(url, payload), headers]
      : [populate(url, payload), {}, headers];

    return backend[methodToLowerCase](...args)
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
        throw err;
      });
  };
}

/**
 * Deletes a record
 *
 * @param {string} recordType Type of record (the "class name")
 * @param {number} id Database identifier
 * @param {string} method HTTP method
 * @param {string} url API endpoint
 * @param {object} Headers
 */
export function deleteRecord(recordType, id, method, url, headers = {}) {
  const payload = { recordType, id };
  const methodToLowerCase = method.toLowerCase();
  const args = methodToLowerCase === 'get'
      ? [populate(url, payload), headers]
      : [populate(url, payload), {}, headers];

  return (dispatch) => {
    dispatch({
      type: ACTION_TYPES.DELETE_RECORD_REQUEST,
      payload,
    });
    return backend[methodToLowerCase](...args)
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
        throw err;
      });
  };
}
