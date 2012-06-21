/**
 * On-demand JavaScript handler
 * 
 * Based on http://plugins.jquery.com/files/issues/jquery.ondemand.js_.txt 
 * and heavily modified to integrate with SilverStripe and prototype.js.
 * Adds capabilities for custom X-Include-CSS and X-Include-JS HTTP headers
 * to request loading of externals alongside an ajax response.
 * 
 * Requires jQuery 1.5 ($.Deferred support)
 * 
 * CAUTION: Relies on customization of the 'beforeSend' callback in jQuery.ajaxSetup()
 * 
 * @author Ingo Schommer (ingo at silverstripe dot com)
 * @author Sam Minnee (sam at silverstripe dot com)
 */
(function($){

	var decodePath = function(str) {
		return str.replace(/%2C/g,',').replace(/\&amp;/g, '&');
	};

	$.extend({

		// loaded files list - to protect against loading existed file again  (by PGA)
		_ondemand_loaded_list : null,
	    
		/**
		 * Returns true if the given CSS or JS script has already been loaded
		 */
		isItemLoaded : function(scriptUrl) {
			var self = this, src;
			if(this._ondemand_loaded_list === null) {
				this._ondemand_loaded_list = {};
				$('script').each(function() {
					src = $(this).attr('src');
					if(src) self._ondemand_loaded_list[src] = 1;
				});
				$('link[rel="stylesheet"]').each(function() {
					src = $(this).attr('href');
					if(src) self._ondemand_loaded_list[src] = 1;
				});
			}
			return (this._ondemand_loaded_list[decodePath(scriptUrl)] !== undefined);
		},

		requireCss : function(styleUrl, media){
			if(!media) media = 'all';

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
		processOnDemandHeaders: function(xml, status, xhr) {
			var self = this, processDfd = new $.Deferred();
			
			// CSS
			if(xhr.getResponseHeader && xhr.getResponseHeader('X-Include-CSS')) {
				var cssIncludes = xhr.getResponseHeader('X-Include-CSS').split(',');
				for(var i=0;i<cssIncludes.length;i++) {
					// Syntax: "URL:##:media"
					if(cssIncludes[i].match(/^(.*):##:(.*)$/)) {
						$.requireCss(decodePath(RegExp.$1), RegExp.$2);
					// Syntax: "URL"
					} else {
						$.requireCss(decodePath(cssIncludes[i]));
					}
				}
			}

			// JavaScript
			var newJsIncludes = [];
			if(xhr.getResponseHeader && xhr.getResponseHeader('X-Include-JS')) {
				var jsIncludes = xhr.getResponseHeader('X-Include-JS').split(',');
				for(var i=0;i<jsIncludes.length;i++) {
					var jsIncludePath = decodePath(jsIncludes[i]);
					if(!$.isItemLoaded(jsIncludePath)) {
						newJsIncludes.push(jsIncludePath);
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
					processDfd.resolve(xml, status, xhr);
				}
			}

			if(newJsIncludes.length) {
				getScriptQueue();
			} else {
				// If there aren't any new includes, then we can just call the callbacks ourselves                
				processDfd.resolve(xml, status, xhr);
			}
			
			return processDfd.promise();
		}
		
	});
	
	$.ajaxSetup({
		// beforeSend is the only place to access the XHR object before success handlers are added
		beforeSend: function(jqXHR, s) {
			// Avoid recursion in ajax callbacks caused by getScript(), by not parsing
			// ondemand headers for 'script' datatypes
			if(s.dataType == 'script') return;
			
			var dfd = new $.Deferred();
			
			// Register our own success handler (assumes no handlers are already registered)
			// 'success' is an alias for 'done', which is executed by the built-in deferred instance in $.ajax()
			jqXHR.success(function(success, statusText, jXHR) {
				$.processOnDemandHeaders(success, statusText, jXHR).done(function() {
					dfd.resolveWith(s.context || this, [success, statusText, jXHR]);
				});
			});
			
			// Reroute all external success hanlders through our own deferred.
			// Not overloading fail() as no event can cause the original request to fail.
			jqXHR.success = function(callback) {
				dfd.done(callback);
			}
		}
	});
	

})(jQuery);