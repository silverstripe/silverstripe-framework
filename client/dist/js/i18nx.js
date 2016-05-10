(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.i18nx', ['exports'], factory);
	} else if (typeof exports !== "undefined") {
		factory(exports);
	} else {
		var mod = {
			exports: {}
		};
		factory(mod.exports);
		global.ssI18nx = mod.exports;
	}
})(this, function (exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	function _classCallCheck(instance, Constructor) {
		if (!(instance instanceof Constructor)) {
			throw new TypeError("Cannot call a class as a function");
		}
	}

	var _createClass = function () {
		function defineProperties(target, props) {
			for (var i = 0; i < props.length; i++) {
				var descriptor = props[i];
				descriptor.enumerable = descriptor.enumerable || false;
				descriptor.configurable = true;
				if ("value" in descriptor) descriptor.writable = true;
				Object.defineProperty(target, descriptor.key, descriptor);
			}
		}

		return function (Constructor, protoProps, staticProps) {
			if (protoProps) defineProperties(Constructor.prototype, protoProps);
			if (staticProps) defineProperties(Constructor, staticProps);
			return Constructor;
		};
	}();

	var i18nx = function () {
		function i18nx() {
			_classCallCheck(this, i18nx);

			this.currentLocale = 'en_US';
			this.defaultLocale = 'en_US';
		}

		_createClass(i18nx, [{
			key: '_t',
			value: function _t(entity, fallbackString, priority, context) {
				return fallbackString;
			}
		}, {
			key: 'sprintf',
			value: function sprintf(s) {
				for (var _len = arguments.length, params = Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
					params[_key - 1] = arguments[_key];
				}

				if (params.length === 0) {
					return s;
				}

				var regx = new RegExp('(.?)(%s)', 'g');

				var i = 0;

				return s.replace(regx, function (match, subMatch1, subMatch2, offset, string) {
					if (subMatch1 === '%') {
						return match;
					}

					return subMatch1 + params[i += 1];
				});
			}
		}, {
			key: 'inject',
			value: function inject(s, map) {
				var regx = new RegExp('\{([A-Za-z0-9_]*)\}', 'g');

				return s.replace(regx, function (match, key, offset, string) {
					return map[key] ? map[key] : match;
				});
			}
		}, {
			key: 'addDictionary',
			value: function addDictionary() {}
		}, {
			key: 'getDictionary',
			value: function getDictionary() {}
		}]);

		return i18nx;
	}();

	;

	var _i18nx = new i18nx();

	exports.default = _i18nx;
});