$(document).ready(function() {
	
	/**
	 * Toggle field readonly modes, if check configuration comes from
	 * _ss_environment (values populated on reload).
	 */
	$('#use_environment').click(function(e) {
		if(!$(this).is(':checked')) {
			$('.configured-by-env').removeAttr('disabled');
		} else {
			$('.configured-by-env').attr('disabled', 'disabled');
		}
	});
	
	/**
	 * Hide all existing database warnings, and show only current one
	 */
	$('#database_selection li').click(function(e) {
		$('.dbfields').hide();
		// only show fields if there's no db error
		if(!$('.databaseError', this).length) $('.dbfields', this).show();
		$('.databaseError').hide();
		$('.databaseError', this).show();
	});
	// Select first
	$('#database_selection li input:checked').parents('li:first').click();
	
	/**
	 * Install button
	 */
	$('#ReIn').click(function() {
		$('#install_button').attr('disabled', !$(this).is(':checked'));
	})
	$('#install_button').click(function() {
		$('#saving_top').hide(); 
		$(this).val('Installing SilverStripe...');
	})
});