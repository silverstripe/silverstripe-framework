if(typeof(ss) == 'undefined') ss = {};

/*
 * Lightweight clientside i18n implementation.
 * Caution: Only available after DOM loaded because we need to detect the language
 * 
 * For non-i18n stub implementation, see framework/javascript/i18nx.js
 * 
 * Based on jQuery i18n plugin: 1.0.0  Feb-10-2008
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 * Based on 'javascript i18n that almost doesn't suck' by markos
 * http://markos.gaivo.net/blog/?p=100
 */
ss.i18n = {
	
	currentLocale: null,
	
	defaultLocale: 'en_US',
	
	lang: {},

	inited: false,
	
	init: function() {
		if(this.inited) return;

		this.currentLocale = this.detectLocale();
		this.inited = true;
	},
	
	/**
	 * set_locale()
	 * Set locale in long format, e.g. "de_AT" for Austrian German.
	 * @param string locale
	 */
	setLocale: function(locale) {
		this.currentLocale = locale;
	},
	
	/**
	 * getLocale()
	 * Get locale in long format. Falls back to i18n.defaut_locale.
	 * @return string
	 */
	getLocale: function() {
		return (this.currentLocale) ? this.currentLocale : this.defaultLocale;
	},
	
	/**
	 * _()
	 * The actual translation function. Looks the given string up in the 
	 * dictionary and returns the translation if one exists. If a translation 
	 * is not found, returns the original word
	 *
	 * @param string entity A "long" locale format, e.g. "de_DE" (Required)
	 * @param string fallbackString (Required)
	 * @param int priority (not used)
	 * @param string context Give translators context for the string
	 * @return string : Translated word
	 *
	 */
		_t: function (entity, fallbackString, priority, context) {
			this.init();

			var langName = this.getLocale().replace(/_[\w]+/i, '');
			var defaultlangName = this.defaultLocale.replace(/_[\w]+/i, '');
			
			if (this.lang && this.lang[this.getLocale()] && this.lang[this.getLocale()][entity]) {
				return this.lang[this.getLocale()][entity];
			} else if (this.lang && this.lang[langName] && this.lang[langName][entity]) {
				return this.lang[langName][entity];
			} else if (this.lang && this.lang[this.defaultLocale] && this.lang[this.defaultLocale][entity]) {
				return this.lang[this.defaultLocale][entity];
			} else if (this.lang && this.lang[defaultlangName] && this.lang[defaultlangName][entity]) {
				return this.lang[defaultlangName][entity]; 
			} else if(fallbackString) {
				return fallbackString;
			} else {
				return '';
			}
		},
		
		/**
		 * Add entities to a dictionary. If a dictionary doesn't
		 * exist for this locale, its automatically created.
		 * Existing entities are overwritten.
		 * 
		 * @param string locale
		 * @param Object dict
		 */
		addDictionary: function(locale, dict) {
			if(!this.lang[locale]) this.lang[locale] = {};
			for(entity in dict) {
				this.lang[locale][entity] = dict[entity];
			}
		},
		
		/**
		 * Get dictionary for a specific locale.
		 * 
		 * @param string locale
		 */
		getDictionary: function(locale) {
			return this.lang[locale];
		},
	
	/**
	 * stripStr()
	 *
	 * @param string str : The string to strip
	 * @return string result : Stripped string
	 *
	 */
		stripStr: function(str) {
			return str.replace(/^\s*/, "").replace(/\s*$/, "");
		},
	
	/**
	 * stripStrML()
	 *
	 * @param string str : The multi-line string to strip
	 * @return string result : Stripped string
	 *
	 */
		stripStrML: function(str) {
			// Split because m flag doesn't exist before JS1.5 and we need to
			// strip newlines anyway
			var parts = str.split('\n');
			for (var i=0; i<parts.length; i++)
				parts[i] = stripStr(parts[i]);
	
			// Don't join with empty strings, because it "concats" words
			// And strip again
			return stripStr(parts.join(" "));
		},

	/*
	 * printf()
	 * C-printf like function, which substitutes %s with parameters
	 * given in list. %%s is used to escape %s.
	 *
	 * Doesn't work in IE5.0 (splice)
	 *
	 * @param string S : string to perform printf on.
	 * @param string L : Array of arguments for printf()
	 */
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
		
		/**
		 * Detect document language settings by looking at <meta> tags.
		 * If no match is found, returns this.defaultLocale.
		 * 
		 * @todo get by <html lang=''> - needs modification of SSViewer
		 * 
		 * @return string Locale in mixed lowercase/uppercase format suitable
		 * for usage in ss.i18n.lang arrays (e.g. 'en_US').
		 */
		detectLocale: function() {
			var rawLocale;
			var detectedLocale;

			// get by container tag
			rawLocale = jQuery('body').attr('lang');
		
			// get by meta
			if(!rawLocale) {
				var metas = document.getElementsByTagName('meta');
				for(var i=0; i<metas.length; i++) {
					if(metas[i].attributes['http-equiv'] && metas[i].attributes['http-equiv'].nodeValue.toLowerCase() == 'content-language') {
						rawLocale = metas[i].attributes['content'].nodeValue;
					}
				}
			}
			
			// fallback to default locale
			if(!rawLocale) rawLocale = this.defaultLocale;
			
			var rawLocaleParts = rawLocale.match(/([^-|_]*)[-|_](.*)/);
			// get locale (e.g. 'en_US') from common name (e.g. 'en')
			// by looking at ss.i18n.lang tables
			if(rawLocale.length == 2) {
				for(compareLocale in ss.i18n.lang) {
					if(compareLocale.substr(0,2).toLowerCase() == rawLocale.toLowerCase()) {
						detectedLocale = compareLocale;
						break;
					}
				}
			} else if(rawLocaleParts) {
				detectedLocale = rawLocaleParts[1].toLowerCase() + '_' + rawLocaleParts[2].toUpperCase();
			}
			
			return detectedLocale;
		},
		
		/**
		 * Attach an event listener to the given object.
		 * Modeled after behaviour.js, but externalized
		 * to keep the i18n library standalone for now.
		 */
		addEvent: function(obj, evType, fn, useCapture){
			if (obj.addEventListener){
				obj.addEventListener(evType, fn, useCapture);
				return true;
			} else if (obj.attachEvent){
				var r = obj.attachEvent("on"+evType, fn);
				return r;
			} else {
				alert("Handler could not be attached");
			}
		}
};



ss.i18n.addEvent(window, "load", function() {
	ss.i18n.init();
});
