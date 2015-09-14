<?php
/**
 * The delete member class which represents a user that has been deleted from the system
 * @package cmsworkflow
 */
class DeletedMember extends DataObject {

	static $db = array(
		'FirstName' => 'Varchar',
		'Surname' => 'Varchar',
		'Email' => 'Varchar',
		'NumVisit' => 'Int',
		'LastVisited' => 'SS_Datetime',
		'Locale' => 'Varchar(6)',
		// In ISO format
		'DateFormat' => 'Varchar(30)',
		'TimeFormat' => 'Varchar(30)',
	);

	function getFirstName() {
		return '(deleted) ' . $this->getField('FirstName');
	}

	public function getTitle() {
		return '(deleted) ' . $this->getField('Surname') . ', ' . $this->getField('FirstName');
	}

	public function getEmail() {
		return '(deleted) ' . $this->getField('Email');
	}

	/**
	 * Get the complete name of the member
	 *
	 * @return string Returns the first- and surname of the member.
	 */
	public function getName() {
		return ($this->data()->Surname) ? trim($this->data()->FirstName . ' ' . $this->data()->Surname) : $this->data()->FirstName;
	}

}
