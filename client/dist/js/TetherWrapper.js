(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define('ss.TetherWrapper', ['exports', 'tether'], factory);
  } else if (typeof exports !== "undefined") {
    factory(exports, require('tether'));
  } else {
    var mod = {
      exports: {}
    };
    factory(mod.exports, global.tether);
    global.ssTetherWrapper = mod.exports;
  }
})(this, function (exports, _tether) {
  'use strict';

  Object.defineProperty(exports, "__esModule", {
    value: true
  });

  var _tether2 = _interopRequireDefault(_tether);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  window.Tether = _tether2.default;
  exports.default = _tether2.default;
});