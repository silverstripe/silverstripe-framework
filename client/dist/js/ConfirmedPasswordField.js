(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.ConfirmedPasswordField', ['./jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('./jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssConfirmedPasswordField = mod.exports;
	}
})(this, function (_jQuery) {
	'use strict';

	var _jQuery2 = _interopRequireDefault(_jQuery);

	function _interopRequireDefault(obj) {
		return obj && obj.__esModule ? obj : {
			default: obj
		};
	}

	(0, _jQuery2.default)(document).on('click', '.confirmedpassword .showOnClick a', function () {
		var $container = (0, _jQuery2.default)('.showOnClickContainer', (0, _jQuery2.default)(this).parent());

		$container.toggle('fast', function () {
			$container.find('input[type="hidden"]').val($container.is(":visible") ? 1 : 0);
		});

		return false;
	});
});