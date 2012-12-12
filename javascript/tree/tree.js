/*
 * Content-separated javascript tree widget
 * 
 * Usage:
 *     behaveAs(someUL, Tree)
 *  OR behaveAs(someUL, DraggableTree)
 *
 * Extended by Steven J. DeRose, deroses@mail.nih.gov, sderose@acm.org.
 * 
 * INPUT REQUIREMENTS:
 *     Put class="tree" on topmost UL(s).
 *     Can put class="closed" on LIs to have them collapsed on startup.
 *
 * The structure we build is:
 *     li class="children last closed"      <=== original li from source
 *                                           children: there's a UL child
 *                                           last: no following LI
 *                                           closed: is collapsed (may be in src)
 *       span class="a children spanClosed" <=== contains children before UL
 *                                           children: there's a UL (now a sib)
 *                                           spanClosed: is collapsed
 *          span class="b"                  <=== +/- click is caught here
 *             span class="c"               <=== for spacing and lines
 *                a href="..."              <=== original pre-UL stuff (e.g., a)
 *       ul...
 * 
 */

Tree = Class.create();
Tree.prototype = {
	/*
	 * Initialise a tree node, converting all its LIs appropriately.
	 * This means go through all li children, and move the content of each
	 * (before any UL child) down into 3 intermediate spans, classes a/b/c.
	 */
	initialize: function(options) {
		this.isDraggable = false;
		var i,li;
	
		this.options = options ? options : {};
		if(!this.options.tree) this.options.tree = this;
	
		this.tree = this.options.tree;
		
		// Set up observer
		if(this == this.tree) Observable.applyTo(this);
	
		// Find all LIs to process
		// Don't let it re-do a node it's already done.
		for(i=0;i<this.childNodes.length;i++) {
			if(this.childNodes[i].tagName && this.childNodes[i].tagName.toLowerCase() == 'li' &&
			!(this.childNodes[i].childNodes[0] &&
			this.childNodes[i].childNodes[0].attributes &&
			this.childNodes[i].childNodes[0].attributes["class"] &&
			this.childNodes[i].childNodes[0].attributes["class"] == "a")) {
				li = this.childNodes[i];
				
				this.castAsTreeNode(li);
				
				// If we've added a DIV to this node, then increment i;
				while(this.childNodes[i].tagName.toLowerCase() != "li") i++;
			}
		}
	
		// Not sure what following line is really doing for us....
		this.className = this.className.replace(/ ?unformatted ?/, ' ');
		
		if(li) {
			li.addNodeClass('last');
			//li.addNodeClass('closed');
				
			if(this.parentNode.tagName.toLowerCase() == "li") {
				this.treeNode = this.parentNode;
			}

			return true;
		} else {
			return false;
		}
	},
	destroy: function() {
		this.tree = null;
		this.treeNode = null;
		if(this.options) this.options.tree = null;
		this.options = null;
	},

	/**
	 * Convert the given <li> tag into a suitable tree node	
	 */
	castAsTreeNode: function(li) {
		behaveAs(li, TreeNode, this.options);
	},

	getIdxOf : function(el) {
		if(!el.treeNode) el.treeNode = el;
		// Special case for TreeMultiselectField
		if(el.treeNode.id.match(/^selector-([^-]+)-([0-9]+)$/)) return RegExp.$2;
		// Other case for LHS tree of CMS
		if(el.treeNode.id.match(/([^-]+)-(.+)$/)) return RegExp.$1;
		else return el.treeNode.id;
	},
	
	childTreeNodes: function() {
		var i,item, children = [];
		for(i=0;item=this.childNodes[i];i++) {
			if(item.tagName && item.tagName.toLowerCase() == 'li') children.push(item);
		}
		return children;
	},
	hasChildren: function() {
		return this.childTreeNodes().length > 0;
	},
	
	/**
	 * Turn a normal tree into a draggable one.
	 */
	makeDraggable: function() {
		this.isDraggable = true;
		var i,item,x;

		var trees = this.getElementsByTagName('ul');
		for(x in DraggableTree.prototype) this[x] = DraggableTree.prototype[x];
		DraggableTree.prototype.setUpDragability.apply(this);

		var nodes = this.getElementsByTagName('li');
		for(i=0;item=nodes[i];i++) {
			for(x in DraggableTreeNode.prototype) item[x] = DraggableTreeNode.prototype[x];
		}
		for(i=0;item=trees[i];i++) {
			for(x in DraggableTree.prototype) item[x] = DraggableTree.prototype[x];
		}
		for(i=0;item=nodes[i];i++) {
			DraggableTreeNode.prototype.setUpDragability.apply(item);
		}
		for(i=0;item=trees[i];i++) {
			DraggableTree.prototype.setUpDragability.apply(item);
		}
	},
	
	/**
	 * Add the given child node to this tree node.
	 * If 'before' is specified, then it will be inserted before that.
	 */
	appendTreeNode : function(child, before) {
		if(!child) return;

		// Remove from the old parent node - this will ensure that the classes of the old tree
		// item are updated accordingly
		if(child && child.parentTreeNode) {
			var oldParent = child.parentTreeNode;
			oldParent.removeTreeNode(child);
		}
		var lastNode, i, holder = this;
		if(lastNode = this.lastTreeNode()) lastNode.removeNodeClass('last');
		
		// Do the actual moving
		if(before) {
			child.removeNodeClass('last');
			
			if(holder != before.parentNode) {
				throw("TreeNode.appendTreeNode: 'before' not contained within the holder");
				holder.appendChild(child);
			} else {
				holder.insertBefore(child, before);
			}
		} else {
			holder.appendChild(child);
		}
		
		if(this.parentNode && this.parentNode.fixDragHelperDivs) this.parentNode.fixDragHelperDivs();
		if(oldParent && oldParent.fixDragHelperDivs) oldParent.fixDragHelperDivs();
		
		// Update the helper classes
		if(this.parentNode && this.parentNode.tagName.toLowerCase() == 'li') {
			if(this.parentNode.className.indexOf('closed') == -1) this.parentNode.addNodeClass('children');
			this.lastTreeNode().addNodeClass('last');
		}
		
		// Update the helper variables
		if(this.parentNode.tagName.toLowerCase() == 'li') child.parentTreeNode = this.parentNode;
		else child.parentTreeNode = null;
		
		if(this.isDraggable) {
			for(x in DraggableTreeNode.prototype) child[x] = DraggableTreeNode.prototype[x];
			DraggableTreeNode.prototype.setUpDragability.apply(child);
		}
	},

	lastTreeNode : function() {
		var i, holder = this;
		for(i=holder.childNodes.length-1;i>=0;i--) {
			if(holder.childNodes[i].tagName && holder.childNodes[i].tagName.toLowerCase() == 'li') return holder.childNodes[i];
		}
	},
	
	/**
	 * Remove the given child node from this tree node.
	 */
	removeTreeNode : function(child) {
		// Remove the child
		var holder = this;
		try { holder.removeChild(child); } catch(er) { }
		
		// Look for remaining children
		var i, hasChildren = false;
		for(i=0;i<holder.childNodes.length;i++) {
			if(holder.childNodes[i].tagName && holder.childNodes[i].tagName.toLowerCase() == "li") {
				hasChildren = true; 
				break; 
			}
		}

		// Update the helper classes accordingly
		if(!hasChildren) this.removeNodeClass('children');
		else this.lastTreeNode().addNodeClass('last');
		
		// Update the helper variables
		if(child.parentTreeNode == this.parentNode) {
			child.parentTreeNode = null;
		}
	},
	
	open: function() {
		
	},
	
	expose: function() {
	
	},
	
	addNodeClass : function(className) {
		if( this.parentNode.tagName.toLowerCase() == 'li' )
			this.parentNode.addNodeClass(className);
	},
	removeNodeClass : function(className) {
		if( this.parentNode.tagName.toLowerCase() == 'li' )
			this.parentNode.removeNodeClass(className);
	}
}

