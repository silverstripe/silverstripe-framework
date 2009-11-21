/**
 * Very basic Class utility. Based on base and jquery.class.
 * 
 * Class definition: var Foo = Base.extend({ init: function(){ Constructor }; method_name: function(){ Method } });
 *
 * Inheritance: var Bar = Foo.extend({ method_name: function(){ this._super(); } });
 * 
 * new-less Constructor: new Foo(arg) <-same as-> Foo(arg)
 */  	
Base = (function(){
	
	var marker = {}, fnTest = /xyz/.test(function(){xyz;}) ? /\b_super\b/ : /.*/;

	// The base Class implementation (does nothing)
	Base = function(){};
 
	Base.addMethod = function(name, func) {
		var _super = this._super;
		if (_super && fnTest.test(func))	 {
			this.prototype[name] = function(){
				var tmp = this._super;
				this._super = _super[name];
				try {
					var ret = func.apply(this, arguments);
				}
				finally {
					this._super = tmp;
				}
				return ret;
			}
		}
		else this.prototype[name] = func;
	}
 
	// Create a new Class that inherits from this class
	Base.extend = function(prop) {
  	
		// The dummy class constructor
		var Kls = function() {
			if (arguments[0] === marker) return;
			
			if (this instanceof Kls) {
				if (this.init) this.init.apply(this, arguments);
			}
			else {
				var ret = new Kls(marker); if (ret.init) ret.init.apply(ret, arguments); return ret;
			}
		}
   
		Kls.constructor = Kls;
		Kls.extend = Base.extend;
		Kls.addMethod = Base.addMethod;
		Kls._super = this.prototype;
	
		Kls.prototype = new this(marker);
	
		// Copy the properties over onto the new prototype
		for (var name in prop) {
			if (typeof prop[name] == 'function') Kls.addMethod(name, prop[name]);
			else Kls.prototype[name] = prop;
		}
		
		return Kls;
	}; 

	return Base;
})();