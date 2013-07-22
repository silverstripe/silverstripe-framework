<?php

/**
 * Class ErrorControlChain
 *
 * Runs a set of steps, optionally suppressing (but recording) any errors (even fatal ones) that occur in each step.
 * If an error does occur, subsequent steps are normally skipped, but can optionally be run anyway
 *
 * Normal errors are suppressed even past the end of the chain. Fatal errors are only suppressed until the end
 * of the chain - the request will then die silently.
 *
 * Usage:
 *
 * $chain = new ErrorControlChain();
 * $chain->then($callback1)->then($callback2)->then(true, $callback3)->execute();
 *
 * WARNING: This class is experimental and designed specifically for use pre-startup in main.php
 * It will likely be heavily refactored before the release of 3.2
 */
class ErrorControlChain {
	protected $error = false;
	protected $steps = array();

	protected $suppression = true;

	/** We can't unregister_shutdown_function, so this acts as a flag to enable handling */
	protected $handleFatalErrors = false;

	public function hasErrored() {
		return $this->error;
	}

	public function setErrored($error) {
		$this->error = (bool)$error;
	}

	public function setSuppression($suppression) {
		$this->suppression = (bool)$suppression;
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

	public function handleError($errno, $errstr) {
		if ((error_reporting() & $errno) == $errno && $this->suppression) throw new Exception('Generic Error');
		else return false;
	}

	protected function lastErrorWasFatal() {
		$error = error_get_last();
		return $error && $error['type'] == 1;
	}

	public function handleFatalError() {
		if ($this->handleFatalErrors && $this->suppression) {
			if ($this->lastErrorWasFatal()) {
				ob_clean();
				$this->error = true;
				$this->step();
			}
		}
	}

	public function execute() {
		set_error_handler(array($this, 'handleError'));
		register_shutdown_function(array($this, 'handleFatalError'));
		$this->handleFatalErrors = true;

		$this->step();
	}

	protected function step() {
		if ($this->steps) {
			$step = array_shift($this->steps);

			if ($step['onErrorState'] === null || $step['onErrorState'] === $this->error) {
				try {
					call_user_func($step['callback'], $this);
				}
				catch (Exception $e) {
					if ($this->suppression) $this->error = true;
					else throw $e;
				}
			}

			$this->step();
		}
		else {
			// Now clean up
			$this->handleFatalErrors = false;
			restore_error_handler();
		}
	}
}