TreeNode = Class.create();
TreeNode.prototype = {
	initialize: function(options) {
		var spanA, spanB, spanC;
		var startingPoint, stoppingPoint, childUL;
		var j;
		
		// Basic hook-ups
		var li = this;
		this.options = options ? options : {};
		
		this.tree = this.options.tree;
		
		if(!this.ajaxExpansion && this.options.ajaxExpansion)
			this.ajaxExpansion = this.options.ajaxExpansion;
		if(this.options.getIdx)
			this.getIdx = this.options.getIdx;
		
		// Get this.recordID from the last "-" separated chunk of the id HTML attribute
		// eg: <li id="treenode-6"> would give a recordID of 6
		if(this.id && this.id.match(/([^-]+)-(.+)$/))
			this.recordID = RegExp.$1;
		
		// Create our extra spans
		spanA = document.createElement('span');
		spanB = document.createElement('span');
		spanC = document.createElement('span');
		spanA.appendChild(spanB);
		spanB.appendChild(spanC);	
		spanA.className = 'a ' + li.className.replace('closed','spanClosed');
		spanB.className = 'b';
		spanB.onclick = TreeNode_bSpan_onclick;
		spanC.className = 'c';
		
		this.castAsSpanA(spanA);
		
		// Add +/- icon to select node that has children
		if (li.hasChildren() && li.className.indexOf('current') > -1) {
			li.className = li.className + ' children';
			spanA.className = spanA.className + ' children';
		}
		
		// Find the UL within the LI, if it exists
		stoppingPoint = li.childNodes.length;
		startingPoint = 0;
		childUL = null;
		for(j=0;j<li.childNodes.length;j++) {
			// Find last div before first ul (unnecessary in our usage)
			/*
			if(li.childNodes[j].tagName && li.childNodes[j].tagName.toLowerCase() == 'div') {
				startingPoint = j + 1;
				continue;
			}
			*/
	
			if(li.childNodes[j].tagName && li.childNodes[j].tagName.toLowerCase() == 'ul') {
				childUL = li.childNodes[j];
				stoppingPoint = j;
				break;					
			}
		}
		
		// Move all the nodes up until that point into spanC
		for(j=startingPoint;j<stoppingPoint;j++) {
			/* Use [startingPoint] every time, because the appentChild
				removes the node, so it then points to the next one. */
			spanC.appendChild(li.childNodes[startingPoint]);
		}
		
		// Insert the outermost extra span into the tree
		if(li.childNodes.length > startingPoint) li.insertBefore(spanA, li.childNodes[startingPoint]);
		else li.appendChild(spanA);
	
		// Create appropriate node references;
		if(li.parentNode && li.parentNode.parentNode && li.parentNode.parentNode.tagName.toLowerCase() == 'li') {
			li.parentTreeNode = li.parentNode.parentNode;
		}
		li.aSpan = spanA;
		li.bSpan = spanB;
		li.cSpan = spanC;
		li.treeNode = spanA.treeNode = spanB.treeNode = spanC.treeNode = li;
		var aTag = spanC.getElementsByTagName('a')[0];
		if(aTag) {
			aTag.treeNode = li;
			li.aTag = aTag;
			
		} else {
			throw("Tree creation: A tree needs <a> tags inside the <li>s to work properly.");
		}
		

		aTag.onclick = TreeNode_aTag_onclick.bindAsEventListener(aTag);
		
		
		// Process the children
		if(childUL != null) {
			if(this.castAsTree(childUL)) { /* ***** RECURSE ***** */
				if(this.className.indexOf('closed') == -1) {
					this.addNodeClass('children');
				}
			}
		} else {
			this.removeNodeClass('closed');
		}
		
		this.setIconByClass();
	},

	destroy: function() {
		// Debug.show(this);
		
		this.tree = null;
		this.treeNode = null;
		this.parentTreeNode = null;

		if(this.options) this.options.tree = null;
		this.options = null;
		
		if(this.aTag) {
			this.aTag.treeNode = null;
			this.aTag.onclick = null;
		}			
		if(this.aSpan) {
			this.aSpan.treeNode = null;
			this.aSpan.onmouseover = null;
			this.aSpan.onmouseout = null;
		}
		if(this.bSpan) {
			this.bSpan.treeNode = null;
			this.bSpan.onclick = null;
		}
		if(this.cSpan) this.cSpan.treeNode = null;
		
		this.aSpan = null;
		this.bSpan = null;
		this.cSpan = null;
		this.aTag = null;
	},
	
	/**
	 * Cast the given span as the <span class="a"> item for this tree.
	 */
	castAsSpanA: function(spanA) {
		var x;
		for(x in TreeNode_SpanA) spanA[x] = TreeNode_SpanA[x];
	},
	/**
	 * Cast the child <ul> as a tree
	 */
	castAsTree: function(childUL) {
		return behaveAs(childUL, Tree, this.options);
	},
	
	/**
	 * Triggered from clicks on spans of class b, the +/- buttons.
	 * Closed is represented by adding class close to the LI, and
	 *     class spanClose to spanA.
	 * Pass 'force' as "open" or "close" to force it to that state,
	 *     otherwise it toggles.
	 */
	toggle : function(force) {
		if(this.treeNode.wasDragged || this.treeNode.anchorWasClicked) {
			this.treeNode.wasDragged = false;
			this.treeNode.anchorWasClicked = false;
			return;
		}
		
		/* Note: It appears the 'force' parameter is no longer used. Here is old code that used it:
		if( force == "open"){
			treeOpen( topSpan, el )
		}
		else if( force == "close" ){
			treeClose( topSpan, el )
		}
		*/

		if(this.hasChildren() || this.className.match(/(^| )unexpanded($| )/)) {
			if(this.className.match(/(^| )closed($| )/) || this.className.match(/(^| )unexpanded($| )/)) this.open();
			else this.close();
		}
	},	
	
	open : function () {
		// Normal tree node
		if(Element.hasClassName(this, 'unexpanded') && !this.hasChildren()) {
			if(this.ajaxExpansion) this.ajaxExpansion();
		} 

		if(!this.className.match(/(^| )closed($| )/)) return;

		this.removeNodeClass('closed');
		this.removeNodeClass('unexpanded');
	},
	close : function () {
		this.addNodeClass('closed');
	},
	expose : function() {
		if(this.parentTreeNode) {
			this.parentTreeNode.open();
			this.parentTreeNode.expose();
		}
	},
	setIconByClass: function() {
		if(typeof _TREE_ICONS == 'undefined') return;
		var classes = this.className.split(/\s+/);
		var obj = this;
		
		classes.each(function(className) {
			var className = className.replace(/class-/, '');
			if(_TREE_ICONS[className]) {
				obj.fileIcon = _TREE_ICONS[className].fileIcon;
				obj.openFolderIcon = _TREE_ICONS[className].openFolderIcon;
				obj.closedFolderIcon = _TREE_ICONS[className].closedFolderIcon;
				throw $break;
			
			} else if(className == "Page") {
				obj.fileIcon = null;
				obj.openFolderIcon = null;
				obj.closedFolderIcon = null;
			}
		});
		
		this.updateIcon();
	},
	updateIcon: function() {
		var icon;
		if(this.closedFolderIcon && this.className.indexOf('closed') != -1) {
			icon = this.closedFolderIcon;

		} else if(this.openFolderIcon && this.className.indexOf('children') != -1) {
			icon = this.openFolderIcon;
			
		} else if(this.fileIcon) {
			icon = this.fileIcon;
		}
		if(icon) this.aTag.style.background = "url(" +icon + ") no-repeat";
		else this.aTag.style.backgroundImage = "";
	},

	/**
	 * Add the given child node to this tree node.
	 * If 'before' is specified, then it will be inserted before that.
	 */
	appendTreeNode : function(child, before) {
		this.treeNodeHolder().appendTreeNode(child, before);
	},
	
	treeNodeHolder : function(performCast) {
		if(performCast == null) performCast = true;
		
		var uls = this.getElementsByTagName('ul');
		if(uls.length > 0) return uls[0];
		else {
			var ul = document.createElement('ul');
			this.appendChild(ul);
			if(performCast) this.castAsTree(ul);
			return ul;
		}
	},
	hasChildren: function() {
		var uls = this.getElementsByTagName('ul');
		if(uls.length > 0) {
			var i,item;
			for(i=0;item=uls[0].childNodes[i];i++) {
				if(item.tagName && item.tagName.toLowerCase() == 'li') return true;
			}
		}
		return false;
	},
	
	/**
	 * Remove the given child node from this tree node.
	 */
	removeTreeNode : function(child) {
		// Remove the child
		var holder = this.treeNodeHolder();
		try { holder.removeChild(child); } catch(er) { }
		
		// Look for remaining children
		var i, hasChildren = false;
		for(i=0;i<holder.childNodes.length;i++) {
			if(holder.childNodes[i].tagName && holder.childNodes[i].tagName.toLowerCase() == "li") {
				hasChildren = true; 
				break; 
			}
		}

		// Update the helper classes accordingly
		if(!hasChildren) this.removeNodeClass('children');
		else this.lastTreeNode().addNodeClass('last');
		
		// Update the helper variables
		child.parentTreeNode = null;
	},
	lastTreeNode : function() {
		return this.treeNodeHolder().lastTreeNode();
	},
	firstTreeNode : function() {
		var i, holder = this.treeNodeHolder();
		for(i=0;i<holder.childNodes.length;i++) {
			if(holder.childNodes[i].tagName && holder.childNodes[i].tagName.toLowerCase() == 'li') return holder.childNodes[i];
		}
	},
	addNodeClass : function(className) {
		if(Element && Element.addClassName) {
			Element.addClassName(this, className);
			if(className == 'closed') Element.removeClassName(this, 'children');
			this.aSpan.className = 'a ' + this.className.replace('closed','spanClosed');
	
			if(className == 'children' || className == 'closed') this.updateIcon();
		}
	},
	removeNodeClass : function(className) {
		if(Element && Element.removeClassName) {
			Element.removeClassName(this, className);
			if(className == 'closed' && this.hasChildren()) Element.addClassName(this, 'children');
			this.aSpan.className = 'a ' + this.className.replace('closed','spanClosed');
	
			if(className == 'children' || className == 'closed') this.updateIcon();
		}
	},
	
	getIdx : function() {
		if(this.id.match(/([^-]+)-(.+)$/)) return RegExp.$2;
		else return this.id;
	},
	getTitle: function() {
		return this.aTag.innerHTML;
	},
	
	installSubtree : function(response) {
		var ul = this.treeNodeHolder(false);
		ul.innerHTML = response.responseText;
		ul.appendTreeNode = null;
		this.castAsTree(ul);
		/*		
		var i,lis = ul.childTreeNodes();
		for(i=0;i<lis.length;i++) {
			this.tree.castAsTreeNode(lis[i]);
		}
		*/

		// Cued new nodes are nodes added while we were waiting for the expansion to finish
		if(ul.cuedNewNodes) {
			var i;
			for(i=0;i<ul.cuedNewNodes.length;i++) {
				ul.appendTreeNode(ul.cuedNewNodes[i]);
			}
			ul.cuedNewNodes = null;
		}

		this.removeNodeClass('closed');
		this.addNodeClass('children');
		this.removeNodeClass('loading');
		this.removeNodeClass('unexpanded');
	}
}

