import $ from 'jQuery';

class SilverStripeBackend {

    /**
     * Makes a network request using the GET HTTP verb.
     *
     * @param string url - Endpoint URL.
     *
     * @return object - jqXHR. See http://api.jquery.com/Types/#jqXHR
     */
    get(url) {
        return $.ajax({ type: 'GET', url });
    }

    /**
     * Makes a network request using the POST HTTP verb.
     *
     * @param string url - Endpoint URL.
     * @param object data - Data to send with the request.
     *
     * @return object - jqXHR. See http://api.jquery.com/Types/#jqXHR
     */
    post(url, data) {
        return $.ajax({ type: 'POST', url, data });
    }

    /**
     * Makes a newtwork request using the PUT HTTP verb.
     *
     * @param string url - Endpoint URL.
     * @param object data - Data to send with the request.
     *
     * @return object - jqXHR. See http://api.jquery.com/Types/#jqXHR
     */
    put(url, data) {
        return $.ajax({ type: 'PUT', url, data });
    }

    /**
     * Makes a newtwork request using the DELETE HTTP verb.
     *
     * @param string url - Endpoint URL.
     * @param object data - Data to send with the request.
     *
     * @return object - jqXHR. See http://api.jquery.com/Types/#jqXHR
     */
    delete(url, data) {
        return $.ajax({ type: 'DELETE', url, data });
    }

}

// Exported as a singleton so we can implement things like
// global caching and request batching at some stage.
let backend = new SilverStripeBackend();

export default backend;
