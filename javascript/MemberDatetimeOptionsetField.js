(function($) {
	$('.formattingHelpText').hide();
	$('.formattingHelpToggle').click(function() {
		$(this).parent().find('.formattingHelpText').toggle();
		return false;
	})
})(jQuery);