/* Close or Open all the trees, at beginning or on request. sjd. */
function treeCloseAll() {
	var candidates = document.getElementsByTagName('li');
	for (var i=0;i<candidates.length;i++) {
		var aSpan = candidates[i].childNodes[0];
		if(aSpan.childNodes[0] && aSpan.childNodes[0].className == "b") {
			if (!aSpan.className.match(/spanClosed/) && candidates[i].id != 'record-0' ) {
				aSpan.childNodes[0].onclick();
			}
		}
	}
}

function treeOpenAll() {
	var candidates = document.getElementsByTagName('li');
	for (var i=0;i<candidates.length;i++) {
		var aSpan = candidates[i].childNodes[0];
		if(aSpan.childNodes[0] && aSpan.childNodes[0].className == "b") {
			if (aSpan.className.match(/spanClosed/)) {
				aSpan.childNodes[0].onclick();
			}
		}
	}
}


TreeNode_aTag_onclick = function(event) {
	Event.stop(event);
	jQuery(this.treeNode.tree).trigger('nodeclicked', {node: this.treeNode});
	if(!this.treeNode.tree || this.treeNode.tree.notify('NodeClicked', this.treeNode)) {
		if(this.treeNode.options.onselect) {
			return this.treeNode.options.onselect.apply(this.treeNode, [event]);
		} else if(this.treeNode.onselect) {
			return this.treeNode.onselect();
		}
	}
	
	return false;
}

