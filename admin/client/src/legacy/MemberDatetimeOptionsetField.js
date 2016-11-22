import $ from 'jQuery';

$.entwine('ss', function($){

	$('.memberdatetimeoptionset').entwine({
		onmatch: function() {
			this.find('.toggle-content').hide();
			this._super();
		}
	});

	$('.memberdatetimeoptionset .toggle').entwine({
		onclick: function(e) {
			$(this).closest('.form__field-description').parent().find('.toggle-content').toggle();
			return false;
		}
	});

});
