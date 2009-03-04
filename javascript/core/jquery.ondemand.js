/**
 * On-demand JavaScript handler
 * Based on http://plugins.jquery.com/files/issues/jquery.ondemand.js_.txt and modified to integrate with Sapphire
 */
(function($){

	function isExternalScript(url){
	    re = new RegExp('(http|https)://');
		return re.test(url);
	};

	$.extend({

		requireConfig : {
			routeJs		: '',	// empty default paths give more flexibility and user has
			routeCss	: ''	// choice of using this config or full path in scriptUrl argument
		},						// previously were useless for users which don't use '_js/' and '_css/' folders.  (by PGA)

		queue : [],
		pending : null,
		loaded_list : null,		// loaded files list - to protect against loading existed file again  (by PGA)
		
		
		// Added by SRM: Initialise the loaded_list with the scripts included on first load
		initialiseItemLoadedList : function() {
		    if(this.loaded_list == null) {
		        $this = this;
		        $this.loaded_list = {};
		        $('script').each(function() {
		            if($(this).attr('src')) $this.loaded_list[ $(this).attr('src') ] = 1;
		        });
		        $('link[@rel="stylesheet"]').each(function() {
		            if($(this).attr('href')) $this.loaded_list[ $(this).attr('href') ] = 1;
		        });
		    }
	    },
	    
	    /**
	     * Returns true if the given CSS or JS script has already been loaded
	     */
	    isItemLoaded : function(scriptUrl) {
			this.initialiseItemLoadedList();
	        return this.loaded_list[scriptUrl] != undefined;
	    },

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
			
			this.initialiseItemLoadedList();

			if (this.loaded_list[this.pending.url] != undefined) {		// if required file exists  (by PGA)
				this.requestComplete();									// => request complete
				return;
			}

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

			this.loaded_list[this.pending.url] = 1;			// adding loaded file to loaded list  (by PGA)
			this.pending = null;

			if(this.queue.length > 0)
			{
				var request = this.queue.shift();
				this.requireJs(request.url, request.callback, request.opts, request.obj, request.scope);
			}
		},

		requireCss : function(styleUrl, media){
		    if(media == null) media = 'all';

		    // Don't double up on loading scripts
		    if(this.isItemLoaded(styleUrl)) return;

			if(document.createStyleSheet){
				var ss = document.createStyleSheet($.requireConfig.routeCss + styleUrl);
				ss.media = media;
				
			} else {
				var styleTag = document.createElement('link');
				$(styleTag).attr({
					href	: $.requireConfig.routeCss + styleUrl,
					type	: 'text/css',
					media 	: media,
					rel		: 'stylesheet'
				}).appendTo($('head').get(0));
			}
			
			this.loaded_list[styleUrl] = 1;

		}

	})
	
	/**
	 * Sapphire extensions
	 * Ajax requests are amended to look for X-Include-JS and X-Include-CSS headers
	 */
	 _originalAjax = $.ajax;
	 $.ajax = function(s) {
        var _complete = s.complete;
        var _success = s.success;
        var _dataType = s.dataType;

        // This replaces the usual ajax success & complete handlers.  They are called after any on demand JS is loaded.
        var _ondemandComplete = function(xml) {
            var status = jQuery.httpSuccess(xml) ? 'success' : 'error';
            if(status == 'success') {
                data = jQuery.httpData(xml, _dataType);
                if(_success) _success(data, status, xml);
            }
            if(_complete) _complete(xml, status);
        }
	    
	    // We remove the success handler and take care of calling it outselves within _ondemandComplete
	    s.success = null;
        s.complete = function(xml, status) {
            processOnDemandHeaders(xml, _ondemandComplete);
        }

        return _originalAjax(s);
    }

})(jQuery);

/**
 * This is the on-demand handler used by our patched version of prototype.
 * once we get rid of all uses of prototype, we can remove this
 */
function prototypeOnDemandHandler(xml, callback) {
    processOnDemandHeaders(xml, callback);
}


/**
 * Process the X-Include-CSS and X-Include-JS headers provided by the Requirements class
 */
function processOnDemandHeaders(xml, _ondemandComplete) {
    var i;
    // CSS
    if(xml.getResponseHeader('X-Include-CSS')) {
        var cssIncludes = xml.getResponseHeader('X-Include-CSS').split(',');
        for(i=0;i<cssIncludes.length;i++) {
            // Syntax 1: "URL:##:media"
            if(cssIncludes[i].match(/^(.*):##:(.*)$/)) {
                jQuery.requireCss(RegExp.$1, RegExp.$2);

            // Syntax 2: "URL"
            } else {
                jQuery.requireCss(cssIncludes[i]);
            }
        }
    }

    // JavaScript
    var newIncludes = [];
    if(xml.getResponseHeader('X-Include-JS')) {
        var jsIncludes = xml.getResponseHeader('X-Include-JS').split(',');
        for(i=0;i<jsIncludes.length;i++) {
            if(!jQuery.isItemLoaded(jsIncludes[i])) {
                newIncludes.push(jsIncludes[i]);
            }
        }
    }
    
    // We make an array of the includes that are actually new, and attach the callback to the last one
    // They are placed in a queue and will be included in order.  This means that the callback will 
    // be able to execute script in the new includes (such as a livequery update)
    if(newIncludes.length > 0) {
        for(i=0;i<jsIncludes.length;i++) {
            jQuery.requireJs(jsIncludes[i], (i == jsIncludes.length-1) ? function() { _ondemandComplete(xml); } : null);
        }
        
    // If there aren't any new includes, then we can just call the callbacks ourselves                
    } else {
        _ondemandComplete(xml, status);
    }
}