TreeNode_bSpan_onclick = function() {
	this.treeNode.toggle();
};

TreeNode_SpanA = {
	onmouseover : function(event) {
		this.parentNode.addNodeClass('over');
	},
	onmouseout : function(event) {
		this.parentNode.removeNodeClass('over');
	}
}

//-----------------------------------------------------------------------------------------------//


DraggableTree = Class.extend('Tree');
DraggableTree.prototype = {
	initialize: function(options) {
		this.Tree.initialize(options);
		this.setUpDragability();
	},
	setUpDragability: function() {
		this.isDraggable = true;

		this.allDragHelpers = [];
		if(this.parentNode.tagName.toLowerCase() == "li") {
			this.treeNode = this.parentNode;
			if(this.treeNode.hasChildren()) {
				this.treeNode.createDragHelper();
			}
		}
	},
	/**
	 * Turn a draggable tree into a normal one.
	 */
	stopBeingDraggable: function() {
		// this.parentNode.destroy();
		this.isDraggable = false;
		
		var i,item,nodes = this.getElementsByTagName('li');
		for(i=0;item=nodes[i];i++) {
			item.destroyDraggable();
		}
		for(i=0;item=this.allDragHelpers[i];i++) {
			Droppables.remove(item);
			if(item.parentNode){
				item.parentNode.removeChild(item);				
			}
		}
		this.allDragHelpers = [];
	},
	
	/**
	 * Convert the given <li> tag into a suitable tree node	
	 */
	castAsTreeNode: function(li) {
		behaveAs(li, DraggableTreeNode, this.options);
	}
}


