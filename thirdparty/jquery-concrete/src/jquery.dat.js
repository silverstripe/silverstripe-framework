/*
 * Provides a per-node data store that is automatically cloned when dom node is clone() or cloneNode()'d.
 */

(function($){
 	var data_store = {};
 	var check_name = 'com.silverstripe:check_id';
 	
	var expando = 'data_id:';
	var id = 0;

	/**
	 * Clone a data object. Currently uses jQuery's deep copy routine
	 */
	var cloneData = function(data) {
		return $.extend(true, {}, data);
	}
 
	/**
	 * Set the data object for an element.
	 * Picks a new ID, sets the tracking attribute and value on the DOM node and stores data in the data_store
	 * 
	 * @param {jQuery selection} el - The element to store this data on
	 * @param {Object} data - The object to use as data, or undefined / null for an empty object
	 */
 	var setData = function(el, data) {
		if (!data) data = {};
		
		id += 1;
		var data_id = expando + id;
		
		el.attr('data', data_id); el.data(check_name, data_id);
		return data_store[data_id] = data;
	}
 
 
	/**
	 * Delete the data object for an element
	 * It's important this is called when the related element is deled, or memory could leak. We monkey-patch jQuery.removeData to make sure this happens
	 * @param {jQuery selection} el - The element to remove the data for
	 */
 	var clearData = function(el) {
		var data_id = el.attr('data');
		if (!data_id) return;
		
		el.removeAttr('data');
		// Only remove the data if this is the last element with a data reference to it. This is so removing an element
		// doesn't delete the data before any cloned elements have a chance to copy it
		if ($('[data='+data_id+']').length == 0) delete data_store[data_id];
	}
 
	/**
	 * Get the data object for an element
	 * Sets an empty data object if the element does not have one yet
	 * Clones the data object if the element it's attached to has been cloned
	 * @param {jQuery selection} el - The element to retrieve the data of
	 */
	var getData = function(el) {
		// If the data_id is missing, the element has no data
		var data_id = el.attr('data');
		if (!data_id) return setData(el);
		
		var check_id = el.data(check_name);
		if (!check_id || check_id != data_id) {
			// If the check_id is missing, the element has been cloned. So clone the data too
			var newdata = cloneData(data_store[data_id]); 
			setData(el, newdata);
			// If we were the last element holding on to a reference to that old data, delete it now that we're done with it
			if ($('[data='+data_id+']').length == 0) delete data_store[data_id];
			return newdata;
		}	
			
		// Otherwise, this element has some data, so return it
		return data_store[data_id];
	}

	$.dat = {};
	/**
	 * Check all data in data_store, removing any that are not longer referenced in the DOM
	 * Returns number of garbage-collected entries, for finding memory leaks 
	 */
	$.dat.vacuum = function() {
		var i = 0;
		for (var k in data_store) {
			if ($('[data='+k+']').length == 0) { delete data_store[k]; i++; }
		}
		return i;
	}
	/**
	 * Return count of items in data_store.
	 * Used in tests
	 */
	$.dat.size = function() {
		var i = 0;
		for (var k in data_store) i++;
		return i;
	}
	
	/**
	 * Get the data object for the current element
	 */
	$.fn.d = function(a){
		return getData(this.eq(0));
	};
	
	// Monkey patch removeData to also remove dat
	var removeData_without_dat = $.removeData
	$.removeData = function(elem, name) {
		if (!name) clearData($(elem));
		return removeData_without_dat.apply(this, arguments);
	}
	
 })(jQuery);
 
