jQuery(function($){
		
	var onGridClick = function(){
		var form = $(this).closest("form");
		var gridField = $(this).closest(".ss-gridfield");
		$(this).addClass('loading');
		$.ajax({
			type: "POST",
			url: form.attr('action')+'/field/'+gridField.attr('id'),
			data: form.serialize()+"&page="+$(this).attr('value'), 
			success: function(data) {
				$(gridField).replaceWith(data);
				gridInit();
			},
			error: function() {
				alert('There seems like there where some failure when trying to fetch the page, please reload and try again.');
			}
			
		});

		return false;
	}
	
	var gridInit = function() {
		$('.ss-gridfield-pagination-button').click(onGridClick);
	}
	
	gridInit();
	
});