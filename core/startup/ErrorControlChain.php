<?php

/**
 * Class ErrorControlChain
 *
 * Runs a set of steps, optionally suppressing uncaught errors or exceptions which would otherwise be fatal that
 * occur in each step. If an error does occur, subsequent steps are normally skipped, but can optionally be run anyway.
 *
 * Usage:
 *
 * $chain = new ErrorControlChain();
 * $chain->then($callback1)->then($callback2)->thenIfErrored($callback3)->execute();
 *
 * WARNING: This class is experimental and designed specifically for use pre-startup in main.php
 * It will likely be heavily refactored before the release of 3.2
 */
class ErrorControlChain {
	public static $fatal_errors = null; // Initialised after class definition

	protected $error = false;
	protected $steps = array();

	protected $suppression = true;

	/** We can't unregister_shutdown_function, so this acts as a flag to enable handling */
	protected $handleFatalErrors = false;

	/** We overload display_errors to hide errors during execution, so we need to remember the original to restore to */
	protected $originalDisplayErrors = null;

	public function hasErrored() {
		return $this->error;
	}

	public function setErrored($error) {
		$this->error = (bool)$error;
	}

	public function setSuppression($suppression) {
		$this->suppression = (bool)$suppression;
		if ($this->handleFatalErrors) ini_set('display_errors', !$suppression);
	}

	/**
	 * Add this callback to the chain of callbacks to call along with the state
	 * that $error must be in this point in the chain for the callback to be called
	 *
	 * @param $callback - The callback to call
	 * @param $onErrorState - false if only call if no errors yet, true if only call if already errors, null for either
	 * @return $this
	 */
	public function then($callback, $onErrorState = false) {
		$this->steps[] = array(
			'callback' => $callback,
			'onErrorState' => $onErrorState
		);
		return $this;
	}

	public function thenWhileGood($callback) {
		return $this->then($callback, false);
	}

	public function thenIfErrored($callback) {
		return $this->then($callback, true);
	}

	public function thenAlways($callback) {
		return $this->then($callback, null);
	}

	protected function lastErrorWasFatal() {
		$error = error_get_last();
		return $error && ($error['type'] & self::$fatal_errors) != 0;
	}

	protected function lastErrorWasMemoryExhaustion() {
		$error = error_get_last();
		$message = $error ? $error['message'] : '';
		return stripos($message, 'memory') !== false && stripos($message, 'exhausted') !== false;
	}

	static $transtable = array(
		'k' => 1024,
		'm' => 1048576,
		'g' => 1073741824
	);

	protected function translateMemstring($memString) {
		$char = strtolower(substr($memString, -1));
		$fact = isset(self::$transtable[$char]) ? self::$transtable[$char] : 1;
		return ((int)$memString) * $fact;
	}

	public function handleFatalError() {
		if ($this->handleFatalErrors && $this->suppression) {
			if ($this->lastErrorWasFatal()) {
				if ($this->lastErrorWasMemoryExhaustion()) {
					// Bump up memory limit by an arbitrary 10% / 10MB (whichever is bigger) since we've run out
					$cur = $this->translateMemstring(ini_get('memory_limit'));
					if ($cur != -1) ini_set('memory_limit', $cur + max(round($cur*0.1), 10000000));
				}

				$this->error = true;
				$this->step();
			}
		}
	}

	public function execute() {
		register_shutdown_function(array($this, 'handleFatalError'));
		$this->handleFatalErrors = true;

		$this->originalDisplayErrors = ini_get('display_errors');
		ini_set('display_errors', !$this->suppression);

		$this->step();
	}

	protected function step() {
		if ($this->steps) {
			$step = array_shift($this->steps);

			if ($step['onErrorState'] === null || $step['onErrorState'] === $this->error) {
				call_user_func($step['callback'], $this);
			}

			$this->step();
		}
		else {
			// Now clean up
			$this->handleFatalErrors = false;
			ini_set('display_errors', $this->originalDisplayErrors);
		}
	}
}

ErrorControlChain::$fatal_errors = E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;
if (defined('E_RECOVERABLE_ERROR')) ErrorControlChain::$fatal_errors |= E_RECOVERABLE_ERROR;
