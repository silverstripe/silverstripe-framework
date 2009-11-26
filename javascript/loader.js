/*
 * common/js/loader.js
 * Common file, to be included before any of the other common/js scripts
 */
 
/*
 * Gracefully deal with a whole lot of modules wanting to set the onload() event handler
 */
if(typeof _LOADERS == 'undefined') _LOADERS = Array();

function callAllLoaders() {
	var i, loaderFunc;
	for(i=0;i<_LOADERS.length;i++) {
		loaderFunc = _LOADERS[i];
		if(loaderFunc != callAllLoaders) loaderFunc();
	}
}

function appendLoader(loaderFunc) {
	if(window.onload && window.onload != callAllLoaders)
		_LOADERS[_LOADERS.length] = window.onload;

	window.onload = callAllLoaders;

	_LOADERS[_LOADERS.length] = loaderFunc;
}


/*
 * Call the given function on any element of the given tag and class
 */
function setUpHandlers(tagName,className, handlerFunction) {	
	var allElements = document.getElementsByTagName(tagName);
	for(var i = 0;i<allElements.length;i++) {
		if(allElements[i].className) {
			tester = ' ' + allElements[i].className + ' ';
			if(tester.indexOf(' ' + className + ' ') != -1) {
				handlerFunction(allElements[i]);
			}
		}
	}
}

/*
 * Return an array of all elements
 */
function getAllElements() {
	var allElements = document.getElementsByTagName('*');
	if(allElements.length == 0) return document.all;
	else return allElements;
}	

/*
 * Functions to add and remove class names
 * Mac IE hates unnecessary spaces
 */
function addClass(el, cls, forceBefore) {
	if(!el.className) el.className = "";
	if(forceBefore != null && el.className.match(new RegExp('(^| )' + forceBefore))) {
		el.className = el.className.replace(new RegExp("( |^)" + forceBefore), '$1' + cls + ' ' + forceBefore);

	} else if(!el.className.match(new RegExp('(^| )' + cls + '($| )'))) {
		el.className += ' ' + cls;
		el.className = el.className.replace(/(^ +)|( +$)/g, '');
	}
}
function removeClass(el, cls) {
	var old = el.className;
	var newCls = ' ' + el.className + ' ';
	newCls = newCls.replace(new RegExp(' (' + cls + ' +)+','g'), ' ');
	el.className = newCls.replace(/(^ +)|( +$)/g, '');
}
function removeClasses(el, cls) {
	removeClass(el,cls);
	var items = el.getElementsByTagName('*');
	var i;
	for(i=0;i<items.length;i++) if(items[i].className) removeClass(items[i],cls);
} 

/*
 * Add an event handler, saving the existing one
 */
function addEventHandler(obj, evt, handler) {
	if(obj[evt]) obj['old_' + evt] = obj[evt];
	obj[evt] = handler;
}