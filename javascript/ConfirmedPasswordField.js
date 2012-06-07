(function ($) {
	$('.confirmedpassword .showOnClick a').live('click', function () {
		var $container = $('.showOnClickContainer', $(this).parent());

		$container.toggle('fast', function() {
			$container.find('input[type="hidden"]').val($container.is(":visible") ? 1 : 0);
		});
		
		return false;
	});
})(jQuery);