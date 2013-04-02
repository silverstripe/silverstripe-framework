<?php
/**
 * Record all login attempts through the {@link LoginForm} object.
 * This behaviour is disabled by default.
 *
 * Enable through {@link Security::$login_recording}.
 * 
 * Caution: Please make sure that enabling logging
 * complies with your privacy standards. We're logging
 * username and IP.
 * 
 * @package framework
 * @subpackage security
 */
class LoginAttempt extends DataObject {
	
	private static $db = array(
		'Email' => 'Varchar(255)', 
		'Status' => "Enum('Success,Failure')", 
		'IP' => 'Varchar(255)', 
	);
	
	private static $has_one = array(
		'Member' => 'Member', // only linked if the member actually exists
	);
	
	private static $has_many = array();
	
	private static $many_many = array();
	
	private static $belongs_many_many = array();
	
	/**
	 *
	 * @param boolean $includerelations a boolean value to indicate if the labels returned include relation fields
	 * 
	 */
	public function fieldLabels($includerelations = true) {
		$labels = parent::fieldLabels($includerelations);
		$labels['Email'] = _t('LoginAttempt.Email', 'Email Address');
		$labels['Status'] = _t('LoginAttempt.Status', 'Status');
		$labels['IP'] = _t('LoginAttempt.IP', 'IP Address');
		
		return $labels;
	}
	
}
