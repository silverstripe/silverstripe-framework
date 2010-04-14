<?php


/**
 * Navigator items are links that appear in the $SilverStripeNavigator bar.
 * To add an item, extends this class.
 */
class SilverStripeNavigatorItem extends Object {
	function getHTML($controller) {}
	function getMessage($controller) {}
}

class SilverStripeNavigatorItem_CMSLink extends SilverStripeNavigatorItem {
	static $priority = 10;	
	
	function getHTML($controller) {
		if(is_a(Controller::curr(), 'CMSMain')) {
			return '<a class="current">CMS</a>';
		} else {
			$cmsLink = 'admin/show/' . $controller->ID;
			$cmsLink = "<a href=\"$cmsLink\" target=\"cms\">". _t('ContentController.CMS', 'CMS') ."</a>";
	
			return $cmsLink;
		}
	}
}

class SilverStripeNavigatorItem_StageLink extends SilverStripeNavigatorItem {
	static $priority = 20;

	function getHTML($controller) {
		if(Versioned::current_stage() == 'Stage' && !(ClassInfo::exists('SiteTreeFutureState') && SiteTreeFutureState::get_future_datetime())) {
			return "<a class=\"current\">". _t('ContentController.DRAFTSITE', 'Draft Site') ."</a>";
		} else {
			$thisPage = $controller->Link();
			return "<a href=\"$thisPage?stage=Stage\" target=\"site\" style=\"left : -1px;\">". _t('ContentController.DRAFTSITE', 'Draft Site') ."</a>";
		}
	}
	
	function getMessage($controller) {
		if(Versioned::current_stage() == 'Stage') {
			return "<div id=\"SilverStripeNavigatorMessage\" title=\"". _t('ContentControl.NOTEWONTBESHOWN', 'Note: this message will not be shown to your visitors') ."\">".  _t('ContentController.DRAFTSITE', 'Draft Site') ."</div>";
		}
	}
}

class SilverStripeNavigatorItem_LiveLink extends SilverStripeNavigatorItem {
	static $priority = 30;

	function getHTML($controller) {
		if(Versioned::current_stage() == 'Live') {
			return "<a class=\"current\">". _t('ContentController.PUBLISHEDSITE', 'Published Site') ."</a>";
		} else {
			$thisPage = $controller->Link();
			return "<a href=\"$thisPage?stage=Live\" target=\"site\" style=\"left : -3px;\">". _t('ContentController.PUBLISHEDSITE', 'Published Site') ."</a>";
		}
	}
	
	function getMessage($controller) {
		if(Versioned::current_stage() == 'Live') {
			return "<div id=\"SilverStripeNavigatorMessage\" title=\"". _t('ContentControl.NOTEWONTBESHOWN', 'Note: this message will not be shown to your visitors') ."\">".  _t('ContentController.PUBLISHEDSITE', 'Published Site') ."</div>";
		}
	}
}

class SilverStripeNavigatorItem_ArchiveLink extends SilverStripeNavigatorItem {
	static $priority = 40;

	function getHTML($controller) {
		if(Versioned::current_archived_date()) {
			return "<a class=\"current\">". _t('ContentController.ARCHIVEDSITE', 'Archived Site') ."</a>";
		}
	}
	
	function getMessage($controller) {
		if($date = Versioned::current_archived_date()) {
			$dateObj = Object::create('Datetime', $date, null);
			
			return "<div id=\"SilverStripeNavigatorMessage\" title=\"". _t('ContentControl.NOTEWONTBESHOWN', 'Note: this message will not be shown to your visitors') ."\">". _t('ContentController.ARCHIVEDSITEFROM', 'Archived site from') ."<br>" . $dateObj->Nice() . "</div>";
		}
	}
}

?>
