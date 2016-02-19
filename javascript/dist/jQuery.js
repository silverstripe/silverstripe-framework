(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define('ss.jQuery', ['exports'], factory);
  } else if (typeof exports !== "undefined") {
    factory(exports);
  } else {
    var mod = {
      exports: {}
    };
    factory(mod.exports);
    global.ssJQuery = mod.exports;
  }
})(this, function (exports) {
  'use strict';

  Object.defineProperty(exports, "__esModule", {
    value: true
  });

  var jQuery = typeof window.jQuery !== 'undefined' ? window.jQuery : null;

  exports.default = jQuery;
});