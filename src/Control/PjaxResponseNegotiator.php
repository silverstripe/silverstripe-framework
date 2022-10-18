<?php

namespace SilverStripe\Control;

use SilverStripe\Core\Convert;
use InvalidArgumentException;

/**
 * Handle the X-Pjax header that AJAX responses may provide, returning the
 * fragment, or, in the case of non-AJAX form submissions, redirecting back
 * to the submitter.
 *
 * X-Pjax ensures that users won't end up seeing the unstyled form HTML in
 * their browser.
 *
 * If a JS error prevents the Ajax overriding of form submissions from happening.
 *
 * It also provides better non-JS operation.
 *
 * Caution: This API is volatile, and might eventually be replaced by a generic
 * action helper system for controllers.
 */
class PjaxResponseNegotiator
{

    /**
     * See {@link respond()}
     *
     * @var array
     */
    protected $callbacks = [];

    protected HTTPResponse $response;

    /**
     * Overridden fragments (if any). Otherwise uses fragments from the request.
     */
    protected $fragmentOverride = null;

    /**
     * @param array $callbacks
     * @param HTTPResponse $response An existing response to reuse (optional)
     */
    public function __construct($callbacks = [], HTTPResponse $response = null)
    {
        $this->callbacks = $callbacks;
        $this->response = $response ?: HTTPResponse::create();
    }

    public function getResponse(): HTTPResponse
    {
        return $this->response;
    }

    public function setResponse(HTTPResponse $response)
    {
        $this->response = $response;
    }

    /**
     * Out of the box, the handler "CurrentForm" value, which will return the rendered form.
     * Non-Ajax calls will redirect back.
     *
     * @param HTTPRequest $request
     * @param array $extraCallbacks List of anonymous functions or callables returning either a string
     * or HTTPResponse, keyed by their fragment identifier. The 'default' key can
     * be used as a fallback for non-ajax responses.
     * @throws HTTPResponse_Exception
     */
    public function respond(HTTPRequest $request, $extraCallbacks = []): HTTPResponse
    {
        // Prepare the default options and combine with the others
        $callbacks = array_merge($this->callbacks, $extraCallbacks);
        $response = $this->getResponse();

        $responseParts = [];

        if (isset($this->fragmentOverride)) {
            $fragments = $this->fragmentOverride;
        } elseif ($fragmentStr = $request->getHeader('X-Pjax')) {
            $fragments = explode(',', $fragmentStr ?? '');
        } else {
            if ($request->isAjax()) {
                throw new HTTPResponse_Exception("Ajax requests to this URL require an X-Pjax header.", 400);
            } elseif (empty($callbacks['default'])) {
                throw new HTTPResponse_Exception("Missing default response handler for this URL", 400);
            }
            $response->setBody(call_user_func($callbacks['default']));
            return $response;
        }

        // Execute the fragment callbacks and build the response.
        foreach ($fragments as $fragment) {
            if (isset($callbacks[$fragment])) {
                $res = call_user_func($callbacks[$fragment]);
                $responseParts[$fragment] = $res ? (string) $res : $res;
            } else {
                throw new HTTPResponse_Exception("X-Pjax = '$fragment' not supported for this URL.", 400);
            }
        }
        $response->setBody(json_encode($responseParts));
        $response->addHeader('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @param string   $fragment
     * @param Callable $callback
     */
    public function setCallback($fragment, $callback)
    {
        $this->callbacks[$fragment] = $callback;
    }

    /**
     * Set up fragment overriding - will completely replace the incoming fragments.
     *
     * @param array $fragments Fragments to insert.
     * @return $this
     */
    public function setFragmentOverride($fragments)
    {
        if (!is_array($fragments)) {
            throw new InvalidArgumentException("fragments must be an array");
        }

        $this->fragmentOverride = $fragments;
        return $this;
    }

    /**
     * @return array
     */
    public function getFragmentOverride()
    {
        return $this->fragmentOverride;
    }
}
