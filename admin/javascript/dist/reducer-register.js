(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define('ss.reducer-register', ['exports'], factory);
  } else if (typeof exports !== "undefined") {
    factory(exports);
  } else {
    var mod = {
      exports: {}
    };
    factory(mod.exports);
    global.ssReducerRegister = mod.exports;
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

  var register = {};

  var ReducerRegister = function () {
    function ReducerRegister() {
      _classCallCheck(this, ReducerRegister);
    }

    _createClass(ReducerRegister, [{
      key: 'add',
      value: function add(key, reducer) {
        if (typeof register[key] !== 'undefined') {
          throw new Error('Reducer already exists at \'' + key + '\'');
        }

        register[key] = reducer;
      }
    }, {
      key: 'getAll',
      value: function getAll() {
        return register;
      }
    }, {
      key: 'getByKey',
      value: function getByKey(key) {
        return register[key];
      }
    }, {
      key: 'remove',
      value: function remove(key) {
        delete register[key];
      }
    }]);

    return ReducerRegister;
  }();

  var reducerRegister = new ReducerRegister();

  exports.default = reducerRegister;
});