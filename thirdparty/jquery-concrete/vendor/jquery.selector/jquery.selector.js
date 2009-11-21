(function($){

	var tokens = {
		UNICODE: /\\[0-9a-f]{1,6}(?:\r\n|[ \n\r\t\f])?/,
		ESCAPE: /(?:UNICODE)|\\[^\n\r\f0-9a-f]/,
		NONASCII: /[^\x00-\x7F]/,
		NMSTART: /[_a-z]|(?:NONASCII)|(?:ESCAPE)/,
		NMCHAR: /[_a-z0-9-]|(?:NONASCII)|(?:ESCAPE)/,
		IDENT: /-?(?:NMSTART)(?:NMCHAR)*/,
		
		NL: /\n|\r\n|\r|\f/,

		STRING: /(?:STRING1)|(?:STRING2)|(?:STRINGBARE)/,
		STRING1: /"(?:(?:ESCAPE)|\\(?:NL)|[^\n\r\f\"])*"/,
		STRING2: /'(?:(?:ESCAPE)|\\(?:NL)|[^\n\r\f\'])*'/,
		STRINGBARE: /(?:(?:ESCAPE)|\\(?:NL)|[^\n\r\f\]])*/,
		
		FUNCTION: /(?:IDENT)\(\)/,
		
		INTEGER: /[0-9]+/,
		
		WITHN: /([-+])?(INTEGER)?(n)\s*(?:([-+])\s*(INTEGER))?/,
		WITHOUTN: /([-+])?(INTEGER)/
	}
	
	var rx = {
		not: /:not\(/,
		not_end: /\)/,
		
 		tag: /((?:IDENT)|\*)/,
		id: /#(IDENT)/,
		cls: /\.(IDENT)/,
		attr: /\[\s*(IDENT)\s*(?:([^=]?=)\s*(STRING)\s*)?\]/,
		pseudo_el: /(?::(first-line|first-letter|before|after))|(?:::((?:FUNCTION)|(?:IDENT)))/,
		pseudo_cls_nth: /:nth-child\(\s*(?:(?:WITHN)|(?:WITHOUTN)|(odd|even))\s*\)/,
		pseudo_cls: /:(IDENT)/,

		comb: /\s*(\+|~|>)\s*|\s+/,
		comma: /\s*,\s*/,
		important: /\s+!important\s*$/
	}

	/* Replace placeholders with actual regex, and mark all as case insensitive */
	var token = /[A-Z][A-Z0-9]+/;
	for (var k in rx) {
		var src = rx[k].source;
		while (m = src.match(token)) src = src.replace(m[0], tokens[m[0]].source);
		rx[k] = new RegExp(src, 'gi');
	}

	/**
	 * A string that matches itself against regexii, and keeps track of how much of itself has been matched
	 */
	var ConsumableString = Base.extend({
		init: function(str) {
			this.str = str;
			this.pos = 0;
		},
		match: function(rx) {
			var m;
			rx.lastIndex = this.pos;
			if ((m = rx.exec(this.str)) && m.index == this.pos ) {
				this.pos = rx.lastIndex ? rx.lastIndex : this.str.length ;
				return m;
			}
			return null;
		},
		peek: function(rx) {
			var m;
			rx.lastIndex = this.pos;
			if ((m = rx.exec(this.str)) && m.index == this.pos ) return m;
			return null;
		},
		showpos: function() {
			return this.str.slice(0,this.pos)+'<HERE>' + this.str.slice(this.pos);
		},
		done: function() {
			return this.pos == this.str.length;
		}
	})
	
	/* A base class that all Selectors inherit off */
	var SelectorBase = Base.extend({});
	
	/**
	 * A class representing a Simple Selector, as per the CSS3 selector spec
	 */
	var SimpleSelector = SelectorBase.extend({
		init: function() {
			this.tag = null;
			this.id = null;
			this.classes = [];
			this.attrs = [];
			this.nots = [];
			this.pseudo_classes = [];
			this.pseudo_els = [];
		},
		parse: function(selector) {
			var m;
			
			/* Pull out the initial tag first, if there is one */
			if (m = selector.match(rx.tag)) this.tag = m[1];
			
			/* Then for each selection type, try and find a match */
			do {
				if (m = selector.match(rx.not)) {
					this.nots[this.nots.length] = SelectorsGroup().parse(selector)
					if (!(m = selector.match(rx.not_end))) {
						throw 'Invalid :not term in selector';
					}
				}
				else if (m = selector.match(rx.id))         this.id = m[1];
				else if (m = selector.match(rx.cls))        this.classes[this.classes.length] = m[1];
				else if (m = selector.match(rx.attr))       this.attrs[this.attrs.length] = [ m[1], m[2], m[3] ];
				else if (m = selector.match(rx.pseudo_el))  this.pseudo_els[this.pseudo_els.length] = m[1] || m[2];
				else if (m = selector.match(rx.pseudo_cls_nth)) {
					if (m[3]) {
						var a = parseInt((m[1]||'')+(m[2]||'1'));
						var b = parseInt((m[4]||'')+(m[5]||'0'));
					}
					else {
						var a = m[8] ? 2 : 0;
						var b = m[8] ? (4-m[8].length) : parseInt((m[6]||'')+m[7]);
					}
					this.pseudo_classes[this.pseudo_classes.length] = ['nth-child', [a, b]];
				}
				else if (m = selector.match(rx.pseudo_cls)) this.pseudo_classes[this.pseudo_classes.length] = [m[1]];
				
			} while(m && !selector.done());
			
			return this;
		}
	})

	/**
	 * A class representing a Selector, as per the CSS3 selector spec
	 */
	var Selector = SelectorBase.extend({ 
		init: function(){
			this.parts = [];
		},
		parse: function(cons){
			this.parts[this.parts.length] = SimpleSelector().parse(cons);
			
			while (!cons.done() && !cons.peek(rx.comma) && (m = cons.match(rx.comb))) {
				this.parts[this.parts.length] = m[1] || ' ';
				this.parts[this.parts.length] = SimpleSelector().parse(cons);
			}
			
			return this.parts.length == 1 ? this.parts[0] : this;
		}
	});
	
	/**
	 * A class representing a sequence of selectors, as per the CSS3 selector spec
	 */
	var SelectorsGroup = SelectorBase.extend({ 
		init: function(){
			this.parts = [];
		},
		parse: function(cons){
			this.parts[this.parts.length] = Selector().parse(cons);
			
			while (!cons.done() && (m = cons.match(rx.comma))) {
				this.parts[this.parts.length] = Selector().parse(cons);
			}
			
			return this.parts.length == 1 ? this.parts[0] : this;
		}
	});

	
	$.selector = function(s){
		var cons = ConsumableString(s);
		var res = SelectorsGroup().parse(cons); 
		
		res.selector = s;
		
		if (!cons.done()) throw 'Could not parse selector - ' + cons.showpos() ;
		else return res;
	}
	
	$.selector.SelectorBase = SelectorBase;
	$.selector.SimpleSelector = SimpleSelector;
	$.selector.Selector = Selector;
	$.selector.SelectorsGroup = SelectorsGroup;
	
})(jQuery)
