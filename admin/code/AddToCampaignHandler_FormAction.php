<?php
/**
 * Created by PhpStorm.
 * User: dmooyman
 * Date: 12/08/16
 * Time: 12:15 PM
 */
namespace SilverStripe\Admin;

use FormAction;

/**
 * A form action to return from geCMSActions or otherwise include in a CMS Edit Form that
 * has the right action name and CSS classes to trigger the AddToCampaignHandler.
 *
 * See SiteTree.php and CMSMain.php for an example of it's use
 */
class AddToCampaignHandler_FormAction extends FormAction
{

	function __construct()
	{
		parent::__construct('addtocampaign', _t('CAMPAIGNS.ADDTOCAMPAIGN', 'Add to campaign'));
		$this->setUseButtonTag(false);
		$this->addExtraClass('add-to-campaign-action');
		$this->setValidationExempt(true);
		$this->addExtraClass('btn-primary');
	}
}
