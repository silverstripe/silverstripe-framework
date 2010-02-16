<?php
/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigator {

	/**
	 * @param SiteTree $record
	 * @return Array template data
	 */
	static function get_for_record($record) {
		$items = '';
		$message = '';
	
		$navItemClasses = ClassInfo::subclassesFor('SilverStripeNavigatorItem');
		array_shift($navItemClasses);
		
		// Sort menu items according to priority
		$menuPriority = array();
		$i = 0;
		foreach($navItemClasses as $navItemClass) {
			if($navItemClass == 'SilverStripeNavigatorItem') continue;
			
			$i++;
			$obj = new $navItemClass();
			// This funny litle formula ensures that the first item added with the same priority will be left-most.
			$priority = Object::get_static($navItemClass, 'priority');
			$menuPriority[$priority * 100 - 1] = $obj;
		}
		ksort($menuPriority);
		
		foreach($menuPriority as $obj) {
			
			$text = $obj->getHTML($record);
			if($text) $items .= $text;
			$newMessage = $obj->getMessage($record);
			if($newMessage) $message = $newMessage;
		}
		
		return array(
			'items' => $items,
			'message' => $message
		);
	}
}

/**
 * Navigator items are links that appear in the $SilverStripeNavigator bar.
 * To add an item, extends this class.
 * 
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem extends Object {
	function getHTML($page) {}
	function getMessage($page) {}
}

/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem_CMSLink extends SilverStripeNavigatorItem {
	static $priority = 10;	
	
	function getHTML($page) {
		if(is_a(Controller::curr(), 'CMSMain')) {
			return '<a class="current">CMS</a>';
		} else {
			$cmsLink = 'admin/show/' . $page->ID;
			$cmsLink = "<a href=\"$cmsLink\" class=\"newWindow\" target=\"cms\">". _t('ContentController.CMS', 'CMS') ."</a>";
	
			return $cmsLink;
		}
	}
	
	function getLink($page) {
		if(is_a(Controller::curr(), 'CMSMain')) {
			return Controller::curr()->AbsoluteLink('show') . $page->ID;
		}
	}

}

/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem_StageLink extends SilverStripeNavigatorItem {
	static $priority = 20;

	function getHTML($page) {
		// TODO cmsworkflow module coupling
		if(Versioned::current_stage() == 'Stage' && !(ClassInfo::exists('SiteTreeFutureState') && SiteTreeFutureState::get_future_datetime())) {
			return "<a class=\"current\">". _t('ContentController.DRAFTSITE', 'Draft Site') ."</a>";
		} else {
			$thisPage = $page->AbsoluteLink();
			return "<a href=\"$thisPage?stage=Stage\" class=\"newWindow\" target=\"site\" style=\"left : -1px;\">". _t('ContentController.DRAFTSITE', 'Draft Site') ."</a>";
		}
	}
	
	function getMessage($page) {
		if(Versioned::current_stage() == 'Stage') {
			return "<div id=\"SilverStripeNavigatorMessage\" title=\"". _t('ContentControl.NOTEWONTBESHOWN', 'Note: this message will not be shown to your visitors') ."\">".  _t('ContentController.DRAFTSITE', 'Draft Site') ."</div>";
		}
	}
	
	function getLink($page) {
		if(Versioned::current_stage() == 'Stage') {
			return $page->AbsoluteLink() . '?stage=Stage';
		}
	}
}

/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem_LiveLink extends SilverStripeNavigatorItem {
	static $priority = 30;

	function getHTML($page) {
		if(Versioned::current_stage() == 'Live') {
			return "<a class=\"current\">". _t('ContentController.PUBLISHEDSITE', 'Published Site') ."</a>";
		} else {
			$livePage = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree"."ID" = ' . $page->ID);
			if($livePage) {
				$thisPage = $livePage->AbsoluteLink();
				return "<a href=\"$thisPage?stage=Live\" class=\"newWindow\" target=\"site\" style=\"left : -3px;\">". _t('ContentController.PUBLISHEDSITE', 'Published Site') ."</a>";
			}
		}
	}
	
	function getMessage($page) {
		if(Versioned::current_stage() == 'Live') {
			return "<div id=\"SilverStripeNavigatorMessage\" title=\"". _t('ContentControl.NOTEWONTBESHOWN', 'Note: this message will not be shown to your visitors') ."\">".  _t('ContentController.PUBLISHEDSITE', 'Published Site') ."</div>";
		}
	}
	
	function getLink($page) {
		if(Versioned::current_stage() == 'Live') {
			return $page->AbsoluteLink() . '?stage=Live';
		}
	}
}

/**
 * @package cms
 * @subpackage content
 */
class SilverStripeNavigatorItem_ArchiveLink extends SilverStripeNavigatorItem {
	static $priority = 40;

	function getHTML($page) {
		if(Versioned::current_archived_date()) {
			return "<a class=\"current\">". _t('ContentController.ARCHIVEDSITE', 'Archived Site') ."</a>";
		}
	}
	
	function getMessage($page) {
		if($date = Versioned::current_archived_date()) {
			$dateObj = Object::create('Datetime', $date, null);
			
			return "<div id=\"SilverStripeNavigatorMessage\" title=\"". _t('ContentControl.NOTEWONTBESHOWN', 'Note: this message will not be shown to your visitors') ."\">". _t('ContentController.ARCHIVEDSITEFROM', 'Archived site from') ."<br>" . $dateObj->Nice() . "</div>";
		}
	}
	
	function getLink($page) {
		if($date = Versioned::current_archived_date()) {
			return $page->AbsoluteLink() . '?archiveDate=' . $date;
		}
	}
}

?>