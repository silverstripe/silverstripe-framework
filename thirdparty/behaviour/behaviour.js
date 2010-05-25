var Class = {
  create: function() {
  	return function() {
  		if(this.destroy) Class.registerForDestruction(this);
      if(this.initialize) this.initialize.apply(this, arguments);
    }
  },
  
  extend: function(baseClassName) {
  	var constructor = function() {
  		var i;
  		
			/*		
			var tmp = this.initialize;
			this.initialize = window[baseClassName].initialize;
  		window[baseClassName].apply(this, arguments);
  		this.initialize = tmp;
  		*/
  		this[baseClassName] = {}
  		for(i in window[baseClassName].prototype) {
  			if(!this[i]) this[i] = window[baseClassName].prototype[i];
  			if(typeof window[baseClassName].prototype[i] == 'function') {
  				this[baseClassName][i] = window[baseClassName].prototype[i].bind(this);
  			}
  		}
  		
  		if(window[baseClassName].getInheritedStuff) {
  			window[baseClassName].getInheritedStuff.apply(this);
  		}
  		
  		if(this.destroy) Class.registerForDestruction(this);
      if(this.initialize) this.initialize.apply(this, arguments);
  	}
  	constructor.getInheritedStuff = function() {
  	    var i;
  		this[baseClassName] = {}
  		for(i in window[baseClassName].prototype) {
  			if(!this[i]) this[i] = window[baseClassName].prototype[i];
  			if(typeof window[baseClassName].prototype[i] == 'function') {
  				this[baseClassName][i] = window[baseClassName].prototype[i].bind(this);
  			}
  		}

  		if(window[baseClassName].getInheritedStuff) {
  			window[baseClassName].getInheritedStuff.apply(this);
  		}
  	}
  	
  	return constructor;
  	
  },
  
  objectsToDestroy : [],  
  registerForDestruction: function(obj) {
  	if(!Class.addedDestructionLoader) {
			Event.observe(window, 'unload', Class.destroyAllObjects);
  		Class.addedDestructionLoader = true;
  	}
  	Class.objectsToDestroy.push(obj);
  },
  
  destroyAllObjects: function() {
  	var i,item;
  	for(i=0;item=Class.objectsToDestroy[i];i++) {
  		if(item.destroy) item.destroy();
  	}
  	Class.objectsToDestroy = null;
  }  
}

/**
 * Extend function used in multiple inheritance
 */
Function.prototype.extend = function(baseClassName) {
	var parentFunc = this;
	
	var constructor = function() {
		this[baseClassName] = {}
		for(var i in window[baseClassName].prototype) {
			if(!this[i]) this[i] = window[baseClassName].prototype[i];
			this[baseClassName][i] = window[baseClassName].prototype[i].bind(this);
		}

		if(window[baseClassName].getInheritedStuff) {
			window[baseClassName].getInheritedStuff.apply(this);
		}
		if(parentFunc.getInheritedStuff) {
			parentFunc.getInheritedStuff.apply(this);
		}

		parentFunc.apply(this, arguments);
	}

	constructor.getInheritedStuff = function() {
		this[baseClassName] = {}
		var i;
		for(i in window[baseClassName].prototype) {
			if(!this[i]) this[i] = window[baseClassName].prototype[i];
			this[baseClassName][i] = window[baseClassName].prototype[i].bind(this);
		}

		if(window[baseClassName].getInheritedStuff) {
			window[baseClassName].getInheritedStuff.apply(this);
		}
		if(parentFunc.getInheritedStuff) {
			parentFunc.getInheritedStuff.apply(this);
		}
	}
	
	return constructor;
}

Function.prototype.bindAsEventListener = function(object) {
  var __method = this;
  return function(event) {
    return __method.call(object, event || window.event);
  }
}
Function.prototype.applyTo = function(cssSelector, arg1, arg2, arg3, arg4, arg5, arg6) {
	if(typeof cssSelector == 'string') {
		var registration = {}
		var targetClass = this;
		
		registration[cssSelector] = {
			initialise: function() {
				behaveAs(this, targetClass, arg1, arg2, arg3, arg4, arg5, arg6);
			}
		}
		
		Behaviour.register(registration);
	
	} else {
		behaveAs(cssSelector, this);		
	}
}

