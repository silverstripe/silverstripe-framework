/**
 * TreeDropdownField.js
 */
TreeDropdownField = Class.extend('Observable');
TreeDropdownField.prototype = {
	initialize: function() {
		// Hook up all the fieldy bits
		this.editLink = this.getElementsByTagName('a')[0];
		this.humanItems = this.getElementsByTagName('span')[0];
		this.inputTag = this.getElementsByTagName('input')[0];
		this.editLink.treeDropdownField = this;
		this.humanItems.treeDropdownField = this;
		this.inputTag.treeDropdownField = this;
		
		this.editLink.onclick = this.edit_click;
		this.humanItems.onclick = this.edit_click;
		this.inputTag.setValue = this.setValue.bind(this);
	},

	destroy: function() {
		if(this.editLink) {
			this.editLink.onclick = null;
			this.editLink.treeDropdownField = null;
			this.editLink = null;
		}
		if(this.humanItems) {
			this.humanItems.onclick = null;
			this.humanItems.treeDropdownField = null;
			this.humanItems = null;
		}
		if(this.inputTag) {
			this.inputTag.setValue = null;
			this.inputTag.treeDropdownField = null;
			this.inputTag = null;
		}
	},
	
	refresh: function() {
		this.createTreeNode();
		
		this.ajaxGetTree( (function(response) {
			this.newTreeReady(response, false);
			this.updateTreeLabel();
		}).bind(this));
	},
	
	helperURLBase: function() {
		return this.ownerForm().action + '/field/' + this.inputTag.name + '/';
	},
	ownerForm: function() {
		var f =this.parentNode;
		while(f && f.tagName.toLowerCase() != 'form') f = f.parentNode;
		return f;
	},
	
	toggleTree: function() {
		if(this.treeShown) this.hideTree();
		else this.showTree();
	},
	
	createTreeNode: function(keepTreeHidden) {
		if(!this.itemTree) {
			this.itemTree = document.createElement('div');
			
			if(keepTreeHidden) {
				this.hideTree();
			}
			
			this.itemTree.className = 'tree_holder';
			this.itemTree.innerHTML = ss.i18n._t('LOADING', 'Loading...');
			this.appendChild(this.itemTree);
		}
	},
	
	showTree: function () {
		this.treeShown = true;
		
		if(this.itemTree) {
			this.itemTree.style.display = 'block';
			// Store this in a parameter so that stopObserving works
			this.bound_testForBlur = this.testForBlur.bind(this);
			Event.observe(document, 'click', this.bound_testForBlur);
			this.stretchIframeIfNeeded();
		} else {
			this.createTreeNode();
			
			this.ajaxGetTree( (function(response) {
				this.newTreeReady(response, false);
				this.updateTreeLabel();
			}).bind(this));
		}
	},
	
	/**
	 * If this control is inside an iframe, stretch the iframe out to fit the tree.
	 */
	stretchIframeIfNeeded: function() {
		if(parent && parent.document) {
			if(!this.iframeObj) {
				var iframes = parent.document.getElementsByTagName('iframe')
				var i,item;
				for(i=0;item=iframes[i];i++) {
					if(item.contentWindow == window) {
						this.iframeObj = item;
						break;
					}
				}
			}
			
			// This iframe stretching doesn't work with the greybox
			if(this.iframeObj && this.iframeObj.id == 'GB_frame') return;
			
			var desiredHeight = Position.cumulativeOffset(this.itemTree)[1] + this.itemTree.offsetHeight + 2;
			if(this.iframeObj && desiredHeight > this.iframeObj.offsetHeight) {
				this.iframeObj.oldHeight = this.iframeObj.offsetHeight;
				this.iframeObj.style.height = desiredHeight + 'px';
			}
		}
	},
	
	unstretchIframeIfNeeded: function() {
		if(this.iframeObj && this.iframeObj.oldHeight)
			this.iframeObj.style.height = this.iframeObj.oldHeight + 'px';
	},
	
	testForBlur: function (event) {
		var clicked = Event.element(event);
		if(clicked != this.itemTree && !hasAncestor(clicked, this.itemTree) && clicked != this.editLink && clicked != this.humanItems) {
			this.hideTree();
		}
	},
		
	hideTree: function() {
		this.treeShown = false;
		if(this.itemTree) {
			this.itemTree.style.display = 'none';
			if(this.bound_testForBlur) Event.stopObserving(document, 'click', this.bound_testForBlur);
			// this.editLink.style.display = this.humanItems.style.display = 'block';
			this.unstretchIframeIfNeeded();
		}
		// this.style.position = '';
	},
	
	ajaxGetTree: function(after) {
		var ajaxURL = this.helperURLBase() + 'gettree?forceValues=' + this.inputTag.value;
		ajaxURL += $('SecurityID') ? '&SecurityID=' + $('SecurityID').value : '';
		if($('Form_EditForm_Locale')) ajaxURL += "&locale=" + $('Form_EditForm_Locale').value;
		
		new Ajax.Request(ajaxURL, {
			method : 'get', 
			onSuccess : after,
			onFailure : function(response) { errorMessage("Error getting data", response); }
		})
	},
	
	/**
	 * Called once the tree has been delivered from ajax
	 */
	newTreeReady: function (response, keepTreeHidden) {
		// alert('newTreeReady');

		this.itemTree.innerHTML = response.responseText;
		// HACK IE6: see http://www.hedgerwow.com/360/bugs/css-select-free.html
		this.itemTree.appendChild(document.createElement('iframe'));
		this.tree = Tree.create(this.itemTree.getElementsByTagName('ul')[0], { 
			ajaxExpansion: this.ajaxExpansion, 
			getIdx: function() {
				return this.id.replace(this.options.idxBase,'');
			},
			idxBase : 'selector-' + this.inputTag.name + '-',
			dropdownField : this,
			onselect : this.tree_click
		});

		// Select the appropriate items
		var selectedItems = this.inputTag.value.split(/ *, */);
		var i, isSelected = {};
		for(i=0;i<selectedItems.length;i++) isSelected[selectedItems[i]] = true;
		
		if(!keepTreeHidden) {
			this.showTree();
		}
	},

	/**
	 * Expander bound to each tree node
	 */
	ajaxExpansion: function() {
		this.addNodeClass('loading');
		var ul = this.treeNodeHolder();
		ul.innerHTML = ss.i18n._t('LOADING', 'Loading...');
		
		var ajaxURL = this.options.dropdownField.helperURLBase() + 'getsubtree?&SubtreeRootID=' + this.getIdx();
		ajaxURL += $('SecurityID') ? '&SecurityID=' + $('SecurityID').value : '';
		if($('Form_EditForm_Locale')) ajaxURL += "&locale=" + $('Form_EditForm_Locale').value;
		
		new Ajax.Request(ajaxURL, {
			onSuccess : this.installSubtree.bind(this),
			onFailure : function(response) { errorMessage('error loading subtree', response); }
		});
	},
	
	setValue: function(val) {
		if(this.inputTag.value != val) {
			this.inputTag.value = val;
			this.notify('Change', val);
			
			// If the tree item is already downloaded, just update the label
			if($('selector-' + this.inputTag.name + '-' + this.inputTag.value)) {
				this.updateTreeLabel();
				
			// Otherwise, update the tree with ajax
			} else {
				this.ajaxGetTree( (function(response) {
					this.createTreeNode(true);
					this.newTreeReady(response, true);
					this.updateTreeLabel();
				}).bind(this));
			}
		}
	},
	updateTreeLabel: function() {
		var treeNode;
		if(treeNode = $('selector-' + this.inputTag.name + '-' + this.inputTag.value)) {
			this.humanItems.innerHTML = treeNode.getTitle();

			if(treeNode.tree.selected && treeNode.tree.selected.removeNodeClass) treeNode.tree.selected.removeNodeClass('current');
			treeNode.addNodeClass('current');
			this.tree.selected = treeNode;
			
		} else {
			this.humanItems.innerHTML = this.inputTag.value ? this.inputTag.value : '(Choose)';
			/*
			new Ajax.Request(this.options.dropdownField.helperURLBase() + '&methodName=findsubtreefor&ID=' + this.getIdx(), {
				onSuccess : this.installSubtree.bind(this),
				onFailure : function(response) { errorMessage('error loading subtree', response); }
			});
			*/			
		}
	},
	setValueFromTree: function(treeID, title) {
		this.humanItems.innerHTML = title;
		this.inputTag.value = treeID.replace('selector-' + this.inputTag.name + '-','');
		this.notify('Change', this.inputTag.value);

		this.hideTree();
	},
	
	edit_click : function() {
		this.treeDropdownField.toggleTree();
		return false;
	},
	tree_click : function() {
		this.options.dropdownField.setValueFromTree(this.id, this.getTitle());

		if(this.tree.selected && this.tree.selected.removeNodeClass) this.tree.selected.removeNodeClass('current');
		this.addNodeClass('current');
		this.tree.selected = this;

	}
}

