(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define('ss.silverstripe-backend', ['exports', 'isomorphic-fetch'], factory);
  } else if (typeof exports !== "undefined") {
    factory(exports, require('isomorphic-fetch'));
  } else {
    var mod = {
      exports: {}
    };
    factory(mod.exports, global.isomorphicFetch);
    global.ssSilverstripeBackend = mod.exports;
  }
})(this, function (exports, _isomorphicFetch) {
  'use strict';

  Object.defineProperty(exports, "__esModule", {
    value: true
  });

  var _isomorphicFetch2 = _interopRequireDefault(_isomorphicFetch);

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

  function checkStatus(response) {
    if (response.status >= 200 && response.status < 300) {
      return response;
    } else {
      var error = new Error(response.statusText);
      error.response = response;
      throw error;
    }
  }

  var SilverStripeBackend = function () {
    function SilverStripeBackend() {
      _classCallCheck(this, SilverStripeBackend);
    }

    _createClass(SilverStripeBackend, [{
      key: 'get',
      value: function get(url) {
        return (0, _isomorphicFetch2.default)(url, { method: 'get', credentials: 'same-origin' }).then(checkStatus);
      }
    }, {
      key: 'post',
      value: function post(url, data) {
        return (0, _isomorphicFetch2.default)(url, { method: 'post', credentials: 'same-origin', body: data }).then(checkStatus);
      }
    }, {
      key: 'put',
      value: function put(url, data) {
        return (0, _isomorphicFetch2.default)(url, { method: 'put', credentials: 'same-origin', body: data }).then(checkStatus);
      }
    }, {
      key: 'delete',
      value: function _delete(url, data) {
        return (0, _isomorphicFetch2.default)(url, { method: 'delete', credentials: 'same-origin', body: data }).then(checkStatus);
      }
    }]);

    return SilverStripeBackend;
  }();

  var backend = new SilverStripeBackend();

  exports.default = backend;
});