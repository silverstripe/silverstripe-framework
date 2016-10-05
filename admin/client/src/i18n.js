/*
 * Lightweight clientside i18n implementation.
 * Caution: Only available after DOM loaded because we need to detect the language
 *
 * For non-i18n stub implementation, see framework/javascript/src/i18nx.js
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

class i18n {
	constructor() {
		this.defaultLocale = 'en_US';
		this.currentLocale = this.detectLocale();
		this.lang = {};
	}

	/**
	 * Set locale in long format, e.g. "de_AT" for Austrian German.
	 *
	 * @param string locale
	 */
	setLocale(locale) {
		this.currentLocale = locale;
	}

	/**
	 * Get locale in long format. Falls back to i18n.defaut_locale.
	 *
	 * @return string
	 */
	getLocale() {
		return this.currentLocale !== null ? this.currentLocale : this.defaultLocale;
	}

	/**
	 * The actual translation function. Looks the given string up in the
	 * dictionary and returns the translation if one exists. If a translation
	 * is not found, returns the original word.
	 *
	 * @param string entity - A "long" locale format, e.g. "de_DE" (Required)
	 * @param string fallbackString - (Required)
	 * @param int priority - (not used)
	 * @param string context - Give translators context for the string
	 * @return string : Translated word
	 */
	_t(entity, fallbackString, priority, context) {
		const langName = this.getLocale().replace(/_[\w]+/i, '');
		const defaultlangName = this.defaultLocale.replace(/_[\w]+/i, '');

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
	}

	/**
	 * Add entities to a dictionary. If a dictionary doesn't
	 * exist for this locale, its automatically created.
	 * Existing entities are overwritten.
	 *
	 * @param string locale
	 * @param Object dict
	 */
	addDictionary(locale, dict) {
		if (typeof this.lang[locale] === 'undefined') {
			this.lang[locale] = {};
		}

		for (let entity in dict) {
			this.lang[locale][entity] = dict[entity];
		}
	}

	/**
	 * Get dictionary for a specific locale.
	 *
	 * @param string locale
	 */
	getDictionary(locale) {
		return this.lang[locale];
	}

	/**
	 * @param string str - The string to strip.
	 * @return string result - Stripped string.
	 *
	 */
	stripStr(str) {
		return str.replace(/^\s*/, '').replace(/\s*$/, '');
	}

	/**
	 * @param string str - The multi-line string to strip.
	 * @return string result - Stripped string.
	 *
	 */
	stripStrML(str) {
		// Split because m flag doesn't exist before JS1.5 and we need to
		// strip newlines anyway
		var parts = str.split('\n');

		for (let i = 0; i < parts.length; i += 1) {
			parts[i] = stripStr(parts[i]);
		}

		// Don't join with empty strings, because it "concats" words
		// And strip again
		return stripStr(parts.join(' '));
	}

	/**
	 * Substitutes %s with parameters
 	 * given in list. %%s is used to escape %s.
 	 *
	 * @param string s - The string to perform the substitutions on.
	 * @return string - The new string with substitutions made.
	 */
	sprintf(s, ...params) {
		if (params.length === 0) {
			return s;
		}

		const regx = new RegExp('(.?)(%s)', 'g');

		let i = 0;

		return s.replace(regx, function (match, subMatch1, subMatch2, offset, string) {
			// skip %%s
			if (subMatch1 === '%') {
				return match;
			}

			return subMatch1 + params[i++];
		});
	}

	/**
	 * Substitutes variables with a list of injections.
 	 *
	 * @param string s - The string to perform the substitutions on.
	 * @param object map - An object with the substitions map e.g. {var: value}.
	 * @return string - The new string with substitutions made.
	 */
	inject(s, map) {
		const regx = new RegExp('\{([A-Za-z0-9_]*)\}', 'g');

		return s.replace(regx, function (match, key, offset, string) {
			return (map[key]) ? map[key] : match;
		});
	}

	/**
	 * Detect document language settings by looking at <meta> tags.
	 * If no match is found, returns this.defaultLocale.
	 *
	 * @todo get by <html lang=''> - needs modification of SSViewer
	 *
	 * @return string - Locale in mixed lowercase/uppercase format suitable
	 * for usage in i18n.lang arrays (e.g. 'en_US').
	 */
	detectLocale() {
		var rawLocale;
		var detectedLocale;

		// Get by container tag
		rawLocale = document.body.getAttribute('lang');

		// Get by meta
		if (!rawLocale) {
			var metas = document.getElementsByTagName('meta');

			for (var i=0; i<metas.length; i++) {
				if (metas[i].attributes['http-equiv'] && metas[i].attributes['http-equiv'].nodeValue.toLowerCase() == 'content-language') {
					rawLocale = metas[i].attributes['content'].nodeValue;
				}
			}
		}

		// Fallback to default locale
		if (!rawLocale) {
			rawLocale = this.defaultLocale;
		}

		var rawLocaleParts = rawLocale.match(/([^-|_]*)[-|_](.*)/);
		// Get locale (e.g. 'en_US') from common name (e.g. 'en')
		// by looking at i18n.lang tables
		if (rawLocale.length == 2) {
			for (let compareLocale in i18n.lang) {
				if (compareLocale.substr(0,2).toLowerCase() == rawLocale.toLowerCase()) {
					detectedLocale = compareLocale;
					break;
				}
			}
		} else if (rawLocaleParts) {
			detectedLocale = rawLocaleParts[1].toLowerCase() + '_' + rawLocaleParts[2].toUpperCase();
		}

		return detectedLocale;
	}

	/**
	 * Attach an event listener to the given object.
	 * Modeled after behaviour.js, but externalized
	 * to keep the i18n library standalone for now.
	 */
	addEvent(obj, evType, fn, useCapture) {
		if (obj.addEventListener) {
			obj.addEventListener(evType, fn, useCapture);
			return true;
		} else if (obj.attachEvent) {
			return obj.attachEvent('on' + evType, fn);
		} else {
			console.log('Handler could not be attached');
		}
	}
}

let _i18n = new i18n();

// This module has to support legacy loading...
window.ss = typeof window.ss !== 'undefined' ? window.ss : {};
window.ss.i18n = window.i18n = _i18n;

export default _i18n;
