<?php

class ParameterConfirmationTokenTest_Token extends ParameterConfirmationToken {

	public function currentAbsoluteURL() {
		return parent::currentAbsoluteURL();
	}

}

class ParameterConfirmationTokenTest extends SapphireTest {

	private function addPart($answer, $slash, $part) {
		$bare = str_replace('/', '', $part);

		if ($bare) $answer = array_merge($answer, array($bare));
		if ($part) $slash = (substr($part, -1) == '/') ? '/' : '';

		return array($answer, $slash);
	}

	/**
	 * currentAbsoluteURL needs to handle base or url being missing, or any combination of slashes.
	 * 
	 * There should always be exactly one slash between each part in the result, and any trailing slash
	 * should be preserved.
	 */
	function testCurrentAbsoluteURLHandlesSlashes() {
		global $url;

		$token = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_parameter');

		foreach(array('foo','foo/') as $host) {
			list($hostAnswer, $hostSlash) = $this->addPart(array(), '', $host);

			foreach(array('', '/', 'bar', 'bar/', '/bar', '/bar/') as $base) {
				list($baseAnswer, $baseSlash) = $this->addPart($hostAnswer, $hostSlash, $base);

				foreach(array('', '/', 'baz', 'baz/', '/baz', '/baz/') as $url) {
					list($urlAnswer, $urlSlash) = $this->addPart($baseAnswer, $baseSlash, $url);

					$_SERVER['HTTP_HOST'] = $host;
					ParameterConfirmationToken::$alternateBaseURL = $base;

					$this->assertEquals('http://'.implode('/', $urlAnswer) . $urlSlash, $token->currentAbsoluteURL());
				}
			}
		}
	}

}