DraggableTreeNode = Class.extend('TreeNode');
DraggableTreeNode.prototype = {
	initialize: function(options) {
		this.TreeNode.initialize(options);
		this.setUpDragability();
	},
	setUpDragability: function() {
		// Set up drag and drop
		this.draggableObj = new Draggable(this, TreeNodeDragger);	
		
		//if(!this.dropperOptions || this.dropperOptions.accept != 'none')
		Droppables.add(this.aTag, this.dropperOptions ? Object.extend(this.dropperOptions, TreeNodeDropper) : TreeNodeDropper);
		
		// Add before DIVs to be Droppable items
		if(this.parentTreeNode && this.parentTreeNode.createDragHelper){		
			this.parentTreeNode.createDragHelper(this);
		} 
		
		if(this.hasChildren() && this.parentNode.tagName.toLowerCase() == "li") {
			this.treeNode = this.parentNode;
			// this.treeNode.createDragHelper();
		}
		
		// Fix up the <a> click action
		this.aTag._onclick_before_draggable = this.aTag.onclick;
		this.aTag.baseClick = this.aTag.onclick;
		
		if(this.options.onParentChanged) this.onParentChanged = this.options.onParentChanged;
		if(this.options.onOrderChanged) this.onOrderChanged = this.options.onOrderChanged;
	},
	
	/**
	 * Remove all the draggy stuff
	 */
	destroyDraggable: function() {
		Droppables.remove(this.aTag);
		this.aTag.onclick = this.aTag._onclick_before_draggable;
		
		if(this.draggableObj) {
			this.draggableObj.destroy();
			this.draggableObj = null;
		}
	},
	/*
	this was commented out because SiteTreeNode takes care of it instead
	castAsTree: function(childUL) {
		// Behaving as DraggableTree directly doesn't load in expansion behaviours
		behaveAs(childUL, Tree, this.options);
		childUL.makeDraggable();
	},
	*/
	
	/**
	 * Rebuild the "Drag Helper DIVs" that sit around each tree node within this node
	 */
	fixDragHelperDivs : function() {
		var i, holder = this.treeNodeHolder();
		
		// This variable toggles between div & li
		var lastDiv, expecting = "div";
		for(i=0;i<holder.childNodes.length;i++) {
			if(holder.childNodes[i].tagName) {
				if(holder.childNodes[i].tagName.toLowerCase() == "div") lastDiv = holder.childNodes[i];

				// alert(i + ': ' + expecting + ', ' + holder.childNodes[i].tagName);
				if(expecting != holder.childNodes[i].tagName.toLowerCase()) {
					if(expecting == "div") {
						this.createDragHelper(holder.childNodes[i]);
					} else {
						holder.removeChild(holder.childNodes[i]);
					}
					i--;
				
				} else {
					// Toggle expecting
					expecting = (expecting == "div") ? "li" : "div";
				}
			}
		}
		// If we were left looking for an li, remove the last div
		// if(expecting == "li") holder.removeChild(lastDiv);

		// If we were left looking for a div, add one at the end
		if(expecting == "div") this.createDragHelper();
	},

	/** 
	 * Create a drag helper within this item.
	 * It will be inserted to the end, or before the 'before' element if that is given.
	 */
	createDragHelper : function(before) {	
		// Create the node
		var droppable = document.createElement('div');
		droppable.className = "droppable";
		droppable.treeNode = this;
		
		this.dragHelper = droppable;
		this.tree.allDragHelpers[this.tree.allDragHelpers.length] = this.dragHelper;
		
		// Insert into the DOM
		var holder = this.treeNodeHolder();
		if(before) holder.insertBefore(droppable, before);
		else holder.appendChild(droppable);

		// Make droppable
		var customOptions = holder.parentNode.dropperOptions ? Object.extend(holder.parentNode.dropperOptions, TreeNodeSeparatorDropper) : TreeNodeSeparatorDropper;
		if(!customOptions.accept != 'none') {
			if(Droppables) Droppables.add(droppable, customOptions);
		}
	}
}

