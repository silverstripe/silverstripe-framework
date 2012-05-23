<?php
/**
 * Handle the X-Pjax header that AJAX responses may provide, returning the 
 * fragment, or, in the case of non-AJAX form submissions, redirecting back to the submitter.
 *
 * X-Pjax ensures that users won't end up seeing the unstyled form HTML in their browser
 * If a JS error prevents the Ajax overriding of form submissions from happening. 
 * It also provides better non-JS operation.
 * 
 * Caution: This API is volatile, and might eventually be replaced by a generic
 * action helper system for controllers.
 */
class PjaxResponseNegotiator {

	/**
	 * Holds the overriden type.
	 */
	protected $pjaxTypeOverride = null;

	/**
	 * @var Array See {@link respond()}
	 */
	protected $callbacks = array(
		// TODO Using deprecated functionality, but don't want to duplicate Controller->redirectBack()
		'default' => array('Director', 'redirectBack'),
	);

	/**
	 * @param RequestHandler $controller
	 * @param Array $callbacks
	 */
	function __construct($callbacks = array()) {
		$this->callbacks = $callbacks; 
	}

	/**
	 * Out of the box, the handler "CurrentForm" value, which will return the rendered form.  
	 * Non-Ajax calls will redirect back.
	 * 
	 * @param SS_HTTPRequest $request 
	 * @param array $extraCallbacks List of anonymous functions or callables returning either a string
	 * or SS_HTTPResponse, keyed by their fragment identifier. The 'default' key can
	 * be used as a fallback for non-ajax responses.
	 * @return SS_HTTPResponse
	 */
	public function respond(SS_HTTPRequest $request, $extraCallbacks = array()) {
		// Prepare the default options and combine with the others
		$callbacks = array_merge(
			array_change_key_case($this->callbacks, CASE_LOWER),
			array_change_key_case($extraCallbacks, CASE_LOWER)
		);

		// Get the PJAX type for this request (might have been overriden).
		$fragment = $this->pjaxTypeOverride ? $this->pjaxTypeOverride : $request->getHeader('X-Pjax');
		
		if($fragment) {
			$fragment = strtolower($fragment);
			if(isset($callbacks[$fragment])) {
				return call_user_func($callbacks[$fragment]);
			} else {
				throw new SS_HTTPResponse_Exception("X-Pjax = '$fragment' not supported for this URL.", 400);
			}
		} else {
			if($request->isAjax()) throw new SS_HTTPResponse_Exception("Ajax requests to this URL require an X-Pjax header.", 400);
			return call_user_func($callbacks['default']);
		}
		
	}

	/**
	 * Overrides the Pjax request type.
	 *
	 * @param $type string Overriding type.
	 */
	public function forcePjaxType($type) {
		$this->pjaxTypeOverride = $type;
		return $this;
	}

	/**
	 * @param String   $fragment
	 * @param Callable $callback
	 */
	public function setCallback($fragment, $callback) {
		$this->callbacks[$fragment] = $callback;
	}
}
