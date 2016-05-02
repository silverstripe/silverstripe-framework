<?php

/**
 * A class that proxies another, allowing various functionality to be
 * injected.
 *
 * @package framework
 * @subpackage injector
 */
class AopProxyService {
	public $beforeCall = array();

	public $afterCall = array();

	public $proxied;

	/**
	 * Because we don't know exactly how the proxied class is usually called,
	 * provide a default constructor
	 */
	public function __construct() {

	}

	public function __call($method, $args) {
		if (method_exists($this->proxied, $method)) {
			$continue = true;
			$result = null;

			if (isset($this->beforeCall[$method])) {
				$methods = $this->beforeCall[$method];
				if (!is_array($methods)) {
					$methods = array($methods);
				}
				foreach ($methods as $handler) {
					$alternateReturn = null;
					$proceed = $handler->beforeCall($this->proxied, $method, $args, $alternateReturn);
					if ($proceed === false) {
						$continue = false;
						// if something is set in, use it
						if ($alternateReturn) {
							$result = $alternateReturn;
						}
					}
				}
			}

			if ($continue) {
				$result = call_user_func_array(array($this->proxied, $method), $args);

				if (isset($this->afterCall[$method])) {
					$methods = $this->afterCall[$method];
					if (!is_array($methods)) {
						$methods = array($methods);
					}
					foreach ($methods as $handler) {
						$return = $handler->afterCall($this->proxied, $method, $args, $result);
						if (!is_null($return)) {
							$result = $return;
						}
					}
				}
			}

			return $result;
		}
	}
}
