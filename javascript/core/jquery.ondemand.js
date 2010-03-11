/**
 * On-demand JavaScript handler
 * Based on http://plugins.jquery.com/files/issues/jquery.ondemand.js_.txt 
 * and heavily modified to integrate with SilverStripe and prototype.js.
 * Adds capabilities for custom X-Include-CSS and X-Include-JS HTTP headers
 * to request loading of externals alongside an ajax response.
 * 
 * IMPORTANT: This plugin monkeypatches the jQuery.ajax() method.
 */
(function($){

	$.extend({

		// loaded files list - to protect against loading existed file again  (by PGA)
		_ondemand_loaded_list : null,
		
		// Added by SRM: Initialise the loaded_list with the scripts included on first load
		initialiseItemLoadedList : function() {
			if(this.loaded_list == null) {
				$this = this;
				$this.loaded_list = {};
				$('script').each(function() {
					if($(this).attr('src')) $this.loaded_list[ $(this).attr('src') ] = 1;
				});
				$('link[rel="stylesheet"]').each(function() {
					if($(this).attr('href')) $this.loaded_list[ $(this).attr('href') ] = 1;
				});
			}
		},
	    
		/**
		 * Returns true if the given CSS or JS script has already been loaded
		 */
		isItemLoaded : function(scriptUrl) {
			var self = this;
			
			if(this._ondemand_loaded_list == null) {
				this._ondemand_loaded_list = {};
				$('script').each(function() {
					if($(this).attr('src')) self._ondemand_loaded_list[ $(this).attr('src') ] = 1;
				});
				$('link[rel="stylesheet"]').each(function() {
					if($(this).attr('href')) self._ondemand_loaded_list[ $(this).attr('href') ] = 1;
				});
			}
			
			return (this._ondemand_loaded_list[scriptUrl] != undefined);
		},

		requireCss : function(styleUrl, media){
			if(media == null) media = 'all';

			// Don't double up on loading scripts
			if($.isItemLoaded(styleUrl)) return;

			if(document.createStyleSheet){
				var ss = document.createStyleSheet(styleUrl);
				ss.media = media;
				
			} else {
				var styleTag = document.createElement('link');
				$(styleTag).attr({
					href	: styleUrl,
					type	: 'text/css',
					media 	: media,
					rel		: 'stylesheet'
				}).appendTo($('head').get(0));
			}
			
			this._ondemand_loaded_list[styleUrl] = 1;

		},
		
		/**
		 * Process the X-Include-CSS and X-Include-JS headers provided by the Requirements class
		 */
		processOnDemandHeaders: function(xml, status, _ondemandComplete) {
			var self = this;
			
			// CSS
			if(xml.getResponseHeader('X-Include-CSS')) {
				var cssIncludes = xml.getResponseHeader('X-Include-CSS').split(',');
				for(var i=0;i<cssIncludes.length;i++) {
					// Syntax: "URL:##:media"
					if(cssIncludes[i].match(/^(.*):##:(.*)$/)) {
						$.requireCss(RegExp.$1, RegExp.$2);
					// Syntax: "URL"
					} else {
						$.requireCss(cssIncludes[i]);
					}
				}
			}

			// JavaScript
			var newJsIncludes = [];
			if(xml.getResponseHeader('X-Include-JS')) {
				var jsIncludes = xml.getResponseHeader('X-Include-JS').split(',');
				for(var i=0;i<jsIncludes.length;i++) {
					if(!$.isItemLoaded(jsIncludes[i])) {
						newJsIncludes.push(jsIncludes[i]);
					}
				}
			}

			// We make an array of the includes that are actually new, and attach the callback to the last one
			// They are placed in a queue and will be included in order.  This means that the callback will 
			// be able to execute script in the new includes (such as a livequery update)			
			var getScriptQueue = function() {
				if(newJsIncludes.length) {
					var newJsInclude = newJsIncludes.shift();
					// emulates getScript() with addtl. setting
					$.ajax({
						dataType: 'script',
						url: newJsInclude, 
						success: function() {
							self._ondemand_loaded_list[newJsInclude] = 1;
							getScriptQueue();
						},
						cache: false,
						// jQuery seems to override the XHR objects if used in async mode
						async: false
					});
				} else {
					_ondemandComplete(xml, status); 
				}
			}

			if(newJsIncludes.length) {
				getScriptQueue();
			} else {
				// If there aren't any new includes, then we can just call the callbacks ourselves                
				_ondemandComplete(xml, status);
			}
		}

	});
	
	/**
	 * Ajax requests are amended to look for X-Include-JS and X-Include-CSS headers
	 */
	 _originalAjax = $.ajax;
	 $.ajax = function(s) {
		// Avoid recursion in ajax callbacks caused by getScript(), by not parsing
		// ondemand headers for 'script' datatypes
		if(s.dataType == 'script') return _originalAjax(s);

		var _complete = s.complete;
		var _success = s.success;
		var _dataType = s.dataType;

		// This replaces the usual ajax success & complete handlers.  They are called after any on demand JS is loaded.
		var _ondemandComplete = function(xml, status) {
			var status = $.httpSuccess(xml) ? 'success' : 'error';
			if(status == 'success') {
				var data = jQuery.httpData(xml, _dataType);
				if(_success) _success(data, status);
			}
			if(_complete) _complete(xml, status);
		}
	    
		// We remove the success handler and take care of calling it outselves within _ondemandComplete
		s.success = null;
		s.complete = function(xml, status) {
			$.processOnDemandHeaders(xml, status, _ondemandComplete);
		}

		return _originalAjax(s);
    }

})(jQuery);

/**
 * This is the on-demand handler used by our patched version of prototype.
 * once we get rid of all uses of prototype, we can remove this
 */
function prototypeOnDemandHandler(xml, status, callback) {
    jQuery.processOnDemandHeaders(xml, status, callback);
}