<?php

namespace SilverStripe\Framework\Control;

use ClassInfo;
use SS_HTTPRequest;

/**
 * Matches URL patterns to controllers.
 *
 * Each pattern can consist of a number of parts separated by slashes:
 *   - Literals must be provided in order to match.
 *   - Optional variables begin with $ (e.g. "$Variable").
 *   - Variables can be required by appending a ! (e.g. "$Variable!").
 *   - Rules can start with a required HTTP method to match (e.g. "POST foo").
 */
class Router {

	private $rules = array();

	private $request;

	/**
	 * @return Router
	 */
	public static function create() {
		return new static();
	}

	/**
	 * @return array
	 */
	public function getRules() {
		return $this->rules;
	}

	/**
	 * @param array $rules
	 * @return $this
	 */
	public function setRules(array $rules) {
		$this->rules = $rules;
		return $this;
	}

	/**
	 * @return SS_HTTPRequest
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * @param SS_HTTPRequest $request
	 * @return $this
	 */
	public function setRequest(SS_HTTPRequest $request) {
		$this->request = $request;
		return $this;
	}

	/**
	 * Routes a request against a set of rules, and returns the corresponding rule's value. The
	 * request is updated with any extracted parameters.
	 *
	 * @param SS_HTTPRequest $request the request to route, updated with parameters
	 * @param array $rules rules to consider
	 * @return mixed
	 */
	public function route(SS_HTTPRequest $request = null, array $rules = null) {
		$request = $request ?: $this->getRequest();
		$rules = $rules ?: $this->getRules();
		$urlParts = $request->getUrlParts();

		foreach ($rules as $rule => $val) {
			// Check if a specific request method is required.
			if (preg_match('/^([A-Z]+)\s+(.*)$/i', $rule, $matches)) {
				$method = $matches[1];
				$rule = $matches[2];

				if (strtoupper($method) != $request->getMethod()) {
					continue;
				}
			}

			// Special case for the root controller.
			if (!$rule) {
				if (!$urlParts) {
					return $val;
				} else {
					continue;
				}
			}

			// Extract the '//' marker which denotes the shifting point.
			if (($pos = strpos($rule, '//')) !== false) {
				$shift = substr_count(substr($rule, 0, $pos), '/') + 1;
				$parts = explode('/', str_replace('//', '/', $rule));
			} else {
				$parts = explode('/', $rule);
				$shift = count($parts);
			}

			// Loop through each rule part to check for a match.
			$matches = true;
			$params = array();
			$parts = array_map('trim', $parts);

			foreach ($parts as $i => $part) {
				// Match a variable beginning with $, and ending with ! if it
				// is required.
				if ($part[0] == '$') {
					if (substr($part, -1) == '!') {
						$required = true;
						$name = substr($part, 1, -1);
					} else {
						$required = false;
						$name = substr($part, 1);
					}

					if ($required && !isset($urlParts[$i])) {
						continue 2;
					}

					$params[$name] = isset($urlParts[$i]) ? $urlParts[$i] : null;

					if ($part == '$Controller') {
						$controller = $params['Controller'];

						if (!ClassInfo::exists($controller) || !is_subclass_of($controller, 'Controller')) {
							continue 2;
						}
					}
					// Match a literal part.
				} elseif (!isset($urlParts[$i]) || $urlParts[$i] != $part) {
					continue 2;
				}
			}

			$request->shift($shift);
			$request->pushParams($params);
			$request->setUnshiftedButParsed(count($parts) - $shift);

			if (is_array($val)) {
				$request->setRouteParams($val);
			}

			return $val;
		}

		return false;
	}

}
