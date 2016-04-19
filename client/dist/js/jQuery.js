(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define('ss.jQuery', ['module'], factory);
  } else if (typeof exports !== "undefined") {
    factory(module);
  } else {
    var mod = {
      exports: {}
    };
    factory(mod);
    global.ssJQuery = mod.exports;
  }
})(this, function (module) {
  'use strict';

  var jQuery = typeof window.jQuery !== 'undefined' ? window.jQuery : null;

  module.exports = jQuery;
});