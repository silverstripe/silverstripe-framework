<?php
/**
 * Accepts form encoded strings and converts them
 * to a valid PHP array via {@link parse_str()}.
 *
 * Example when using cURL on commandline:
 * <code>
 * curl -d "Name=This is a new record" http://host/api/v1/(DataObject)
 * curl -X PUT -d "Name=This is an updated record" http://host/api/v1/(DataObject)/1
 * </code>
 * 
 * @todo Format response form encoded as well - currently uses XMLDataFormatter
 * 
 * @author Cam Spiers <camspiers at gmail dot com>
 * 
 * @package framework
 * @subpackage formatters
 */
class FormEncodedDataFormatter extends XMLDataFormatter {
	
	public function supportedExtensions() {
		return array(
		);
	}

	public function supportedMimeTypes() {
		return array(
			'application/x-www-form-urlencoded'
		);
	}
	
	public function convertStringToArray($strData) {
        $postArray = array();
        parse_str($strData, $postArray);
        return $postArray;
		//TODO: It would be nice to implement this function in Convert.php
		//return Convert::querystr2array($strData);
	}
	
}
