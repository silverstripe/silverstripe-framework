(function($) {	

	/* Add the methods to handle constructor & destructor binding to the Namespace class */
	$.entwine.Namespace.addMethods({
		bind_condesc: function(selector, name, func) {
			var ctors = this.store.ctors || (this.store.ctors = $.entwine.RuleList()) ;
			
			var rule;
			for (var i = 0 ; i < ctors.length; i++) {
				if (ctors[i].selector.selector == selector.selector) {
					rule = ctors[i]; break;
				}
			}
			if (!rule) {
				rule = ctors.addRule(selector, 'ctors');
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
						
						try      { func.call(namespace.$(el)); }
						catch(e) { $.entwine.warn_exception(name, el, e); } 
						finally  { el.i = tmp_i; el.f = tmp_f; }					
					}
				};
				
				ctors[name+'proxy'] = proxy;
			}
		}
	});
	
	$.entwine.Namespace.addHandler({
		order: 30,
		
		bind: function(selector, k, v) {
			if ($.isFunction(v) && (k == 'onmatch' || k == 'onunmatch')) {
				this.bind_condesc(selector, k, v);
				return true;
			}
		}
	});

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
	$(document).bind('DOMMaybeChanged', function(){
		// For every namespace
		for (var k in $.entwine.namespaces) {
			// That has constructors or destructors
			var ctors = $.entwine.namespaces[k].store.ctors;
			if (ctors) {
			
				// Keep a record of elements that have matched already
				var matched = $([]), add, rem, res, rule, sel, ctor, dtor;
				// Stepping through each selector from most to least specific
				var j = ctors.length;
				while (j--) {
					// Build some quick-access variables
					rule = ctors[j];
					sel = rule.selector.selector;
					ctor = rule.onmatch; 
					dtor = rule.onunmatch;
					
					// Get the list of elements that match this selector, that haven't yet matched a more specific selector
					res = add = $(sel).not(matched);
					
					// If this selector has a list of elements it matched against last time
					if (rule.cache) {
						// Find the ones that are extra this time
						add = res.not(rule.cache);
						if (dtor) {
							// Find the ones that are gone this time
							rem = rule.cache.not(res);
							// And call the destructor on them
							if (rem.length && !rule.onunmatchRunning) {
								rule.onunmatchRunning = true;
								ctors.onunmatchproxy(rem, j, dtor);
								rule.onunmatchRunning = false;
							}
						}
					}
					
					// Call the constructor on the newly matched ones
					if (add.length && ctor && !rule.onmatchRunning) {
						rule.onmatchRunning = true;
						ctors.onmatchproxy(add, j, ctor);
						rule.onmatchRunning = false;
					}
					
					// Add these matched ones to the list tracking all elements matched so far
					matched = matched.add(res);
					// And remember this list of matching elements again this selector, so next matching we can find the unmatched ones
					ctors[j].cache = res;
				}
			}
		}
	});
	

})(jQuery);
