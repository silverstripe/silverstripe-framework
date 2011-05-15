(function($) {
	
	$.entwine('ss', function($){
		
		$('.cms-menu').entwine({
			
		});
		
		$('.cms-menu.collapsed li').entwine({
			onclick: function() {
				// the container is overflow: hidden, so we need to break the subnavigation out of it	
				// return false;
			}
		});
		
	});
}(jQuery));