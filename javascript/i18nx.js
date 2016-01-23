if(typeof(ss) == 'undefined') ss = {};

/**
 * Stub implementation for ss.i18n code.
 * Use instead of framework/javascript/i18n.js
 * if you want to use any SilverStripe javascript
 * without internationalization support.
 */
ss.i18n = {
		currentLocale: 'en_US',

		defaultLocale: 'en_US',

		_t: function (entity, fallbackString, priority, context) {
			return fallbackString;
		},

		sprintf: function(S) {
			if (arguments.length == 1) return S;

			var args  = [],
					len   = arguments.length,
					index = 0,
					regx  = new RegExp('(.?)(%s)', 'g'),
					result;

			for (var i=1; i<len; ++i) {
				args.push(arguments[i]);
			};

			result = S.replace(regx, function(match, subMatch1, subMatch2, offset, string){
				if (subMatch1 == '%') return match; // skip %%s
				return subMatch1 + args[index++];
			});

			return result;
		},

		inject: function(S, map) {
			var regx = new RegExp("\{([A-Za-z0-9_]*)\}", "g"),
					result;

			result = S.replace(regx, function(match, key, offset, string){
				return (map[key]) ? map[key] : match;
			});

			return result;
		},

		// stub methods
		addDictionary: function() {},
		getDictionary: function() {}
};
