FieldEditor = Class.create();
FieldEditor.applyTo('div.FieldEditor');
FieldEditor.prototype = {
	initialize: function() {
		FieldEditorField.applyToChildren(this, 'div.EditableFormField');
		FieldEditorHeadingField.applyToChildren(this, 'div.EditableFormHeading');
		FieldEditorRadioField.applyToChildren(this, 'div.EditableRadioField');
		FieldEditorCheckboxGroupField.applyToChildren(this, 'div.EditableCheckboxGroupField');
		FieldEditorDropdown.applyToChildren(this, 'div.EditableDropdown');
		FieldEditorEmailField.applyToChildren(this, 'div.EditableEmailField');
		FieldEditorTextField.applyToChildren(this, 'div.EditableTextField');

		if( !Element.hasClassName( this, 'readonly' ) ) {
			Sortable.create('Fields_fields', {tag: 'div', handle:'handle'});
			$('Form_EditForm').observeMethod('BeforeSave', this.beforeSave.bind(this));
		}
		
	},
	sortFields: function() {
		var fieldEditor = $('Fields_fields');
		
		if(fieldEditor) {
			
			var i, j, div, field, editables = fieldEditor.childNodes;
			
			for( i = 0; div = editables[i]; i++ ) {
				var fields = div.getElementsByTagName('input');
				/*fields[fields.length - 1].value = i;*/
				for( j = 0; field = fields.item(j); j++ ) {
					if( field.name == div.id + '[Sort]' ) {
						field.value = i;
					}
				}
			}
		}
	},
	beforeSave: function() {
		var fieldEditor = $('Fields_fields');
		
		if(fieldEditor) {
			this.sortFields();
		
			var children = $('Fields_fields').childNodes;
		
			for( var i = 0; i < children.length; ++i ) {
				var child = children[i];
			
				if( child.beforeSave )
					child.beforeSave();
			}
		}
	},
	deleteOption: function( optionToRemove ) {
		this.getElementsByTagName('div')[0].removeChild( optionToRemove );
	}
}

FieldEditorField = Class.create();

FieldEditorField.prototype = {
	initialize: function() {
		var fieldInfoDiv = this.findDescendant( 'div', 'FieldInfo' );
		
		this.titleField = this.findDescendant( 'input', 'text', element );
		
		this.titleField.onchange = this.changeTitle.bind(this);
		this.titleField.onblur = this.changeTitle.bind(this);
		this.titleField.onfocus = this.focusTitle.bind(this);
		
		this.titleField.onchange();
		
		var links = fieldInfoDiv.getElementsByTagName('a');
		this.toggler = this.findDescendant( 'a', 'toggler' );
		this.fieldInfo = this.getElementsByTagName('div')[0];
		
		
		this.toggler.onclick = this.toggle.bind(this);
		this.extraOptions = this.getExtraOptions();
		this.visible = false;
		this.deleteButton = this.findDescendant('a', 'delete');
		
		//this.style.height = "auto";
		
		if( this.deleteButton )
			this.deleteButton.onclick = this.confirmDelete.bind(this);
	},
	toggle: function() {
		// this.parentNode.autoSize();
		
		if( this.visible )
			this.hide();
		else
			this.show();
			
		this.fieldInfo.style.display = 'block';
			
		return false;
	},
	show: function() {
		/*this.style.height = "";
		this.style.overflow = "";*/
		
		if( this.selectedOption )
			this.selectedOption.checked = true;
		
		this.visible = true;
		// var extraOptions = this.getExtraOptions();
		// if( this.extraOptions )
		this.extraOptions.style.display = 'block';
	},
	hide: function() {

		this.visible = false;
		// var extraOptions = this.getExtraOptions();
		//if( this.extraOptions )
		this.extraOptions.style.display = 'none';
	},
	getExtraOptions: function() {
		var extraOptions = this.findDescendant('div', 'ExtraOptions');
		
		if( extraOptions.parentNode != this )
			alert("Found extra options but not this parent (" + this.id + ")");
		
		return extraOptions;
	},
	confirmDelete: function() {
		if( confirm( 'Are you sure you want to delete this field from the form?' ) )
			this.parentNode.parentNode.deleteOption( this );
		
		return false;
	},
	findDescendant: function( tag, clsName, element ) {
		
		if( !element )
			element = this;
		
		var descendants = element.getElementsByTagName(tag);
		
		for( var i = 0; i < descendants.length; i++ ) {
			var el = descendants[i];
			// alert(el.tagName + ' ' + el.className);
			
			if( tag.toUpperCase() == el.tagName && el.className.indexOf( clsName ) != -1 )
				return el;
		}
		
		return null;
	},
	focusTitle: function() {
		if( this.titleField && this.titleField.value == this.titleField.title )
			this.titleField.value = '';
	},
	changeTitle: function() {
		if( this.titleField && this.titleField.value == '' )
			this.titleField.value = this.titleField.title;
	}
}

