<?php

/**
 * @package sapphire
 * @subpackage control
 */

/**
 * Represenets an HTTPResponse returned by a controller.
 *
 * @package sapphire
 * @subpackage control
 */
class HTTPResponse extends Object {
	protected static $status_codes = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Request Range Not Satisfiable',
		417 => 'Expectation Failed',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
	);
	
	protected $statusCode = 200;
	protected $headers = array();
	protected $body = null;
	
	function setStatusCode($code) {
		if(isset(self::$status_codes[$code])) $this->statusCode = $code;
		else user_error("Unrecognised HTTP status code '$code'", E_USER_WARNING);
	}
	function getStatusCode() {
		return $this->statusCode;
	}
	
	function setBody($body) {
		$this->body = $body;
	}
	function getBody() {
		return $this->body;
	}
	
	/**
	 * Add a HTTP header to the response, replacing any header of the same name
	 */
	function addHeader($header, $value) {
		$this->headers[$header] = $value;
	}
	
	/**
	 * Return the HTTP header of the given name
	 * @returns string
	 */
	function getHeader($header) {
		if(isset($this->headers[$header])) {
			return $this->headers[$header];			
		} else {
			return null;
		}
	}
	
	function redirect($dest) {
		$this->statusCode = 302;
		$this->headers['Location'] = $dest;
	}

	/**
	 * Send this HTTPReponse to the browser
	 */
	function output() {
		if($this->statusCode == 302 && headers_sent($file, $line)) {
			$url = $this->headers['Location'];
			echo 
			"<p>Redirecting to <a href=\"$url\" title=\"Please click this link if your browser does not redirect you\">$url... (output started on $file, line $line)</a></p>
			<meta http-equiv=\"refresh\" content=\"1; url=$url\" />
			<script type=\"text/javascript\">setTimeout('window.location.href = \"$url\"', 50);</script>";
		} else {
			if(!headers_sent()) {
				header($_SERVER['SERVER_PROTOCOL'] . " $this->statusCode " . self::$status_codes[$this->statusCode]); 
				foreach($this->headers as $header => $value) {
					header("$header: $value");
				}
			}
			
			echo $this->body;
		}
	}
	
	/**
	 * Returns true if this response is "finished", that is, no more script execution should be done.
	 * Specifically, returns true if a redirect has already been requested
	 */
	function isFinished() {
		return $this->statusCode == 302 || $this->statusCode == 301;
	}
    
    /**
     * Return all the links in the body as an array.
     * @returns An array of maps.  Each map will contain 'id', 'class', and 'href', representing the HTML attributes of the link.
     */
    function getLinks() {
        $attributes = array('id', 'href', 'class');
        $links = array();
		$results = array();
        
        preg_match_all('/<a[^>]+>/i', $this->body, $links);
        // $links[0] contains the actual matches
        foreach($links[0] as $link) {
			$processedLink = array();
            foreach($attributes as $attribute) {
                $matches = array();
                if(preg_match('/' . $attribute  . '\s*=\s*"([^"]+)"/i', $link, $matches)) {
                    $processedLink[$attribute] = $matches[1];
                }                
            }
			$results[] = $processedLink;
        }
		
		return $results;
    }
	
}