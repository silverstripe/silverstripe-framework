import fetch from 'isomorphic-fetch';
import es6promise from 'es6-promise';
import qs from 'qs';

es6promise.polyfill();

/**
 * @see https://github.com/github/fetch#handling-http-error-statuses
 */
function checkStatus(response) {
  let ret;
  let error;
  if (response.status >= 200 && response.status < 300) {
    ret = response;
  } else {
    error = new Error(response.statusText);
    error.response = response;
    throw error;
  }

  return ret;
}

class SilverStripeBackend {

  constructor() {
    // Allow mocking
    this.fetch = fetch;
  }

  /**
   * Create an endpoint fetcher from an endpoint spec.
   *
   * An endpoint fetcher is a anonymous function that returns a Promise.
   * The function receives an JS object with properties, and will pass another JS object to
   * the handler callbacks attached Promise. Other consumers don't need to deal with payload
   * encoding, etc.
   *
   * An endpoint spec is a JS object with the following properties:
   *
   *   - url: A fully-qualified URL
   *   - method: 'get', 'post', 'put', or 'delete'
   *   - payloadFormat: the content-type of the request data.
   *   - responseFormat: the content-type of the response data. Decoding will be handled for you.
   *
   * There is a special payloadFormat value, 'querystring', that will appear url-encoded data to
   * the request URL instead of encoding data in the request body. It's a useful format to use with
   * get requests.
   *
   * Both payloadFormat and responseFormat can use the following shortcuts for their corresponding
   * mime types:
   *
   *   - urlencoded: application/x-www-form-url-encoded
   *   - json: application/json
   *
   * For now, these are the only two mime types supported.
   *
   * The intent is that your endpoint spec can keep track of the mechanics of interacting with the
   * backend server, and your application code can just pass a JS object endpoint fetcher. This also
   * simplifies mocking.
   */
  createEndpointFetcher(endpointSpec) {
    // Encode a payload based on the given contentType
    function encode(contentType, data) {
      switch (contentType) {
        case 'application/x-www-form-url-encoded':
          return qs.stringify(data);

        case 'application/json':
        case 'application/x-json':
        case 'application/x-javascript':
        case 'text/javascript':
        case 'text/x-javascript':
        case 'text/x-json':
          return JSON.stringify(data);

        default:
          throw new Error(`Can\'t encode format: ${contentType}`);
      }
    }

    // Decode a payload based on the given contentType
    function decode(contentType, text) {
      switch (contentType) {
        case 'application/x-www-form-url-encoded':
          return qs.parse(text);

        case 'application/json':
        case 'application/x-json':
        case 'application/x-javascript':
        case 'text/javascript':
        case 'text/x-javascript':
        case 'text/x-json':
          return JSON.parse(text);

        default:
          throw new Error(`Can\'t decode format: ${contentType}`);
      }
    }

    // Add a querystring to a url
    function addQuerystring(url, querystring) {
      if (url.match(/\?/)) return `${url}&${querystring}`;
      return `${url}?${querystring}`;
    }

    // Parse the response based on the content type returned
    function parseResponse(response) {
      return response.text().then(
        body => decode(response.headers.get('Content-Type'), body)
      );
    }

    // Parameter defaults
    const refinedSpec = Object.assign({
      method: 'get',
      payloadFormat: 'application/x-www-form-url-encoded',
      responseFormat: 'application/json',
    }, endpointSpec);

    // Substitute shorcut format values with their full mime types
    const formatShortcuts = {
      json: 'application/json',
      urlencoded: 'application/x-www-form-url-encoded',
    };
    ['payloadFormat', 'responseFormat'].forEach(
      (key) => {
        if (formatShortcuts[refinedSpec[key]]) refinedSpec[key] = formatShortcuts[refinedSpec[key]];
      }
    );

    // Different execution path for using querystring as the payload format
    if (refinedSpec.payloadFormat === 'querystring') {
      return (data) => {
        const headers = {
          Accept: refinedSpec.responseFormat,
        };

        const encodedData = encode('application/x-www-form-url-encoded', data);
        const url = addQuerystring(endpointSpec.url, encodedData);

        return this[refinedSpec.method](url, null, headers)
          .then(parseResponse);
      };
    }

    // Return the default fetcher function
    return (data) => {
      const headers = {
        Accept: refinedSpec.responseFormat,
        'Content-Type': refinedSpec.payloadFormat,
      };

      const encodedData = encode(refinedSpec.payloadFormat, data);

      return this[refinedSpec.method](endpointSpec.url, encodedData, headers)
        .then(parseResponse);
    };
  }

  /**
   * Makes a network request using the GET HTTP verb.
   *
   * @param string url - Endpoint URL.
   * @return object - Promise
   */
  get(url) {
    return this.fetch(url, { method: 'get', credentials: 'same-origin' })
      .then(checkStatus);
  }

  /**
   * Makes a network request using the POST HTTP verb.
   *
   * @param string url - Endpoint URL.
   * @param object data - Data to send with the request.
   * @return object - Promise
   */
  post(url, data) {
    return this.fetch(url, {
      method: 'post',
      headers: new Headers({
        'Content-Type': 'application/x-www-form-urlencoded',
      }),
      credentials: 'same-origin',
      body: data,
    })
    .then(checkStatus);
  }

  /**
   * Makes a newtwork request using the PUT HTTP verb.
   *
   * @param string url - Endpoint URL.
   * @param object data - Data to send with the request.
   * @return object - Promise
   */
  put(url, data) {
    return this.fetch(url, { method: 'put', credentials: 'same-origin', body: data })
      .then(checkStatus);
  }

  /**
   * Makes a newtwork request using the DELETE HTTP verb.
   *
   * @param string url - Endpoint URL.
   * @param object data - Data to send with the request.
   * @return object - Promise
   */
  delete(url, data) {
    return this.fetch(url, { method: 'delete', credentials: 'same-origin', body: data })
      .then(checkStatus);
  }

}

// Exported as a singleton so we can implement things like
// global caching and request batching at some stage.
const backend = new SilverStripeBackend();

export default backend;