var _APPLYTOCHILDREN_GENERATED_IDS = 0;
Function.prototype.applyToChildren = function(parentNode, cssSelector, arg1, arg2, arg3, arg4, arg5, arg6) {
	if(!parentNode.id) {
		_APPLYTOCHILDREN_GENERATED_IDS++;
		parentNode.id = 'atc-gen-id-' + _APPLYTOCHILDREN_GENERATED_IDS;
	}
	this.applyTo('#' + parentNode.id + ' ' + cssSelector);
}


if(typeof Behaviour == 'undefined') {
var Behaviour = {
	isEventHandler : { onclick : true, onfocus : true, onblur : true, onmousedown : true, onmouseup : true, onmouseover: true, onmouseout: true, onclick : true },
	
	list : new Array,

	namedList : {},
	isDebugging : false,
	
	register : function(name, sheet){
		if(typeof name == 'object') {
			Behaviour.list.push(name);

    		if(Behaviour.alreadyApplied) Behaviour.process(name);
		} else {
			Behaviour.list.push(sheet);
			Behaviour.namedList[name] = sheet;

    		if(Behaviour.alreadyApplied) Behaviour.process(sheet);
		}
	},
	
	start : function(){
		Behaviour.addLoader(function() {Behaviour.apply();});
	},
	
	debug : function() {
		Behaviour.isDebugging = true;
	},
	
	apply : function(parentNode, applyToParent){
		// reapply livequery listeners if present
		if(typeof(jQuery) != 'undefined' && typeof(jQuery.livequery) != 'undefined') jQuery.livequery.run();
		
		if(Behaviour.isDebugging) console.time('Behaviour: apply took');
		
		if(typeof parentNode == 'string') parentNode = document.getElementById(parentNode);
		var h;
		for (h=0;sheet=Behaviour.list[h];h++){
			Behaviour.process(sheet, parentNode, applyToParent);
		}
		
		if(Behaviour.isDebugging) console.timeEnd('Behaviour: apply took');

		Behaviour.alreadyApplied = true;
	},
	
	reapply : function(name) {
		// reapply livequery listeners if present
		if(typeof(jQuery) != 'undefined' && typeof(jQuery.livequery) != 'undefined') jQuery.livequery.run();
		
		if(Behaviour.namedList[name]) Behaviour.process(Behaviour.namedList[name]);
	},
	
	process : function(sheet, parentNode, applyToParent) {
		var i;
		var selector;
		var list;
		var element;
		var debugText = "";
		for (selector in sheet){
			if(!sheet[selector]) continue;
			if(Behaviour.isDebugging) console.time('Behaviour: ' + selector);
			list = document.getElementsBySelector(selector, parentNode);

			if (list && list.length > 0) {
				if(Behaviour.isDebugging) console.log("Behaviour: %s: %d items, %o", selector, list.length, list);
			
				for (i=0;element=list[i];i++){
					if(parentNode == element && applyToParent != true) continue;

					// lastSelectorApplied is a duplicate checker.  getElementsBySelector sometimes returns duplicates
					if(element.lastSelectorApplied != sheet[selector]) {
						element.lastSelectorApplied = sheet[selector];
						if(sheet[selector].prototype) {
							behaveAs(element, sheet[selector]);
						} else {
							var x;
							for(x in sheet[selector]) {
								if(element[x] && !element['old_' + x]) element['old_' + x] = element[x];
							
								if(sheet[selector][x]) {
									if(Behaviour.isEventHandler[x]) {
										element[x] = sheet[selector][x].bindAsEventListener(element);
										// Event.observe(element, x.substr(2), sheet[selector][x]);
									} else {
										element[x] = sheet[selector][x];
									}
								}
							}
							// Two diferent ways of spelling initialize depending on your version of the English language
				            if(sheet[selector].initialise) {
				            	element.initialise();
				            } else if(sheet[selector].initialize) {
				            	element.initialize();
				            }
				
							// Sometimes applyToChildren classes cause sheet[selector] to die in initialise().  Why?
							if(typeof sheet[selector] == 'undefined') break;
							
				        	if(sheet[selector].destroy) Class.registerForDestruction(element);
						}
					}
				}
			}
			
			if(Behaviour.isDebugging) console.timeEnd('Behaviour: ' + selector);
		}
	},
	
	/**
	 * Add a window.onload function.
	 */
	addLoader : function(func){
		Behaviour.addEvent(window,'load', func);
	},
	
	/**
	 * Attach an event listener to the given object
	 */
	addEvent: function(obj, evType, fn, useCapture){
		if (obj.addEventListener){
			obj.addEventListener(evType, fn, useCapture);
			return true;
		} else if (obj.attachEvent){
			var r = obj.attachEvent("on"+evType, fn);
			return r;
		} else {
			alert("Handler could not be attached");
		}
	}
}

Behaviour.start();
}

