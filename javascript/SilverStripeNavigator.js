function windowName(suffix) {
	var base = document.getElementsByTagName('base')[0].href.replace('http://','').replace(/\//g,'_').replace(/\./g,'_');
	return base + suffix;
}

(function($) {
	$('#switchView a.newWindow').live('click',
		function() {
			var w = window.open(this.href, windowName(this.target));
			w.focus();
			return false;
		}
	);

	$('#SilverStripeNavigatorLink').live('click',
		function() {
			$('#SilverStripeNavigatorLinkPopup').toggle();
			return false;
		}
	);
	
	$('#SilverStripeNavigatorLinkPopup a.close').live('click',
		function() {
			$('#SilverStripeNavigatorLinkPopup').hide();
			return false;
		}
	);
	
	$('#SilverStripeNavigatorLinkPopup input').live('focus',
		function() {
			this.select();
		}
	);

})(jQuery);
