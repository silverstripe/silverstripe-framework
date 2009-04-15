<?php
/**
 * Soap server class
 * @todo Improve documentation
 * @package sapphire
 * @subpackage integration
 */
class SapphireSoapServer extends Controller {
	static $methods = array();
	static $xsd_types = array(
		'int' => 'xsd:int',
		'boolean' => 'xsd:boolean',
		'string' => 'xsd:string',
		'binary' => 'xsd:base64Binary',
	);
	
	function wsdl() {
		ContentNegotiator::disable();
		header("Content-type: text/xml");
		return array();
	}
	
	function getWSDLURL() {
		return Director::absoluteBaseURLWithAuth() . $this->Link() . "wsdl";
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
	$wsdl = $this->getViewer('wsdl')->process($this);
	$wsdlFile = TEMP_FOLDER . '/sapphire-wsdl-' . $this->class;
	$fh = fopen($wsdlFile, 'w');
	fwrite($fh, $wsdl);
	fclose($fh);

	$s = new SoapServer($wsdlFile, array('cache_wsdl' => WSDL_CACHE_NONE));
	$s->setClass($this->class);
	$s->handle();
  }
}

?>
