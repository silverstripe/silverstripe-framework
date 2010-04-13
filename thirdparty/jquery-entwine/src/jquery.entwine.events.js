(function($) {	

	/* If we are any browser other than IE or Safari, we don't have to do anything special to handle
	 * onchange delegation */ 
	$.support.bubblingChange = !($.browser.msie || $.browser.safari);
	
	/* Return true if node b is the same as, or is a descendant of, node a */
	if (document.compareDocumentPosition) {
		var is_or_contains = function(a, b) {
			return a && b && (a == b || !!(a.compareDocumentPosition(b) & 16));
		};
	}
	else {
		var is_or_contains = function(a, b) {
			return a && b && (a == b || (a.contains ? a.contains(b) : true));
		};
	}

	/* Add the methods to handle event binding to the Namespace class */
	$.entwine.Namespace.addMethods({
		build_event_proxy: function(name) {
			var one = this.one(name, 'func');
			
			var prxy = function(e, data) {
				// For events that do not bubble we manually trigger delegation (see delegate_submit below) 
				// If this event is a manual trigger, the event we actually want to bubble is attached as a property of the passed event
				e = e.delegatedEvent || e;
				
				var el = e.target;
				while (el && el.nodeType == 1 && !e.isPropagationStopped()) {
					var ret = one(el, arguments);
					if (ret !== undefined) e.result = ret;
					if (ret === false) { e.preventDefault(); e.stopPropagation(); }
					
					el = el.parentNode;
				}
			};
			
			return prxy;
		},
		
		build_mouseenterleave_proxy: function(name) {
			var one = this.one(name, 'func');
			
			var prxy = function(e) {
				var el = e.target;
				var rel = e.relatedTarget;
				
				while (el && el.nodeType == 1 && !e.isPropagationStopped()) {
					/* We know el contained target. If it also contains relatedTarget then we didn't mouseenter / leave. What's more, every ancestor will also
					contan el and rel, and so we can just stop bubbling */
					if (is_or_contains(el, rel)) break;
					
					var ret = one(el, arguments);
					if (ret !== undefined) e.result = ret;
					if (ret === false) { e.preventDefault(); e.stopPropagation(); }
					
					el = el.parentNode;
				}
			};
			
			return prxy;
		},
		
		build_change_proxy: function(name) {
			var one = this.one(name, 'func');
			
			var prxy = function(e) {
				var el = e.target;
				// If this is a keydown event, only worry about the enter key, since browsers only trigger onchange on enter or focus loss
				if (e.type === 'keydown' && e.keyCode !== 13) return;
				// Make sure this is event is for an input type we're interested in
				if (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA' && el.tagName !== 'SELECT') return;
					
				var $el = $(el), nowVal, oldVal = $el.data('changeVal');
			
				// Detect changes on checkboxes & radiobuttons, which have different value logic. We don't use el.value, since el is part
				// of a set, and we only want to raise onchange once for a single user action.
				if (el.type == 'checkbox' || el.type == 'radio') {
					if (!el.disabled && e.type === 'click') {
						nowVal = el.checked;
						// If radio, we get two changes - the activation, and the deactivation. We only want to fire one change though
						if ((el.type === 'checkbox' || nowVal === true) && oldVal !== nowVal) e.type = 'change';
					}
				}
				// Detect changes on other input types. In this case value is OK.
				else {
					nowVal = el.value;
					if (oldVal !== undefined && oldVal !== nowVal) e.type = 'change';
				}
			
				// Save the current value for next time
				if (nowVal !== undefined) $el.data('changeVal', nowVal);
			
				// And if we decided that a change happened, do the actual triggering
				if (e.type == 'change') {
					while (el && el.nodeType == 1 && !e.isPropagationStopped()) {
						var ret = one(el, arguments);
						if (ret !== undefined) e.result = ret;
						if (ret === false) { e.preventDefault(); e.stopPropagation(); }
						
						el = el.parentNode;
					}
				}
			};
			
			return prxy;
		},
		
		bind_event: function(selector, name, func, event) {
			var funcs = this.store[name] || (this.store[name] = $.entwine.RuleList()) ;
			var proxies = funcs.proxies || (funcs.proxies = {});
			
			var rule = funcs.addRule(selector, name); rule.func = func;
			
			if (!proxies[name]) {
				switch (name) {
					case 'onmouseenter':
						proxies[name] = this.build_mouseenterleave_proxy(name);
						event = 'mouseover';
						break;
					case 'onmouseleave':
						proxies[name] = this.build_mouseenterleave_proxy(name);
						event = 'mouseout';
						break;
					case 'onchange':
						if (!$.support.bubblingChange) {
							proxies[name] = this.build_change_proxy(name);
							event = 'click focusin focusout keydown';
						}
						break;
					case 'onsubmit':
						event = 'delegatedSubmit';
						break;
					case 'onfocus':
					case 'onblur':
						$.entwine.warn('Event '+event+' not supported - using focusin / focusout instead', $.entwine.WARN_LEVEL_IMPORTANT);
				}
				
				// If none of the special handlers created a proxy, use the generic proxy
				if (!proxies[name]) proxies[name] = this.build_event_proxy(name);
				
				$(document).bind(event+'.entwine', proxies[name]);
			}
		}
	});
	
	$.entwine.Namespace.addHandler({
		order: 40,
		
		bind: function(selector, k, v){
			var match, event;
			if ($.isFunction(v) && (match = k.match(/^on(.*)/))) {
				event = match[1];
				this.bind_event(selector, k, v, event);
				return true;
			}
		}
	});
	
	// Find all forms and bind onsubmit to trigger on the document too. 
	// This is the only event that can't be grabbed via delegation
	
	var form_binding_cache = $([]); // A cache for already-handled form elements
	var delegate_submit = function(e, data){ 
		var delegationEvent = $.Event('delegatedSubmit'); delegationEvent.delegatedEvent = e;
		return $(document).trigger(delegationEvent, data); 
	};

	$(document).bind('DOMMaybeChanged', function(){
		var forms = $('form');
		// Only bind to forms we haven't processed yet
		forms.not(form_binding_cache).bind('submit', delegate_submit);
		// Then remember the current set of forms
		form_binding_cache = forms;
	});

})(jQuery);
	