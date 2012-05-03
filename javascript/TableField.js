/**
 * Javascript for TableField. allows the deletion of records via 
 * AJAX, and the addition of rows via javasript.
 * 
 * TODO relies on include-order at the moment to override actions :/
 */
TableField = Class.create();
TableField.prototype = {
	
	newRowID: 1,
	
	/**
	 * Applies behaviour to the delete button for deleting objects via ajax.
	 */
	initialize: function() {
		var rules = {};
		
		rules['#'+this.id+' table.data a.deletelink'] = {
			onclick: this.deleteRecord.bind(this)
		};
		
		rules['#'+this.id+' table.data a.addrow'] = {
			onclick: this.addRow.bind(this)
		};
		
		Behaviour.register('TableField_'+this.id,rules);
	},
	
	/**
	 * Deletes the given dataobject record via an ajax request. If the record doesn't have any 
	 * information in it, it just removes it from the form.
	 * to tablefield->Delete()
	 * @param {Object} e
	 */
	deleteRecord: function(e) {
		var img = Event.element(e);
		var link = Event.findElement(e,"a");
		var row = Event.findElement(e,"tr");
		var params = link.getAttribute("href").toQueryParams();
		var isEmpty = true;
		var recordID = row.getRecordId();
		var self = this;
		
		// Check to see if there is a dataobject to delete first, otherwise remove the row.
		// or: Check if a childID is set (not present on new items)
		if(
			(this.hasNoValues(row,"input") && this.hasNoValues(row,"select") && this.hasNoValues(row,"textarea"))
			|| params["childID"] <= 0 || (recordID <= 0 || recordID == false)
		){
			if( row.parentNode.getElementsByTagName('tr').length > 1 ) {
				jQuery(row).fadeOut();
			} else {
				// clear all fields in the row
				var fields = row.getElementsByTagName('input');
				if( fields )
					for( var i = 0; i < fields.length; i++ ) {
						fields[i].value = '';
					}
			}
			Event.stop(e);
			return false;
		}
		
		// TODO ajaxErrorHandler and loading-image are dependent on cms, but formfield is in sapphire
		var confirmed = confirm(ss.i18n._t('TABLEFIELD.DELETECONFIRMMESSAGE', 'Are you sure you want to delete this record?'));
		if(confirmed){
			img.setAttribute("src",'sapphire/images/network-save.gif'); // TODO doesn't work
			jQuery.ajax({
				'url': link.getAttribute("href"),
				'method': 'post', 
				'data': {ajax: 1, 'SecurityID': document.getElementById('SecurityID') ? document.getElementById('SecurityID').value : null},
				'success': function(response){
					jQuery(row).fadeOut('fast', function() {
						// remove row from DOM
						this.element.parentNode.removeChild(obj.element);
						// recalculate summary if needed (assumes that TableListField.js is present)
						// TODO Proper inheritance
						if(self._summarise) self._summarise();
						// custom callback
						if(self.callback_deleteRecord) self.callback_deleteRecord(e);
					});
				},
				'error': ajaxErrorHandler
			});
		}
		Event.stop(e);
		return false;
	},
	
	/**
	 * 
	 * 
	 * @param {Object} element
	 * @param {Object} tagName
	 */
	hasNoValues: function(element,tagName){
		elements = element.getElementsByTagName(tagName);
		
		if(elements.length >= 1){
			var isEmpty = true;
			for(var i = 0; i < elements.length;i++){
				if(elements[i].type != "hidden"){
					if(elements[i].value != null && elements[i].value != ""){
						isEmpty = false;
					}
				}
			}
			
			return isEmpty;
		}else{
			return true;
		}
	},
	
	
	/**
	 * Appends a new row to the DOM. 
	 * 
	 * @param {Object} tableid
	 */	
	addRow: function (e){
	
		var table = Event.findElement(e,"table");
		if(table){
			// Clone the last TR
			var tbody = table.tBodies[0];
			var numRows = tbody.rows.length;
			var newRow = tbody.rows[0].cloneNode(true);
			   
			// Get the input elements in this new row
			var inputs = newRow.getElementsByTagName('input');
			// For every input, set it's value to blank if it is not hidden
			for(var i = 0; i < inputs.length; i++) {
				if(inputs[i].type != 'hidden') {
					inputs[i].value = ""
				};
			}
			
			this.newRowID++;
			
			if(newRow.id != "new"){
				this.resetNames(newRow);
			}
			
			// Make sure all inputs have unique IDs
			for(var i = 0; i < inputs.length; i++) {
				inputs[i].id += "-" + this.newRowID;
			}
			
			// Change the ID to a unique one
			newRow.id = "New_" + this.newRowID;
			
			// Append the new row to the DOM
			table.tBodies[0].appendChild(newRow);
			Behaviour.apply(table);
		}
		Event.stop(e);
	},
	
	/**
	 * resets the names for all elements inside a row.
	 * @param {Object} row
	 */
	resetNames: function(row){
		
		// Support for addressing the ID's appropriately.
		for(i = 0; i < row.cells.length;i++){
			for(b=0; b < row.cells[i].childNodes.length;b++){
				inputElement = row.cells[i].childNodes[b];
				if(inputElement.type != 'hidden') inputElement.value = "";
				if(inputElement.name != null){
					if(inputElement.name.substr(inputElement.name.length - 2,inputElement.name.length) != "[]"){
						inputElement.name = 
							inputElement.name.substr(0,inputElement.name.indexOf('[')+1) + "new" +	
							inputElement.name.substr(inputElement.name.indexOf(']'),inputElement.name.length) + "[]";
					}else{
						inputElement.name = 
							inputElement.name.substr(0,inputElement.name.indexOf('[')+1) + "new" +	
							inputElement.name.substr(inputElement.name.indexOf(']'),inputElement.name.length);
					}
				}
			}
		}
	}
}
TableField.applyTo('div.TableField');
if(typeof ajaxErrorHandler == 'undefined'){
	ajaxErrorHandler = function(response) {
		alert(response.responseText);
	}
}