TreeNodeDragger = { 
	onStartDrag : function(dragger) {
		dragger.oldParent = dragger.parentTreeNode;
	},
	revert: true 
}

TreeNodeDropper = {
	onDrop :  function(dragger, dropper, event) {
		var result = true;
		
		// Handle event handlers
		if(dragger.onParentChanged && dragger.parentTreeNode != dropper.treeNode)
			result = dragger.onParentChanged(dragger, dragger.parentTreeNode, dropper.treeNode);
			
		// Get the future order of the children after the drop completes
		var i = 0, item = null, items = [];
		items[items.length] = dragger.treeNode;
		for(i=0;item=dropper.treeNode.treeNodeHolder().childNodes[i];i++) {
			if(item != dragger.treeNode) items[items.length] = item;
		}
			
		if(result && dragger.onOrderChanged)
			result = dragger.onOrderChanged(items, items[0]);
			
		if(result) {
			dropper.treeNode.appendTreeNode(dragger.treeNode, dropper.treeNode.firstTreeNode());
		}

		dragger.wasDragged = true;
		
	},
	hoverclass : 'dragOver', 
	checkDroppableIsntContained : true
}

TreeNodeSeparatorDropper = {
	onDrop : function(dragger, dropper, event) {
		var result = true;

		// Handle parent-change handlers
		if(dragger.onParentChanged && dragger.parentTreeNode != dropper.treeNode)
			result = dragger.onParentChanged(dragger, dragger.parentTreeNode, dropper.treeNode);

		// Get the future order of the children after the drop completes
		var i = 0, item = null, items = [];
		for(i=0;item=dropper.treeNode.treeNodeHolder().childNodes[i];i++) {
			if(item == dropper) items[items.length] = dragger.treeNode;
			if(item != dragger.treeNode) items[items.length] = item;
		}

		// Handle order change
		if(result && dragger.onOrderChanged)
			result = dragger.onOrderChanged(items, dragger.treeNode);
			
		if(result) {
			dropper.treeNode.appendTreeNode(
				dragger.treeNode, dropper);
		}
		
		dragger.wasDragged = true;
	},
	hoverclass : 'dragOver',
	greedy : true,
	checkDroppableIsntContained : true
}

