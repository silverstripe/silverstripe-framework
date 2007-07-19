DropdownField_WithAdd = Class.create();
DropdownField_WithAdd.applyTo('div.dropdownfield_withadd');
DropdownField_WithAdd.prototype = {
	initialize: function() {
		WithAdd_Link.applyToChildren(this, '.editlink');
		WithAdd_Link.applyToChildren(this, '.link');
	}, 
	
	getAddLink: function(){
		return this.getElementsByTagName('a')[0];
	},
	getUseExistingLink: function(){
		return this.getElementsByTagName('a')[1];
	},
	toggleLinks: function(){
		var addlink = this.getAddLink();
		var useExistinglink = this.getUseExistingLink();
		if(addlink.style.display == "none"){
			addlink.style.display = "inline";
			useExistinglink.style.display = "none";
		}else{
			addlink.style.display = "none";
			useExistinglink.style.display = "inline";
		}
	}
}

WithAdd_Link = Class.create();
WithAdd_Link.prototype = {
	onclick: function(){
		this.toggleSiblingDropdown();
		
		var ownerField = this.getOwnerField();
		ownerField.toggleLinks();
		var relatedDataEditor = this.getRelatedDataEditor();
		if(relatedDataEditor){
			if(this.className.match(/addlink/))
				relatedDataEditor.status = 'adding';
			else
				relatedDataEditor.status = 'useexisting';
			relatedDataEditor.linkOnClick();		
		}
		return false;
	},
	
	findSiblingDropdown: function(){
		return this.parentNode.getElementsByTagName('select')[0];
	},
	
	toggleSiblingDropdown: function(){
		var dropdown = this.findSiblingDropdown();
		if(dropdown.disabled)
			dropdown.disabled = false;
		else
			dropdown.disabled = true;
	},
	
	getOwnerField: function(){
		var f = this.parentNode;
		while(f && !f.className.match(/dropdownfield_withadd/)) f=f.parentNode;
		return f;
	},
	
	getRelatedDataEditor: function(){
		var f = this.parentNode;
		while(f && !f.className.match(/RelatedDataEditor/)) {
			f=f.parentNode;
			if(f.tagName.toLowerCase() == 'form') break;
		}
		return f;
	}
}

WithAdd_Link = Class.create();
WithAdd_Link.prototype = {
	initialize: function() {
		this.checkDisplay();
	},

	onclick: function(){
		var relatedDataEditor = this.getRelatedDataEditor();
		if(relatedDataEditor){
			relatedDataEditor.linkOnClick();		
		}
		return false;
	}, 
	
	getRelatedDataEditor: function(){
		var f = this.parentNode;
		while(f && !f.className.match(/RelatedDataEditor/)) {
			f=f.parentNode;
			if(f.tagName.toLowerCase() == 'form') break;
		}
		return f;
	},
	
	checkDisplay: function(){
		var dropdown = this.getdropdownField();
		if(dropdown.value==0)
			this.style.display = 'none';
		else
			this.style.display = 'inline';
	},
	
	getdropdownField: function(){
		var f = this.parentNode;
		var dropdown = f.getElementsByTagName('select')[0];
		return dropdown;
	}
}