FieldEditorHeadingField = Class.extend('FieldEditorField');

FieldEditorHeadingField.prototype = {
	initialize: function() {
		this.FieldEditorField.initialize();
	}
}

FieldEditorEmailField = Class.extend('FieldEditorField');


FieldEditorEmailField.prototype = {
	initialize: function() {
		this.extraOptions = this.getExtraOptions();
		this.defaultText = this.getDefaultText();
		
		this.FieldEditorField.initialize();
	},
	getDefaultText: function() {
		var defaultField = this.getDefaultField();
		if(defaultField) {
			var j, nestedChild, nestedChildren = defaultField.childNodes;
			for( j=0; nestedChild = nestedChildren[j]; j++) {
				if (nestedChild.className == 'defaultText' )
				{
					return nestedChild;
				}
			}
		}
	},
	getDefaultField: function() {
	
		var i, child, children = this.getElementsByTagName('div');
		for( i = 0; child = children[i]; i++){
			if(child.className == 'FieldDefault'){
				return child;
			}
		}
	}
}


FieldEditorTextField = Class.extend('FieldEditorField');
FieldEditorTextField.prototype = {
	initialize: function() {
		this.FieldEditorField.initialize();
		this.defaultText = this.getDefaultText();
		this.numRows = this.extraOptions.getElementsByTagName('input')[3];
		if(this.numRows) {
			this.numRows.onchange = this.changedRows.bind(this);
			this.oldNumRows = eval(this.numRows.value);
		}
		
	},
	changedRows: function() {
		var newNumRows = eval(this.numRows.value);
		
		// TODO Show that the field is actually longer than 5 rows
		if( newNumRows > 5 )
			newNumRows == 5;
		
		if( this.oldNumRows == newNumRows )
			return;
		
		if( newNumRows < 1 )
			newNumRows = 1;
			
		// resize/convert the textarea
		var newType = '';
		
		if( newNumRows == 1 )
			newType = 'input';
		else
			newType = 'textarea'
		
		var newDefaultText = document.createElement(newType);
		newDefaultText.className = this.defaultText.className;
		newDefaultText.value = this.defaultText.value;
		newDefaultText.id = this.defaultText.id;
		newDefaultText.name = this.defaultText.name;
		
		if( newDefaultText.rows ) 
			newDefaultText.rows = newNumRows;
			
		//Does not work any more		
		//this.replaceChild( newDefaultText, this.defaultText );
		
		//instead, using the following code
		var defaultField = this.getDefaultField();
		defaultField.replaceChild(newDefaultText, this.defaultText);
		
		//keep other codes.
		this.defaultText = newDefaultText;
		this.oldNumRows = newNumRows;
	},
	getDefaultText: function() {
		var defaultField = this.getDefaultField();
		
		if(defaultField) {
			var j, nestedChild, nestedChildren = defaultField.childNodes;
			for( j=0; nestedChild = nestedChildren[j]; j++) {
			
				if (nestedChild.className == 'defaultText' )
				{
					return nestedChild;
				}
			}
		}
	},
	getDefaultField: function() {
		var i, child, children = this.getElementsByTagName('div');
		for( i = 0; child = children[i]; i++){
			if(child.className == 'FieldDefault'){
				return child.getElementsByTagName('div')[0];
			}
		}
	}
}

/**
 * This should extend FieldEditorField
 */
FieldEditorRadioField = Class.extend('FieldEditorField');

