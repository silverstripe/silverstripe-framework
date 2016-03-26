(function (global, factory) {
    if (typeof define === "function" && define.amd) {
        define('ss.silverstripe-backend', ['exports', 'jQuery'], factory);
    } else if (typeof exports !== "undefined") {
        factory(exports, require('jQuery'));
    } else {
        var mod = {
            exports: {}
        };
        factory(mod.exports, global.jQuery);
        global.ssSilverstripeBackend = mod.exports;
    }
})(this, function (exports, _jQuery) {
    'use strict';

    Object.defineProperty(exports, "__esModule", {
        value: true
    });

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

    var SilverStripeBackend = function () {
        function SilverStripeBackend() {
            _classCallCheck(this, SilverStripeBackend);
        }

        _createClass(SilverStripeBackend, [{
            key: 'get',
            value: function get(url) {
                return _jQuery2.default.ajax({ type: 'GET', url: url });
            }
        }, {
            key: 'post',
            value: function post(url, data) {
                return _jQuery2.default.ajax({ type: 'POST', url: url, data: data });
            }
        }, {
            key: 'put',
            value: function put(url, data) {
                return _jQuery2.default.ajax({ type: 'PUT', url: url, data: data });
            }
        }, {
            key: 'delete',
            value: function _delete(url, data) {
                return _jQuery2.default.ajax({ type: 'DELETE', url: url, data: data });
            }
        }]);

        return SilverStripeBackend;
    }();

    var backend = new SilverStripeBackend();

    exports.default = backend;
});