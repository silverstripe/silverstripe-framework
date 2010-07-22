/**
 * TreeDropdownField.js
 */
TreeDropdownField = Class.extend('Observable');
TreeDropdownField.prototype = {
	initialize: function() {
		// Hook up all the fieldy bits
		this.editLink = this.getElementsByTagName('a')[0];
		if (this.getElementsByTagName('span').length > 0) {
			// no search, humanItems is a span
			this.humanItems = this.getElementsByTagName('span')[0];
			this.inputTag = this.getElementsByTagName('input')[0];
		}
		else {
			// search is present, humanItems is an input
			this.inputTag = this.getElementsByTagName('input')[0];
			this.humanItems = this.getElementsByTagName('input')[1];
			this.humanItems.onkeyup = this.search_onkeyup;
		}
		this.editLink.treeDropdownField = this;
		this.humanItems.treeDropdownField = this;
		this.inputTag.treeDropdownField = this;
		
		this.editLink.onclick = this.edit_click;
		this.humanItems.onclick = this.human_click;
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
	
	getName: function() {
		return this.inputTag.name;
	},
	
	refresh: function() {
		this.createTreeNode();
		
		this.ajaxGetTree( (function(response) {
			this.newTreeReady(response, false);
			this.updateTreeLabel();
		}).bind(this));
	},
	
	// Build a URL from the field's base URL and the given sub URL
	buildURL: function(subURL) {
		var baseURL = jQuery(this).attr('href');
		if (!baseURL) {
			// Occurs if treedropdown has no form e.g. treefields in widget areas.
			baseURL = this.ownerForm().action + '/field/' + this.getName() + '/';
			var baseTags = document.getElementsByTagName('base');
			var base = (baseTags) ? baseTags[0].href : '';
			if (base == baseURL.substring(0, base.length))
				baseURL = baseURL.substring(base.length);
		}
		var subHasQuerystring = subURL.match(/\?/);
		
		if(baseURL.match(/^(.*)\?(.*)$/)) {
			if(subHasQuerystring) return RegExp.$1 + '/' + subURL + '&' + RegExp.$2
			else return RegExp.$1 + '/' + subURL + '?' + RegExp.$2
		} else {
			return baseURL + '/' + subURL;
		}
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

	deleteTreeNode: function() {
		if (!this.itemTree) return;
		var parent = this.itemTree.parentNode;
		parent.removeChild(this.itemTree);
		this.itemTree = null;
	},

	showTree: function () {
		if (!this.treeShown) this.saveCurrentState();
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

	saveCurrentState: function() {
		this.origHumanText = this.getHumanText();
	},

	restoreOriginalState: function() {
		this.setHumanText(this.origHumanText);
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
		var ajaxURL = this.buildURL('tree?forceValue=' + this.inputTag.value); 
		var secId = jQuery(':input[name=SecurityID]');
		ajaxURL += secId.length ? '&SecurityID=' + secId.val() : '';
		var localeField = jQuery(this.ownerForm()).find(':input[name=locale],:input[name=Locale]');
		if(localeField.length) {ajaxURL += "&locale=" + localeField.val();}
		if(this.inputTag.value) ajaxURL += '&forceValue=' + this.inputTag.value;
		if(this.search() != null) ajaxURL += "&search=" + this.search(); 
		new Ajax.Request(ajaxURL, {
			method : 'get', 
			onSuccess : after,
			onFailure : function(response) { errorMessage("Error getting data", response); }
		})
	},

	search: function() {
		if (this.humanItems.nodeName != 'INPUT' || !this.searched) return null;
		return this.humanItems.value;
	},

	/**
	 * Called once the tree has been delivered from ajax
	 */
	newTreeReady: function (response, keepTreeHidden) {
		this.itemTree.innerHTML = response.responseText;
		// HACK IE6: see http://www.hedgerwow.com/360/bugs/css-select-free.html
		this.itemTree.appendChild(document.createElement('iframe'));
		this.tree = Tree.create(this.itemTree.getElementsByTagName('ul')[0], { 
			ajaxExpansion: this.ajaxExpansion, 
			getIdx: function() {
				return this.getElementsByTagName('a')[0].getAttribute('rel');
			},
			idxBase : 'selector-' + this.getName() + '-',
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
		
		var ajaxURL = this.options.dropdownField.buildURL('tree/' + this.getIdx());
		ajaxURL += $('SecurityID') ? '&SecurityID=' + $('SecurityID').value : '';
		if($('Form_EditForm_Locale')) ajaxURL += "&locale=" + $('Form_EditForm_Locale').value;
		// ajaxExpansion is called in context of TreeNode, not Tree, so search() doesn't exist.
		if (this.search && this.search() != null) ajaxURL += "&search=" + this.search();
		
		new Ajax.Request(ajaxURL, {
			onSuccess : this.installSubtree.bind(this),
			onFailure : function(response) { errorMessage('error loading subtree', response); }
		});
	},
	
	setValue: function(val) {
		this.inputTag = this.getElementsByTagName('input')[0];

		if(this.inputTag.value != val) {
			this.inputTag.value = val;
			this.notify('Change', val);
			
			// If the tree item is already downloaded, just update the label
			if($('selector-' + this.getName() + '-' + this.inputTag.value)) {
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
		if ( this.searched || (this.humanItems.nodeName == 'INPUT' && !this.inputTag.value) ) return; // don't update the search
		var treeNode;
		if(treeNode = $('selector-' + this.getName() + '-' + this.inputTag.value)) {
			this.setHumanText(treeNode.getTitle());

			if(treeNode.tree.selected && treeNode.tree.selected.removeNodeClass) treeNode.tree.selected.removeNodeClass('current');
			treeNode.addNodeClass('current');
			this.tree.selected = treeNode;
			
		} else {
			this.setHumanText(this.inputTag.value ? this.inputTag.value : '(Choose)');
		}
	},

	getHumanText: function() {
		return this.humanItems.nodeName == 'INPUT' ? this.humanItems.value : this.humanItems.innerHTML;
	},

	setHumanText: function (s) {
		if (this.humanItems.nodeName == 'INPUT')
			this.humanItems.value = s;
		else
			this.humanItems.innerHTML = s;
	},

	setValueFromTree: function(treeID, title) {
		this.setHumanText(title);
		this.inputTag.value = treeID.replace('selector-' + this.getName() + '-','');
		this.notify('Change', this.inputTag.value);

		this.hideTree();
	},

	edit_click : function() {
		if (this.treeDropdownField.treeShown) this.treeDropdownField.restoreOriginalState();
		this.treeDropdownField.toggleTree();
		return false;
	},

	search_onkeyup: function(e) {
		if(typeof window.event!="undefined") e=window.event; //code for IE
		if (e.keyCode == 27) { // esc, cancel the selection and hide the tree.
			this.treeDropdownField.restoreOriginalState();
			this.treeDropdownField.hideTree();
		}
		else {
			var that = this;
			clearTimeout(this.timeout);
			this.timeout = setTimeout(function() {
				that.treeDropdownField.searched = true;
				that.treeDropdownField.deleteTreeNode();
				that.treeDropdownField.showTree();
			}, 750);
		}
	},

	human_click: function() {
		if (this.treeDropdownField.humanItems.nodeName != 'INPUT') {
			if (this.treeDropdownField.treeShown) this.treeDropdownField.restoreOriginalState();
			this.treeDropdownField.toggleTree();
			return false;
		}

		if (!this.treeDropdownField.treeShown) this.treeDropdownField.toggleTree();
		if (!this.treeDropdownField.defaultCleared || !this.treeDropdownField.searched) {
			this.treeDropdownField.defaultCleared = true;
			this.treeDropdownField.setHumanText('');
		}

		return false;
	},
	
	tree_click : function() {
		this.options.dropdownField.setValueFromTree(this.id, this.getTitle());

		if(this.tree.selected && this.tree.selected.removeNodeClass) this.tree.selected.removeNodeClass('current');
		this.addNodeClass('current');
		this.tree.selected = this;
		
		this.options.dropdownField.searched = false;
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
		this.tree.options.onselect = this.updateVal.bind(this);
	
		// Select the appropriate items
		var selectedItems = this.inputTag.value.split(/ *, */);
		var i, isSelected = {};
		for(i=0;i<selectedItems.length;i++) isSelected[selectedItems[i]] = true;

		var allNodes = this.tree.getElementsByTagName('li');
		
		for(i=0;i<allNodes.length;i++) {
			allNodes[i].id.match(/([^-]+)-(\d+)$/);
			var idx = RegExp.$2
			if(isSelected[idx]) {
				this.tree.selectNode(allNodes[i]);
				allNodes[i].expose();
			}
		}
	},
	
	hideTree: function() {
		this.TreeDropdownField.hideTree();
		if(this.tree) this.updateVal();
	},

	/**
	 * Update the inputTag and humanItems from the currently selected nodes.
	 */
	updateVal: function() {
		var internalVal = humanVal = ""; 
		
		for(i in this.tree.selectedNodes) {
			internalVal += (internalVal?',':'') + i;
			humanVal += (humanVal?', ':'') + this.tree.selectedNodes[i];
		}
		
		this.inputTag.value = internalVal;
		this.setHumanText(humanVal);
	},

	updateTreeLabel: function() {
		var treeNode;
		
		if(this.inputTag.value) {
			var innerHTML = '';
			var selectedItems = this.inputTag.value.split(/ *, */);
			for(i=0;i<selectedItems.length;i++) {
				if(treeNode = $('selector-' + this.getName() + '-' + selectedItems[i])) {
					innerHTML += (innerHTML?', ':'') + treeNode.getTitle();
				} else {
					innerHTML += selectedItems[i];
				}
			}
			this.setHumanText(innerHTML);
		} else {
			this.setHumanText('(Choose)');
		}
	}

}

TreeMultiselectField.applyTo('div.TreeDropdownField.multiple');
TreeDropdownField.applyTo('div.TreeDropdownField.single');
