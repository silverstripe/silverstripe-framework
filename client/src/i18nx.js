/**
 * Stub implementation for i18n code.
 * Use instead of framework/javascript/src/i18n.js
 * if you want to use any SilverStripe javascript
 * without internationalization support.
 */
class i18nx {
	constructor() {
		this.currentLocale = 'en_US';
		this.defaultLocale = 'en_US';
	}

	_t(entity, fallbackString, priority, context) {
		return fallbackString;
	}

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

			return subMatch1 + params[i += 1];
		});
	}

	inject(s, map) {
		const regx = new RegExp('\{([A-Za-z0-9_]*)\}', 'g');

		return s.replace(regx, function (match, key, offset, string) {
			return (map[key]) ? map[key] : match;
		});
	}

	addDictionary() {

	}

	getDictionary() {

	}
};

let _i18nx = new i18nx();

export default _i18nx;
