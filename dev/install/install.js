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
	$('#database_selection li label, #database_selection input:radio').click(function(e) {
		$('.dbfields').hide();
		// only show fields if there's no db error
		if(!$('.databaseError', $(this).parent()).length) $('.dbfields', $(this).parent()).show();
		$('.databaseError').hide();
		$('.databaseError', $(this).parent()).show();
	});
	
	// Select first
	$('#database_selection li input:checked').siblings('label').click();
	
	/**
	 * Install button
	 */
	$('#reinstall_confirmation').click(function() {
		$('#install_button').attr('disabled', !$(this).is(':checked'));
	});
	
	$('#install_button').click(function() {
		$('#saving_top').hide(); 
		$(this).val('Installing SilverStripe...');
	});
	
	/**
	 * Show all the requirements 
	 */
	$('h5.requirement a').click(function() {
		if($(this).text() == 'Hide All Requirements') {
			// hide the shown requirements
			$(this).parents('h5').next('table.testResults').find('.good').hide();
			$(this).text('Show All Requirements');
		}
		else {
			// show the requirements.
			$(this).parents('h5').next('table.testResults').find('.good').show();
			$(this).text('Hide All Requirements');			
		}
		
		return false;
	});
});