<?php
/**
 * Created by PhpStorm.
 * User: dmooyman
 * Date: 12/08/16
 * Time: 12:13 PM
 */
namespace SilverStripe\Admin;

use SS_HTTPResponse;

/**
 * Allow overriding finished state for faux redirects.
 *
 * @package framework
 * @subpackage admin
 */
class LeftAndMain_HTTPResponse extends SS_HTTPResponse
{

	protected $isFinished = false;

	public function isFinished()
	{
		return (parent::isFinished() || $this->isFinished);
	}

	public function setIsFinished($bool)
	{
		$this->isFinished = $bool;
	}

}
