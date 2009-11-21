var console;

(function($) {	

	/** What to call to run a function 'soon'. Normally setTimeout, but for syncronous mode we override so soon === now */
	var runSoon = window.setTimeout;
	
	/** Stores a count of definitions, so that we can sort identical selectors by definition order */
	var rulecount = 0;

	/** Utility to optionally display warning messages depending on level */
	var warn = function(message, level) {
		if (level <= $.concrete.warningLevel && console && console.log) { 
			console.warn(message);
			if (console.trace) console.trace();
		}
	}

   /** A property definition */
	$.property = function(options) {
		if (this instanceof $.property) this.options = options;
		else return new $.property(options);
	}
	$.extend($.property, {
		/**
		 * Strings for how to cast a value to a specific type. Used in some nasty meta-programming stuff below to try and
		 * keep property access as fast as possible
		 */
		casters: {
			'int': 'Math.round(parseFloat(v));',
			'float': 'parseFloat(v);',
			'string': '""+v;'
		},
		
		getter: function(options) {
			options = options || {};
			
			if (options.initial === undefined) return function(){ return this.d()[arguments.callee.pname] };
			
			var getter = function(){ 
				var d = this.d(); var k = arguments.callee.pname;
				return d.hasOwnProperty(k) ? d[k] : (d[k] = arguments.callee.initial);
			};
			var v = options.initial;
			getter.initial = options.restrict ? eval($.property.casters[options.restrict]) : v;
			
			return getter;
		},
		
		setter: function(options){
			options = options || {};
			if (options.restrict) {
				var restrict = options.restrict;
				return new Function('v', 'return this.d()[arguments.callee.pname] = ' + $.property.casters[options.restrict]);
			}
			
			return function(v){ return this.d()[arguments.callee.pname] = v; }
		}
	});
	$.extend($.property.prototype, {
		getter: function(){
			return $.property.getter(this.options);
		},
		setter: function(){
			return $.property.setter(this.options);
		}
	});

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

	$.fn._super = function(){
		var rv, i = this.length;
		while (i--) {
			var el = this[0];
			rv = el.f(el, arguments, el.i);
		}
		return rv;
	}

	var namespaces = {};

	var Namespace = Base.extend({
		init: function(name){
			if (name && !name.match(/^[A-Za-z0-9.]+$/)) warn('Concrete namespace '+name+' is not formatted as period seperated identifiers', $.concrete.WARN_LEVEL_BESTPRACTISE);
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
				var subfn = function(jq){
					this.selector = jq.selector; this.context = jq.context; this.setArray($.makeArray(jq));
				}
				this.injectee = subfn.prototype = new $();
				
				// And then we provide an overriding $ that returns objects of our new Class
				this.$ = function() {
					return new subfn($.apply(window, arguments));
				}
				// Copy static functions through from $ to this.$ so e.g. $.ajax still works
				// @bug, @cantfix: Any class functions added to $ after this call won't get mirrored through 
				$.extend(this.$, $);
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
			var funcs = this.store[name] || (this.store[name] = []) ;
			
			var rule = funcs[funcs.length] = Rule(selector, name); rule.func = func;
			funcs.sort(Rule.compare);
			
			if (!this.injectee.hasOwnProperty(name)) {
				this.injectee[name] = this.build_proxy(name);
				this.injectee[name].concrete = true;
			}

			if (!this.injectee[name].concrete) {
				warn('Warning: Concrete function '+name+' clashes with regular jQuery function - concrete function will not be callable directly on jQuery object', $.concrete.WARN_LEVEL_IMPORTANT);
			}
		},
		
		bind_event: function(selector, name, func, event) {
			var funcs = this.store[name] || (this.store[name] = []) ;
			
			var rule = funcs[funcs.length] = Rule(selector, name); rule.func = func;
			funcs.sort(Rule.compare);
			
			if (!funcs.proxy) { 
				funcs.proxy = this.build_proxy(name);
				$(selector.selector).live(event, funcs.proxy);
			}
		},
		
		bind_condesc: function(selector, name, func) {
			var ctors = this.store.ctors || (this.store.ctors = []) ;
			
			var rule;
			for (var i = 0 ; i < ctors.length; i++) {
				if (ctors[i].selector.selector == selector.selector) {
					rule = ctors[i]; break;
				}
			}
			if (!rule) {
				rule = ctors[ctors.length] = Rule(selector, 'ctors');
				ctors.sort(Rule.compare);
			}
			
			rule[name] = func;
			
			if (!ctors[name+'proxy']) {
				var one = this.one('ctors', name);
				var namespace = this;
				
				var proxy = function(els, i, func) {
					var j = els.length;
					while (j--) {
						var el = els[j];
						
						var tmp_i = el.i, tmp_f = el.f;
						el.i = i; el.f = one;
						try { func.call(namespace.$(el)); }
						catch(e) { el.i = tmp_i; el.f = tmp_f; }					
					}
				}
				
				ctors[name+'proxy'] = proxy;
			}
		},
		
		add: function(selector, data) {
			var k, v, match, event;
			
			for (k in data) {
				v = data[k];
				
				if ($.isFunction(v)) {
					if (k == 'onmatch' || k == 'onunmatch') {
						this.bind_condesc(selector, k, v);
					}
					else if (match = k.match(/^on(.*)/)) {
						event = match[1];
						
						if (!$.fn.liveHover && $.concrete.event_needs_extensions[event]) {
							warn('Event '+event+' requires live-extensions to function, which does not seem to be present', $.concrete.WARN_LEVEL_IMPORTANT);
						}
						else if (event == 'submit') {
							warn('Event submit not currently supported', $.concrete.WARN_LEVEL_IMPORTANT);
						}
						else if (event == 'focus' || event == 'blur') {
							warn('Event '+event+' not supported - use focusin / focusout instead', $.concrete.WARN_LEVEL_IMPORTANT);
						}
						
						this.bind_event(selector, k, v, event);
					}
					else {
						this.bind_proxy(selector, k, v);
					}
				}
				else {
					var g, s, p;

					if (k.charAt(0) != k.charAt(0).toUpperCase()) warn('Concrete property '+k+' does not start with a capital letter', $.concrete.WARN_LEVEL_BESTPRACTISE);
					
					if (v == $.property || v instanceof $.property) {
						g = v.getter(); s = v.setter();
					}
					else {
						p = $.property({initial: v}); g = p.getter(); s = p.setter(); 
					}
					
					g.pname = s.pname = k;
					this.bind_proxy(selector, k, g);
					this.bind_proxy(selector, 'set'+k, s);
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
	 * Main concrete function. Used for new definitions, calling into a namespace (or forcing the base namespace) and entering a using block
	 * 
	 */
	$.fn.concrete = function() {
		var i = 0;
		var selector = $.selector(this.selector);
		
		var namespace = namespaces.__base || Namespace();
		if (typeof arguments[i] == 'string') {
			namespace = namespaces[arguments[i]] || Namespace(arguments[i]);
			i++;
		}
		
		while (i < arguments.length) {
			var res = arguments[i];
			// If it's a function, call it - either it's a using block or it's a concrete definition builder
			if ($.isFunction(res)) {
				if (res.length != 1) warn('Function block inside concrete definition does not take $ argument properly', $.concrete.WARN_LEVEL_IMPORTANT);
				res = res.call(this, namespace.$);
			}
			else if (namespace.name != '__base') warn('Raw object inside namespaced ('+namespace.name+') concrete definition - namespace lookup will not work properly', $.concrete.WARN_LEVEL_IMPORTANT);
				
			// Now if we still have a concrete definition object, inject it into namespace
			if (res) namespace.add(selector, res);
			i++
		}
		
		return namespace.$(this);
	}
	
	/**
	 * A couple of utility functions for accessing the store outside of this closure, and for making things
	 * operate in a little more easy-to-test manner
	 */
	$.concrete = {
		
		/**
		 * Get all the namespaces. Usefull for introspection? Internal interface of Namespace not guaranteed consistant
		 */
		namespaces: function() { return namespaces; },
		
		/**
		 * Remove all concrete rules
		 */
		clear_all_rules: function() { 
			// Remove proxy functions
			for (var k in $.fn) { if ($.fn[k].concrete) delete $.fn[k] ; }
			// Remove namespaces, and start over again
			namespaces = [];
		},
		
		/**
		 * Make onmatch and onunmatch work in synchronous mode - that is, new elements will be detected immediately after
		 * the DOM manipulation that made them match. This is only really useful for during testing, since it's pretty slow
		 * (otherwise we'd make it the default).
		 */
		synchronous_mode: function() {
			if (check_id) clearTimeout(check_id); check_id = null;
			runSoon = function(func, delay){ func.call(this); return null; }
		},
		
		/**
		 * Trigger onmatch and onunmatch now - usefull for after DOM manipulation by methods other than through jQuery.
		 * Called automatically on document.ready
		 */
		triggerMatching: function() {
			matching();
		},
		
		WARN_LEVEL_NONE: 0,
		WARN_LEVEL_IMPORTANT: 1,
		WARN_LEVEL_BESTPRACTISE: 2,
		
		/** 
		 * Warning level. Set to a higher level to get warnings dumped to console.
		 */
		warningLevel: 0,
		
		/**
		 * These events need the live-extensions plugin
		 */
		event_needs_extensions: { mouseenter: true, mouseleave: true, change: true, focusin: true, focusout: true }
	}
	
	var check_id = null;

	/**
	 * Finds all the elements that now match a different rule (or have been removed) and call onmatch on onunmatch as appropriate
	 * 
	 * Because this has to scan the DOM, and is therefore fairly slow, this is normally triggered off a short timeout, so that
	 * a series of DOM manipulations will only trigger this once.
	 * 
	 * The downside of this is that things like:
	 *   $('#foo').addClass('tabs'); $('#foo').tabFunctionBar();
	 * won't work.
	 */
	function matching() {
		// For every namespace
		for (var k in namespaces) {
			// That has constructors or destructors
			var ctors = namespaces[k].store.ctors;
			if (ctors) {
			
				// Keep a record of elements that have matched already
				var matched = $([]), match, add, rem;
				// Stepping through each selector from most to least specific
				var j = ctors.length;
				while (j--) {
					// Build some quick-acccess variables
					var sel = ctors[j].selector.selector, ctor = ctors[j].onmatch; dtor = ctors[j].onunmatch;
					// Get the list of elements that match this selector, that haven't yet matched a more specific selector
					res = add = $(sel).not(matched);
					
					// If this selector has a list of elements it matched against last time					
					if (ctors[j].cache) {
						// Find the ones that are extra this time
						add = res.not(ctors[j].cache);
						// Find the ones that are gone this time
						rem = ctors[j].cache.not(res);
						// And call the desctructor on them
						if (rem.length && dtor) ctors.onunmatchproxy(rem, j, dtor);
					}
					
					// Call the constructor on the newly matched ones
					if (add.length && ctor) ctors.onmatchproxy(add, j, ctor);
					
					// Add these matched ones to the list tracking all elements matched so far
					matched = matched.add(res);
					// And remember this list of matching elements again this selector, so next matching we can find the unmatched ones
					ctors[j].cache = res;
				}
			}
		}
		
		check_id = null;
	}
	
	function registerMutateFunction() {
		$.each(arguments, function(i,func){
			var old = $.fn[func];
			$.fn[func] = function() {
				var rv = old.apply(this, arguments);
				if (!check_id) check_id = runSoon(matching, 100);
				return rv;
			}
		})
	}
	
	function registerSetterGetterFunction() {
		$.each(arguments, function(i,func){
			var old = $.fn[func];
			$.fn[func] = function(a, b) {
				var rv = old.apply(this, arguments);
				if (!check_id && (b !== undefined || typeof a != 'string')) check_id = runSoon(matching, 100);
				return rv;
			}
		})
	}

	// Register core DOM manipulation methods
	registerMutateFunction('append', 'prepend', 'after', 'before', 'wrap', 'removeAttr', 'addClass', 'removeClass', 'toggleClass', 'empty', 'remove');
	registerSetterGetterFunction('attr');
	
	// And on DOM ready, trigger matching once
	$(function(){ matching(); })
	
})(jQuery);
