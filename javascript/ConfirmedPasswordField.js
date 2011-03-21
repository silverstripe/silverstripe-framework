(function ($) {
	$('.confirmedpassword .showOnClick a').live('click', function () {
		$('.showOnClickContainer', $(this).parent()).toggle('fast');
		return false;
	});
})(jQuery);