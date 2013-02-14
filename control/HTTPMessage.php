<?php
/**
 * A base class for HTTP requests and responses.

 * @package framework
 * @subpackage control
 */
abstract class SS_HTTPMessage {

	/**
	 * @var string
	 */
	private $body;

	/**
	 * @var array
	 */
	private $headers = array();

	/**
	 * Gets the message body.
	 *
	 * @return string
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * Sets the message body.
	 *
	 * @param $body
	 * @return $this
	 */
	public function setBody($body) {
		$this->body = $body ? (string) $body : $body; // Don't type-cast false-ish values
		return $this;
	}

	/**
	 * Gets a map of all message headers to their values.
	 *
	 * @return array
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * Sets several headers on this message.
	 *
	 * @param array $headers a map of headers to their values
	 * @return $this
	 */
	public function setHeaders(array $headers) {
		foreach($headers as $k => $v) {
			$this->setHeader($k, $v);
		}

		return $this;
	}

	/**
	 * Gets a header by name.
	 *
	 * @param string $name the header name
	 * @return string
	 */
	public function getHeader($name) {
		$name = strtolower($name);

		if(isset($this->headers[$name])) {
			return $this->headers[$name];
		}
	}

	/**
	 * Sets a header by name.
	 *
	 * @param string $name
	 * @param string $value
	 * @return $this
	 */
	public function setHeader($name, $value) {
		$this->headers[strtolower($name)] = $value;
		return $this;
	}

	/**
	 * Unsets a header by name.
	 *
	 * @param string $name
	 * @return $this
	 */
	public function unsetHeader($name) {
		$name = strtolower($name);

		if(isset($this->headers[$name])) {
			unset($this->headers[$name]);
		}

		return $this;
	}

	/**
	 * @deprecated Use {@link setHeader()}.
	 */
	public function addHeader($name, $value) {
		Deprecation::notice('3.2');
		return $this->setHeader($name, $value);
	}

	/**
	 * @deprecated Use {@link unsetHeader()}.
	 */
	public function removeHeader($name) {
		Deprecation::notice('3.2');
		return $this->unsetHeader($name);
	}

}
