(function ($) {
	$.entwine('ss', function($){
		$('.showOnClick a').entwine({
			onclick: function () {
				$('.showOnClickContainer', this.parent()).toggle('fast');
				return false;
			}
		});
	});
})(jQuery);