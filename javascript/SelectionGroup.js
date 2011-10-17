(function($) {
	$(document).ready(function() {
		$('ul.SelectionGroup input.selector').live('click', function() {
			var li = $(this).parent('li')
			li.addClass('selected');

			var prev = li.prevAll('li.selected');
			if(prev.length) prev.removeClass('selected');
			var next = li.nextAll('li.selected');
			if(next.length) next.removeClass('selected');

			$(this).focus();
		});
	})
})(jQuery);