/*
 * Force elemnt to "behave like" the given class
 * The constructor will be called an all of the methods attached
 * Think of it as dynamic multiple inheritance... welcome to the messed up
 * yet delightful world of JavaScript
 */
function behaveAs(element, behaviourClass, arg1, arg2, arg3, arg4, arg5, arg6) {
	if(!element) return;
	
	// You can get into icky situations if behaveAs is called twice - the first class passed *has* initialize,
	// and the 2nd class passed *doesn't have it*.  The first initialize is called twice, without this delete.
	element.initialize = null;

	var x;
	for(x in behaviourClass.prototype) {
		element[x] = behaviourClass.prototype[x];
		if(x == 'onclick' && element[x]) {
			element[x] = element[x].bindAsEventListener(element);
		}
	}
	
	behaviourClass.apply(element, [arg1, arg2, arg3, arg4, arg5, arg6]);

	return element;
}

Function.prototype.create = function(item, arg1, arg2, arg3, arg4, arg5, arg6) {
	return behaveAs(item, this, arg1, arg2, arg3, arg4, arg5, arg6);
}

/*
   The following code is Copyright (C) Simon Willison 2004.

   document.getElementsBySelector(selector)
   - returns an array of element objects from the current document
     matching the CSS selector. Selectors can contain element names, 
     class names and ids and can be nested. For example:
     
       elements = document.getElementsBySelect('div#main p a.external')
     
     Will return an array of all 'a' elements with 'external' in their 
     class attribute that are contained inside 'p' elements that are 
     contained inside the 'div' element which has id="main"

   New in version 0.4: Support for CSS2 and CSS3 attribute selectors:
   See http://www.w3.org/TR/css3-selectors/#attribute-selectors

   Version 0.4 - Simon Willison, March 25th 2003
   -- Works in Phoenix 0.5, Mozilla 1.3, Opera 7, Internet Explorer 6, Internet Explorer 5 on Windows
   -- Opera 7 fails 
   
   
   ***NOTE***: This function will sometimes return duplicates.  Sam decided that rather than slow
   down the code with uniqueness checks, it was up to the code that uses this to do so.
*/

function getAllChildren(e) {
  // Returns all children of element. Workaround required for IE5/Windows. Ugh.
  return e.all ? e.all : e.getElementsByTagName('*');
}

