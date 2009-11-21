/**
 * @author Alexander Farkas
 * @ version 1.05
 */
(function($){
	
	function getFnIndex(args){
		var ret = 2;
		$.each(args, function(i, data){
			
			if($.isFunction(data)){
				ret = i;
				return false;
			}
		});
		return ret;
	}
	
	
(function(){
	
	var contains = document.compareDocumentPosition ?  function(a, b){
		return a.compareDocumentPosition(b) & 16;
	} : function(a, b){
		return a !== b && (a.contains ? a.contains(b) : true);
	},
	oldLive = $.fn.live,
	oldDie = $.fn.die;
	
	function createEnterLeaveFn(fn, type){
		return jQuery.event.proxy(fn, function(e) {
			if( this !== e.relatedTarget && e.relatedTarget && !contains(this, e.relatedTarget) ){
				e.type = type;
				fn.apply(this, arguments);
			}
		});
	}
	
	var enterLeaveTypes = {
		mouseenter: 'mouseover',
		mouseleave: 'mouseout'
	};
	
	$.fn.live = function(types){
		var that 	= this,
			args 	= arguments,
			fnIndex 	= getFnIndex(args),
			fn 		= args[fnIndex];
		
		$.each(types.split(' '), function(i, type){
			var proxy = fn;
			
			if(enterLeaveTypes[type]){
				proxy = createEnterLeaveFn(proxy, type);
				type = enterLeaveTypes[type];
			}
			args[0] = type;
			args[fnIndex] = proxy;
			oldLive.apply(that, args);
		});
		return this;
	};
	
	$.fn.die = function(type, fn){
		if(/mouseenter|mouseleave/.test(type)){
			if(type == 'mouseenter'){
				type = type.replace(/mouseenter/g, 'mouseover');
			} 
			if(type == 'mouseleave') {
				type = type.replace(/mouseleave/g, 'mouseout');
			}
		}
		oldDie.call(this, type, fn);
		return this;
	};
	
	
	function createBubbleFn(fn, selector, context){
		return jQuery.event.proxy(fn, function(e) {
			var parent = this.parentNode,
				stop 	= (enterLeaveTypes[e.type]) ? e.relatedTarget : undefined;
			fn.apply(this, arguments);
			while(parent && parent !== context && parent !== e.relatedTarget){
				if($.multiFilter( selector, [parent] )[0]){
					fn.apply(parent, arguments);
				}
				parent = parent.parentNode;
			}
		});
	}
	
	$.fn.bubbleLive = function(){
		var args 	= arguments,
			fnIndex = getFnIndex(args);
		
		args[fnIndex] = createBubbleFn(args[fnIndex], this.selector, this.context);
		$.fn.live.apply(this, args);
	};
	
	$.fn.liveHover = function(enter, out){
		return this.live('mouseenter', enter)
					.live('mouseleave', out);
	};
})();



(function(){
	
	$.support.bubblingChange = !($.browser.msie || $.browser.safari);
	
	if(!$.support.bubblingChange){
	
	var oldLive = $.fn.live,
		oldDie = $.fn.die;
	
	function detectChange(fn){
		return $.event.proxy(fn, function(e){
			var jElm = $(e.target);
			if ((e.type !== 'keydown' || e.keyCode === 13) && jElm.is('input, textarea, select')) {
				
				var oldData 			= jElm.data('changeVal'), 
					isRadioCheckbox 	= jElm.is(':checkbox, :radio'),
					nowData;
				if(isRadioCheckbox && jElm.is(':enabled') && e.type === 'click'){
					nowData = jElm.is(':checked');
					if((e.target.type !== 'radio' || nowData === true) && e.type !== 'change' && oldData !== nowData){
						e.type = 'change';
						jElm.trigger(e);
					}
				} else if (!isRadioCheckbox) {
					nowData = jElm.val();
					if(oldData !== undefined && oldData !== nowData){
						e.type = 'change';
						jElm.trigger(e);
					}
				}
				if(nowData !== undefined){
					jElm.data('changeVal', nowData);
				}
			}
		});
	}
	
	function createChangeProxy(fn){
		return $.event.proxy(fn, function(e){
			if(e.type === 'change'){
				var jElm 	= $(e.target),
					nowData = (jElm.is(':checkbox, :radio')) ? jElm.is(':checked') : jElm.val();
				if(nowData === jElm.data('changeVal')){
					return false;
				}
				jElm.data('changeVal', nowData);
			}
			fn.apply(this, arguments);
		});
	}
	
	$.fn.live = function(type, fn){
		var that 	= this,
			args 	= arguments,
			fnIndex	= getFnIndex(args),
			proxy 	= args[fnIndex];
			
		if(type.indexOf('change') != -1){
			$(this.context)
				.bind('click focusin focusout keydown', detectChange(proxy));
			proxy = createChangeProxy(proxy);
		}
		args[fnIndex] = proxy;
		oldLive.apply(that, args);
		return this;
	};
	$.fn.die = function(type, fn){
		if(type.indexOf('change') != -1){
			$(this.context)
				.unbind('click focusin focusout keydown', fn);
		}
		oldDie.apply(this, arguments);
		return this;
	};
	
	}
})();

/**
 * Copyright (c) 2007 Jörn Zaefferer
 */


(function(){
	$.support.focusInOut = !!($.browser.msie);
	if (!$.support.focusInOut) {
		$.each({
			focus: 'focusin',
			blur: 'focusout'
		}, function(original, fix){
			$.event.special[fix] = {
				setup: function(){
					if (!this.addEventListener) {
						return false;
					}
					this.addEventListener(original, $.event.special[fix].handler, true);
				},
				teardown: function(){
					if (!this.removeEventListener) {
						return false;
					}
					this.removeEventListener(original, $.event.special[fix].handler, true);
				},
				handler: function(e){
					arguments[0] = $.event.fix(e);
					arguments[0].type = fix;
					return $.event.handle.apply(this, arguments);
				}
			};
		});
	}
	//IE has some troubble with focusout with select and keyboard navigation
	var activeFocus = null, block;
	
	$(document)
		.bind('focusin', function(e){
			var target = e.realTarget || e.target;
			if (activeFocus && activeFocus !== target) {
				e.type = 'focusout';
				$(activeFocus).trigger(e);
				e.type = 'focusin';
				e.target = target;
			}
			activeFocus = target;
		})
		.bind('focusout', function(e){
			activeFocus = null;
		});
		
})();
})(jQuery);
