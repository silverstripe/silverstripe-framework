<?php

/**
 * @package sapphire
 * @subpackage integration
 */

/**
 * RestfulService class allows you to consume various RESTful APIs.
 * Through this you could connect and aggregate data of various web services.
 */
class RestfulService extends ViewableData {
	protected $baseURL;
	protected $queryString;
	protected $rawXML;
	protected $errorTag;
	protected $checkErrors;
	protected $cache_expire;
	
	function __construct($base, $expiry=3600){
		$this->baseURL = $base;
		$this->cache_expire = $expiry;
	}
	
	/**
 	* Sets the Query string parameters to send a request.
 	* @param params An array passed with necessary parameters. 
 	*/
	function setQueryString($params=NULL){
		$this->queryString = http_build_query($params,'','&');
	}
	
	protected function constructURL(){
		return "$this->baseURL?$this->queryString";
	}
	
	/**
 	* Connects to the RESTful service and gets its response.
 	* TODO implement authentication via cURL for
 	*/
	
	function connect(){
		$url = $this->constructURL(); // url for the request
		
		// check for file exists in cache		
		// set the cache directory
		$cachedir = TEMP_FOLDER; // default silverstripe-cache
			
		$cache_file = md5($url); // encoded name of cache file
		$cache_path = $cachedir . "/$cache_file";
				
		if((@file_exists("$cache_path") && ((@filemtime($cache_path) + $this->cache_expire) > (time())))){
			$this->rawXML = file_get_contents($cache_path);
		}	else {
			// not available in cache fetch from server
			$ch = curl_init();
			$timeout = 5;
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$this->rawXML = curl_exec($ch);
			curl_close($ch);
			
		}
		
		// Try using file_get_contents if cURL is not installed in your system.
		// $this->rawXML = file_get_contents($url);
		
		// results returned - from cache / live
		if($this->rawXML != ""){
			// save the response in cache
			$fp = @fopen($cache_path,"w+");
			@fwrite($fp,$this->rawXML);
			@fclose($fp);
			
			if($this->checkErrors == true) {
				return $this->errorCatch($this->rawXML);
			} else {
				return $this->rawXML;
			}
		} else {
			user_error("Invalid Response (maybe your calling to wrong URL or server unavailable)", E_USER_ERROR);
		}
	}
	
	/**
 	* Gets attributes as an array, of a particular type of element.
 	* @params xml - the source xml to parse, this could be the original response received.
 	* @params collection - parent node which wraps the elements, if available
 	* @params element - element we need to extract the attributes.
 	* Example : <photo id="2636" owner="123" secret="ab128" server="2"> 
 	* returns id, owner,secret and sever attribute values of all such photo elements.
 	*/
	
	function getAttributes($xml, $collection=NULL, $element=NULL){
		$xml = new SimpleXMLElement($xml);
		$output = new DataObjectSet();
		
		if($collection)
			$childElements = $xml->{$collection};
		if($element)
			$childElements = $xml->{$collection}->{$element};
		
		if($childElements){
		foreach($childElements as $child){
		$data = array();
			foreach($child->attributes() as $key => $value){
				$data["$key"] = Convert::raw2xml($value);
			}
			
			$output->push(new ArrayData($data));
		}
		}
		return $output;
		
	}
	
	/**
 	* Gets an attribute of a particular element.
 	* @params xml - the source xml to parse, this could be the original response received.
 	* @params collection - parent node which wraps the element, if available
 	* @params element - element we need to extract the attribute
 	* @params attr - name of the attribute
 	*/
	
	function getAttribute($xml, $collection=NULL, $element=NULL, $attr){
	$xml = new SimpleXMLElement($xml);
	$attr_value = "";
	
	if($collection)
			$childElements = $xml->{$collection};
		if($element)
			$childElements = $xml->{$collection}->{$element};
		
		if($childElements)
			$attr_value = (string) $childElements[$attr];
		
		return Convert::raw2xml($attr_value);
		
	}
	
	/**
 	* Gets set of node values as an array. 
 	* When you get to the depth in the hierachchy use node_child_subchild syntax to get the value.
 	* @params xml - the source xml to parse, this could be the original response received.
 	* @params collection - parent node which wraps the elements, if available
 	* @params element - element we need to extract the node values.
 	*/
	
	function getValues($xml, $collection=NULL, $element=NULL){
		$xml = new SimpleXMLElement($xml);
		$output = new DataObjectSet();
		
			$childElements = $xml;
		if($collection)
			$childElements = $xml->{$collection};
		if($element)
			$childElements = $xml->{$collection}->{$element};
		
		if($childElements){
		foreach($childElements as $child){
			$data = array();
			$this->getRecurseValues($child,$data);			
			$output->push(new ArrayData($data));
		}
		}
		return $output;
	}
	
	protected function getRecurseValues($xml,&$data,$parent=""){
		$child_count = 0;
		foreach($xml as $key=>$value)
		{
			$child_count++;    
			$k = ($parent == "") ? (string)$key : $parent . "_" . (string)$key;
			if($this->getRecurseValues($value,$data,$k) == 0)  // no childern, aka "leaf node"
				$data[$k] = Convert::raw2xml($value);  
		}
		return $child_count;
			
	}
	
	/**
 	* Gets a single node value. 
 	* @params xml - the source xml to parse, this could be the original response received.
 	* @params collection - parent node which wraps the elements, if available
 	* @params element - element we need to extract the node value.
 	*/
	
	function getValue($xml, $collection=NULL, $element=NULL){
		$xml = new SimpleXMLElement($xml);
		
		if($collection)
			$childElements = $xml->{$collection};
		if($element)
			$childElements = $xml->{$collection}->{$element};
		
		if($childElements)
			return Convert::raw2xml($childElements);
	}
	
	function searchValue($xml, $node=NULL){
		$xml = new SimpleXMLElement($xml);
		$childElements = $xml->xpath($node);
		
		if($childElements)
			return Convert::raw2xml($childElements[0]);
	}
	
	function searchAttributes($xml, $node=NULL){
		$xml = new SimpleXMLElement($xml);
		$output = new DataObjectSet();
	
		$childElements = $xml->xpath($node);
		
		if($childElements)
		foreach($childElements as $child){
		$data = array();
			foreach($child->attributes() as $key => $value){
				$data["$key"] = Convert::raw2xml($value);
			}
			
			$output->push(new ArrayData($data));
		}
			
			//Debug::show($attr_value);
		
		return $output;
	}
	
	
}
?>
