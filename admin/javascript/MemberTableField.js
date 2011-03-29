/**
 * File: MemberTableField.js
 */
(function($) {
	$.entwine('ss', function($){
		/**
		 * Class: #Permissions .checkbox[value=ADMIN]
		 * 
		 * Automatically check and disable all checkboxes if ADMIN permissions are selected.
		 * As they're disabled, any changes won't be submitted (which is intended behaviour),
		 * checking all boxes is purely presentational.
		 */
		$('#Permissions .checkbox[value=ADMIN]').entwine({
			onmatch: function() {
				this.toggleCheckboxes();

				this._super();
			},
			/**
			 * Function: onclick
			 */
			onclick: function(e) {
				this.toggleCheckboxes();
			},
			/**
			 * Function: toggleCheckboxes
			 */
			toggleCheckboxes: function() {
				var self = this, checkboxes = this.parents('.field:eq(0)').find('.checkbox').not(this);
				
				if(this.is(':checked')) {
					checkboxes.each(function() {
						$(this).data('SecurityAdmin.oldChecked', $(this).attr('checked'));
						$(this).data('SecurityAdmin.oldDisabled', $(this).attr('disabled'));
						$(this).attr('disabled', 'disabled');
						$(this).attr('checked', 'checked');
					});
				} else {
					checkboxes.each(function() {
						$(this).attr('checked', $(this).data('SecurityAdmin.oldChecked'));
						$(this).attr('disabled', $(this).data('SecurityAdmin.oldDisabled'));
					});
				}
			}
		});
	});
}(jQuery));

/**
 * Modified 2006-10-05, Ingo Schommer
 * This is more or less a copy of Member.js, with additions and changes
 * to match the switch from Member.php to MemberTableField.php all over the UI.
 * Eventually it will replace Member.js (please remove this message then).
 */
 
// no confirm message for removal from a group
if(typeof(ComplexTableField) != 'undefined') {
	ComplexTableField.prototype.deleteConfirmMessage = null;
}

/**
 * Class: AjaxMemberLookup
 * 
 * Auto-lookup on ajax fields
 */
AjaxMemberLookup = {
	initialise : function() {
		var div = document.createElement('div');
		div.id = this.id + '_ac';
		div.className = 'autocomplete';
		this.parentNode.appendChild(div);
		if(this.id) {
			new Ajax.Autocompleter(this.id, div.id, 'admin/security/autocomplete/' + this.name, {
				afterUpdateElement : this.afterAutocomplete.bind(this)
			});
			
		}
	},
	afterAutocomplete : function(field, selectedItem) {
		var items = jQuery(selectedItem).data('fields'), form = jQuery(selectedItem).parents('form:first');
		for(name in items) {
			jQuery(form).find('input[name='+name+']').val(items[name]);
		}
	}		
}

/**
 * Class: MemberTableField
 */
