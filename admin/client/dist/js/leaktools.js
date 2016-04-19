(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.leaktools', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssLeaktools = mod.exports;
	}
})(this, function (_jQuery) {
	'use strict';

	var _jQuery2 = _interopRequireDefault(_jQuery);

	function _interopRequireDefault(obj) {
		return obj && obj.__esModule ? obj : {
			default: obj
		};
	}

	var getHTML = function getHTML(el) {
		var clone = el.cloneNode(true);

		var div = (0, _jQuery2.default)('<div></div>');
		div.append(clone);

		return div.html();
	};

	_jQuery2.default.leaktools = {

		logDuplicateElements: function logDuplicateElements() {
			var els = (0, _jQuery2.default)('*');
			var dirty = false;

			els.each(function (i, a) {
				els.not(a).each(function (j, b) {
					if (getHTML(a) == getHTML(b)) {
						dirty = true;
						console.log(a, b);
					}
				});
			});

			if (!dirty) console.log('No duplicates found');
		},

		logUncleanedElements: function logUncleanedElements(clean) {
			_jQuery2.default.each(_jQuery2.default.cache, function () {
				var source = this.handle && this.handle.elem;
				if (!source) return;

				var parent = source;
				while (parent && parent.nodeType == 1) {
					parent = parent.parentNode;
				}if (!parent) {
					console.log('Unattached', source);
					console.log(this.events);
					if (clean) (0, _jQuery2.default)(source).unbind().remove();
				} else if (parent !== document) console.log('Attached, but to', parent, 'not our document', source);
			});
		}
	};
});