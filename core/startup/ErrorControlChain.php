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
 *
 * @package framework
 * @subpackage misc
 */
class ErrorControlChain {
	public static $fatal_errors = null; // Initialised after class definition

	/**
	 * Is there an error?
	 *
	 * @var bool
	 */
	protected $error = false;

	/**
	 * List of steps
	 *
	 * @var array
	 */
	protected $steps = array();

	/**
	 * True if errors should be hidden
	 *
	 * @var bool
	 */
	protected $suppression = true;

	/** We can't unregister_shutdown_function, so this acts as a flag to enable handling */
	protected $handleFatalErrors = false;

	/** We overload display_errors to hide errors during execution, so we need to remember the original to restore to */
	protected $originalDisplayErrors = null;

	/**
	 * Any exceptions passed through the chain
	 *
	 * @var Exception
	 */
	protected $lastException = null;

	/**
	 * Determine if an error has been found
	 *
	 * @return bool
	 */
	public function hasErrored() {
		return $this->error;
	}

	public function setErrored($error) {
		$this->error = (bool)$error;
	}

	/**
	 * Sets whether errors are suppressed or not
	 * Notes:
	 * - Errors cannot be suppressed if not handling errors.
	 * - Errors cannot be un-suppressed if original mode dis-allowed visible errors
	 *
	 * @param bool $suppression
	 */
	public function setSuppression($suppression) {
		$this->suppression = (bool)$suppression;
		// If handling fatal errors, conditionally disable, or restore error display
		// Note: original value of display_errors could also evaluate to "off"
		if ($this->handleFatalErrors) {
			if($suppression) {
				$this->setDisplayErrors(0);
			} else {
				$this->setDisplayErrors($this->originalDisplayErrors);
			}
		}
	}

	/**
	 * Set display_errors
	 *
	 * @param mixed $errors
	 */
	protected function setDisplayErrors($errors) {
		ini_set('display_errors', $errors);
	}

	/**
	 * Get value of display_errors ini value
	 *
	 * @return mixed
	 */
	protected function getDisplayErrors() {
		return ini_get('display_errors');
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

	/**
	 * Request that the callback is invoked if not errored
	 *
	 * @param callable $callback
	 * @return $this
	 */
	public function thenWhileGood($callback) {
		return $this->then($callback, false);
	}

	/**
	 * Request that the callback is invoked on error
	 *
	 * @param callable $callback
	 * @return $this
	 */
	public function thenIfErrored($callback) {
		return $this->then($callback, true);
	}

	/**
	 * Request that the callback is invoked always
	 *
	 * @param callable $callback
	 * @return $this
	 */
	public function thenAlways($callback) {
		return $this->then($callback, null);
	}

	/**
	 * Return true if the last error was fatal
	 *
	 * @return boolean
	 */
	protected function lastErrorWasFatal() {
		if($this->lastException) return true;
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

		$this->originalDisplayErrors = $this->getDisplayErrors();
		$this->setSuppression($this->suppression);

		$this->step();
	}

	protected function step() {
		if ($this->steps) {
			$step = array_shift($this->steps);

			if ($step['onErrorState'] === null || $step['onErrorState'] === $this->error) {
				try {
					call_user_func($step['callback'], $this);
				} catch (Exception $ex) {
					$this->lastException = $ex;
					throw $ex;
				}
			}

			$this->step();
		}
		else {
			// Now clean up
			$this->handleFatalErrors = false;
			$this->setDisplayErrors($this->originalDisplayErrors);
		}
	}
}

ErrorControlChain::$fatal_errors = E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;
if (defined('E_RECOVERABLE_ERROR')) ErrorControlChain::$fatal_errors |= E_RECOVERABLE_ERROR;
