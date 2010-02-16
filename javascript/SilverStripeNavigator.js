function windowName(suffix) {
	var base = document.getElementsByTagName('base')[0].href.replace('http://','').replace(/\//g,'_').replace(/\./g,'_');
	return base + suffix;
}

(function($) {
	$('#switchView a.newWindow').livequery('click',
		function() {
			var w = window.open(this.href, windowName(this.target));
			w.focus();
			return false;
		}
	);

	$('#SilverStripeNavigatorLink').livequery('click',
		function() {
			$('#SilverStripeNavigatorLinkPopup').toggle();
			return false;
		}
	);
	
	$('#SilverStripeNavigatorLinkPopup input').livequery('focus',
		function() {
			this.select();
		}
	);

})(jQuery);
