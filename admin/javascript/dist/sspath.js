(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.sspath', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssSspath = mod.exports;
	}
})(this, function (_jQuery) {
	'use strict';

	var _jQuery2 = _interopRequireDefault(_jQuery);

	function _interopRequireDefault(obj) {
		return obj && obj.__esModule ? obj : {
			default: obj
		};
	}

	var $window = (0, _jQuery2.default)(window),
	    $html = (0, _jQuery2.default)('html'),
	    $head = (0, _jQuery2.default)('head'),
	    path = {
		urlParseRE: /^(((([^:\/#\?]+:)?(?:(\/\/)((?:(([^:@\/#\?]+)(?:\:([^:@\/#\?]+))?)@)?(([^:\/#\?\]\[]+|\[[^\/\]@#?]+\])(?:\:([0-9]+))?))?)?)?((\/?(?:[^\/\?#]+\/+)*)([^\?#]*)))?(\?[^#]+)?)(#.*)?/,

		parseUrl: function parseUrl(url) {
			if (_jQuery2.default.type(url) === "object") {
				return url;
			}

			var matches = path.urlParseRE.exec(url || "") || [];

			return {
				href: matches[0] || "",
				hrefNoHash: matches[1] || "",
				hrefNoSearch: matches[2] || "",
				domain: matches[3] || "",
				protocol: matches[4] || "",
				doubleSlash: matches[5] || "",
				authority: matches[6] || "",
				username: matches[8] || "",
				password: matches[9] || "",
				host: matches[10] || "",
				hostname: matches[11] || "",
				port: matches[12] || "",
				pathname: matches[13] || "",
				directory: matches[14] || "",
				filename: matches[15] || "",
				search: matches[16] || "",
				hash: matches[17] || ""
			};
		},

		makePathAbsolute: function makePathAbsolute(relPath, absPath) {
			if (relPath && relPath.charAt(0) === "/") {
				return relPath;
			}

			relPath = relPath || "";
			absPath = absPath ? absPath.replace(/^\/|(\/[^\/]*|[^\/]+)$/g, "") : "";

			var absStack = absPath ? absPath.split("/") : [],
			    relStack = relPath.split("/");
			for (var i = 0; i < relStack.length; i++) {
				var d = relStack[i];
				switch (d) {
					case ".":
						break;
					case "..":
						if (absStack.length) {
							absStack.pop();
						}
						break;
					default:
						absStack.push(d);
						break;
				}
			}
			return "/" + absStack.join("/");
		},

		isSameDomain: function isSameDomain(absUrl1, absUrl2) {
			return path.parseUrl(absUrl1).domain === path.parseUrl(absUrl2).domain;
		},

		isRelativeUrl: function isRelativeUrl(url) {
			return path.parseUrl(url).protocol === "";
		},

		isAbsoluteUrl: function isAbsoluteUrl(url) {
			return path.parseUrl(url).protocol !== "";
		},

		makeUrlAbsolute: function makeUrlAbsolute(relUrl, absUrl) {
			if (!path.isRelativeUrl(relUrl)) {
				return relUrl;
			}

			var relObj = path.parseUrl(relUrl),
			    absObj = path.parseUrl(absUrl),
			    protocol = relObj.protocol || absObj.protocol,
			    doubleSlash = relObj.protocol ? relObj.doubleSlash : relObj.doubleSlash || absObj.doubleSlash,
			    authority = relObj.authority || absObj.authority,
			    hasPath = relObj.pathname !== "",
			    pathname = path.makePathAbsolute(relObj.pathname || absObj.filename, absObj.pathname),
			    search = relObj.search || !hasPath && absObj.search || "",
			    hash = relObj.hash;

			return protocol + doubleSlash + authority + pathname + search + hash;
		},

		addSearchParams: function addSearchParams(url, params) {
			var u = path.parseUrl(url),
			    params = typeof params === "string" ? path.convertSearchToArray(params) : params,
			    newParams = _jQuery2.default.extend(path.convertSearchToArray(u.search), params);
			return u.hrefNoSearch + '?' + _jQuery2.default.param(newParams) + (u.hash || "");
		},

		getSearchParams: function getSearchParams(url) {
			var u = path.parseUrl(url);
			return path.convertSearchToArray(u.search);
		},

		convertSearchToArray: function convertSearchToArray(search) {
			var params = {},
			    search = search.replace(/^\?/, ''),
			    parts = search ? search.split('&') : [],
			    i,
			    tmp;
			for (i = 0; i < parts.length; i++) {
				tmp = parts[i].split('=');
				params[tmp[0]] = tmp[1];
			}
			return params;
		},

		convertUrlToDataUrl: function convertUrlToDataUrl(absUrl) {
			var u = path.parseUrl(absUrl);
			if (path.isEmbeddedPage(u)) {
				return u.hash.split(dialogHashKey)[0].replace(/^#/, "");
			} else if (path.isSameDomain(u, document)) {
				return u.hrefNoHash.replace(document.domain, "");
			}
			return absUrl;
		},

		get: function get(newPath) {
			if (newPath === undefined) {
				newPath = location.hash;
			}
			return path.stripHash(newPath).replace(/[^\/]*\.[^\/*]+$/, '');
		},

		getFilePath: function getFilePath(path) {
			var splitkey = '&' + _jQuery2.default.mobile.subPageUrlKey;
			return path && path.split(splitkey)[0].split(dialogHashKey)[0];
		},

		set: function set(path) {
			location.hash = path;
		},

		isPath: function isPath(url) {
			return (/\//.test(url)
			);
		},

		clean: function clean(url) {
			return url.replace(document.domain, "");
		},

		stripHash: function stripHash(url) {
			return url.replace(/^#/, "");
		},

		cleanHash: function cleanHash(hash) {
			return path.stripHash(hash.replace(/\?.*$/, "").replace(dialogHashKey, ""));
		},

		isExternal: function isExternal(url) {
			var u = path.parseUrl(url);
			return u.protocol && u.domain !== document.domain ? true : false;
		},

		hasProtocol: function hasProtocol(url) {
			return (/^(:?\w+:)/.test(url)
			);
		}
	};

	_jQuery2.default.path = path;
});