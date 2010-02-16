Behaviour.register({
	'#switchView a.newWindow' :  {
		onclick : function() {
			var w = window.open(this.href,windowName(this.target));
			w.focus();
			return false;
		}
	}
});

function windowName(suffix) {
	var base = document.getElementsByTagName('base')[0].href.replace('http://','').replace(/\//g,'_').replace(/\./g,'_');
	return base + suffix;
}
window.name = windowName('site');

(function($) {
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
