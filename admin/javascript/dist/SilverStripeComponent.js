(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.SilverStripeComponent', ['exports', 'react', '../../../javascript/src/jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(exports, require('react'), require('../../../javascript/src/jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(mod.exports, global.react, global.jQuery);
		global.ssSilverStripeComponent = mod.exports;
	}
})(this, function (exports, _react, _jQuery) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	var _react2 = _interopRequireDefault(_react);

	var _jQuery2 = _interopRequireDefault(_jQuery);

	function _interopRequireDefault(obj) {
		return obj && obj.__esModule ? obj : {
			default: obj
		};
	}

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

	function _possibleConstructorReturn(self, call) {
		if (!self) {
			throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
		}

		return call && (typeof call === "object" || typeof call === "function") ? call : self;
	}

	function _inherits(subClass, superClass) {
		if (typeof superClass !== "function" && superClass !== null) {
			throw new TypeError("Super expression must either be null or a function, not " + typeof superClass);
		}

		subClass.prototype = Object.create(superClass && superClass.prototype, {
			constructor: {
				value: subClass,
				enumerable: false,
				writable: true,
				configurable: true
			}
		});
		if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass;
	}

	var SilverStripeComponent = function (_Component) {
		_inherits(SilverStripeComponent, _Component);

		function SilverStripeComponent() {
			_classCallCheck(this, SilverStripeComponent);

			return _possibleConstructorReturn(this, Object.getPrototypeOf(SilverStripeComponent).apply(this, arguments));
		}

		_createClass(SilverStripeComponent, [{
			key: 'componentDidMount',
			value: function componentDidMount() {
				if (typeof this.props.cmsEvents === 'undefined') {
					return;
				}

				this.cmsEvents = this.props.cmsEvents;

				for (var cmsEvent in this.cmsEvents) {
					(0, _jQuery2.default)(document).on(cmsEvent, this.cmsEvents[cmsEvent].bind(this));
				}
			}
		}, {
			key: 'componentWillUnmount',
			value: function componentWillUnmount() {
				for (var cmsEvent in this.cmsEvents) {
					(0, _jQuery2.default)(document).off(cmsEvent);
				}
			}
		}, {
			key: 'emitCmsEvent',
			value: function emitCmsEvent(componentEvent, data) {
				(0, _jQuery2.default)(document).trigger(componentEvent, data);
			}
		}]);

		return SilverStripeComponent;
	}(_react.Component);

	SilverStripeComponent.propTypes = {
		'cmsEvents': _react2.default.PropTypes.object
	};

	exports.default = SilverStripeComponent;
});