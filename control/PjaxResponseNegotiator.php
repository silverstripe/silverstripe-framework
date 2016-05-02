<?php
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
 *
 * @package framework
 * @subpackage control
 */
class PjaxResponseNegotiator {

	/**
	 * @var Array See {@link respond()}
	 */
	protected $callbacks = array(
		// TODO Using deprecated functionality, but don't want to duplicate Controller->redirectBack()
		'default' => array('Director', 'redirectBack'),
	);

	protected $response = null;

	/**
	 * Overriden fragments (if any). Otherwise uses fragments from the request.
	 */
	protected $fragmentOverride = null;

	/**
	 * @param RequestHandler $controller
	 * @param SS_HTTPResponse An existing response to reuse (optional)
	 * @param Array $callbacks
	 */
	public function __construct($callbacks = array(), $response = null) {
		$this->callbacks = $callbacks;
		$this->response = $response;
	}

	public function getResponse() {
		if(!$this->response) $this->response = new SS_HTTPResponse();
		return $this->response;
	}

	public function setResponse($response) {
		$this->response = $response;
	}

	/**
	 * Out of the box, the handler "CurrentForm" value, which will return the rendered form.
	 * Non-Ajax calls will redirect back.
	 *
	 * @param SS_HTTPRequest $request
	 * @param array $extraCallbacks List of anonymous functions or callables returning either a string
	 * or SS_HTTPResponse, keyed by their fragment identifier. The 'default' key can
	 * be used as a fallback for non-ajax responses.
	 * @param array $fragmentOverride Change the response fragments.
	 * @return SS_HTTPResponse
	 */
	public function respond(SS_HTTPRequest $request, $extraCallbacks = array()) {
		// Prepare the default options and combine with the others
		$callbacks = array_merge($this->callbacks, $extraCallbacks);
		$response = $this->getResponse();

		$responseParts = array();

		if (isset($this->fragmentOverride)) {
			$fragments = $this->fragmentOverride;
		} elseif ($fragmentStr = $request->getHeader('X-Pjax')) {
			$fragments = explode(',', $fragmentStr);
		} else {
			if($request->isAjax()) {
				throw new SS_HTTPResponse_Exception("Ajax requests to this URL require an X-Pjax header.", 400);
			}
			$response->setBody(call_user_func($callbacks['default']));
			return $response;
		}

		// Execute the fragment callbacks and build the response.
		foreach($fragments as $fragment) {
			if(isset($callbacks[$fragment])) {
				$res = call_user_func($callbacks[$fragment]);
				$responseParts[$fragment] = $res ? (string) $res : $res;
			} else {
				throw new SS_HTTPResponse_Exception("X-Pjax = '$fragment' not supported for this URL.", 400);
			}
		}
		$response->setBody(Convert::raw2json($responseParts));
		$response->addHeader('Content-Type', 'text/json');

		return $response;
	}

	/**
	 * @param String   $fragment
	 * @param Callable $callback
	 */
	public function setCallback($fragment, $callback) {
		$this->callbacks[$fragment] = $callback;
	}

	/**
	 * Set up fragment overriding - will completely replace the incoming fragments.
	 *
	 * @param array $fragments Fragments to insert.
	 */
	public function setFragmentOverride($fragments) {
		if (!is_array($fragments)) throw new InvalidArgumentException();

		$this->fragmentOverride = $fragments;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getFragmentOverride() {
		return $this->fragmentOverride;
	}
}
