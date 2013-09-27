(function($) {

	$.entwine.Namespace.addMethods({
		bind_capture: function(selector, event, name, capture) {
			var store  = this.captures || (this.captures = {});
			var rulelists = store[event] || (store[event] = {});
			var rulelist = rulelists[name] || (rulelists[name] = $.entwine.RuleList());

			rule = rulelist.addRule(selector, event);
			rule.handler = name;

			this.bind_proxy(selector, name, capture);
		}
	});

	var bindings = $.entwine.capture_bindings = {};

	var event_proxy = function(event) {
		return function(e) {
			var namespace, capturelists, forevent, capturelist, rule, handler, sel;

			for (var k in $.entwine.namespaces) {
				namespace = $.entwine.namespaces[k];
				capturelists = namespace.captures;

				if (capturelists && (forevent = capturelists[event])) {
					for (var k in forevent) {
						var capturelist = forevent[k];
						var triggered = namespace.$([]);

						// Stepping through each selector from most to least specific
						var j = capturelist.length;
						while (j--) {
							rule = capturelist[j];
							handler = rule.handler;
							sel = rule.selector.selector;

							var matching = namespace.$(sel).not(triggered);
							matching[handler].apply(matching, arguments);

							triggered = triggered.add(matching);
						}
					}
				}
			}
		}
	};

	var selector_proxy = function(selector, handler, includechildren) {
		var matcher = $.selector(selector);
		return function(e){
			if (matcher.matches(e.target)) return handler.apply(this, arguments);
		}
	};

	var document_proxy = function(selector, handler, includechildren) {
		return function(e){
			if (e.target === document) return handler.apply(this, arguments);
		}
	};

	var window_proxy = function(selector, handler, includechildren) {
		return function(e){
			if (e.target === window) return handler.apply(this, arguments);
		}
	};

	var property_proxy = function(property, handler, includechildren) {
		var matcher;

		return function(e){
			var match = this['get'+property]();

			if (typeof(match) == 'string') {
				var matcher = (matcher && match == matcher.selector) ? matcher : $.selector(match);
				if (matcher.matches(e.target)) return handler.apply(this, arguments);
			}
			else {
				if ($.inArray(e.target, match) !== -1) return handler.apply(this, arguments);
			}
		}
	};

	$.entwine.Namespace.addHandler({
		order: 10,

		bind: function(selector, k, v) {
			var match;
			if ($.isPlainObject(v) && (match = k.match(/^from\s*(.*)/))) {
				var from = match[1];
				var proxyGen;

				if (from.match(/[^\w]/)) proxyGen = selector_proxy;
				else if (from == 'Window' || from == 'window') proxyGen = window_proxy;
				else if (from == 'Document' || from == 'document') proxyGen = document_proxy;
				else proxyGen = property_proxy;

				for (var onevent in v) {
					var handler = v[onevent];
					match = onevent.match(/^on(.*)/);
					var event = match[1];

					this.bind_capture(selector, event, k + '_' + event, proxyGen(from, handler));

					if (!bindings[event]) {
						var namespaced = event.replace(/(\s+|$)/g, '.entwine$1');
						bindings[event] = event_proxy(event);

						$(proxyGen == window_proxy ? window : document).bind(namespaced, bindings[event]);
					}
				}

				return true;
			}
		}
	});

})(jQuery);
