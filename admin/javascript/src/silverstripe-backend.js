import fetch from 'isomorphic-fetch';
import es6promise from 'es6-promise';
import qs from 'qs';
import merge from 'merge';

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
   * The intent is that your endpoint spec can keep track of the mechanics of interacting with the
   * backend server, and your application code can just pass a JS object endpoint fetcher. This also
   * simplifies mocking.
   *
   * # Endpoint Specification
   *
   * An endpoint spec is a JS object with the following properties:
   *
   *   - url: A fully-qualified URL
   *   - method: 'get', 'post', 'put', or 'delete'
   *   - payloadFormat: the content-type of the request data.
   *   - responseFormat: the content-type of the response data. Decoding will be handled for you.
   *   - payloadSchema: Definition for how the payload data passed into the created method
   *     will be processed. See "Payload Schema"
   *   - defaultData: Data to merge into the payload
   *     (which is passed into the returned method when invoked)
   *
   * # Payload Formats
   *
   * Both `payloadFormat` and `responseFormat` can use the following shortcuts for their
   * corresponding mime types:
   *
   *   - urlencoded: application/x-www-form-urlencoded
   *   - json: application/json
   *
   * Requests with `method: 'get'` will automatically be sent as `urlencoded`,
   * with any `data` passed to the endpoint fetcher being added to the `url`
   * as query parameters.
   *
   * # Payload Schema
   *
   * The `payloadSchema` argument can contain one or more keys found in the data payload,
   * and defines how to transform the request parameters accordingly.
   *
   * ```json
   * let endpoint = createEndpointFetcher({
   *   url: 'http://example.org/:one/:two',
   *   method: 'post',
   *   payloadSchema: {
   *    one: { urlReplacement: ':one', remove: true },
   *    two: { urlReplacement: ':two' },
   *    three: { querystring: true }
   *   }
   * });
   * endpoint({one: 1, two: 2, three: 3});
   * // Calls http://example.org/1/2?three=3 with a HTTP body of '{"two": 2}'
   * ```
   * **urlReplacement**
   *
   * Can be used to replace template placeholders in the 'url' endpoint spec.
   * If using it alongside `remove: true`, the original key will be removed from the data payload.
   *
   * **querystring**
   *
   * Forces a specific key in the `data` payload to be added to the `url`
   * as a query parameter. This only makes sense for HTTP POST/PUT/DELETE requests,
   * since all `data` payload gets added to the URL automatically for GET requests.
   *
   * @param  {Object} endpointSpec
   * @return {Function} A function taking one argument (a payload object),
   *                    and returns a promise.
   */
  createEndpointFetcher(endpointSpec) {
    /**
     * Encode a payload based on the given contentType
     *
     * @param  {string} contentType
     * @param  {Object} data
     * @return {string}
     */
    function encode(contentType, data) {
      switch (contentType) {
        case 'application/x-www-form-urlencoded':
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

    /**
     * Decode a payload based on the given contentType
     *
     * @param  {string} contentType
     * @param  {string} text
     * @return {Object}
     */
    function decode(contentType, text) {
      switch (contentType) {
        case 'application/x-www-form-urlencoded':
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

    /**
     * Add a querystring to a url
     *
     * @param {string} url
     * @param {string} querystring
     * @return {string}
     */
    function addQuerystring(url, querystring) {
      if (querystring === '') {
        return url;
      }

      if (url.match(/\?/)) {
        return `${url}&${querystring}`;
      }

      return `${url}?${querystring}`;
    }

    /**
     * Parse the response based on the content type returned
     *
     * @param  {Promise} response
     * @return {Promise}
     */
    function parseResponse(response) {
      return response.text().then(
        body => decode(response.headers.get('Content-Type'), body)
      );
    }

    /**
     * Apply the payload schema rules to the passed-in payload,
     * returning the transformed payload.
     *
     * @param  {Object} payloadSchema
     * @param  {Object} data
     * @return {Object}
     */
    function applySchemaToData(payloadSchema, data) {
      return Object.keys(data).reduce((prev, key) => {
        const schema = payloadSchema[key];

        // Remove key if schema requires it.
        // Usually set because the specific payload key
        // is used to populate a url placeholder instead.
        if (schema && (schema.remove === true || schema.querystring === true)) {
          return prev;
        }

        // TODO Support for nested keys
        return Object.assign(prev, { [key]: data[key] });
      }, {});
    }

    /**
     * Applies URL templating and query parameter transformation based on the payloadSchema.
     *
     * @param  {Object} payloadSchema
     * @param  {string} url
     * @param  {Object} data
     * @param  {Object} opts
     * @return {string}               New URL
     */
    function applySchemaToUrl(payloadSchema, url, data, opts = { setFromData: false }) {
      let newUrl = url;

      // Set query parameters
      const queryData = Object.keys(data).reduce((prev, key) => {
        const schema = payloadSchema[key];
        const includeThroughSetFromData = (
          opts.setFromData === true
          && !(schema && schema.remove === true)
        );
        const includeThroughSpec = (
          schema
          && schema.querystring === true
          && schema.remove !== true
        );
        if (includeThroughSetFromData || includeThroughSpec) {
          return Object.assign(prev, { [key]: data[key] });
        }

        return prev;
      }, {});

      newUrl = addQuerystring(
        newUrl,
        encode('application/x-www-form-urlencoded', queryData)
      );

      // Template placeholders
      newUrl = Object.keys(payloadSchema).reduce((prev, key) => {
        const replacement = payloadSchema[key].urlReplacement;
        if (replacement) {
          return prev.replace(replacement, data[key]);
        }

        return prev;
      }, newUrl);

      return newUrl;
    }

    // Parameter defaults
    const refinedSpec = Object.assign({
      method: 'get',
      payloadFormat: 'application/x-www-form-urlencoded',
      responseFormat: 'application/json',
      payloadSchema: {},
      defaultData: {},
    }, endpointSpec);

    // Substitute shorcut format values with their full mime types
    const formatShortcuts = {
      json: 'application/json',
      urlencoded: 'application/x-www-form-urlencoded',
    };
    ['payloadFormat', 'responseFormat'].forEach(
      (key) => {
        if (formatShortcuts[refinedSpec[key]]) refinedSpec[key] = formatShortcuts[refinedSpec[key]];
      }
    );

    return (data = {}) => {
      const headers = {
        Accept: refinedSpec.responseFormat,
        'Content-Type': refinedSpec.payloadFormat,
      };

      const mergedData = merge.recursive({}, refinedSpec.defaultData, data);

      // Replace url placeholders, and add query parameters
      // from the payload based on the schema spec.
      const url = applySchemaToUrl(
        refinedSpec.payloadSchema,
        refinedSpec.url,
        mergedData,
        // Always add full payload data to GET requests.
        // GET requests with a HTTP body are technically legal,
        // but throw an error in the WHATWG fetch() implementation.
        { setFromData: (refinedSpec.method.toLowerCase() === 'get') }
      );

      const encodedData = encode(
        refinedSpec.payloadFormat,
        // Filter raw data through the defined schema,
        // potentially removing keys because they're
        applySchemaToData(refinedSpec.payloadSchema, mergedData)
      );

      const args = refinedSpec.method.toLowerCase() === 'get'
        ? [url, headers]
        : [url, encodedData, headers];

      return this[refinedSpec.method](...args)
        .then(parseResponse);
    };
  }

  /**
   * Makes a network request using the GET HTTP verb.
   *
   * @experimental
   *
   * @param string url - Endpoint URL.
   * @param object data - Data to send with the request.
   * @param Array headers
   * @return object - Promise
   */
  get(url, headers = {}) {
    return this.fetch(url, {
      method: 'get',
      credentials: 'same-origin',
      headers,
    })
      .then(checkStatus);
  }

  /**
   * Makes a network request using the POST HTTP verb.
   *
   * @param string url - Endpoint URL.
   * @param object data - Data to send with the request.
   * @param Array headers
   * @return object - Promise
   */
  post(url, data = {}, headers = {}) {
    const defaultHeaders = { 'Content-Type': 'application/x-www-form-urlencoded' };
    return this.fetch(url, {
      method: 'post',
      headers: Object.assign({}, defaultHeaders, headers),
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
   * @param Array headers
   * @return object - Promise
   */
  put(url, data = {}, headers = {}) {
    return this.fetch(url, { method: 'put', credentials: 'same-origin', body: data, headers })
      .then(checkStatus);
  }

  /**
   * Makes a newtwork request using the DELETE HTTP verb.
   *
   * @param string url - Endpoint URL.
   * @param object data - Data to send with the request.
   * @param Array headers
   * @return object - Promise
   */
  delete(url, data = {}, headers = {}) {
    return this.fetch(url, { method: 'delete', credentials: 'same-origin', body: data, headers })
      .then(checkStatus);
  }

}

// Exported as a singleton so we can implement things like
// global caching and request batching at some stage.
const backend = new SilverStripeBackend();

export default backend;