document.getElementsBySelector = function(selector, parentNode) {
  // Attempt to fail gracefully in lesser browsers
  if (!document.getElementsByTagName) {
    return new Array();
  }
  // Split selector in to tokens
  var tokens = selector.split(' ');
  var currentContext = new Array(document);
  for (var i = 0; i < tokens.length; i++) {
    token = tokens[i].replace(/^\s+/,'').replace(/\s+$/,'');;
    
    if (token.indexOf('#') > -1) {
      // Token is an ID selector
      var bits = token.split('#');
      var tagName = bits[0];
      var id = bits[1];
      var element = document.getElementById(id);
      if (!element || (tagName && element.nodeName.toLowerCase() != tagName)) {
        // tag with that ID not found, return false
        return new Array();
      }

			// Parent node limitation      
      if(parentNode && !hasAncestor(element, parentNode) && !hasAncestor(parentNode, element)) {
        return new Array();
       }

		// currentContext limitation: for "body.class #Something" selectors.
		var foundInContext = false;
		for (var h = 0; h < currentContext.length; h++) {
			if(currentContext[h] == document || hasAncestor(element, currentContext[h])) {
				foundInContext = true;
			}
		}
		if(!foundInContext) return new Array();
   


      // Set currentContext to contain just this element
      currentContext = new Array(element);
      continue; // Skip to next token
    }
    
    if (token.indexOf('.') > -1) {
      // Token contains a class selector
      var bits = token.split('.');
      var tagName = bits[0];
      var className = bits[1];
      
      if (!tagName) {
        tagName = '*';
      }
      // Get elements matching tag, filter them for class selector
      var found = new Array;
      var foundCount = 0;
      for (var h = 0; h < currentContext.length; h++) {
        var elements;
        if(currentContext[h]) {
	        if (tagName == '*') {
	            elements = getAllChildren(currentContext[h]);
	        } else {
	            elements = currentContext[h].getElementsByTagName(tagName);
	        }
	        for (var j = 0; j < elements.length; j++) found[foundCount++] = elements[j];
	      }
      }
      currentContext = new Array;
      var currentContextIndex = 0;

      // Single class
      if(bits.length == 2) {
	      for (var k = 0; k < found.length; k++) {
	        if (found[k].className && found[k].className.match(new RegExp('\\b'+className+'\\b'))) {
						// Parent node limitation      
			      if(!parentNode || hasAncestor(found[k], parentNode) || hasAncestor(parentNode, found[k])) {
		          currentContext[currentContextIndex++] = found[k];
						}
	        }
	      }
	      
	    // Multiple classes
	    } else {
	      var classNameMatcher = function(el) {
	      	var i;
	      	if(!el.className) return false;
	      	for(i=1;i<bits.length;i++) if(!el.className.match(new RegExp('\\b'+bits[i]+'\\b'))) return false;
	      	return true;
	      }
	      for (var k = 0; k < found.length; k++) {
	        if (classNameMatcher(found[k])) {
						// Parent node limitation      
			      if(!parentNode || hasAncestor(found[k], parentNode) || hasAncestor(parentNode, found[k])) {
		          currentContext[currentContextIndex++] = found[k];
						}
	        }
	      }
	    	
	    }

      continue; // Skip to next token
    }
    
    // Code to deal with attribute selectors
    if (token.match(/^(\w*)\[(\w+)([=~\|\^\$\*]?)=?"?([^\]"]*)"?\]$/)) {
      var tagName = RegExp.$1;
      var attrName = RegExp.$2;
      var attrOperator = RegExp.$3;
      var attrValue = RegExp.$4;
      if (!tagName) {
        tagName = '*';
      }
      // Grab all of the tagName elements within current context
      var found = new Array;
      var foundCount = 0;
      for (var h = 0; h < currentContext.length; h++) {
      	if(currentContext[h]){
	        var elements;
	        if (tagName == '*') {
	            elements = getAllChildren(currentContext[h]);
	        } else {
	            elements = currentContext[h].getElementsByTagName(tagName);
	        }
	        for (var j = 0; j < elements.length; j++) {
						// Parent node limitation      
			      if(!parentNode || hasAncestor(elements[j], parentNode) || hasAncestor(parentNode, elements[j])) {
		          found[foundCount++] = elements[j];
						}
	        }
        }
      }
      currentContext = new Array;
      var currentContextIndex = 0;
      var checkFunction; // This function will be used to filter the elements

      switch (attrOperator) {
        case '=': // Equality
          checkFunction = function(candAttrValue) { return (candAttrValue == attrValue); };
          break;
        case '~': // Match one of space seperated words 
          checkFunction = function(candAttrValue) { return (candAttrValue.match(new RegExp('\\b'+attrValue+'\\b'))); };
          break;
        case '|': // Match start with value followed by optional hyphen
          checkFunction = function(candAttrValue) { return (candAttrValue.match(new RegExp('^'+attrValue+'-?'))); };
          break;
        case '^': // Match starts with value
          checkFunction = function(candAttrValue) { return (candAttrValue.indexOf(attrValue) == 0); };
          break;
        case '$': // Match ends with value - fails with "Warning" in Opera 7
          checkFunction = function(candAttrValue) { return (candAttrValue.lastIndexOf(attrValue) == candAttrValue.length - attrValue.length); };
          break;
        case '*': // Match ends with value
          checkFunction = function(candAttrValue) { return (candAttrValue.indexOf(attrValue) > -1); };
          break;
        default :
          // Just test for existence of attribute
          checkFunction = function(candAttrValue) { return candAttrValue; };
      }
      currentContext = new Array;
      var currentContextIndex = 0;
      for (var k = 0; k < found.length; k++) {
				// Class needs special handling      
	      var candAttrValue = attrName == 'class' ? found[k].className : found[k].getAttribute(attrName);
        if (checkFunction(candAttrValue)) {
		      if(!parentNode || hasAncestor(found[k], parentNode) || hasAncestor(parentNode, found[k])) {
		         currentContext[currentContextIndex++] = found[k];
					}
        }
      }
      // alert('Attribute Selector: '+tagName+' '+attrName+' '+attrOperator+' '+attrValue);
      continue; // Skip to next token
    }
    
    if (!currentContext[0]){
    	return;
    }
    
    // If we get here, token is JUST an element (not a class or ID selector)
    tagName = token;
    var found = new Array;
    var foundCount = 0;
    for (var h = 0; h < currentContext.length; h++) {
      var elements = currentContext[h].getElementsByTagName(tagName);
      for (var j = 0; j < elements.length; j++) {
				// Parent node limitation      
	      if(!parentNode || hasAncestor(elements[j], parentNode) || hasAncestor(parentNode, elements[j])) {
	        found[foundCount++] = elements[j];
	      }
      }
    }
    currentContext = found;
  }
  
  if(parentNode) {
  	var i;
  	for(i=0;i<currentContext.length;i++) {
  		if(!hasAncestor(currentContext[i], parentNode)) currentContext.splice( i, 1 );
  	}
  }
  
  return currentContext;
}

