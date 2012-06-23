/*
This attempts to do the opposite of Sizzle.
Sizzle is good for finding elements for a selector, but not so good for telling if an individual element matches a selector
*/

(function($) {
	
	/**** CAPABILITY TESTS ****/
	var div = document.createElement('div');
	div.innerHTML = '<form id="test"><input name="id" type="text"/></form>';
	
	// In IE 6-7, getAttribute often does the wrong thing (returns similar to el.attr), so we need to use getAttributeNode on that browser
	var getAttributeDodgy = div.firstChild.getAttribute('id') !== 'test';
	
	// Does browser support Element.firstElementChild, Element.previousElementSibling, etc.
	var hasElementTraversal = div.firstElementChild && div.firstElementChild.tagName == 'FORM';
	
	// Does browser support Element.children
	var hasChildren = div.children && div.children[0].tagName == 'FORM';

	/**** INTRO ****/
	
	var GOOD = /GOOD/g;
	var BAD = /BAD/g;
	
	var STARTS_WITH_QUOTES = /^['"]/g;
	
	var join = function(js) {
		return js.join('\n');
	};
	
	var join_complex = function(js) {
		var code = new String(js.join('\n')); // String objects can have properties set. strings can't
		code.complex = true;
		return code;
	};
	
	/**** ATTRIBUTE ACCESSORS ****/
	
	// Not all attribute names can be used as identifiers, so we encode any non-acceptable characters as hex
	var varForAttr = function(attr) {
		return '_' + attr.replace(/^[^A-Za-z]|[^A-Za-z0-9]/g, function(m){ return '_0x' + m.charCodeAt(0).toString(16) + '_'; });
	};
	
	var getAttr;
	
	// Good browsers
	if (!getAttributeDodgy) {
		getAttr = function(attr){ return 'var '+varForAttr(attr)+' = el.getAttribute("'+attr+'");' ; };
	}
	// IE 6, 7
	else {
		// On IE 6 + 7, getAttribute still has to be called with DOM property mirror name, not attribute name. Map attributes to those names
		var getAttrIEMap = { 'class': 'className', 'for': 'htmlFor' };
		
		getAttr = function(attr) {
			var ieattr = getAttrIEMap[attr] || attr;
			return 'var '+varForAttr(attr)+' = el.getAttribute("'+ieattr+'",2) || (el.getAttributeNode("'+attr+'")||{}).nodeValue;';
		};
	}
	
	/**** ATTRIBUTE COMPARITORS ****/
	
	var attrchecks = {
		'-':  '!K',
		'=':  'K != "V"',
		'!=': 'K == "V"',
		'~=': '_WS_K.indexOf(" V ") == -1',
		'^=': '!K || K.indexOf("V") != 0',
		'*=': '!K || K.indexOf("V") == -1',
		'$=': '!K || K.substr(K.length-"V".length) != "V"'
	};

	/**** STATE TRACKER ****/
	
	var State = $.selector.State = Base.extend({
		init: function(){ 
			this.reset(); 
		},
		reset: function() {
			this.attrs = {}; this.wsattrs = {};
		},

		prev: function(){
			this.reset();
			if (hasElementTraversal) return 'el = el.previousElementSibling';
			return 'while((el = el.previousSibling) && el.nodeType != 1) {}';
		},
		next: function() {
			this.reset();
			if (hasElementTraversal) return 'el = el.nextElementSibling';
			return 'while((el = el.nextSibling) && el.nodeType != 1) {}';
		},
		prevLoop: function(body){
			this.reset();
			if (hasElementTraversal) return join([ 'while(el = el.previousElementSibling){', body]);
			return join([
				'while(el = el.previousSibling){',
					'if (el.nodeType != 1) continue;',
					body
			]);
		},
		parent: function() {
			this.reset();
			return 'el = el.parentNode;';
		},
		parentLoop: function(body) {
			this.reset();
			return join([
				'while((el = el.parentNode) && el.nodeType == 1){',
					body,
				'}'
			]);
		},
		
		uses_attr: function(attr) {
			if (this.attrs[attr]) return;
			this.attrs[attr] = true;
			return getAttr(attr); 
		},
		uses_wsattr: function(attr) {
			if (this.wsattrs[attr]) return;
			this.wsattrs[attr] = true;
			return join([this.uses_attr(attr), 'var _WS_'+varForAttr(attr)+' = " "+'+varForAttr(attr)+'+" ";']); 
		},

		uses_jqueryFilters: function() {
			if (this.jqueryFiltersAdded) return;
			this.jqueryFiltersAdded = true;
			return 'var _$filters = jQuery.find.selectors.filters;';
		},

		save: function(lbl) {
			return 'var el'+lbl+' = el;';
		},
		restore: function(lbl) {
			this.reset();
			return 'el = el'+lbl+';';
		}
	});
	
	/**** PSEUDO-CLASS DETAILS ****/
	
	var pseudoclschecks = {
		'first-child': join([
			'var cel = el;',
			'while(cel = cel.previousSibling){ if (cel.nodeType === 1) BAD; }'
		]),
		'last-child': join([
			'var cel = el;',
			'while(cel = cel.nextSibling){ if (cel.nodeType === 1) BAD; }'
		]),
		'nth-child': function(a,b) {
			var get_i = join([
				'var i = 1, cel = el;',
				'while(cel = cel.previousSibling){',
					'if (cel.nodeType === 1) i++;',
				'}'
			]);
			
			if (a == 0) return join([
				get_i,
				'if (i- '+b+' != 0) BAD;'
			]);
			else if (b == 0 && a >= 0) return join([
				get_i,
				'if (i%'+a+' != 0 || i/'+a+' < 0) BAD;'
			]);
			else if (b == 0 && a < 0) return join([
				'BAD;'
			]);
			else return join([
				get_i,
				'if ((i- '+b+')%'+a+' != 0 || (i- '+b+')/'+a+' < 0) BAD;'
			]);
		}
	};
	
	// Needs to refence contents of object, so must be injected after definition
	pseudoclschecks['only-child'] = join([
		pseudoclschecks['first-child'],
		pseudoclschecks['last-child']
	]);
	
	/**** SimpleSelector ****/
	
	$.selector.SimpleSelector.addMethod('compile', function(el) {
		var js = [];
		
		/* Check against element name */			
		if (this.tag && this.tag != '*') {
			js[js.length] = 'if (el.tagName != "'+this.tag.toUpperCase()+'") BAD;';
		}

		/* Check against ID */
		if (this.id) {
			js[js.length] = el.uses_attr('id');
			js[js.length] = 'if (_id !== "'+this.id+'") BAD;';
		}
		
		/* Build className checking variable */
		if (this.classes.length) {
			js[js.length] = el.uses_wsattr('class');
			
			/* Check against class names */
			$.each(this.classes, function(i, cls){
				js[js.length] = 'if (_WS__class.indexOf(" '+cls+' ") == -1) BAD;';
			});
		}
		
		/* Check against attributes */
		$.each(this.attrs, function(i, attr){
			js[js.length] = (attr[1] == '~=') ? el.uses_wsattr(attr[0]) : el.uses_attr(attr[0]);
			var check = attrchecks[ attr[1] || '-' ];
			check = check.replace( /K/g, varForAttr(attr[0])).replace( /V/g, attr[2] && attr[2].match(STARTS_WITH_QUOTES) ? attr[2].slice(1,-1) : attr[2] );
			js[js.length] = 'if ('+check+') BAD;';
		});
		
		/* Check against nots */
		$.each(this.nots, function(i, not){
			var lbl = ++lbl_id;
			var func = join([
				'l'+lbl+':{',
					not.compile(el).replace(BAD, 'break l'+lbl).replace(GOOD, 'BAD'),
				'}'
			]);
			
			if (!(not instanceof $.selector.SimpleSelector)) func = join([
				el.save(lbl),
				func,
				el.restore(lbl)
			]);
				
			js[js.length] = func;
		});
		
		/* Check against pseudo-classes */
		$.each(this.pseudo_classes, function(i, pscls){
			var check = pseudoclschecks[pscls[0]];
			if (check) {
				js[js.length] = ( typeof check == 'function' ? check.apply(this, pscls[1]) : check );
			}
			else if (check = $.find.selectors.filters[pscls[0]]) {
				js[js.length] = el.uses_jqueryFilters();
				js[js.length] = 'if (!_$filters.'+pscls[0]+'(el)) BAD;';
			}
		});
		
		js[js.length] = 'GOOD';
		
		/* Pass */
		return join(js);
	});
	
	var lbl_id = 0;
	/** Turns an compiled fragment into the first part of a combination */
	function as_subexpr(f) {
		if (f.complex)
			return join([
				'l'+(++lbl_id)+':{',
					f.replace(GOOD, 'break l'+lbl_id),
				'}'
			]);
		else
			return f.replace(GOOD, '');
	}
	
	var combines = {
		' ': function(el, f1, f2) {
			return join_complex([
				f2,
				'while(true){',
					el.parent(),
					'if (!el || el.nodeType !== 1) BAD;',
					f1.compile(el).replace(BAD, 'continue'),
				'}'
			]);
		},
		
		'>': function(el, f1, f2) {
			return join([
				f2,
				el.parent(),
				'if (!el || el.nodeType !== 1) BAD;',
				f1.compile(el)
			]);
		},
		
		'~': function(el, f1, f2) {
			return join_complex([
				f2,
				el.prevLoop(),
					f1.compile(el).replace(BAD, 'continue'),
				'}',
				'BAD;'
			]);
		},
		
		'+': function(el, f1, f2) {
			return join([
				f2,
				el.prev(),
				'if (!el) BAD;',
				f1.compile(el)
			]);
		}
	};
	
	$.selector.Selector.addMethod('compile', function(el) {
		var l = this.parts.length;
		
		var expr = this.parts[--l].compile(el);
		while (l) {
			var combinator = this.parts[--l];
			expr = combines[combinator](el, this.parts[--l], as_subexpr(expr));
		}
		
		return expr;
	});

	$.selector.SelectorsGroup.addMethod('compile', function(el) {
		var expr = [], lbl = ++lbl_id;
		
		for (var i=0; i < this.parts.length; i++) {
			expr[expr.length] = join([
				i == 0 ? el.save(lbl) : el.restore(lbl), 
				'l'+lbl+'_'+i+':{',
					this.parts[i].compile(el).replace(BAD, 'break l'+lbl+'_'+i),
				'}'
			]);
		}
		
		expr[expr.length] = 'BAD;';
		return join(expr);
	});

	$.selector.SelectorBase.addMethod('matches', function(el){	
		this.matches = new Function('el', join([ 
			'if (!el) return false;',
			this.compile(new State()).replace(BAD, 'return false').replace(GOOD, 'return true')
		]));
		return this.matches(el);
	});
	
})(jQuery);

