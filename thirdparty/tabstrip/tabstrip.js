var _CUR_TABS = [];
var _TABS_ON_PAGE = [];
var _TAB_DIVS_ON_PAGE = [];

Behaviour.register({
	'ul.tabstrip': {
		initialize: function() {
			initTabstrip(this);
	    if(window.ontabschanged) window.ontabschanged();
		}
	}
});

function initTabstrip(tabstrip, namedAnchors) {
	var i, anchor, container, anchorName, li;
	var childAnchors = tabstrip.getElementsByTagName('a');
	var base, curTab = null, curURL = window.location.href;
	var previousTab = null;
	var firstTab, foundATab = false;

	// Strip query string from current URL
	var curQuery = window.location.search;

	// Detect a current tab from the # link
	if(curURL.indexOf('#') == -1) {
		base = curURL.length - curQuery.length;
	} else {
		base = curURL.indexOf('#') - curQuery.length;
		curTab = curURL.substr(curURL.indexOf('#')+1);
	}

	// Get a stored current tab, used when Ajax-switching between pages
	if(_CUR_TABS[tabstrip.parentNode.id] && $(_CUR_TABS[tabstrip.parentNode.id]) ) {
		curTab = _CUR_TABS[tabstrip.parentNode.id];
		
	} else {
		// Default to showing the first tab
		for(i=0;i<childAnchors.length;i++) {
			var child = childAnchors[i];
			
			var anchorPos = child.href.indexOf('#');
			
			if(anchorPos != -1) {
				anchorName = child.href.substr(anchorPos+1);
				if(firstTab == null) firstTab = anchorName;
				if(anchorName == curTab) foundATab = true;
			}
		}
		if(!foundATab) curTab = firstTab;
	}
	

	_CUR_TABS[tabstrip.parentNode.id] = curTab;
	
	
	for(i=0;i<childAnchors.length;i++) {
		// Detect an anchor reference
		
		var anchorBase = childAnchors[i].href.indexOf('#');
		
		// if(childAnchors[i].href.substr(base,1) == '#') {
		
		if( anchorBase != -1 ) {
			anchorName = childAnchors[i].href.substr(anchorBase+1);
			li = childAnchors[i].parentNode;
			container = document.getElementById(anchorName);
			
			if(container) tabstrip_initTab(childAnchors[i], anchorName, tabstrip, container);
			// else throw("Cannot find ID: " + anchorName);
			
			// Hook up previousTab / nextTab suppoort
			if(previousTab) {				
				previousTab.nextTab = li;
				li.previousTab = previousTab;
			}
			previousTab = li;
			
			// Default to showing the first tab
			// if(curTab == null && anchorName) curTab = anchorName;
			
			// Show current tab
			if(curTab && anchorName == curTab) {
				tabstrip.currentlyShowing = li;
				addClass(li, 'current');
				if(container) {
					container.style.display = '';
					
					/*
					// Show any parent tab that might be lurking about
					var p = container.parentNode;
					while(p.tagName.toLowerCase() != 'body') {
						if(p.ownerTab) p.ownerTab.onclick('init');
						p = p.parentNode;
					}
					*/
				}
			} else {
				if(container) container.style.display = 'none';
			}
		}
	}
	
	// store the tabs in the window for window.ontabschanged
	if($('Form_EditForm')) {
		var divs = $('Form_EditForm').getElementsByTagName('div');
	} else {
		var divs = document.getElementsBySelector('form div');
	}
	for(i=0;i<divs.length;i++) {
		if( ( Element.hasClassName(divs[i],'tab') || Element.hasClassName(divs[i],'tabset') ) ) {
			_TAB_DIVS_ON_PAGE.push(divs[i]);
		}
	}
	// Add nextTab() and previousTab() functions to the tabstrip
	tabstrip.openNextTab = tabstrip_openNextTab;
	tabstrip.openPreviousTab = tabstrip_openPreviousTab;
	
	// Hook this into the destroyer, to prevent memory leaks
	if(Class && Class.registerForDestruction) {
		tabstrip.destroy = tabstrip_destroy;
		Class.registerForDestruction(tabstrip);
	}
}

function tabstrip_destroy() {
	this.currentlyShowing = null;
	
	_CUR_TABS = null;
	
	var childAnchors = this.getElementsByTagName('a');
	var i,a,li;
	for(i=0;a=childAnchors[i];i++) {
		a.onclick = null;

		li = a.parentNode;
		li.onclick = null;
		li.onmouseover = null;
		li.onmouseout = null;
		li.tabstrip = null;
		li.previousTab = null;
		li.nextTab = null;
		
		if(li.container) {
			li.container.ownerTab = null;
			li.container = null;
		}
		
	}
}