function hasAncestor(child, ancestor) {
	if(ancestor) {
		if(ancestor.contains) return ancestor == child || ancestor.contains(child);
		
		var p = child;
		while(p) {
			if(p == ancestor) return true;
			p = p.parentNode;
		}
	}
	
	return false;	
}

/* That revolting regular expression explained 
/^(\w+)\[(\w+)([=~\|\^\$\*]?)=?"?([^\]"]*)"?\]$/
  \---/  \---/\-------------/    \-------/
    |      |         |               |
    |      |         |           The value
    |      |    ~,|,^,$,* or =
    |   Attribute 
   Tag
*/

/**
 * Simple observer pattern
 * 
 * Call $('sitetree').observe('SelectionChanged', this)
 *   -> this.onSelectionChanged(newNode) will be called whenever the selection changes
 * Call $('sitetree').observeMethod('SelectionChanged', this.updateDropdown.bind(this))
 *   -> this.updateDropdown(newNode) will be called whenever the selection changes
 * Call $('sitetree').notify('SelectionChanged', newNode)
 *   -> The SelectionChanged event will be sent to all observers
 */
Observable = Class.create();
Observable.prototype = {
	observe : function(event, observer) {
		return this.observeMethod(event, observer['on' + Event].bind(observer));
	},
	observeMethod : function(event, method) {
		if(!this.observers) this.observers = {};
		if(!this.observers[event]) this.observers[event] = [];
		
		var nextIdx = this.observers[event].length;
		this.observers[event][nextIdx] = method;
		return event + '|' + nextIdx;
	},
	stopObserving : function(observerCode) {
		var parts = observerCode.split('|');
		if(this.observers && this.observers[parts[0]] && this.observers[parts[0]][parts[1]])
			this.observers[parts[0]][parts[1]] = null;
		else
			throw("Observeable.stopObserving: couldn't find '" + observerCode + "'");
	},
	notify : function(event, arg) {
		if(typeof(jQuery) != 'undefined' && typeof(jQuery.livequery) != 'undefined') jQuery(this).trigger(event, arg);  
		var i, returnVal = true;
		if(this.observers && this.observers[event]) {
			for(i=0;i<this.observers[event].length;i++) {
				if(this.observers[event][i]) {
					if(this.observers[event][i](arg) == false) returnVal = false;
				}
			}
		}
		return returnVal;
	}
};

if(window.location.href.indexOf('debug_behaviour=') > -1) Behaviour.debug();