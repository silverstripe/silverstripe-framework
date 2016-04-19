(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define('ss.silverstripe-backend', ['exports', 'isomorphic-fetch', 'es6-promise', 'qs', 'merge'], factory);
  } else if (typeof exports !== "undefined") {
    factory(exports, require('isomorphic-fetch'), require('es6-promise'), require('qs'), require('merge'));
  } else {
    var mod = {
      exports: {}
    };
    factory(mod.exports, global.isomorphicFetch, global.es6Promise, global.qs, global.merge);
    global.ssSilverstripeBackend = mod.exports;
  }
})(this, function (exports, _isomorphicFetch, _es6Promise, _qs, _merge) {
  'use strict';

  Object.defineProperty(exports, "__esModule", {
    value: true
  });

  var _isomorphicFetch2 = _interopRequireDefault(_isomorphicFetch);

  var _es6Promise2 = _interopRequireDefault(_es6Promise);

  var _qs2 = _interopRequireDefault(_qs);

  var _merge2 = _interopRequireDefault(_merge);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  function _defineProperty(obj, key, value) {
    if (key in obj) {
      Object.defineProperty(obj, key, {
        value: value,
        enumerable: true,
        configurable: true,
        writable: true
      });
    } else {
      obj[key] = value;
    }

    return obj;
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
      key: 'createEndpointFetcher',
      value: function createEndpointFetcher(endpointSpec) {
        var _this = this;

        function encode(contentType, data) {
          switch (contentType) {
            case 'application/x-www-form-urlencoded':
              return _qs2.default.stringify(data);

            case 'application/json':
            case 'application/x-json':
            case 'application/x-javascript':
            case 'text/javascript':
            case 'text/x-javascript':
            case 'text/x-json':
              return JSON.stringify(data);

            default:
              throw new Error('Can\'t encode format: ' + contentType);
          }
        }

        function decode(contentType, text) {
          switch (contentType) {
            case 'application/x-www-form-urlencoded':
              return _qs2.default.parse(text);

            case 'application/json':
            case 'application/x-json':
            case 'application/x-javascript':
            case 'text/javascript':
            case 'text/x-javascript':
            case 'text/x-json':
              return JSON.parse(text);

            default:
              throw new Error('Can\'t decode format: ' + contentType);
          }
        }

        function addQuerystring(url, querystring) {
          if (querystring === '') {
            return url;
          }

          if (url.match(/\?/)) {
            return url + '&' + querystring;
          }

          return url + '?' + querystring;
        }

        function parseResponse(response) {
          return response.text().then(function (body) {
            return decode(response.headers.get('Content-Type'), body);
          });
        }

        function applySchemaToData(payloadSchema, data) {
          return Object.keys(data).reduce(function (prev, key) {
            var schema = payloadSchema[key];

            if (schema && (schema.remove === true || schema.querystring === true)) {
              return prev;
            }

            return Object.assign(prev, _defineProperty({}, key, data[key]));
          }, {});
        }

        function applySchemaToUrl(payloadSchema, url, data) {
          var opts = arguments.length <= 3 || arguments[3] === undefined ? { setFromData: false } : arguments[3];

          var newUrl = url;

          var queryData = Object.keys(data).reduce(function (prev, key) {
            var schema = payloadSchema[key];
            var includeThroughSetFromData = opts.setFromData === true && !(schema && schema.remove === true);
            var includeThroughSpec = schema && schema.querystring === true && schema.remove !== true;
            if (includeThroughSetFromData || includeThroughSpec) {
              return Object.assign(prev, _defineProperty({}, key, data[key]));
            }

            return prev;
          }, {});

          newUrl = addQuerystring(newUrl, encode('application/x-www-form-urlencoded', queryData));

          newUrl = Object.keys(payloadSchema).reduce(function (prev, key) {
            var replacement = payloadSchema[key].urlReplacement;
            if (replacement) {
              return prev.replace(replacement, data[key]);
            }

            return prev;
          }, newUrl);

          return newUrl;
        }

        var refinedSpec = Object.assign({
          method: 'get',
          payloadFormat: 'application/x-www-form-urlencoded',
          responseFormat: 'application/json',
          payloadSchema: {},
          defaultData: {}
        }, endpointSpec);

        var formatShortcuts = {
          json: 'application/json',
          urlencoded: 'application/x-www-form-urlencoded'
        };
        ['payloadFormat', 'responseFormat'].forEach(function (key) {
          if (formatShortcuts[refinedSpec[key]]) refinedSpec[key] = formatShortcuts[refinedSpec[key]];
        });

        return function () {
          var data = arguments.length <= 0 || arguments[0] === undefined ? {} : arguments[0];

          var headers = {
            Accept: refinedSpec.responseFormat,
            'Content-Type': refinedSpec.payloadFormat
          };

          var mergedData = _merge2.default.recursive({}, refinedSpec.defaultData, data);

          var url = applySchemaToUrl(refinedSpec.payloadSchema, refinedSpec.url, mergedData, { setFromData: refinedSpec.method.toLowerCase() === 'get' });

          var encodedData = encode(refinedSpec.payloadFormat, applySchemaToData(refinedSpec.payloadSchema, mergedData));

          var args = refinedSpec.method.toLowerCase() === 'get' ? [url, headers] : [url, encodedData, headers];

          return _this[refinedSpec.method].apply(_this, args).then(parseResponse);
        };
      }
    }, {
      key: 'get',
      value: function get(url) {
        var headers = arguments.length <= 1 || arguments[1] === undefined ? {} : arguments[1];

        return this.fetch(url, {
          method: 'get',
          credentials: 'same-origin',
          headers: headers
        }).then(checkStatus);
      }
    }, {
      key: 'post',
      value: function post(url) {
        var data = arguments.length <= 1 || arguments[1] === undefined ? {} : arguments[1];
        var headers = arguments.length <= 2 || arguments[2] === undefined ? {} : arguments[2];

        var defaultHeaders = { 'Content-Type': 'application/x-www-form-urlencoded' };
        return this.fetch(url, {
          method: 'post',
          headers: Object.assign({}, defaultHeaders, headers),
          credentials: 'same-origin',
          body: data
        }).then(checkStatus);
      }
    }, {
      key: 'put',
      value: function put(url) {
        var data = arguments.length <= 1 || arguments[1] === undefined ? {} : arguments[1];
        var headers = arguments.length <= 2 || arguments[2] === undefined ? {} : arguments[2];

        return this.fetch(url, { method: 'put', credentials: 'same-origin', body: data, headers: headers }).then(checkStatus);
      }
    }, {
      key: 'delete',
      value: function _delete(url) {
        var data = arguments.length <= 1 || arguments[1] === undefined ? {} : arguments[1];
        var headers = arguments.length <= 2 || arguments[2] === undefined ? {} : arguments[2];

        return this.fetch(url, { method: 'delete', credentials: 'same-origin', body: data, headers: headers }).then(checkStatus);
      }
    }]);

    return SilverStripeBackend;
  }();

  var backend = new SilverStripeBackend();

  exports.default = backend;
});