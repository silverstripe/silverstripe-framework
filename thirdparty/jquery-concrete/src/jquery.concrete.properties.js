(function($) {	

	var concrete_prepend = '__concrete!';
	
	var getConcreteData = function(el, namespace, property) {
		return el.data(concrete_prepend + namespace + '!' + property);
	}
	
	var setConcreteData = function(el, namespace, property, value) {
		return el.data(concrete_prepend + namespace + '!' + property, value);
	}
	
	var getConcreteDataAsHash = function(el, namespace) {
		var hash = {};
		var id = jQuery.data(el[0]);
		
		var matchstr = concrete_prepend + namespace + '!';
		var matchlen = matchstr.length;
		
		var cache = jQuery.cache[id];
		for (var k in cache) {
			if (k.substr(0,matchlen) == matchstr) hash[k.substr(matchlen)] = cache[k];
		}
		
		return hash;
	}
	
	var setConcreteDataFromHash = function(el, namespace, hash) {
		for (var k in hash) setConcreteData(namespace, k, hash[k]);
	}

	var concreteData = function(el, namespace, args) {
		switch (args.length) {
			case 0:
				return getConcreteDataAsHash(el, namespace);
			case 1:
				if (typeof args[0] == 'string') return getConcreteData(el, namespace, args[0]);
				else                            return setConcreteDataFromHash(el, namespace, args[0]);
			default:
				return setConcreteData(el, namespace, args[0], args[1]);
		}
	}
 
	$.extend($.fn, {
		concreteData: function() {
			return concreteData(this, '__base', arguments);
		}
	});
	
	$.concrete.Namespace.addHandler({
		order: 60,
		
		bind: function(selector, k, v) {
			if (k.charAt(0) != k.charAt(0).toUpperCase()) $.concrete.warn('Concrete property '+k+' does not start with a capital letter', $.concrete.WARN_LEVEL_BESTPRACTISE);

			var namespace = this;
			g = function() { return this.concreteData(k) || v ; }
			s = function(v){ return this.concreteData(k, v); }

			g.pname = s.pname = k;

			this.bind_proxy(selector, 'get'+k, g);
			this.bind_proxy(selector, 'set'+k, s);

			return true;
		},
		
		namespaceMethodOverrides: function(namespace){
			return {
				concreteData: function() {
					return concreteData(this, namespace.name, arguments);
				}
			};
		}
	});
	
})(jQuery);
