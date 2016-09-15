(function($) {	
	
	// Adds back concrete methods for backwards compatibility
	$.concrete = $.entwine;
	$.fn.concrete = $.fn.entwine;
	$.fn.concreteData = $.fn.entwineData;
	
	// Use addHandler to hack in the namespace.$.concrete equivilent to the namespace.$.entwine namespace-injection
	$.entwine.Namespace.addHandler({
		order: 100,
		bind: function(selector, k, v) { return false; },
	
		namespaceMethodOverrides: function(namespace){
			namespace.$.concrete = namespace.$.entwine;
			namespace.injectee.concrete = namespace.injectee.entwine;
			namespace.injectee.concreteData = namespace.injectee.entwineData;
			return {};
		}
	});

})(jQuery);
