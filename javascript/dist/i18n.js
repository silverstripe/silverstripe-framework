(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.i18n', ['exports'], factory);
	} else if (typeof exports !== "undefined") {
		factory(exports);
	} else {
		var mod = {
			exports: {}
		};
		factory(mod.exports);
		global.ssI18n = mod.exports;
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

	var i18n = function () {
		function i18n() {
			_classCallCheck(this, i18n);

			this.currentLocale = null;
			this.defaultLocale = 'en_US';
			this.lang = {};
		}

		_createClass(i18n, [{
			key: 'setLocale',
			value: function setLocale(locale) {
				this.currentLocale = locale;
			}
		}, {
			key: 'getLocale',
			value: function getLocale() {
				return this.currentLocale !== null ? this.currentLocale : this.defaultLocale;
			}
		}, {
			key: '_t',
			value: function _t(entity, fallbackString, priority, context) {
				var langName = this.getLocale().replace(/_[\w]+/i, '');
				var defaultlangName = this.defaultLocale.replace(/_[\w]+/i, '');

				if (this.lang && this.lang[this.getLocale()] && this.lang[this.getLocale()][entity]) {
					return this.lang[this.getLocale()][entity];
				} else if (this.lang && this.lang[langName] && this.lang[langName][entity]) {
					return this.lang[langName][entity];
				} else if (this.lang && this.lang[this.defaultLocale] && this.lang[this.defaultLocale][entity]) {
					return this.lang[this.defaultLocale][entity];
				} else if (this.lang && this.lang[defaultlangName] && this.lang[defaultlangName][entity]) {
					return this.lang[defaultlangName][entity];
				} else if (fallbackString) {
					return fallbackString;
				} else {
					return '';
				}
			}
		}, {
			key: 'addDictionary',
			value: function addDictionary(locale, dict) {
				if (typeof this.lang[locale] === 'undefined') {
					this.lang[locale] = {};
				}

				for (var entity in dict) {
					this.lang[locale][entity] = dict[entity];
				}
			}
		}, {
			key: 'getDictionary',
			value: function getDictionary(locale) {
				return this.lang[locale];
			}
		}, {
			key: 'stripStr',
			value: function stripStr(str) {
				return str.replace(/^\s*/, '').replace(/\s*$/, '');
			}
		}, {
			key: 'stripStrML',
			value: function stripStrML(str) {
				var parts = str.split('\n');

				for (var i = 0; i < parts.length; i += 1) {
					parts[i] = stripStr(parts[i]);
				}

				return stripStr(parts.join(' '));
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
			key: 'detectLocale',
			value: function detectLocale() {
				var rawLocale;
				var detectedLocale;

				rawLocale = jQuery('body').attr('lang');

				if (!rawLocale) {
					var metas = document.getElementsByTagName('meta');

					for (var i = 0; i < metas.length; i++) {
						if (metas[i].attributes['http-equiv'] && metas[i].attributes['http-equiv'].nodeValue.toLowerCase() == 'content-language') {
							rawLocale = metas[i].attributes['content'].nodeValue;
						}
					}
				}

				if (!rawLocale) {
					rawLocale = this.defaultLocale;
				}

				var rawLocaleParts = rawLocale.match(/([^-|_]*)[-|_](.*)/);

				if (rawLocale.length == 2) {
					for (var compareLocale in i18n.lang) {
						if (compareLocale.substr(0, 2).toLowerCase() == rawLocale.toLowerCase()) {
							detectedLocale = compareLocale;
							break;
						}
					}
				} else if (rawLocaleParts) {
					detectedLocale = rawLocaleParts[1].toLowerCase() + '_' + rawLocaleParts[2].toUpperCase();
				}

				return detectedLocale;
			}
		}, {
			key: 'addEvent',
			value: function addEvent(obj, evType, fn, useCapture) {
				if (obj.addEventListener) {
					obj.addEventListener(evType, fn, useCapture);
					return true;
				} else if (obj.attachEvent) {
					return obj.attachEvent('on' + evType, fn);
				} else {
					console.log('Handler could not be attached');
				}
			}
		}]);

		return i18n;
	}();

	var _i18n = new i18n();

	window.ss = typeof window.ss !== 'undefined' ? window.ss : {};
	window.ss.i18n = window.i18n = _i18n;

	exports.default = _i18n;
});