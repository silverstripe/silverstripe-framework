import $ from 'jQuery';

// TODO Enable once https://github.com/webpack/extract-text-webpack-plugin/issues/179 is resolved. Included in bundle.scss for now.
// require('../styles/legacy/ConfirmedPasswordField.scss');

$(document).on('click', '.confirmedpassword .showOnClick a', function () {
	var $container = $('.showOnClickContainer', $(this).parent());

	$container.toggle('fast', function() {
		$container.find('input[type="hidden"]').val($container.is(":visible") ? 1 : 0);
	});

	return false;
});
