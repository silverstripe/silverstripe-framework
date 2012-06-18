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
				// When we add new matchers we need to trigger a full global recalc once, regardless of the DOM changes that triggered the event
				this.matchersDirty = true;

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
	$(document).bind('EntwineSubtreeMaybeChanged', function(e, changes){
		// var start = (new Date).getTime();

		// For every namespace
		for (var k in $.entwine.namespaces) {
			var namespace = $.entwine.namespaces[k];

			// That has constructors or destructors
			var ctors = namespace.store.ctors;
			if (ctors) {
			
				// Keep a record of elements that have matched some previous more specific rule.
				// Not that we _don't_ actually do that until this is needed. If matched is null, it's not been calculated yet.
				// We also keep track of any elements that have newly been taken or released by a specific rule
				var matched = null, taken = $([]), released = $([]);

				// Updates matched to contain all the previously matched elements as if we'd been keeping track all along
				var calcmatched = function(j){
					if (matched !== null) return;
					matched = $([]);

					var cache, k = ctors.length;
					while ((--k) > j) {
						if (cache = ctors[k].cache) matched = matched.add(cache);
					}
				}

				// Some declared variables used in the loop
				var add, rem, res, rule, sel, ctor, dtor, full;

				// Stepping through each selector from most to least specific
				var j = ctors.length;
				while (j--) {
					// Build some quick-access variables
					rule = ctors[j];
					sel = rule.selector.selector;
					ctor = rule.onmatch; 
					dtor = rule.onunmatch;

					/*
						Rule.cache might be stale or fresh. It'll be stale if
					   - some more specific selector now has some of rule.cache in it
						- some change has happened that means new elements match this selector now
						- some change has happened that means elements no longer match this selector

						The first we can just compare rules.cache with matched, removing anything that's there already.
					*/

					// Reset the "elements that match this selector and no more specific selector with an onmatch rule" to null.
					// Staying null means this selector is fresh.
					res = null;

					// If this gets changed to true, it's too hard to do a delta update, so do a full update
					full = false;

					if (namespace.matchersDirty || changes.global) {
						// For now, just fall back to old version. We need to do something like changed.Subtree.find('*').andSelf().filter(sel), but that's _way_ slower on modern browsers than the below
						full = true;
					}
					else {
						// We don't deal with attributes yet, so any attribute change means we need to do a full recalc
						for (var k in changes.attrs) {	full = true; break; }

						/*
						 If a class changes, but it isn't listed in our selector, we don't care - the change couldn't affect whether or not any element matches

						 If it is listed on our selector
							- If it is on the direct match part, it could have added or removed the node it changed on
							- If it is on the context part, it could have added or removed any node that were previously included or excluded because of a match or failure to match with the context required on that node
							- NOTE: It might be on _both_
						 */

						var method = rule.selector.affectedBy(changes);

						if (method.classes.context) {
							full = true;
						}
						else {
							for (var k in method.classes.direct) {
								calcmatched(j);
								var recheck = changes.classes[k].not(matched);

								if (res === null) {
									res = rule.cache ? rule.cache.not(taken).add(released.filter(sel)) : $([]);
								}

								res = res.not(recheck).add(recheck.filter(sel));
							}
						}
					}

					if (full) {
						calcmatched(j);
						res = $(sel).not(matched);
					}
					else {
						if (!res) {
							// We weren't stale because of any changes to the DOM that affected this selector, but more specific
							// onmatches might have caused stale-ness

							// Do any of the previous released elements match this selector?
							add = released.length && released.filter(sel);

							if (add && add.length) {
								// Yes, so we're stale as we need to include them. Filter for any possible taken value at the same time
								res = rule.cache ? rule.cache.not(taken).add(add) : add;
							}
							else {
								// Do we think we own any of the elements now taken by more specific rules?
								rem = taken.length && rule.cache && rule.cache.filter(taken);

								if (rem && rem.length) {
									// Yes, so we're stale as we need to exclude them.
									res = rule.cache.not(rem);
								}
							}
						}
					}

					// Res will be null if we know we are fresh (no full needed, selector not affectedBy changes)
					if (res === null) {
						// If we are tracking matched, add ourselves
						if (matched && rule.cache) matched = matched.add(rule.cache);
					}
					else {
						// If this selector has a list of elements it matched against last time
						if (rule.cache) {
							// Find the ones that are extra this time
							add = res.not(rule.cache);
							rem = rule.cache.not(res);
						}
						else {
							add = res; rem = null;
						}

						if ((add && add.length) || (rem && rem.length)) {
							if (rem && rem.length) {
								released = released.add(rem);

								if (dtor && !rule.onunmatchRunning) {
									rule.onunmatchRunning = true;
									ctors.onunmatchproxy(rem, j, dtor);
									rule.onunmatchRunning = false;
								}
							}

							// Call the constructor on the newly matched ones
							if (add && add.length) {
								taken = taken.add(add);
								released = released.not(add);

								if (ctor && !rule.onmatchRunning) {
									rule.onmatchRunning = true;
									ctors.onmatchproxy(add, j, ctor);
									rule.onmatchRunning = false;
								}
							}
						}

						// If we are tracking matched, add ourselves
						if (matched) matched = matched.add(res);

						// And remember this list of matching elements again this selector, so next matching we can find the unmatched ones
						rule.cache = res;
					}
				}

				namespace.matchersDirty = false;
			}
		}

		// console.log((new Date).getTime() - start);
	});
	

})(jQuery);
