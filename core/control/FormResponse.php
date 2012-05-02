<?php
/**
 * This class collects all output that needs to be returned after an Form-Request to the client. It automatically determines
 * if it needs to send back javascript after an Ajax-Request or just redirect to another page (on a normal request).
 * 
 * FormResponse is also responsible for keeping the client- and serverside in sync after an HTTP-Request
 * by collecting javascript-commands (which mostly trigger subsequent update-calls by Ajax.)
 * Use the output as a return-value for Ajax-based saving methods. Be sure to check if the call is acutally "ajaxy"
 * by checking Director::is_ajax(). It is the developers responsibility to include this into his custom form-methods.
 * Use the Request-Parameter 'htmlonly' to enforce a pure HTML-response from the client-side.
 * 
 * Example: A {@TableField} is in an incorrect state after being saved, as it still has rows marked as "new"
 * which are already saved (and have an ID) in the database. By using AjaxSynchroniser we make sure that every instance
 * is refreshed by Ajax and reflects the correct state.
 *  
 * Caution: 
 * - FormResponse assumes that prototype.js is included on the client-side. (We can't put it into Requirements because it has to
 *   be included BEFORE an AjaxSynchroniser is called). 
 * - Please DON'T escape literal parameters which are passed to FormResponse, they are escaped automatically.
 * - Some functions assume a {LeftAndMain}-based environment (e.g. load_form())
 * 
 * @todo Force a specific execution order ($forceTop, $forceBottom)Ω
 * @todo Extension to return different formats, e.g. JSON or XML
 * 
 * WARNING: This should only be used within the CMS context. Please use markup or JSON to transfer state to the client,
 * and react with javascript callbacks instead in other situations.
 * 
 * @package forms
 * @subpackage core
 */
class FormResponse {
	
	/**
	 * @var $rules array
	 */
	static protected $rules = array();

	/**
	 * @var $behaviour_apply_rules array Separated from $rules because
	 * we need to apply all behaviour at the very end of the evaluated script
	 * to make sure we include all possible Behaviour.register()-calls.
	 */
	static protected $behaviour_apply_rules = array();
	
	/**
	 * @var $non_ajax_content string
	 */
	static protected $non_ajax_content;
	
	/**
	 * Status-messages are accumulated, and the "worst" is chosen
	 * 
	 * @var $status_messages array
	 */
	static protected $status_messages = array();
	
	/**
	 * @var $redirect_url string
	 */
	static protected $redirect_url;

	
	/**
	 * @var $redirect_url string
	 */
	static protected $status_include_order = array('bad', 'good', 'unknown');
	
	/**
	 * Get all content as a javascript-compatible string (only if there is an Ajax-Request present).
	 * Falls back to {non_ajax_content}, {redirect_url} or Director::redirectBack() (in this order).
	 * 
	 * @return string
	 */
	static function respond() {
		$response = new SS_HTTPResponse();

		// we don't want non-ajax calls to receive javascript
		if(isset($_REQUEST['forcehtml'])) {
			$response->setBody(self::$non_ajax_content);			
		} else if(isset($_REQUEST['forceajax']) || Director::is_ajax()) {
			$response->addHeader('Content-Type', 'text/javascript');
			$response->setBody(self::get_javascript());
		} elseif(!empty(self::$non_ajax_content)) {
			$response->setBody(self::$non_ajax_content);			
		} elseif(!empty(self::$redirect_url)) {
			Director::redirect(self::$redirect_url);
			return null;
		} elseif(!Director::redirected_to()) {
			Director::redirectBack();
			return null;
		} else {
			return null;
		}

		return $response;
	}
	
	/**
	 * Caution: Works only for forms which inherit methods from LeftAndMain.js
	 */
	static function load_form($content, $id = 'Form_EditForm') {
		// make sure form-tags are stripped
		// loadNewPage() uses innerHTML to replace the form, which makes IE cry when replacing an element with itself
		$content = preg_replace(array('/<form[^>]*>/','/<\/form>/'), '', $content);
		$JS_content = Convert::raw2js($content);
		self::$rules[] = "\$('{$id}').loadNewPage('{$JS_content}');";
		self::$rules[] = "\$('{$id}').initialize();";
		self::$rules[] = "if(typeof onload_init_tabstrip != 'undefined') onload_init_tabstrip();";
	}
	
	/**
	 * Add custom scripts.
	 * Caution: Not escaped for backwards-compatibility.
	 * 
	 * @param $scriptContent string
	 * 
	 * @todo Should this content be escaped?
	 */
	static function add($scriptContent, $uniquenessID = null) {
		if(isset($uniquenessID)) {
			self::$rules[$uniquenessID] = $scriptContent;
		} else {
			self::$rules[] = $scriptContent;
		}
	}
	
	static function clear() {
		self::$rules = array();
	}
	
