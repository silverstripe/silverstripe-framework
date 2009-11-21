
	(function($){
		
		function isExternalScript(url){ 
			return /(http|https):\/\//.test(url);
		};
		
		$.extend({
			
			requireConfig : {
				routeJs		: '_js/',
				routeCss	: '_css/'
			},
			
			queue : [],
			pending : null,
			
			requireJs : function(scriptUrl, callback, opts, obj, scope)
			{
				if(opts != undefined || opts == null){
					$.extend($.requireConfig, opts);
				}
				
				var _request = {
					url 		: scriptUrl,
					callback 	: callback,
					opts		: opts,
					obj 		: obj,
					scope		: scope
				}
				
				if(this.pending)
				{
					this.queue.push(_request);
					return;
				}
				
				this.pending = _request;
				
				var _this		= this;
				var _url		= (isExternalScript(scriptUrl)) ? scriptUrl : $.requireConfig.routeJs + scriptUrl;
				var _head 		= document.getElementsByTagName('head')[0];
				var _scriptTag 	= document.createElement('script');
					
					// Firefox, Opera
					$(_scriptTag).bind('load', function(){
						_this.requestComplete();
					});
					
					// IE
					_scriptTag.onreadystatechange = function(){
						if(this.readyState === 'loaded' || this.readyState === 'complete'){
							_this.requestComplete();
						}
					}
					
					_scriptTag.type = "text/javascript";
					_scriptTag.src = _url;
					
					_head.appendChild(_scriptTag);
			},
			
			requestComplete : function()
			{
				
				if(this.pending.callback){
					if(this.pending.obj){
						if(this.pending.scope){
							this.pending.callback.call(this.pending.obj);
						} else {
							this.pending.callback.call(window, this.pending.obj);
						}
					} else {
						this.pending.callback.call();
					}
				}
				
				this.pending = null;
				
				if(this.queue.length > 0)
				{
					var request = this.queue.shift();
					this.requireJs(request.url, request.callback, request.opts, request.obj, request.scope);
				}
			},
			
			requireCss : function(styleUrl){
				
				if(document.createStyleSheet){
					document.createStyleSheet($.requireConfig.routeCss + styleUrl);
				}
				else {
				
					var styleTag = document.createElement('link');
											
					$(styleTag).attr({
						href	: $.requireConfig.routeCss + styleUrl,
						type	: 'text/css',
						media 	: 'screen',
						rel		: 'stylesheet'
					}).appendTo($('head').get(0));
					
				}
				
			}
			
		})
		
	})(jQuery);