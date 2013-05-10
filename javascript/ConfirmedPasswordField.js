(function ($) {
	$(document).on('click', '.confirmedpassword .showOnClick a', function () {
		var $container = $('.showOnClickContainer', $(this).parent());

		$container.toggle('fast', function() {
			$container.find('input[type="hidden"]').val($container.is(":visible") ? 1 : 0);
		});
		
		return false;
	});
})(jQuery);