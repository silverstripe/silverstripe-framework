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

			var onchange = function(e) {
				var $field = $(e.target);
				var origVal = $field.data('changetracker.origVal');
				if(origVal === null || e.target.value != origVal) {
					// TODO Also add class to radiobutton/checkbox siblings
					$field.addClass(options.changedCssClass);
					self.addClass(options.changedCssClass);
				}
			};
			
			// setup original values
			var fields = this.getFields();
			fields.filter(':radio,:checkbox').bind('click.changetracker', onchange);
			fields.not(':radio,:checkbox').bind('change.changetracker', onchange);
			fields.each(function() {
				var origVal = $(this).is(':radio,:checkbox') ? self.find(':input[name=' + $(this).attr('name') + ']:checked').val() : $(this).val();
				$(this).data('changetracker.origVal', origVal);
			});

			this.data('changetracker', true);
		};

		this.destroy = function() {
			this.getFields()
				.unbind('.changetracker')
				.removeClass(options.changedCssClass)
				.removeData('changetracker.origVal');
			this.removeData('changetracker');
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
			return $(field).removeData('changetracker.origVal').removeClass('changed');
		};
		
		/**
		 * @return jQuery Collection of fields
		 */
		this.getFields = function() {
			return this.find(options.fieldSelector).not(options.ignoreFieldSelector);
		};

		// Support invoking "public" methods as string arguments
		if (typeof arguments[0] === 'string') {  
      var property = arguments[1];  
      var args = Array.prototype.slice.call(arguments);  
      args.splice(0, 1);  
      return this[arguments[0]].apply(this, args);  
  	} else {
    	return this.initialize();
    }
		
	};
}(jQuery));