//---------------------------------------------------------------------------------------------///

/**
 * Mix-in for the tree to enable mulitselect support
 * Usage: 
 *   - tree.behaveAs(MultiselectTree)
 *   - tree.stopBehavingAs(MultiselectTree)
 */
MultiselectTree = Class.create();
MultiselectTree.prototype = {
	initialize: function() {
		Element.addClassName(this, 'multiselect');
		this.MultiselectTree_observer = this.observeMethod('NodeClicked', this.multiselect_onClick.bind(this));
		this.selectedNodes = { }
	},
	destroyDraggable: function() {
		this.stopObserving(this.MultiselectTree_observer);
	},

	multiselect_onClick : function(selectedNode) {
		if(selectedNode.selected) {
			this.deselectNode(selectedNode);
		} else {
			this.selectNode(selectedNode);
		}

		// Trigger the onselect event
		return true;
	},

	selectNode: function(selectedNode) {
		var idx = this.getIdxOf(selectedNode);
		selectedNode.addNodeClass('selected');
		selectedNode.selected = true;
		this.selectedNodes[idx] = selectedNode.aTag.innerHTML;
	},

	deselectNode : function(selectedNode) {
		var idx = this.getIdxOf(selectedNode);
		selectedNode.removeNodeClass('selected');
		selectedNode.selected = false;
		delete this.selectedNodes[idx];
	}

}
