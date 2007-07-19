RelatedDataEditor = Class.create();
RelatedDataEditor.applyTo('#Form_EditForm div.RelatedDataEditor');
RelatedDataEditor.prototype = {
	
	initialize: function(){
		var loading = $(this.id+"_loading");
		if(loading){
			loading.style.position = "absolute";
		}
		var loaded = $(this.id+"_loaded");
		if(loaded){
			loaded.style.position = "absolute";
		}
		
		var ownerForm = this.ownerForm()
		ownerForm.observeMethod('BeforeSave', this.beforeSave.bind(this));
		
		this.zeroToggle();
	},
	
	beforeSave: function(){
		if(this.newKeyValue) {
			var input = document.createElement('input');
			input.name=this.id+"[newID]";
			input.value=this.newKeyValue;
			input.style.display = 'none';
			this.appendChild(input);
		}
	},
	
	reload: function(parentID){
		var relatedClass = this.id
		var url = getAjaxURL('updateRelatedKey', this.getOwnerID(), 'ajax=1&RelatedClass='+relatedClass+'&ParentID='+parentID);
		var __relatedDataEditor = this;
		new Ajax.Updater(
			{success: $(this.id+'_keyholder')},
			url,
			{
				method: 'get',
				onFailure: function(response) {errorMessage("Error getting data", response);},
				onComplete: function() {
					Behaviour.apply(__relatedDataEditor);
					var keyfield = __relatedDataEditor.findKeyField();
					keyfield.onchange();
				}
			}
		);
	},
	
	updatingEffect: function(){
		var childrenHolder = this.findChildrenHolder();
		var loading = $(this.id+"_loading");
		if(loading){
			loading.style.display = "block";
			if(childrenHolder&&!childrenHolder.needToToggleAsBlock){
				new Effect.Fade(childrenHolder, {to: .3, duration: 1});
			}
		}
	},
	
	loadedEffect: function(){
		var childrenHolder = this.findChildrenHolder();
		var loading = $(this.id+"_loading");
		if(loading)
			loading.style.display = "none";
		var loaded = $(this.id+"_loaded");
		if(loaded){
			loaded.style.display = "block";
			new Effect.Fade(loaded);
		}
		if(childrenHolder&&!childrenHolder.needToToggleAsBlock)
			new Effect.Appear(childrenHolder);
	},
	
	findChildrenHolder: function(){
		return $(this.id+'_childrenholder');
	},
	
	findKeyField: function(){
		return this.getElementsByTagName('select')[0];
	},
	
	findChildren: function(entireForm){
		if(entireForm){
			var f = this.ownerForm();
			var allInputs = f.getElementsByTagName('input');
			var allTextareas = f.getElementsByTagName('textarea');
		}else{
			var allInputs = this.getElementsByTagName('input');
			var allTextareas = this.getElementsByTagName('textarea');
		}
		
		var relatedFields = new Array();
		var regEx = new RegExp('^'+this.id+'\\\[.+\\\]$');
		
		for(var i=0; i<allInputs.length; i++){
			if(allInputs[i].name.match(regEx)){
				relatedFields.push(allInputs[i]);
			}
		}
		
		for(var i=0; i<allTextareas.length; i++){
			if(allTextareas[i].name.match(regEx)){
				relatedFields.push(allTextareas[i]);
			}
		}
		
		return relatedFields;
	},
	
	updateChildren: function(dataArray, entireForm){
		var children = this.findChildren(entireForm);
		for(var i=0; i<children.length; i++){
			children[i].value = dataArray[children[i].name];
			
			if(typeof(children[i].onchange)!='undefined'){
				children[i].onchange();
			}
		}
	},
	
	getOwnerID: function(){
		var ownerForm = this.ownerForm();
		var fields = ownerForm.getElementsByTagName('input')
		for(var i=0; i<fields.length; i++){
			if(fields[i].name == 'ID') break;
		}
		return fields[i].value;
	},
	
	ownerForm: function() {
		var f = this.parentNode;
		while(f && f.tagName.toLowerCase() != 'form') f = f.parentNode;
		return f;
	},

	setNewRelatedKey: function(id) {
		this.newKeyValue = id;
	},
	
	unsetNewRelatedKey: function(){
		this.newKeyValue = null;
	},

	linkOnClick: function(){
		var relatedDataKey = this.findKeyField();
		if(this.status == 'adding'){
				relatedDataKey.onchange(false);
		}else{
			relatedDataKey.onchange();
		}
		
		
		var childrenHolder = this.findChildrenHolder();
		if(childrenHolder) {
			if(childrenHolder.needToToggleAsBlock){
				if(childrenHolder.style.display == 'none')
					childrenHolder.style.display = 'block';
				else
					childrenHolder.style.display = 'none';
			}else{
				var children = this.findChildren(false);
				if(children) for(var i=0; i<children.length; i++){
					if(children[i].needToToggle){
						if(children[i].parentNode.parentNode.style.display == 'none'){
							children[i].parentNode.parentNode.style.display = 'block';
						}	else {
							children[i].parentNode.parentNode.style.display = 'none';
						}
					}
				}
			}
		}
	},
	
	zeroToggle: function(){
		var children = this.findChildren();
		var key = this.findKeyField();
		
		if(key.value == 0 && this.status != 'adding'){
			for(var i=0; i<children.length; i++){
				children[i].disabled = true;
			}
		} else {
			for(var i=0; i<children.length; i++){
				children[i].disabled = false;
			}
		}
	}
}

Behaviour.register({
	'select.relatedDataKey':{
		onchange: function(withKey){
			
			if(withKey == null) withKey = true;
			
			var ownerField = this.getOwnerField();
			ownerField.updatingEffect();
			
			ownerField.zeroToggle();
			
			var editlink = this.getEditLink();
			if(editlink) editlink.checkDisplay();
			
			var __relatedDataKey = this;
			window.setTimeout(function() {
				var owner = __relatedDataKey.getOwnerField();
				var url = getAjaxURL("getRelatedData", owner.getOwnerID());
				var id=__relatedDataKey.value;
				var fieldSet = owner.findChildren(true);
				if(withKey){
					fieldSet[fieldSet.length] = __relatedDataKey;
				}else{
					for(var i=0; i<fieldSet.length; i++){
						if(fieldSet[i].name == owner.id+"[ID]"){
							fieldSet[i].value = null;
						}
					}
				}
				ajaxSubmitFieldSet(url, fieldSet, "RelatedClass="+owner.id);
				owner.loadedEffect();
			}, 1000);

		},
		
		getOwnerField: function(){
			var f = this.parentNode;
			while(f && !f.className.match(/RelatedDataEditor/)) f=f.parentNode;
			return f;
		},
		
		getEditLink: function(){
			var parent = this.parentNode;
			var links = parent.getElementsByTagName('a');
			for(var i=0; i<links.length; i++){
				if(links[i].className == 'editlink') return links[i];
			}
		}
	}
});

function getY(el){
	var ret = 0;
	while( el != null ) {
	ret += el.offsetTop;
	el = el.offsetParent;
	}
	return ret;
}

function getX(el){
	var ret = 0;
	while( el != null ) {
	ret += el.offsetLeft;
	el = el.offsetParent;
	}
	return ret;
}