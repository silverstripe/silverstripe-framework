(function($) {	

	var entwine_prepend = '__entwine!';
	
	var getEntwineData = function(el, namespace, property) {
		return el.data(entwine_prepend + namespace + '!' + property);
	};
	
	var setEntwineData = function(el, namespace, property, value) {
		return el.data(entwine_prepend + namespace + '!' + property, value);
	};
	
	var getEntwineDataAsHash = function(el, namespace) {
		var hash = {};
		var id = jQuery.data(el[0]);
		
		var matchstr = entwine_prepend + namespace + '!';
		var matchlen = matchstr.length;
		
		var cache = jQuery.cache[id];
		for (var k in cache) {
			if (k.substr(0,matchlen) == matchstr) hash[k.substr(matchlen)] = cache[k];
		}
		
		return hash;
	};
	
	var setEntwineDataFromHash = function(el, namespace, hash) {
		for (var k in hash) setEntwineData(namespace, k, hash[k]);
	};

	var entwineData = function(el, namespace, args) {
		switch (args.length) {
			case 0:
				return getEntwineDataAsHash(el, namespace);
			case 1:
				if (typeof args[0] == 'string') return getEntwineData(el, namespace, args[0]);
				else                            return setEntwineDataFromHash(el, namespace, args[0]);
			default:
				return setEntwineData(el, namespace, args[0], args[1]);
		}
	};
 
	$.extend($.fn, {
		entwineData: function() {
			return entwineData(this, '__base', arguments);
		}
	});
	
	$.entwine.Namespace.addHandler({
		order: 60,
		
		bind: function(selector, k, v) {
			if (k.charAt(0) != k.charAt(0).toUpperCase()) $.entwine.warn('Entwine property '+k+' does not start with a capital letter', $.entwine.WARN_LEVEL_BESTPRACTISE);

			// Create the getters and setters

			var getterName = 'get'+k;
			var setterName = 'set'+k;

			this.bind_proxy(selector, getterName, function() { var r = this.entwineData(k); return r === undefined ? v : r; });
			this.bind_proxy(selector, setterName, function(v){ return this.entwineData(k, v); });
			
			// Get the get and set proxies we just created
			
			var getter = this.injectee[getterName];
			var setter = this.injectee[setterName];
			
			// And bind in the jQuery-style accessor
			
			this.bind_proxy(selector, k, function(v){ return (arguments.length == 1 ? setter : getter).call(this, v) ; });

			return true;
		},
		
		namespaceMethodOverrides: function(namespace){
			return {
				entwineData: function() {
					return entwineData(this, namespace.name, arguments);
				}
			};
		}
	});
	
})(jQuery);
