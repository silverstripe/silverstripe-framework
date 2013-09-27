(function($) {	

	/** Taken from jQuery 1.5.2 for backwards compatibility */
	if ($.support.changeBubbles == undefined) {
		$.support.changeBubbles = true;

		var el = document.createElement("div");
		eventName = "onchange";

		if (el.attachEvent) {
			var isSupported = (eventName in el);
			if (!isSupported) {
				el.setAttribute(eventName, "return;");
				isSupported = typeof el[eventName] === "function";
			}

			$.support.changeBubbles = isSupported;
		}
	}

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

			/*
			This change bubble emulation code is taken mostly from jQuery 1.6 - unfortunately we can't easily reuse any of
			it without duplication, so we'll have to re-migrate any bugfixes
			*/

			// Get the value of an item. Isn't supposed to be interpretable, just stable for some value, and different
			// once the value changes
			var getVal = function( elem ) {
				var type = elem.type, val = elem.value;

				if (type === "radio" || type === "checkbox") {
					val = elem.checked;
				}
				else if (type === "select-multiple") {
					val = "";
					if (elem.selectedIndex > -1) {
						val = jQuery.map(elem.options, function(elem){ return elem.selected; }).join("-");
					}
				}
				else if (jQuery.nodeName(elem, "select")) {
					val = elem.selectedIndex;
				}

				return val;
			};

			// Test if a node name is a form input
			var rformElems = /^(?:textarea|input|select)$/i;

			// Check if this event is a change, and bubble the change event if it is
			var testChange = function(e) {
				var elem = e.target, data, val;

				if (!rformElems.test(elem.nodeName) || elem.readOnly) return;

				data = jQuery.data(elem, "_entwine_change_data");
				val = getVal(elem);

				// the current data will be also retrieved by beforeactivate
				if (e.type !== "focusout" || elem.type !== "radio") {
					jQuery.data(elem, "_entwine_change_data", val);
				}

				if (data === undefined || val === data) return;

				if (data != null || val) {
					e.type = "change";

					while (elem && elem.nodeType == 1 && !e.isPropagationStopped()) {
						var ret = one(elem, arguments);
						if (ret !== undefined) e.result = ret;
						if (ret === false) { e.preventDefault(); e.stopPropagation(); }

						elem = elem.parentNode;
					}
				}
			};

			// The actual proxy - responds to several events, some of which triger a change check, some
			// of which just store the value for future change checks
			var prxy = function(e) {
				var event = e.type, elem = e.target, type = jQuery.nodeName( elem, "input" ) ? elem.type : "";

				switch (event) {
					case 'focusout':
					case 'beforedeactivate':
						testChange.apply(this, arguments);
						break;

					case 'click':
						if ( type === "radio" || type === "checkbox" || jQuery.nodeName( elem, "select" ) ) {
							testChange.apply(this, arguments);
						}
						break;

					// Change has to be called before submit
					// Keydown will be called before keypress, which is used in submit-event delegation
					case 'keydown':
						if (
							(e.keyCode === 13 && !jQuery.nodeName( elem, "textarea" ) ) ||
							(e.keyCode === 32 && (type === "checkbox" || type === "radio")) ||
							type === "select-multiple"
						) {
							testChange.apply(this, arguments);
						}
						break;

					// Beforeactivate happens also before the previous element is blurred
					// with this event you can't trigger a change event, but you can store
					// information
					case 'focusin':
					case 'beforeactivate':
						jQuery.data( elem, "_entwine_change_data", getVal(elem) );
						break;
				}
			}

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
						if (!$.support.changeBubbles) {
							proxies[name] = this.build_change_proxy(name);
							event = 'click keydown focusin focusout beforeactivate beforedeactivate';
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

				$(document).bind(event.replace(/(\s+|$)/g, '.entwine$1'), proxies[name]);
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
	
	var delegate_submit = function(e, data){
		var delegationEvent = $.Event('delegatedSubmit'); delegationEvent.delegatedEvent = e;
		return $(document).trigger(delegationEvent, data); 
	};

	$(document).bind('EntwineElementsAdded', function(e){
		var forms = $(e.targets).filter('form');
		if (!forms.length) return;

		forms.bind('submit.entwine_delegate_submit', delegate_submit);
	});

})(jQuery);
	