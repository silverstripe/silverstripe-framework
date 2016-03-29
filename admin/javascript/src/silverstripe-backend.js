import fetch from 'isomorphic-fetch';

class SilverStripeBackend {

    /**
     * Makes a network request using the GET HTTP verb.
     *
     * @param string url - Endpoint URL.
     * @return object - Promise
     */
    get(url) {
        return fetch(url, { method: 'get', credentials: 'same-origin' });
    }

    /**
     * Makes a network request using the POST HTTP verb.
     *
     * @param string url - Endpoint URL.
     * @param object data - Data to send with the request.
     * @return object - Promise
     */
    post(url, data) {
        return fetch(url, { method: 'post', credentials: 'same-origin', body: data });
    }

    /**
     * Makes a newtwork request using the PUT HTTP verb.
     *
     * @param string url - Endpoint URL.
     * @param object data - Data to send with the request.
     * @return object - Promise
     */
    put(url, data) {
        return fetch(url, { method: 'put', credentials: 'same-origin', body: data });
    }

    /**
     * Makes a newtwork request using the DELETE HTTP verb.
     *
     * @param string url - Endpoint URL.
     * @param object data - Data to send with the request.
     * @return object - Promise
     */
    delete(url, data) {
        return fetch(url, { method: 'delete', credentials: 'same-origin', body: data });
    }

}

// Exported as a singleton so we can implement things like
// global caching and request batching at some stage.
let backend = new SilverStripeBackend();

export default backend;
