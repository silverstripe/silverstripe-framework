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

			var nS = "";
			var tS = S.split("%s");
			
			var args = [];
			for (var i=1, len = arguments.length; i <len; ++i) {
				args.push(arguments[i]);
			};

			for(var i=0; i<args.length; i++) {
				if (tS[i].lastIndexOf('%') == tS[i].length-1 && i != args.length-1)
					tS[i] += "s"+tS.splice(i+1,1)[0];
				nS += tS[i] + args[i];
			}
			return nS + tS[tS.length-1];
		},
		
		// stub methods
		addDictionary: function() {},
		getDictionary: function() {}
};