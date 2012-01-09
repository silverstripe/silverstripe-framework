jQuery(function($){
		
	$('fieldset.ss-gridfield .action').entwine({
		onclick: function(e){
			var button = this;
			e.preventDefault();
			var form = $(this).closest("form");
			var field = $(this).closest("fieldset.ss-gridfield");
			form.addClass('loading');
			$.ajax({
				headers: {"X-Get-Fragment" : 'CurrentField'},
				type: "POST",
				url: form.attr('action'),
				data: form.serialize()+'&'+escape(button.attr('name'))+'='+escape(button.val()), 
				dataType: 'html',
				success: function(data) {
					// Replace the grid field with response, not the form.
					field.replaceWith(data);
					form.removeClass('loading');
				},
				error: function(e) {
					alert(ss.i18n._t('GRIDFIELD.ERRORINTRANSACTION', 'An error occured while fetching data from the server\n Please try again later.'));
					form.removeClass('loading');
				}
			});
		}
	});
	
	var removeFilterButtons = function() {
		// Remove stuff
		$('th').children('div').each(function(i,v) {
			$(v).remove();
		});	
	}	
	
	/*
	 * Upon focusing on a filter <input> element, move "filter" and "reset" buttons and display next to the current <input> element
	 * ToDo ensure filter-button state is maintained after filtering (see resetState param)
	 * ToDo get working in IE 6-7
	 */
	$('fieldset.ss-gridfield input.ss-gridfield-sort').entwine({
		onfocusin: function(e) {
			// Dodgy results in IE <=7
			if($.browser.msie && $.browser.version <= 7) {
				return false;
			}
			var eleInput = $(this);
			// Remove existing <div> and <button> elements in-lieu of cloning
			removeFilterButtons();		
			var eleButtonSetFilter = $('#action_filter');
			var eleButtonResetFilter = $('#action_reset');
			// Retain current widths to ensure <th>'s don't shift widths
			var eleButtonWidth = eleButtonSetFilter.width();					
			// Check <th> doesn't already have an (extra) cloned <button> appended, otherwise clone
			if(eleInput.closest('th').children().length == 1) {
				var newButtonCss = {
					'position':'absolute',
					'top':'-23px',
					'left':'0',
					'border':'#EEE solid 1px',
					'padding':'0',
					'margin-left':'0'
				};	
				// Append a <div> element used purely for CSS positioning - table elements on their own are untrustworthy to style in this manner
				$('<div/>').append(
					eleButtonSetFilter.clone().css(newButtonCss),
					eleButtonResetFilter.clone().css(newButtonCss).css('left',(eleButtonWidth+4)+'px')
				).css({'position':'relative','margin':'0 auto','width':'65%'}).appendTo(eleInput.closest('th'));
			}
		}
	});	

});