TreeMultiselectField = Class.extend('TreeDropdownField');
TreeMultiselectField.prototype = {
	destroy: function() {
		if(this.TreeDropdownField) this.TreeDropdownField.destroy();
		this.TreeDropdownField = null;
	},
	
	newTreeReady: function (response) {
		this.TreeDropdownField.newTreeReady(response);
		MultiselectTree.create(this.tree);
	
		// Select the appropriate items
		var selectedItems = this.inputTag.value.split(/ *, */);
		var i, isSelected = {};
		for(i=0;i<selectedItems.length;i++) isSelected[selectedItems[i]] = true;
		
		var allNodes = this.tree.getElementsByTagName('li');
		for(i=0;i<allNodes.length;i++) {
			if(isSelected[allNodes[i].getIdx()]) {
				this.tree.multiselect_handleSelectionChange(allNodes[i]);
			}
		}
	},
	
	hideTree: function() {
		this.TreeDropdownField.hideTree();

		var internalVal = humanVal = ""; 
				
		for(i in this.tree.selectedNodes) {
			internalVal += (internalVal?',':'') + i;
			humanVal += (humanVal?', ':'') + this.tree.selectedNodes[i];
		}
		
		this.inputTag.value = internalVal;
		this.humanItems.innerHTML = humanVal;
	}
	
		
}

TreeMultiselectField.applyTo('div.TreeDropdownField.multiple');
TreeDropdownField.applyTo('div.TreeDropdownField.single');