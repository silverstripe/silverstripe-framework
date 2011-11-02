<?php
/**
 * Soap server class which auto-generates a WSDL
 * file to initialize PHPs integrated {@link SoapServer} class.
 * 
 * See {@link SOAPModelAccess} for an auto-generated SOAP API for your models.
 * 
 * @todo Improve documentation
 * @package sapphire
 * @subpackage integration
 */
class SapphireSoapServer extends Controller {
	
	/**
	 * @var array Map of method name to arguments.
	 */
	static $methods = array();
	
	/**
	 * @var array
	 */
	static $xsd_types = array(
		'int' => 'xsd:int',
		'boolean' => 'xsd:boolean',
		'string' => 'xsd:string',
		'binary' => 'xsd:base64Binary',
	);
	
	function wsdl() {
		$this->getResponse()->addHeader("Content-Type", "text/xml"); 
		
		return array();
	}
	
	/**
	 * @return string
	 */
	function getWSDLURL() {
		return Director::absoluteBaseURLWithAuth() . $this->Link() . "wsdl";
	}
	
	/**
	 * @return DataObjectSet Collection of ArrayData elements describing
	 *  the method (keys: 'Name', 'Arguments', 'ReturnType')
	 */
	function Methods() {
		$methods = array();
		
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
	
	/**
	 * @return string
	 */
	function TargetNamespace() {
		return Director::absoluteBaseURL();
	}
	
	/**
	 * @return string
	 */
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
