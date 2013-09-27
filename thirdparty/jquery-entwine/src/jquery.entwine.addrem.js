(function($) {

	$.entwine.Namespace.addMethods({
		build_addrem_proxy: function(name) {
			var one = this.one(name, 'func');

			return function() {
				if (this.length === 0){
					return;
				}
				else if (this.length) {
					var rv, i = this.length;
					while (i--) rv = one(this[i], arguments);
					return rv;
				}
				else {
					return one(this, arguments);
				}
			};
		},

		bind_addrem_proxy: function(selector, name, func) {
			var rulelist = this.store[name] || (this.store[name] = $.entwine.RuleList());

			var rule = rulelist.addRule(selector, name); rule.func = func;

			if (!this.injectee.hasOwnProperty(name)) {
				this.injectee[name] = this.build_addrem_proxy(name);
				this.injectee[name].isentwinemethod = true;
			}
		}
	});

	$.entwine.Namespace.addHandler({
		order: 30,

		bind: function(selector, k, v) {
			if ($.isFunction(v) && (k == 'onadd' || k == 'onremove')) {
				this.bind_addrem_proxy(selector, k, v);
				return true;
			}
		}
	});

	$(document).bind('EntwineElementsAdded', function(e){
		// For every namespace
		for (var k in $.entwine.namespaces) {
			var namespace = $.entwine.namespaces[k];
			if (namespace.injectee.onadd) namespace.injectee.onadd.call(e.targets);
		}
	});

	$(document).bind('EntwineElementsRemoved', function(e){
		for (var k in $.entwine.namespaces) {
			var namespace = $.entwine.namespaces[k];
			if (namespace.injectee.onremove) namespace.injectee.onremove.call(e.targets);
		}
	});




})(jQuery);
