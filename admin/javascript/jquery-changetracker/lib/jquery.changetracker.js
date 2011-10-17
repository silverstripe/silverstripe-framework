(function($){
	// Copyright (c) 2009, SilverStripe Ltd.
	// All rights reserved.
	// 
	// Redistribution and use in source and binary forms, with or without
	// modification, are permitted provided that the following conditions are met:
	//     * Redistributions of source code must retain the above copyright
	//       notice, this list of conditions and the following disclaimer.
	//     * Redistributions in binary form must reproduce the above copyright
	//       notice, this list of conditions and the following disclaimer in the
	//       documentation and/or other materials provided with the distribution.
	//     * Neither the name of the <organization> nor the
	//       names of its contributors may be used to endorse or promote products
	//       derived from this software without specific prior written permission.
	// 
	// THIS SOFTWARE IS PROVIDED BY SilverStripe Ltd. ''AS IS'' AND ANY
	// EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	// WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	// DISCLAIMED. IN NO EVENT SHALL SilverStripe Ltd. BE LIABLE FOR ANY
	// DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	// (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	// LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	// ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	// (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	// SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	
	/**
	 * @class Tracks onchange events on all form fields.
	 * 
	 * @todo Implement form reset handling
	 *  
	 * @name jQuery.changetracker
	 * @author Ingo Schommer, SilverStripe Ltd.
	 * @license BSD License
	 */
	$.fn.changetracker = function(_options) {
		var self = this;

		if(this.length > 1){
			this.each(function(i, item) { 
				this.changetracker(_options); 
			});
			return this;
		}
		
		this.defaults = {
			fieldSelector: ':input:not(:submit)',
			ignoreFieldSelector: "",
			changedCssClass: 'changed'
		};
		
		var options = $.extend({}, this.defaults, _options);
		
		this.initialize = function() {
			// optional metadata plugin support
			if ($.meta) options = $.extend({}, options, this.data());
			
			// setup original values
			this.getFields()
			.bind('change', function(e) {
				var $field = $(e.target);
				var origVal = $field.data('changetracker.origVal');
				if(origVal === null || $field.val() != origVal) {
					$field.addClass(options.changedCssClass);
					self.addClass(options.changedCssClass);
				}
			})
			.each(function() {
				$(this).data('changetracker.origVal', $(this).val());
			});
		};
			
		/**
		 * Reset change state of all form fields and the form itself.
		 */
		this.reset = function() {
			this.getFields().each(function() {
				self.resetField(this);
			});
			
			this.removeClass(options.changedCssClass);
		};
		
		/**
		 * Reset the change single form field.
		 * Does not reset to the original value.
		 * 
		 * @param DOMElement field
		 */
		this.resetField = function(field) {
			return $(field).removeData('changetracker.origVal');
		};
		
		/**
		 * @return jQuery Collection of fields
		 */
		this.getFields = function() {
			return this.find(options.fieldSelector).not(options.ignoreFieldSelector);
		};
	
		return this.initialize();
	};
}(jQuery));