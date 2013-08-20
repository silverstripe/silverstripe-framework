(function($){

	// Gets all the child elements of a particular elements, stores it in an array
	function getElements(store, original) {
		var node, i = store.length, next = original.firstChild;

		while ((node = next)) {
			if (node.nodeType === 1) store[i++] = node;
			next = node.firstChild || node.nextSibling;
			while (!next && (node = node.parentNode) && node !== original) next = node.nextSibling;
		}
	}

	// This might be faster? Or slower? @todo: benchmark.
	function getElementsAlt(store, node) {
		if (node.getElementsByTagName) {
			var els = node.getElementsByTagName('*'), len = els.length, i = 0, j = store.length;
			for(; i < len; i++, j++) {
				store[j] = els[i];
			}
		}
		else if (node.childNodes) {
			var els = node.childNodes, len = els.length, i = 0;
			for(; i < len; i++) {
				getElements(store, els[i]);
			}
		}
	}

	var dontTrigger = false;

	var patchDomManipCallback = function(original) {
		var patched = function(elem){
			var added = [];

			if (!dontTrigger) {
				if (elem.nodeType == 1) added[added.length] = elem;
				getElements(added, elem);
			}

			var rv = original.apply(this, arguments);

			if (!dontTrigger && added.length) {
				var event = $.Event('EntwineElementsAdded');
				event.targets = added;
				$(document).triggerHandler(event);
			}

			return rv;
		}
		patched.patched = true;

		return patched;
	}

	var version = $.prototype.jquery.split('.');
	var callbackIdx = (version[0] > 1 || version[1] >= 10 ? 1 : 2);

	// Monkey patch $.fn.domManip to catch all regular jQuery add element calls
	var _domManip = $.prototype.domManip;
	$.prototype.domManip = function() {
		if (!arguments[callbackIdx].patched) arguments[callbackIdx] = patchDomManipCallback(arguments[callbackIdx]);
		return _domManip.apply(this, arguments);
	}

	// Monkey patch $.fn.html to catch when jQuery sets innerHTML directly
	var _html = $.prototype.html;
	$.prototype.html = function(value) {
		if (value === undefined) return _html.apply(this, arguments);

		dontTrigger = true;
		var res = _html.apply(this, arguments);
		dontTrigger = false;

		var added = [];

		var i = 0, length = this.length;
		for (; i < length; i++ ) getElements(added, this[i]);

		var event = $.Event('EntwineElementsAdded');
		event.targets = added;
		$(document).triggerHandler(event);

		return res;
	}

	// If this is true, we've changed something to call cleanData so that we can catch the elements, but we don't
	// want to call the underlying original $.cleanData
	var supressActualClean = false;

	// Monkey patch $.cleanData to catch element removal
	var _cleanData = $.cleanData;
	$.cleanData = function( elems ) {
		// By default we can assume all elements passed are legitimately being removeed
		var removed = elems;

		// Except if we're supressing actual clean - we might be being called by jQuery "being careful" about detaching nodes
		// before attaching them. So we need to check to make sure these nodes currently are in a document
		if (supressActualClean) {
			var i = 0, len = elems.length, removed = [], ri = 0;
			for(; i < len; i++) {
				var node = elems[i], current = node;
				while (current = current.parentNode) {
					if (current.nodeType == 9) { removed[ri++] = node; break; }
				}
			}
		}

		if (removed.length) {
			var event = $.Event('EntwineElementsRemoved');
			event.targets = removed;
			$(document).triggerHandler(event);
		}

		if (!supressActualClean) _cleanData.apply(this, arguments);
	}

	// Monkey patch $.fn.remove to catch when we're just detaching (keepdata == 1) -
	// this doesn't call cleanData but still needs to trigger event
	var _remove = $.prototype.remove;
	$.prototype.remove = function(selector, keepdata) {
		supressActualClean = keepdata;
		var rv = _remove.call(this, selector);
		supressActualClean = false;
		return rv;
	}

	// And on DOM ready, trigger adding once
	$(function(){
		var added = []; getElements(added, document);

		var event = $.Event('EntwineElementsAdded');
		event.targets = added;
		$(document).triggerHandler(event);
	});


})(jQuery);