(function($){

	/** Utility function to monkey-patch a jQuery method */
	var monkey = function( /* method, method, ...., patch */){
		var methods = $.makeArray(arguments);
		var patch = methods.pop();

		$.each(methods, function(i, method){
			var old = $.fn[method];

			$.fn[method] = function() {
				var self = this, args = $.makeArray(arguments);

				var rv = old.apply(self, args);
				patch.apply(self, args);
				return rv;
			}
		});
	}

	/** What to call to run a function 'soon'. Normally setTimeout, but for syncronous mode we override so soon === now */
	var runSoon = window.setTimeout;
	
	/** The timer handle for the asyncronous matching call */
	var ChangeDetails = Base.extend({

		init: function() {
			this.global = false;
			this.attrs = {};
			this.classes = {};
		},

		/** Fire the change event. Only fires on the document node, so bind to that */
		triggerEvent: function() {
			// If we're not the active changes instance any more, don't trigger
			if (changes != this) return;

			// Cancel any pending timeout (if we're directly called in the mean time)
			if (this.check_id) clearTimeout(this.check_id);

			// Reset the global changes object to be a new instance (do before trigger, in case trigger fires changes itself)
			changes = new ChangeDetails();

			// Fire event
			$(document).triggerHandler("EntwineSubtreeMaybeChanged", [this]);
		},

		changed: function() {
			if (!this.check_id) {
				var self = this;
				this.check_id = runSoon(function(){ self.check_id = null; self.triggerEvent(); }, 10);
			}
		},

		addAll: function() {
			if (this.global) return this; // If we've already flagged as a global change, just skip

			this.global = true;
			this.changed();
			return this;
		},

		addSubtree: function(node) {
			return this.addAll();
		},

		/* For now we don't do this. It's expensive, and jquery.entwine.ctors doesn't use this information anyway */
		addSubtreeFuture: function(node) {
			if (this.global) return this; // If we've already flagged as a global change, just skip

			this.subtree = this.subtree ? this.subtree.add(node) : $(node);
			this.changed();
			return this;
		},

		addAttr: function(attr, node) {
			if (this.global) return this;

			this.attrs[attr] = (attr in this.attrs) ? this.attrs[attr].add(node) : $(node);
			this.changed();
			return this;
		},

		addClass: function(klass, node) {
			if (this.global) return this;

			this.classes[klass] = (klass in this.classes) ? this.classes[klass].add(node) : $(node);
			this.changed();
			return this;
		}
	});

	var changes = new ChangeDetails();

	// Element add events trigger maybechanged events

	$(document).bind('EntwineElementsAdded', function(e){ changes.addSubtree(e.targets); });

	// Element remove events trigger maybechanged events, but we have to wait until after the nodes are actually removed
	// (EntwineElementsRemoved fires _just before_ the elements are removed so the data still exists), especially in syncronous mode

	var removed = null;
	$(document).bind('EntwineElementsRemoved', function(e){ removed = e.targets; });

	monkey('remove', 'html', 'empty', function(){
		var subtree = removed; removed = null;
		if (subtree) changes.addSubtree(subtree);
	});

	// We also need to know when an attribute, class, etc changes. Patch the relevant jQuery methods here

	monkey('removeAttr', function(attr){
		changes.addAttr(attr, this);
	});

	monkey('addClass', 'removeClass', 'toggleClass', function(klass){
		if (typeof klass == 'string') changes.addClass(klass, this);
	});

	monkey('attr', function(a, b){
		if (b !== undefined && typeof a == 'string') changes.addAttr(a, this);
		else if (typeof a != 'string') { for (var k in a) changes.addAttr(k, this); }
	});

	// Add some usefull accessors to $.entwine

	$.extend($.entwine, {
		/**
		 * Make onmatch and onunmatch work in synchronous mode - that is, new elements will be detected immediately after
		 * the DOM manipulation that made them match. This is only really useful for during testing, since it's pretty slow
		 * (otherwise we'd make it the default).
		 */
		synchronous_mode: function() {
			if (changes && changes.check_id) clearTimeout(changes.check_id);
			changes = new ChangeDetails();

			runSoon = function(func, delay){ func.call(this); return null; };
		},

		/**
		 * Trigger onmatch and onunmatch now - usefull for after DOM manipulation by methods other than through jQuery.
		 * Called automatically on document.ready
		 */
		triggerMatching: function() {
			changes.addAll();
		}
	});

})(jQuery);