function tabstrip_initTab(a, anchorName, tabstrip, container) {
	// Hook up information / events
	a.onclick = tabstrip_showTab;
	a.onclick = a.onclick.bindAsEventListener(a);
    
    a.openTab = tabstrip_showTab;
    
	
	li = a.parentNode;
	li.container = container;
	li.anchorName = anchorName;
	li.tabstrip = tabstrip;
	li.onclick = li.openTab = tabstrip_showTab;
	li.onmouseover = tabstrip_mouseOver;
	li.onmouseout = tabstrip_mouseOut;
	_TABS_ON_PAGE[anchorName] = li;
	container.ownerTab = li;
}

function openTab( anchorName ) {

	if( typeof anchorName != 'string' )
		return;

	var tabNames = anchorName.split( 'set' );
	
		
	if( tabNames.length > 1 )
		anchorName = tabNames.shift() + 'set';
	while( anchorName ) {
		if( _TABS_ON_PAGE[anchorName] ) _TABS_ON_PAGE[anchorName].openTab( _TABS_ON_PAGE[anchorName].getElementsByTagName('a')[0] );
	
		if( tabNames.length == 0 )
			anchorName = null;
		else {
			anchorName = anchorName + tabNames.shift() + 'set';
		}		
	}
}

/*
 * Returns the form object that the given element is
 * inside; or null if it's not inside a form
 */
function findParentForm(el) {
	var ownerForm = el.parentNode, tn;
	while((tn = ownerForm.tagName.toLowerCase()) != "body" && tn != "form") {
		ownerForm = ownerForm.parentNode;
	}
	if(tn == "form") return ownerForm;
	else return null;
}


function tabstrip_showTab(evt) {
	if(this.tagName.toLowerCase() == "a") var el = this.parentNode;
	else el = this;
	
	_CUR_TABS[el.tabstrip.parentNode.id] = el.container.id;

	if(el.tabstrip.currentlyShowing && el.tabstrip.currentlyShowing.container && el.tabstrip.currentlyShowing != el) {
		el.tabstrip.currentlyShowing.container.style.display = 'none';
		removeClass(el.tabstrip.currentlyShowing.container, 'current');
		removeClass(el.tabstrip.currentlyShowing, 'current');		
	}
	
	var container = document.getElementById( el.container.id );
	
	if( container && container.style.display == 'none' ) {
		container.style.display = 'block';
		addClass( container, 'current' );
	}
	
	// el.container.style.display = '';
	addClass(el, 'current');

	el.tabstrip.currentlyShowing = el;
	
	setHashLink(el.anchorName);

	if(evt != 'init') {
		if(window.ontabschanged) window.ontabschanged();
		else if(window.onresize) window.onresize();
	}
	
	return false;
}

/*
 * Redirect to the given hash link
 * It won't actually reload the page, but it will update the current URL
 */
function setHashLink(hashLink) {
	return; //temporarily disabled this
	
	var preserveScroll = preserveScrollPosition(hashLink);
	
	// Mac/IE5 cannot handle this
	if(navigator.userAgent.indexOf("Mac") > -1 && navigator.userAgent.indexOf("MSIE") > -1)
		return;
	
	if(window.location.href.indexOf('#') == -1)
		window.location.href += '#' + hashLink;
	else
		window.location.href = window.location.href.replace(/#.*$/, '#' + hashLink);
		
	if(typeof preserveScroll != 'undefined') 
		restoreScrollPosition(preserveScroll);
}

/**
 * Preserve the scroll position prior to visiting the given hashlink.
 * Returns an object that you can pass to restoreScrollPosition
 */
function preserveScrollPosition(hashLink) {
	var el = document.getElementById(hashLink);
	var preserved = [];
	while(el) {
		preserved[preserved.length] = el;
		el.oldScroll = el.scrollTop;
		
		if(el.tagName && el.tagName.toLowerCase() == "body") break;
		el = el.parentNode;	
	}
	return preserved;
}

/*
 * Restores the preserved scroll position
 */
function restoreScrollPosition(preserved) {
	var i;
	for(i=0;i<preserved.length;i++) {
		preserved[i].scrollTop = preserved[i].oldScroll;
		delete preserved[i].oldScroll;
	}
}


function tabstrip_mouseOver() {
	if(self.addClass) addClass(this, 'over');
}

function tabstrip_mouseOut() {
	if(self.removeClass) removeClass(this, 'over');
}

function tabstrip_openNextTab() {
	if(this.currentlyShowing && this.currentlyShowing.nextTab)
		this.currentlyShowing.nextTab.onclick();
}
function tabstrip_openPreviousTab() {
	if(this.currentlyShowing && this.currentlyShowing.previousTab)
		this.currentlyShowing.previousTab.onclick();
}