MemberTableField = Class.create();
MemberTableField.applyTo('#Form_EditForm div.MemberTableField');
MemberTableField.prototype = {
	
	initialize: function() {
		Behaviour.register({
			'#Form_EditForm div.MemberFilter input' : {
				onkeypress : this.prepareSearch.bind(this)
			},

			'#Form_EditForm div.MemberTableField table.data tr.addtogrouprow input' : {
				onkeypress : this.prepareAddToGroup.bind(this)
			},

			'#Form_EditForm div.MemberTableField table.data tr.addtogrouprow #Form_AddRecordForm_action_addtogroup' : {
				onclick : this.prepareAddToGroup.bind(this)
			},

			'#Form_EditForm div.MemberTableField table.data tr.addtogrouprow td.actions input' : {
				initialise: function() {
					data = this.parentNode.parentNode.getElementsByTagName('input');
					var i,item,error = [];
					for(i=0;item=data[i];i++) {
						item.originalSerialized = Form.Element.serialize(item);
					}
				},
				onclick : this.addToGroup.bind(this)
			},
			
			//'#Form_EditForm div.MemberTableField input' : AjaxMemberLookup,
			
			'#Form_EditForm' : {
				changeDetection_fieldsToIgnore : {
					'ctf[start]' : true,
					'ctf[ID]' : true,
					'MemberOrderByField' : true,
					'MemberOrderByOrder' : true,
					'MemberGroup' : true,
					'MemberFilterButton' : true,
					'MemberFieldName' : true,
					'MemberDontShowPassword' : true,
					'MemberSearch' : true
				}
			}
		});
	},
	
	// prevent submission of wrong form-button (MemberFilterButton)
	prepareAddToGroup: function(e) {
		// IE6 doesnt send an event-object with onkeypress
		var event = (e) ? e : window.event;
		var keyCode = (event.keyCode) ? event.keyCode : event.which;
		if(keyCode == Event.KEY_RETURN) {
			var el = Event.element(event);
			this.addToGroup(event);
			Event.stop(event);
			return false;
		}
	},

	// prevent submission of wrong form-button (MemberFilterButton)
	prepareSearch: function(e) {
		// IE6 doesnt send an event-object with onkeypress
		var event = (e) ? e : window.event;
		var keyCode = (event.keyCode) ? event.keyCode : event.which;
		
		if(keyCode == Event.KEY_RETURN) {
			var el = Event.element(event);
			$('MemberFilterButton').onclick(event);
			Event.stop(event);
			return false;
		}
	},
	
	addToGroup: function(e) {
		// only submit parts of the form
		var data = this.parentNode.parentNode.getElementsByTagName('input');
		var i,item,error = [];
		var form = Event.findElement(e,"form");
		
		for(i=0;item=data[i];i++) {
			if(item.name == 'Email' && !item.value) error[error.length] = "Email";
			if(item.name == 'Password' && !item.value) error[error.length] = "Password";
		}
		
		if(error.length > 0) {
			alert('Please enter a ' + error.join(' and a ') + ' to add a member.');
		} else {
			updateURL = "";
			updateURL += Event.findElement(e,"form").action;
			// we can't set "fieldName" as a HiddenField because there might be multiple ComplexTableFields in a single EditForm-container
			updateURL += "?fieldName="+$('MemberFieldName').value;
			updateURL += "&action_callfieldmethod&methodName=addtogroup";

			ajaxSubmitFieldSet(updateURL, data);
		}
		
		return false;
	}
	
	/*
		initialise : function() {
			this.headerMap = [];
			
			var i, item, headers = this.getElementsByTagName('thead')[0].getElementsByTagName('tr')[0].getElementsByTagName('td');
			for(i=0;item=headers[i];i++) {
				this.headerMap[i] = item.className;
			}
		},
		
		setRecordDetails : function(id, details, groupID) {
			var row = document.getElementById('member-' + id);
			if(row) {
				var i, item, cells = row.getElementsByTagName('td');
				for(i=0;item=cells[i];i++) {
					if(details[this.headerMap[i]]) {
						item.innerHTML = details[this.headerMap[i]];
					}
				}
			} else {
				this.createRecord(id, details, groupID);
			}
		},
		createRecord : function (id, details, groupId) {
			var row = document.createElement('tr');
			row.id = 'member-' + id;
			var i, cell, cellField;
			for(i=0;cellField=this.headerMap[i];i++) {
				cell = document.createElement('td')
				if(details[cellField]) {
					cell.innerHTML = details[cellField];
				}
				row.appendChild(cell);
			}
			
			// Add the delete icon
			if(typeof groupId == 'undefined')
				var groupId = $('Form_EditForm').elements.ID.value;
			cell = document.createElement('td')
			cell.innerHTML = '<a class="deletelink" href="admin/security/removememberfromgroup/' + groupId + '/' + id + '"><img src="sapphire/images/delete.gif" alt="delete" /></a>';
			cell.getElementsByTagName('0');
			row.appendChild(cell);
			
			var tbody = this.getElementsByTagName('tbody')[0];
			var addRow = document.getElementsByClassName('addrow',tbody)[0];
			if(addRow) tbody.insertBefore(row, addRow);
			else tbody.appendChild(row);
			Behaviour.apply(row, true);
		},
		clearAddForm : function() {
			var tbody = this.getElementsByTagName('tbody')[0];
			var addRow = document.getElementsByClassName('addrow',tbody)[0];
			if(addRow) {
				var i,field,fields = addRow.getElementsByTagName('input');
				for(i=0;field=fields[i];i++) {
					if(field.type != 'hidden' && field.type != 'submit') field.value = '';
				}
			}
		},
		removeMember : function(memberID) {
			var record;
			if(record = $('member-' + memberID)) {
				record.parentNode.removeChild(record);
			} 
		}
		*/
}

