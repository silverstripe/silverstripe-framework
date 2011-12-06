jQuery(function($){
		
	$('.ss-gridfield-button').entwine({
		onclick: function(e){
			button = this;
			e.preventDefault();
			var form = $(this).closest("form");
			form.addClass('loading');
			$.ajax({
				type: "POST",
				url: form.attr('action'),
				data: form.serialize()+'&'+escape(button.attr('name'))+'='+escape(button.val()), 
				dataType: 'html',
				success: function(data) {
					form.replaceWith(data);
					form.removeClass('loading');
				},
				error: function(e) {
					alert(ss.i18n._t('GRIDFIELD.ERRORINTRANSACTION', 'An error occured while fecthing data from the server\n Please try again later.'));
					form.removeClass('loading');
				}
			});
		}
	});
});