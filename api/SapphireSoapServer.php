<?php

/**
 * @package sapphire
 * @subpackage integration
 */

/**
 * Soap server class
 * @package sapphire
 * @subpackage integration
 */
class SapphireSoapServer extends Controller {
	static $methods = array();
	static $xsd_types = array(
		'int' => 'xsd:int',
		'string' => 'xsd:string',
		'binary' => 'xsd:base64Binary',
	);
	
	function wsdl() {
		ContentNegotiator::disable();
		header("Content-type: text/xml");
		return array();
	}
	
	function getWSDLURL() {
		return Director::absoluteBaseURLWithAuth() . $this->class . "/wsdl";
	}
	
	function Methods() {
		foreach($this->stat('methods') as $methodName => $arguments) {
			$returnType = $arguments['_returns'];
			unset($arguments['_returns']);
			
			$processedArguments = array();
			foreach($arguments as $argument => $type) {
				$processedArguments[] = new ArrayData(array(
					"Name" => $argument,
					"Type" => self::$xsd_types[$type],
				));
				
			}
			$methods[] = new ArrayData(array(
				"Name" => $methodName,
				"Arguments" => new DataObjectSet($processedArguments),
				"ReturnType" => self::$xsd_types[$returnType],
			));
		}
		
		return new DataObjectSet($methods);
	}
	function TargetNamespace() {
		return Director::absoluteBaseURL();
	}
	function ServiceURL() {
		return Director::absoluteBaseURLWithAuth() . $this->class . '/';
	}
	
  function index() {
    $s = new SoapServer($this->getWSDLURL());
    $s->setClass($this->class);
    $s->handle();
  }
}

?>