FieldEditorRadioField.prototype = {
	initialize: function() {
		this.FieldEditorField.initialize();
		
	 	this.hiddenFields = this.findDescendant( 'div', 'hidden' );
		
		var dropdownBox = this.findDescendant( 'div', 'EditableDropdownBox' );
		
		this.optionList = dropdownBox.getElementsByTagName('ul')[0];
		var options = this.optionList.getElementsByTagName('li');
		
		if( options && options.length > 0 ) {
			this.addOptionField = options[options.length - 1];
		
			if( typeof this.addOptionField != 'undefined' && this.addOptionField.className != "AddDropdownOption" )
				this.addOptionField = null;
			
			// bind each option's delete link
			for( var i = 0; i < options.length - 1; i++ ) {
				var option = options[i];
				
				var links = option.getElementsByTagName('a');
				
				links[0].onclick = this.removeOption.bindAsEventListener(this);
			}
		}
		
		// Bind method to add option at the bottom of the list
		if( this.addOptionField ) {
			this.addOptionLink = this.addOptionField.getElementsByTagName('a')[0];
			this.addOptionTitle = this.addOptionField.getElementsByTagName('input')[0];
			this.addOptionLink.onclick = this.addOption.bind(this);
		}
	
		if( !Element.hasClassName( $('Fields'), 'readonly' ) ) {
			Sortable.create(this.optionList.id,{handle:'handle',tag:'li',only:'EditableFormFieldOption'});
		}
		this.FieldEditorField.initialize();
		
		// find the Delete field
		var hiddenFields = this.getElementsByTagName('input');
		
		for( var i = 0; i < hiddenFields.length; i++ ) {
			var field = hiddenFields[i];
			if( field.name.indexOf('[Deleted\]' ) != -1 )
				this.deletedOptions = field;
		}
	
		this.selectedOption = null;
		
		$('Form_EditForm').observeMethod('BeforeSave', this.beforeSave.bind(this));
	},
	firstElement: function( el ) {
		
		var node = el.firstChild;
		
		while( !node.tagName )
			node = node.nextSibling;
			
		return node;
	},	
	createOption: function( title, id, selected ) {
		var templateNode = this.firstElement( this.hiddenFields );
		var newOptionNode = templateNode.cloneNode( true );
		
		var newNodeChildren = newOptionNode.childNodes;
		
		for( var i = 0; i < newNodeChildren.length; i++ ) {
			
			var child = newNodeChildren[i];
			
			if( !child.tagName )
				continue;
			
			// input elements
			if( child.tagName.toLowerCase() == 'input' ) {
				
				if( child.className == 'text' ) {
					child.name = this.id + '[' + id + '][Title]';
					child.value = title;
				} else if( child.type == 'checkbox' )
					child.name = this.id + '[' + id + '][Default]';
				else if( child.type == 'radio' ) {
					child.value = id;
				} else if( child.type == 'hidden' ) {
					child.name = this.id + '[' + id + '][Sort]';
					child.value = -1;
				}
			} else if ( child.tagName.toLowerCase() == 'a' ) {
				child.onclick = this.removeOption.bindAsEventListener(this);
			}
		}
		
		this.optionList.insertBefore( newOptionNode, this.addOptionField );
	},
	removeOption: function( event ) {
		
		var target = event.srcElement;
		
		if( !target )
			target = event.target;
		
		var entry = target.parentNode.parentNode;
		var id = entry.id;
		
		if( !id.match( '/^[0-9]+$/' ) ) {
			if( this.deletedOptions.value )
				this.deletedOptions.value += ',';
			
			this.deletedOptions.value += id;
		}
		
		// remove the child from the options
		this.optionList.removeChild( entry );
		
		// remove the child from the dropdown
		/*for( var i = 0; i < this.dropdown.length; i++ ) {
			if( this.dropdown.options[i].text == title ) {
				this.dropdown.remove(i);
				return false;
			}	
		}*/
		
		if( !Element.hasClassName( $('Fields'), 'readonly' ) )
			Sortable.create(this.optionList.id,{handle:'handle',tag:'li',only:'EditableFormFieldOption'});
		
		// return false so it doesn't follow the link
		return false;
	},
	addOption: function() {
		if( this.addOptionTitle.value.length == 0 )
			return false;
		
		// The IDs come from the database and are the ID of the actual record
		// client-side, we will need a unique identifier that can be differentiated
		// from the actual database IDs, unless we just drop all records and 
		// recreate them
		var newID = '_' + this.optionList.childNodes.length;
		
		this.createOption( this.addOptionTitle.value, newID, this.optionList.childNodes.length == 0 );
		
		if( !Element.hasClassName( $('Fields'), 'readonly' ) )
			Sortable.create(this.optionList.id,{handle:'handle',tag:'li',only:'EditableFormFieldOption'});
		
		this.addOptionTitle.value = '';
		
		return false;
	},
	beforeSave: function() {
		this.sortOptions();
	},
	sortOptions: function() {
		var inputTags = this.optionList.getElementsByTagName('input');
		
		var i,item,sort=0;
		for(i=0;item=inputTags[i];i++) {
			if(item.name.match(/\[Sort\]$/) ) {
				item.value = sort++;
			}
		}
	},
	selectOption: function(newOption) {
		
		if( this.selectedOption )
				this.selectedOption.checked = false;
				
		newOption.checked = true;
		this.selectedOption = newOption;
	},
	selectOptionEvent: function(event) {
		if(event.srcElement)
			this.selectOption(event.srcElement);
		else	
			this.selectOption(event.target);
	},
	updateOption: function( prefix, tempID, newID, newSort ) {
		var options = this.optionList.childNodes;
		
		for( var i = 0; i < options.length; i++ ) {
			var option = options[i];
			
			var fields = option.getElementsByTagName('input');
			
			for( var j = 0; j < fields.length; j++ ) {
				var field = fields[j];
				
				var oldPrefix = prefix + '[' + tempID + ']';
				var newPrefix = prefix + '[' + newID + ']';
				
				if( field.name.indexOf( oldPrefix ) == 0 ) {
					
					if( field.name.match( /\[Sort\]$/ ) )
						field.value = newSort;
				
					// rename the field
					field.name = newPrefix + field.name.substring( oldPrefix.length );
					
				} else if( field.name == prefix + '[Default]' ) {
					field.value = newID;
				}			
			}
		}
	}
}

