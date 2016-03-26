(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.silverstripe-component', ['exports', 'react', '../../../javascript/src/jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(exports, require('react'), require('../../../javascript/src/jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(mod.exports, global.react, global.jQuery);
		global.ssSilverstripeComponent = mod.exports;
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

		function SilverStripeComponent(props) {
			_classCallCheck(this, SilverStripeComponent);

			var _this = _possibleConstructorReturn(this, Object.getPrototypeOf(SilverStripeComponent).call(this, props));

			if (typeof _this.props.route !== 'undefined') {
				_this._render = _this.render;

				_this.render = function () {
					var component = null;

					if (_this.isComponentRoute()) {
						component = _this._render();
					}

					return component;
				};

				window.ss.router(_this.props.route, function (ctx, next) {
					_this.handleEnterRoute(ctx, next);
				});
				window.ss.router.exit(_this.props.route, function (ctx, next) {
					_this.handleExitRoute(ctx, next);
				});
			}
			return _this;
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
			key: 'handleEnterRoute',
			value: function handleEnterRoute(ctx, next) {
				next();
			}
		}, {
			key: 'handleExitRoute',
			value: function handleExitRoute(ctx, next) {
				next();
			}
		}, {
			key: 'isComponentRoute',
			value: function isComponentRoute() {
				var params = arguments.length <= 0 || arguments[0] === undefined ? {} : arguments[0];

				if (typeof this.props.route === 'undefined') {
					return true;
				}

				var route = new window.ss.router.Route(this.props.route);

				return route.match(window.ss.router.current, params);
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