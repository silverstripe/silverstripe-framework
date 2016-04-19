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

  function show(pageShow) {
    return function (path, state, dispatch, push) {
      var el = document.createElement('a');
      var pathWithSearch = void 0;
      el.href = path;
      pathWithSearch = el.pathname;
      if (el.search) {
        pathWithSearch += el.search;
      }

      return pageShow(pathWithSearch, state, dispatch, push);
    };
  }

  function routeAppliesToCurrentLocation(route) {
    var r = new _page2.default.Route(route);
    return r.match(_page2.default.current, {});
  }

  _page2.default.show = show(_page2.default.show);
  _page2.default.routeAppliesToCurrentLocation = routeAppliesToCurrentLocation;

  exports.default = _page2.default;
});