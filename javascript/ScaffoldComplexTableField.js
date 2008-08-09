window.onload = function() {

	resourcePath = jQuery('form').attr('action');
	
	jQuery("fieldset input:first").attr('autocomplete', 'off').autocomplete({list: ["mark rickerby", "maxwell sparks"]});
	
	jQuery("fieldset input:first").bind('activate.autocomplete', function(e){
			
		type = jQuery("fieldset input:first").attr('name');
		value = jQuery("fieldset input:first").val();
			
		jQuery.getJSON(resourcePath + '/record', {'type':type, 'value':value}, function(data) {
			jQuery('form input').each(function(i, elm){
				if(elm.name in data.record) {
					val = data.record[elm.name];
					if (val != null) elm.setAttribute('value', val);
				}
			});
		});
			
	});
	
	
};