FieldEditorCheckboxGroupField = Class.extend('FieldEditorRadioField');

FieldEditorDropdown = Class.extend('FieldEditorRadioField');

Behaviour.register(
	{
		'div.FieldEditor ul.Menu li a': {
			

			urlForFieldMethod: function(methodName) {
				return this.ownerForm().action + '&action_callfieldmethod=1&fieldName=' + 'Fields' + '&ajax=1&methodName=' + methodName + '&NewID=' + this.numNewFields; 
			},
			ownerForm: function() {
				var f = this.parentNode;
				while(f && f.tagName.toLowerCase() != 'form') f = f.parentNode;
				return f;
			},

			onclick: function() {
				// get the ID of the field editor here
				
				if( Element.hasClassName( $('Fields'), 'readonly' ) )
					return false;
				
				action = this.urlForFieldMethod("addfield") + "&Type=" + this.id + ($('SecurityID') ? '&SecurityID=' + $('SecurityID').value : '');;
				
				statusMessage('Adding new field' );

				new Ajax.Request(action, {
					method: 'get',
					onFailure: reportError,
					onSuccess: this.appendNewField.bind(this)
				});
				
				return false;
			},
			
			appendNewField: function(response) {
				this.numNewFields++;
				
				var el = document.createElement('div');
				el.innerHTML = response.responseText;
				
				var i=0;
				while(!el.childNodes[i].tagName) i++;
				var newField = el.childNodes[i];
				$('Fields_fields').appendChild(newField);
				
				// Behaviour.debug();
				if(newField) {
					Behaviour.apply(newField,true);
					FieldEditor.applyTo('div.FieldEditor');
				}
				
				// do we want to make sorting explicit?
				Sortable.create('Fields_fields', {tag: 'div', handle:'handle'});
				
				statusMessage('Added new field','good');
			}
		}
	}
);

function reportError(request){
	// More complex error for developers
	statusMessage(request.responseText,'bad');

}
