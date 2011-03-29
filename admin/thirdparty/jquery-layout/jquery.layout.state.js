/*
 *	LAYOUT STATE MANAGEMENT
 *
 *	@requires json2.js - http://www.json.org/json2.js
 *
 *	@example layoutState.options = { layoutName: "myLayout", keys: "west.isClosed,east.isClosed" }
 *	@example layoutState.save( "myLayout", "west.isClosed,north.size,south.isHidden", {expires: 7} );
 *	@example layoutState.load( "myLayout" );
 *	@example layoutState.clear();
 *	@example var hash_SavedState = layoutState.load();
 *	@example var hash_SavedState = layoutState.data;
 */
var layoutState = {

	options: {
		layoutName:	'myLayout' // default name (optional)

	//	*** IMPORTANT *** specify your keys in same format as your layout options...
	/*	Sub-Key-Format State Options
	,	keys:		'north.size,south.size,east.size,west.size,' +
					'north.isClosed,south.isClosed,east.isClosed,west.isClosed,' +
					'north.isHidden,south.isHidden,east.isHidden,west.isHidden'
	*/
	//	Flat-Format State Options
	,	keys:		'north__size,south__size,east__size,west__size,' +
					'north__isClosed,south__isClosed,east__isClosed,west__isClosed,' +
					'north__isHidden,south__isHidden,east__isHidden,west__isHidden'
	// Cookie Options
	,	domain:		''
	,	path:		''
	,	expires:	''	// 'days' to keep cookie - leave blank for 'session cookie'
	,	secure:		false
	}

,	data: {}

,	clear: function (layoutName) {
		this.save( layoutName, 'dummyKey', { expires: -1 });
	}

,	save: function (layoutName, keys, opts) {
	console.log('save');
		var
			o = jQuery.extend( {}, this.options, opts||{} )
		,	layout = window[ layoutName || o.layoutName ]
		;
		if (!keys) keys = o.keys;
		if (typeof keys == 'string') keys = keys.split(',');
		if (!layout || !layout.state || !keys.length) return false;

		var
			isNum	= typeof o.expires == 'number'
		,	date	= new Date()
		,	params	= ''
		,	clear	= false
		;
		if (isNum || o.expires.toUTCString) {
			if (isNum) {
				if (o.expires <= 0) {
					date.setYear(1970);
					clear = true;
				}
				else
					date.setTime(date.getTime() + (o.expires * 24 * 60 * 60 * 1000));
			}
			else
				date = o.expires;
			// use expires attribute, max-age is not supported by IE
			params += ';expires='+ date.toUTCString();
		}
		if (o.path)		params += ';path='+ o.path;
		if (o.domain)	params += ';domain='+ o.domain;
		if (o.secure)	params += ';secure';

		if (clear) {
			this.data = {}; // clear the data struct too
			document.cookie = (layoutName || o.layoutName) +'='+ params;
		}
		else {
			this.data = readState( layout, keys ); // read current panes-state
			document.cookie = (layoutName || o.layoutName) +'='+ encodeURIComponent(JSON.stringify(this.data)) + params;
			//alert( 'JSON.stringify(this.data) = '+ (layoutName || o.layoutName) +'='+ JSON.stringify( this.data ) );
		}
		return this.data;

		// SUB-ROUTINE
		function readState (layout, keys) {
			var
				state	= layout.state	// alias to the 'layout state'
			,	data	= {}
			,	panes	= 'north,south,east,west,center' // validation
			,	alt		= { isClosed: 'initClosed', isHidden: 'initHidden' }
			,	delim	= (keys[0].indexOf('__') > 0 ? '__' : '.')
			,	pair, pane, key, val
			;
			for (var i=0; i < keys.length; i++) {
				pair = keys[i].split(delim);
				pane = pair[0];
				key  = pair[1];
				if (panes.indexOf(pane) < 0) continue; // bad pane!
				if (key=='isClosed') // if 'temporarily open' (sliding), then isClosed=false, so...
					val = state[ pane ][ key ] || state[ pane ][ 'isSliding' ];
				else
					val = state[ pane ][ key ];
				if (val != undefined) {
					if (delim=='.') { // sub-key format
						if (!data[ pane ]) data[ pane ] = {};
						data[ pane ][ alt[key] ? alt[key] : key ] = val;
					}
					else // delim = '__' - flat-format
						data[ pane + delim + (alt[key] ? alt[key] : key) ] = val;
				}
			}
			return data;
		}
	}

,	load: function (layoutName) {
		if (!layoutName) layoutName = this.options.layoutName;
		if (!layoutName) return {};
		var
			data = {}
		,	c = document.cookie
		,	cs, pair, i // loop vars
		;
		if (c && c != '') {
			cs = c.split(';');
			for (i = 0; i < cs.length; i++) {
				c = jQuery.trim(cs[i]);
				pair = c.split('='); // name=value pair
				if (pair[0] == layoutName) { // this is the layout cookie
					data = JSON.parse(decodeURIComponent(pair[1]));
					break; // DONE
				}
			}
		}
		return (this.data = data);
	}

}