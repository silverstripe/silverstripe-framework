/*
 * Stretch an object out to fill its parent.  Give space for siblings
 * If Mozilla didn't have a funky bug, we could *maybe* do this without JavaScript
 * 
 * A more robust stretchObject() replacement
 */
function fitToParent(el, tweakVal) {
	if(typeof el == "string") {
		var elName = el;
		el = document.getElementById(el);
		if(!el) /*throw("fitToParent: Can't find element '" + elName + "'")*/ return;
	}
	var height = getFittingHeight(el, tweakVal) + 'px';
	el.style.height = height;
}
	
function getFittingHeight(el, tweakVal, ignoreElements) {
	if(typeof el == "string") el = document.getElementById(el);

	// we set overflow = hidden so that large children don't muck things up
	if(el.parentNode && el.parentNode != document.body) {
		if(getDimension(el.parentNode,'overflow',true) != 'auto') el.parentNode.style.overflow = 'hidden';
	}
	
	var otherHeight = 0;
	
	var notAComment;
	if(el.parentNode) {
		for(var i=0;i<el.parentNode.childNodes.length;i++) {
			var sibling = el.parentNode.childNodes[i];
			
			if(sibling.tagName && el != sibling) {
				if(sibling.outerHTML == null) notAComment = true;
				else if(sibling.outerHTML.indexOf("<!--") != 0) notAComment = true;
				else notAComment = false;
				
				// notAComment used for other checking
				if(sibling.className && sibling.className.indexOf('fitToParent_ignore') != -1) notAComment = false;
	
				if(getDimension(sibling,'position',true) == 'absolute') notAComment = false;
				else if(getDimension(sibling,'display',true) == 'none') notAComment = false;
				else {
					var floatVal = getDimension(sibling,'float',true);
					if(floatVal == 'left' || floatVal == 'right') notAComment = false;
				}
				if(ignoreElements) {
					for(var j=0;j<ignoreElements.length;j++) {
						if(ignoreElements[j] == sibling) {
							notAComment = false;
							break;
						}
					}
				}

				if(sibling.offsetHeight && notAComment && (sibling.tagName.toLowerCase() != 'script')) {
					otherHeight += parseInt(sibling.offsetHeight);
				}
				
				// Hack for height of menu bar at top
				if(sibling.id == 'top') {
					otherHeight += 27;
				}
			}
		}
	}
	
	if(getDimension(el,'position',true) == 'relative') otherHeight += getDimension(el,'top');
	
	if(!tweakVal) tweakVal = 0;
	
//	if(el.clientHeight) tweakVal += el.offsetHeight - el.clientHeight;

	tweakVal += getDimension(el,'marginTop');
	tweakVal += getDimension(el,'marginBottom');
	tweakVal += getDimension(el,'paddingTop');
	tweakVal += getDimension(el,'paddingBottom');

	tweakVal += getDimension(el,'borderBottomWidth');
	tweakVal += getDimension(el,'borderTopWidth');

	//alert(getDimension(el.parentNode,'paddingTop', true));
	tweakVal += getDimension(el.parentNode,'paddingTop');
	tweakVal += getDimension(el.parentNode,'paddingBottom');
	// Body border not correct in IE
	if(el.parentNode && el.parentNode.tagName != "BODY") {
		tweakVal += getDimension(el.parentNode,'borderTopWidth');
		tweakVal += getDimension(el.parentNode,'borderBottomWidth');
	}
		
	if(el.parentNode && el.parentNode.offsetHeight - otherHeight - tweakVal < 0) {
		return 0;
	}else{
		if(el.parentNode) {
			return (el.parentNode.offsetHeight - otherHeight - tweakVal);		
		}
	}
}



function getSiblingList(el) {
	var i,silbing,result=[];
	for(i=0;i<el.parentNode.childNodes.length;i++) {
		sibling = el.parentNode.childNodes[i];
		if(sibling.tagName) result.push(
			sibling.tagName
			+ (sibling.id ? ('#' + sibling.id) : '')
			+ (sibling.className ? ('.' + sibling.className) : '')
		);
	}
	return result.join(", ");
}

/*
 * Returns the value of the given dimension - marginBottom, paddingTop, etc
 * Checks both stylesheet and style="" attribute
 * Defaults to 0, always returns an integer
 */
function getDimension(el, dim, notAInteger) {
	if(!notAInteger) notAInteger = false;
	
	// This differs from browser to browser
	if(dim == 'float') {
		dim = (typeof el.style.cssFloat == 'string') ? 'cssFloat' : 'styleFloat';
	}
	
	var checkForNoneValue = {
		'borderBottomWidth' : 'borderBottomStyle',
		'borderTopWidth' : 'borderTopStyle',
		'borderLeftWidth' : 'borderLeftStyle',
		'borderRightWidth' : 'borderRightStyle'
	};
	
	// Handle hidden borders
	if(checkForNoneValue[dim]) {
		if(getDimension(el, checkForNoneValue[dim], true) == 'none') return 0;
	}
	
	if(el && el.style && el.style[dim]) {
		return notAInteger ? el.style[dim] : parseInt(el.style[dim]);
	
	} else if(el && el.currentStyle) {
		if(el.currentStyle[dim] != 'auto') {
			return notAInteger ? el.currentStyle[dim] : parseInt(el.currentStyle[dim]);
		}
		
	} else if(document.defaultView && document.defaultView.getComputedStyle) {
		var val;
		try {
			var s = document.defaultView.getComputedStyle(el, null);
			val = s[dim];
		} catch(er) {}
		if(val) {
			if(notAInteger) {
				return val;
			} else {
				var newVal = parseInt(val);
				if(isNaN(newVal)) {
					// throw("getDimension: Couldn't turn " + dim + " value '" + val + "' into an integer.");
					return 0;
				} else {
					return newVal;
				}
			}
		}
	}
	
	return notAInteger ? null : 0;
}
