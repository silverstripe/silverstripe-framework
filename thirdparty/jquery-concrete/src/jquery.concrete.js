var console;

(function($) {	
	
	var namespaces = {};

	$.concrete = function() {
		$.fn.concrete.apply(null, arguments);
	}
	
	/**
	 * A couple of utility functions for accessing the store outside of this closure, and for making things
	 * operate in a little more easy-to-test manner
	 */
	$.extend($.concrete, {
		/**
		 * Get all the namespaces. Useful for introspection? Internal interface of Namespace not guaranteed consistant
		 */
		namespaces: namespaces,
		
		/**
		 * Remove all concrete rules
		 */
		clear_all_rules: function() { 
			// Remove proxy functions
			for (var k in $.fn) { if ($.fn[k].concrete) delete $.fn[k] ; }
			// Remove namespaces, and start over again
			namespaces = $.concrete.namespaces = {};
		},
		
		WARN_LEVEL_NONE: 0,
		WARN_LEVEL_IMPORTANT: 1,
		WARN_LEVEL_BESTPRACTISE: 2,
		
		/** 
		 * Warning level. Set to a higher level to get warnings dumped to console.
		 */
		warningLevel: 0,
		
		/** Utility to optionally display warning messages depending on level */
		warn: function(message, level) {
			if (level <= $.concrete.warningLevel && console && console.log) { 
				console.warn(message);
				if (console.trace) console.trace();
			}
		}
	});
	

	/** Stores a count of definitions, so that we can sort identical selectors by definition order */
	var rulecount = 0;
	
	var Rule = Base.extend({
		init: function(selector, name) {
			this.selector = selector;
			this.specifity = selector.specifity();
			this.important = 0;
			this.name = name;
			this.rulecount = rulecount++;
		}
	});
	
	Rule.compare = function(a, b) {
		var as = a.specifity, bs = b.specifity;
		
		return (a.important - b.important) ||
		       (as[0] - bs[0]) ||
		       (as[1] - bs[1]) ||
		       (as[2] - bs[2]) ||
		       (a.rulecount - b.rulecount) ;
	}

	$.concrete.RuleList = function() {
		var list = [];
		
		list.addRule = function(selector, name){ 
			var rule = Rule(selector, name);
			
			list[list.length] = rule; 
			list.sort(Rule.compare); 
			
			return rule;
		};
		
		return list;
	}

	var handlers = [];
	
	/**
	 * A Namespace holds all the information needed for adding concrete methods to a namespace (including the _null_ namespace)
	 */
	$.concrete.Namespace = Base.extend({
		init: function(name){
			if (name && !name.match(/^[A-Za-z0-9.]+$/)) $.concrete.warn('Concrete namespace '+name+' is not formatted as period seperated identifiers', $.concrete.WARN_LEVEL_BESTPRACTISE);
			name = name || '__base';
			
			this.name = name;
			this.store = {};
			
			namespaces[name] = this;
			
			if (name == "__base") {
				this.injectee = $.fn
				this.$ = $;
			}
			else {
				// We're in a namespace, so we build a Class that subclasses the jQuery Object Class to inject namespace functions into
				var subfn = function(){}
				this.injectee = subfn.prototype = new $();
				
				// And then we provide an overriding $ that returns objects of our new Class, and an overriding pushStack to catch further selection building
				var bound$ = this.$ = function(a) {
					// Try the simple way first
					var jq = $.fn.init.apply(new subfn(), arguments);
					if (jq instanceof subfn) return jq;
					
					// That didn't return a bound object, so now we need to copy it
					var rv = new subfn();
					rv.selector = jq.selector; rv.context = jq.context; var i = rv.length = jq.length;
					while (i--) rv[i] = jq[i];
					return rv;
				}
				this.injectee.pushStack = function(elems, name, selector){
					var ret = bound$(elems);

					// Add the old object onto the stack (as a reference)
					ret.prevObject = this;
					ret.context = this.context;
					
					if ( name === "find" ) ret.selector = this.selector + (this.selector ? " " : "") + selector;
					else if ( name )       ret.selector = this.selector + "." + name + "(" + selector + ")";
					
					// Return the newly-formed element set
					return ret;
				}
				
				// Copy static functions through from $ to this.$ so e.g. $.ajax still works
				// @bug, @cantfix: Any class functions added to $ after this call won't get mirrored through 
				$.extend(this.$, $);
				
				// We override concrete to inject the name of this namespace when defining blocks inside this namespace
				var concrete_wrapper = this.injectee.concrete = function(spacename) {
					var args = arguments;
					
					if (!spacename || typeof spacename != 'string') { args = $.makeArray(args); args.unshift(name); }
					else if (spacename.charAt(0) != '.') args[0] = name+'.'+spacename;
					
					return $.fn.concrete.apply(this, args);
				}
				
				this.$.concrete = function() {
					concrete_wrapper.apply(null, arguments);
				}
				
				for (var i = 0; i < handlers.length; i++) {
					var handler = handlers[i], builder;
					
					// Inject jQuery object method overrides
					if (builder = handler.namespaceMethodOverrides) {
						var overrides = builder(this);
						for (var k in overrides) this.injectee[k] = overrides[k];
					}
					
					// Inject $.concrete function overrides
					if (builder = handler.namespaceStaticOverrides) {
						var overrides = builder(this);
						for (var k in overrides) this.$.concrete[k] = overrides[k];
					}
				}
			}
		},
		
		/**
		 * Returns a function that does selector matching against the function list for a function name
		 * Used by proxy for all calls, and by ctorProxy to handle _super calls
		 * @param {String} name - name of the function as passed in the construction object
		 * @param {String} funcprop - the property on the Rule object that gives the actual function to call
		 */
		one: function(name, funcprop) {
			var namespace = this;
			var funcs = this.store[name];
			
			var one = function(el, args, i){
				if (i === undefined) i = funcs.length;
				while (i--) {
					if (funcs[i].selector.matches(el)) {
						var ret, tmp_i = el.i, tmp_f = el.f;
						el.i = i; el.f = one;
						try { ret = funcs[i][funcprop].apply(namespace.$(el), args); }
						finally { el.i = tmp_i; el.f = tmp_f; }
						return ret;
					}
				}
			}
			
			return one;
		},
		
		/**
		 * A proxy is a function attached to a callable object (either the base jQuery.fn or a subspace object) which handles
		 * finding and calling the correct function for each member of the current jQuery context
		 * @param {String} name - name of the function as passed in the construction object
		 */
		build_proxy: function(name) {
			var one = this.one(name, 'func');
			
			var prxy = function() {
				var rv, ctx = $(this); 
				
				var i = ctx.length;
				while (i--) rv = one(ctx[i], arguments);
				return rv;
			};
			
			return prxy;
		},
		
		bind_proxy: function(selector, name, func) {
			var rulelist = this.store[name] || (this.store[name] = $.concrete.RuleList());
			
			var rule = rulelist.addRule(selector, name); rule.func = func;
			
			if (!this.injectee.hasOwnProperty(name)) {
				this.injectee[name] = this.build_proxy(name);
				this.injectee[name].concrete = true;
			}

			if (!this.injectee[name].concrete) {
				$.concrete.warn('Warning: Concrete function '+name+' clashes with regular jQuery function - concrete function will not be callable directly on jQuery object', $.concrete.WARN_LEVEL_IMPORTANT);
			}
		},
		
		add: function(selector, data) {
			// For every item in the hash, try ever method handler, until one returns true
			for (var k in data) {
				var v = data[k];
				
				for (var i = 0; i < handlers.length; i++) {
					if (handlers[i].bind && handlers[i].bind.call(this, selector, k, v)) break;
				}
			}
		},
		
		has: function(ctx, name) {
			var rulelist = this.store[name];
			if (!rulelist) return false;
			
			/* We go forward this time, since low specifity is likely to knock out a bunch of elements quickly */
			for (var i = 0 ; i < rulelist.length; i++) {
				ctx = ctx.not(rulelist[i].selector);
				if (!ctx.length) return true;
			}
			return false;
		}
	});
	
	/**
	 * A handler is some javascript code that adds support for some time of key / value pair passed in the hash to the Namespace add method.
	 * The default handlers provided (and included by default) are event, ctor and properties
	 */
	$.concrete.Namespace.addHandler = function(handler) {
		for (var i = 0; i < handlers.length && handlers[i].order < handler.order; i++) { /* Pass */ }
		handlers.splice(i, 0, handler);
	}
	
	$.concrete.Namespace.addHandler({
		order: 50,
		
		bind: function(selector, k, v){
			if ($.isFunction(v)) {
				this.bind_proxy(selector, k, v);
				return true;
			}
		}
	});

	$.extend($.fn, {
		/**
		 * Main concrete function. Used for new definitions, calling into a namespace (or forcing the base namespace) and entering a using block
		 * 
		 */
		concrete: function(spacename) {
			var i = 0;
			var selector = this.selector ? $.selector(this.selector) : null;
		
			/* By default we operator on the base namespace */
			var namespace = namespaces.__base || $.concrete.Namespace();
			
			/* If the first argument is a string, then it's the name of a namespace. Look it up */
			if (typeof spacename == 'string') {
				if (spacename.charAt('0') == '.') spacename = spacename.substr(1);
				if (spacename) namespace = namespaces[spacename] || $.concrete.Namespace(spacename);
				i=1;
			}
		
			/* All remaining arguments should either be using blocks or definition hashs */
			while (i < arguments.length) {
				var res = arguments[i++];
				
				// If it's a function, call it - either it's a using block or it's a namespaced concrete definition
				if ($.isFunction(res)) {
					if (res.length != 1) $.concrete.warn('Function block inside concrete definition does not take $ argument properly', $.concrete.WARN_LEVEL_IMPORTANT);
					res = res.call(namespace.$(this), namespace.$);
				}
				
				// If we have a concrete definition hash, inject it into namespace
				if (res) {
					if (selector) namespace.add(selector, res);
					else $.concrete.warn('Concrete block given to concrete call without selector. Make sure you call $(selector).concrete when defining blocks', $.concrete.WARN_LEVEL_IMPORTANT);
				}
				
				
			}
		
			/* Finally, return the jQuery object 'this' refers to, wrapped in the new namespace */
			return namespace.$(this);
		},
		
		/** 
		 * Calls the next most specific version of the current concrete method
		 */
		_super: function(){
			var rv, i = this.length;
			while (i--) {
				var el = this[0];
				rv = el.f(el, arguments, el.i);
			}
			return rv;
		}
	});
	
})(jQuery);