/**
 * Class: MemberFilterButton
 */
MemberFilterButton = Class.create();
MemberFilterButton.applyTo('#MemberFilterButton');
MemberFilterButton.prototype = {
	initialize: function() {
		this.inputFields = new Array();
		
		var childNodes = this.parentNode.parentNode.getElementsByTagName('input');
		
		for( var index = 0; index < childNodes.length; index++ ) {
			if( childNodes[index].tagName ) {
				childNodes[index].resetChanged = function() { return false; }
				childNodes[index].isChanged = function() { return false; }
				this.inputFields.push( childNodes[index] );
			}
		}
		
		childNodes = this.parentNode.getElementsByTagName('select');
		
		for( var index = 0; index < childNodes.length; index++ ) {
			if( childNodes[index].tagName ) {
				childNodes[index].resetChanged = function() { return false; }
				childNodes[index].field_changed = function() { return false; }
				this.inputFields.push( childNodes[index] );
			}
		}
	},
	
	isChanged: function() {
		return false;
	},
	
	onclick: function(e) {
		if(!$('ctf-ID') || !$('MemberFieldName')) {
			return false;
		}

	    try {
    	    var form = Event.findElement(e,"form");
    	    var fieldName = $('MemberFieldName').value;
    	    var fieldID = form.id + '_' + fieldName;
	    
    		var updateURL = form.action + '/field/' + fieldName + '?ajax=1';
    		for( var index = 0; index < this.inputFields.length; index++ ) {
    			if( this.inputFields[index].tagName ) {
    				updateURL += '&' + this.inputFields[index].name + '=' + encodeURIComponent( this.inputFields[index].value );
    			}
    		}
    		updateURL += ($('SecurityID') ? '&SecurityID=' + $('SecurityID').value : '');

    		new Ajax.Updater( fieldID, updateURL, {
    			onComplete: function() {
    			    Behaviour.apply($(fieldID), true);
    			},
    			onFailure: function( response ) {
    				errorMessage('Could not filter results: ' + response.responseText );
    			}
    		});
		} catch(er) {
			errorMessage('Error searching');
		}

		return false;	
	}
}

// has to be external from initialize() because otherwise request will double on each reload - WTF
Behaviour.register({
	'#Form_EditForm div.MemberTableField table.data input.text' : AjaxMemberLookup
});

/**
 * Post the given fields to the given url
 */
function ajaxSubmitFieldSet(href, fieldSet, extraData) {
	// Build data
	var i,field,data = "ajax=1";
	for(i=0;field=fieldSet[i];i++) {
		data += '&' + Form.Element.serialize(field);
	}
	if(extraData){
		data += '&'+extraData;
	}
	// Send request
	jQuery.ajax({
		'url': href, 
		'method' : 'post', 
		'data' : data,
		'success' : function(response) {
			//alert(response.responseText);
			Ajax.Evaluator(response);
		},
		'error' : function(response) {
			alert(response.responseText);
			//errorMessage('Error: ', response);
		}
	});
}