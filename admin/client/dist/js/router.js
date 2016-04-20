(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define('ss.router', ['exports', 'page.js'], factory);
  } else if (typeof exports !== "undefined") {
    factory(exports, require('page.js'));
  } else {
    var mod = {
      exports: {}
    };
    factory(mod.exports, global.page);
    global.ssRouter = mod.exports;
  }
})(this, function (exports, _page) {
  'use strict';

  Object.defineProperty(exports, "__esModule", {
    value: true
  });

  var _page2 = _interopRequireDefault(_page);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  /**
   * Wrapper for `page.show()` with SilverStripe specific behaviour.
   */
  function show(pageShow) {
    return function (path, state, dispatch, push) {
      console.log('wrapper');
      return pageShow(path, state, dispatch, push);
    };
  } /**
     * Handles client-side routing.
     * See https://github.com/visionmedia/page.js
     */


  _page2.default.show = show(_page2.default.show);

  exports.default = _page2.default;
});