	/**
	 * @param $id int
	 */
	static function get_page($id, $form = 'Form_EditForm', $uniquenessID = null) {
		$JS_id = (int)$id;
		if($JS_id){
			if(isset($uniquenessID)) {
				self::$rules[$uniquenessID] = "\$('$form').getPageFromServer($JS_id);";	
			} else {
				self::$rules[] = "\$('$form').getPageFromServer($JS_id);";	
			}
		}
	}

	/**
	 * Sets the status-message (overlay-notification in the CMS).
	 * You can call this method multiple times, it will default to the "worst" statusmessage.
	 * 
	 * @param $message string
	 * @param $status string
	 */
	static function status_message($message = "", $status = null) {
		$JS_message = Convert::raw2js(Convert::raw2xml($message));
		$JS_status = Convert::raw2js(Convert::raw2xml($status));
		if(isset($JS_status)) {
			self::$status_messages[$JS_status] = "statusMessage('{$JS_message}', '{$JS_status}');";
		} else {
			self::$status_messages['unknown'] = "statusMessage('{$JS_message}');";
		}
	}

	/**
	 * Alias for status_message($messsage, 'bad')
	 * 
	 * @param $message string
	 */
	static function error($message = "") {
		$JS_message = Convert::raw2js($message);
		self::$status_messages['bad'] = $JS_message;
	}
	
	/**
	 * Update the status (upper right corner) of the given Form
	 * 
	 * @param $status string
	 * @param $form string
	 */
	static function update_status($status, $form = "Form_EditForm") {
		$JS_form = Convert::raw2js($form);
		$JS_status = Convert::raw2js($status);
		self::$rules[] = "\$('$JS_form').updateStatus('$JS_status');";
	}

	/**
	 * Set the title of a single page in the pagetree
	 * 
	 * @param $id int
	 * @param $title string
	 */
	static function set_node_title($id, $title = "") {
		$JS_id = Convert::raw2js($id);
		$JS_title = Convert::raw2js($title);
		self::$rules[] = "$('sitetree').setNodeTitle('$JS_id', '$JS_title');";
	}
	
	/**
	 * Fallback-method to supply normal HTML-response when not being called by ajax.
	 * 
	 * @param $content string HTML-content
	 */
	static function set_non_ajax_content($content) {
		self::$non_ajax_content = $content;
	}
	
	/**
	 * @param $url string
	 */
	static function set_redirect_url($url) {
		self::$redirect_url = $url;
	}
	
	/**
	 * @return string
	 */
	static function get_redirect_url() {
		return self::$redirect_url;
	}
	
	/**
	 * Replace a given DOM-element with the given content.
	 * It automatically prefills {$non_ajax_content} with the passed content (as a fallback).
	 * 
	 * @param $domID string The DOM-ID of an HTML-element that should be replaced
	 * @param $domContent string The new HTML-content
	 * @param $reapplyBehaviour boolean Applies behaviour to the given domID after refreshing it
	 * @param $replaceMethod string Method for replacing - either 'replace' (=outerHTML) or 'update' (=innerHTML)
	 *   (Caution: "outerHTML" might cause problems on the client-side, e.g. on table-tags)
	 * 
	 * @todo More fancy replacing with loading-wheel etc.
	 */
	static function update_dom_id($domID, $domContent, $reapplyBehaviour = true, $replaceMethod = 'replace', $uniquenessID = null) {
		//self::$non_ajax_content = $domContent;
		$JS_domID = Convert::raw2js($domID);
		$JS_domContent = Convert::raw2js($domContent);
		$JS_replaceMethod = Convert::raw2js($replaceMethod);
		if(isset($uniquenessID)) {
			self::$rules[$uniquenessID] = "Element.$JS_replaceMethod('{$JS_domID}','{$JS_domContent}');";
		} else {
			self::$rules[] = "Element.$JS_replaceMethod('{$JS_domID}','{$JS_domContent}');";
		}
		if($reapplyBehaviour) {
			if(isset($uniquenessID)) {
				self::$behaviour_apply_rules[$uniquenessID] .= "Behaviour.apply('{$JS_domID}', true);";
			} else {
				self::$behaviour_apply_rules[] = "Behaviour.apply('{$JS_domID}', true);";
			}
		}
	}
	
	/**
	 * @return string Compiled string of javascript-function-calls (needs to be evaluated on the client-side!)
	 */
	protected static function get_javascript() {
		$js = "";
		
		// select only one status message (with priority on "bad" messages)
		$msg = "";
		foreach(self::$status_include_order as $status) {
			if(isset(self::$status_messages[$status])) {
				$msg = self::$status_messages[$status];
				break;
			}
		}
		if(!empty($msg)) self::$rules[] = $msg;
		

		$js .= implode("\n", self::$rules);
		$js .= Requirements::get_custom_scripts();

		// make sure behaviour is applied AFTER all registers are collected
		$js .= implode("\n", self::$behaviour_apply_rules);
		
		return $js;
	}
}
?>