import fetch from 'isomorphic-fetch';
import es6promise from 'es6-promise';
es6promise.polyfill();

/**
 * @see https://github.com/github/fetch#handling-http-error-statuses
 */
function checkStatus(response) {
  if (response.status >= 200 && response.status < 300) {
    return response
  } else {
    var error = new Error(response.statusText)
    error.response = response
    throw error
  }
}

class SilverStripeBackend {

    constructor() {
        // Allow mocking
        this.fetch = fetch;
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
        return this.fetch(url, { method: 'post', credentials: 'same-origin', body: data })
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
let backend = new SilverStripeBackend();

export default backend;
