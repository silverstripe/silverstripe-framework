(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define('ss.router', ['exports', 'page.js', 'url'], factory);
  } else if (typeof exports !== "undefined") {
    factory(exports, require('page.js'), require('url'));
  } else {
    var mod = {
      exports: {}
    };
    factory(mod.exports, global.page, global.url);
    global.ssRouter = mod.exports;
  }
})(this, function (exports, _page, _url) {
  'use strict';

  Object.defineProperty(exports, "__esModule", {
    value: true
  });

  var _page2 = _interopRequireDefault(_page);

  var _url2 = _interopRequireDefault(_url);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  function resolveURLToBase(path) {
    var absoluteBase = this.getAbsoluteBase();
    var absolutePath = _url2.default.resolve(absoluteBase, path);

    if (absolutePath.indexOf(absoluteBase) !== 0) {
      return absolutePath;
    }

    return absolutePath.substring(absoluteBase.length - 1);
  }

  function show(pageShow) {
    return function (path, state, dispatch, push) {
      return pageShow(_page2.default.resolveURLToBase(path), state, dispatch, push);
    };
  }

  function routeAppliesToCurrentLocation(route) {
    var r = new _page2.default.Route(route);
    return r.match(_page2.default.current, {});
  }

  function getAbsoluteBase() {
    var baseTags = window.document.getElementsByTagName('base');
    if (baseTags && baseTags[0]) {
      return baseTags[0].href;
    }
    return null;
  }

  _page2.default.getAbsoluteBase = getAbsoluteBase.bind(_page2.default);
  _page2.default.resolveURLToBase = resolveURLToBase.bind(_page2.default);
  _page2.default.show = show(_page2.default.show);
  _page2.default.routeAppliesToCurrentLocation = routeAppliesToCurrentLocation;

  exports.default = _page2.default;
});