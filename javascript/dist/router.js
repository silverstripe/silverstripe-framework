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
            el.href = path;

            return pageShow(el.pathname, state, dispatch, push);
        };
    }

    _page2.default.show = show(_page2.default.show);

    exports.default = _page2.default;
});