(function($) {
	
	$.entwine('ss', function($){
		
		$('.cms-menu li a').entwine({
			onclick: function(e) {
				e.preventDefault();
				console.debug($('.LeftAndMain .cms-content'));
				$('.LeftAndMain .cms-content').entwine('ss').loadPanel(this.attr('href'));
			}
		});
		
		$('.cms-menu.collapsed li').entwine({
			onclick: function() {
				// the container is overflow: hidden, so we need to break the subnavigation out of it	
				// return false;
			}
		});
		
	});
}(jQuery));