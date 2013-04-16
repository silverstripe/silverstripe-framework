var field = $('div.toggleField');

if(field.hasClass('startClosed')) {
	field.find('div.contentMore').hide();
	field.find('div.contentLess').show();
}

$('div.toggleField .triggerLess, div.toggleField .triggerMore').click(function() {
	field.find('div.contentMore').toggle();
	field.find('div.contentLess').toggle();
});

