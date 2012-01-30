jQuery(function($){
	
	$(document).delegate(".ss-gridfield .relation-search", "focus", function (event) {
		$(this).autocomplete({
			source: function(request, response){
				var searchField = $(this.element);
				var form = $(this.element).closest("form");
				// Due to some very weird behaviout of jquery.metadata, the url have to be double quoted
				var suggestionUrl = $(searchField).attr('data-search-url').substr(1,$(searchField).attr('data-search-url').length-2);
				$.ajax({
					headers: {
						"X-Get-Fragment" : 'Partial'
					},
					type: "GET",
					url: suggestionUrl+'/'+request.term,
					data: form.serialize()+'&'+escape(searchField.attr('name'))+'='+escape(searchField.val()), 
					success: function(data) {
						response( $.map(JSON.parse(data), function( name, id ) {
							return { label: name, value: name, id: id }
						}));
					},
					error: function(e) {
						alert(ss.i18n._t('GRIDFIELD.ERRORINTRANSACTION', 'An error occured while fetching data from the server\n Please try again later.'));
					}
				});
			},
			select: function(event, ui) {
				$(this).closest("fieldset.ss-gridfield").find("#action_gridfield_relationfind").replaceWith(
					'<input type="hidden" name="relationID" value="'+ui.item.id+'" id="relationID"/>'
				);
				$(this).closest("fieldset.ss-gridfield").find("#action_gridfield_relationadd").removeAttr('disabled');
			}
		});
	});
	
});