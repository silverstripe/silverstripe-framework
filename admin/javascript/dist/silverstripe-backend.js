(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define('ss.silverstripe-backend', ['exports', 'isomorphic-fetch', 'es6-promise'], factory);
  } else if (typeof exports !== "undefined") {
    factory(exports, require('isomorphic-fetch'), require('es6-promise'));
  } else {
    var mod = {
      exports: {}
    };
    factory(mod.exports, global.isomorphicFetch, global.es6Promise);
    global.ssSilverstripeBackend = mod.exports;
  }
})(this, function (exports, _isomorphicFetch, _es6Promise) {
  'use strict';

  Object.defineProperty(exports, "__esModule", {
    value: true
  });

  var _isomorphicFetch2 = _interopRequireDefault(_isomorphicFetch);

  var _es6Promise2 = _interopRequireDefault(_es6Promise);

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

  _es6Promise2.default.polyfill();

  function checkStatus(response) {
    var ret = void 0;
    var error = void 0;
    if (response.status >= 200 && response.status < 300) {
      ret = response;
    } else {
      error = new Error(response.statusText);
      error.response = response;
      throw error;
    }

    return ret;
  }

  var SilverStripeBackend = function () {
    function SilverStripeBackend() {
      _classCallCheck(this, SilverStripeBackend);

      this.fetch = _isomorphicFetch2.default;
    }

    _createClass(SilverStripeBackend, [{
      key: 'get',
      value: function get(url) {
        return this.fetch(url, { method: 'get', credentials: 'same-origin' }).then(checkStatus);
      }
    }, {
      key: 'post',
      value: function post(url, data) {
        return this.fetch(url, { method: 'post', credentials: 'same-origin', body: data }).then(checkStatus);
      }
    }, {
      key: 'put',
      value: function put(url, data) {
        return this.fetch(url, { method: 'put', credentials: 'same-origin', body: data }).then(checkStatus);
      }
    }, {
      key: 'delete',
      value: function _delete(url, data) {
        return this.fetch(url, { method: 'delete', credentials: 'same-origin', body: data }).then(checkStatus);
      }
    }]);

    return SilverStripeBackend;
  }();

  var backend = new SilverStripeBackend();

  exports.default = backend;
});