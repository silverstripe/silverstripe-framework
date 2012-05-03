HasManyFileField = Class.create();
HasManyFileField.applyTo('div.hasmanyfile');
HasManyFileField.prototype = {
	initialize: function() {
		HasManyFileFieldAddButton.applyToChildren(this, 'a.addFile');
		HasManyFileFieldRemoveButton.applyToChildren(this, 'a.removeFile');
		HasManyFileFieldUploadButton.applyToChildren(this, 'a.uploadFile');
		this.tree = document.getElementsByClassName('TreeDropdownField', this)[0];
		this.list = this.getElementsByTagName('ul')[0];
		this.uploadFolderID = this.getElementsByTagName('input')[0].value;
		this.uploadMessage = document.getElementsByClassName('uploadMessage')[0];
	}
}

HasManyFileFieldAddButton = Class.create();
HasManyFileFieldAddButton.prototype = {
	onclick: function() {
		tree = this.parentNode.parentNode.tree;
		list = this.parentNode.parentNode.list;
		fieldid = this.parentNode.parentNode.id;
		fileid = tree.getElementsByTagName('input')[0].value;
		name = tree.getElementsByTagName('span')[0].innerHTML;
		
		input = document.createElement('input');
		input.className = 'hidden';
		input.type = 'hidden';
		input.name = fieldid + '[]';
		input.value = fileid;
		
		text = document.createTextNode(name);
		link = document.createElement('a');
		link.appendChild(text);
		
		removelink = document.createElement('a');
		removelink.className = 'removeFile';
		removelink.innerHTML = 'Remove file';
		
		li = document.createElement('li');
		li.appendChild(input);
		li.appendChild(link);
		li.appendChild(removelink);
		list.appendChild(li);
		
		HasManyFileFieldRemoveButton.applyTo(removelink);
		
		return false;
	}
}

HasManyFileFieldRemoveButton = Class.create();
HasManyFileFieldRemoveButton.prototype = {
	onclick: function() {
		li = this.parentNode;
		list = this.parentNode.parentNode;
		list.removeChild(li);
		
		return false;
	}
}

HasManyFileFieldUploadButton = Class.create();
HasManyFileFieldUploadButton.prototype = {
	initialize: function() {
		this.upload = new Upload({
			fileUploadLimit : '6',
			securityID : document.getElementById('SecurityID').value,
			beginUploadOnQueue : true,
			fileQueued : this.uploadFileQueuedCallback.bind(this),
			fileComplete : this.uploadFileCompleteCallback.bind(this),
			queueComplete : this.uploadQueueCompleteCallback.bind(this)
		});
		
		this.upload.setFolderID(this.parentNode.parentNode.uploadFolderID);
	},
	
	buildUI: function() {
	
	},
	
	onclick: function(event) {
		Event.stop(event);
		this.upload.browse();
	},
	
	uploadFileQueuedCallback: function(file,queueLength) {
		var message = ss.i18n.sprintf(
			ss.i18n._t('HASMANYFILEFIELD.UPLOADING', 'Uploading... %s'), 
			this.upload.getFilesToUpload()
		);
		this.parentNode.parentNode.uploadMessage.innerHTML = message;    
	},
	
	uploadFileCompleteCallback: function(file,serverData) {
		var message = ss.i18n.sprintf(
			ss.i18n._t('HASMANYFILEFIELD.UPLOADING', 'Uploading... %s'), 
			this.upload.getFilesUploaded() + "/" + this.upload.getFilesToUpload()
		);
		this.parentNode.parentNode.uploadMessage.innerHTML = message;
		idregex = /\/\* IDs: ([0-9,]+) \*\//;
		ids = serverData.match(idregex);
		fileid = ids[1];
		
		nameregex = /\/\* Names: ([^\s]+) \*\//;
		names = serverData.match(nameregex);
		name = names[1];
		
		fieldid = this.parentNode.parentNode.id;
		list = this.parentNode.parentNode.list;
		
		input = document.createElement('input');
		input.className = 'hidden';
		input.type = 'hidden';
		input.name = fieldid + '[]';
		input.value = fileid;
		
		text = document.createTextNode(name);
		link = document.createElement('a');
		link.appendChild(text);
		
		removelink = document.createElement('a');
		removelink.className = 'removeFile';
		removelink.innerHTML = 'Remove file';
		
		li = document.createElement('li');
		li.appendChild(input);
		li.appendChild(link);
		li.appendChild(removelink);
		list.appendChild(li);
		
		HasManyFileFieldRemoveButton.applyTo(removelink);
	},
	
	uploadQueueCompleteCallback: function() {
		this.parentNode.parentNode.uploadMessage.innerHTML = '';
	}
}

