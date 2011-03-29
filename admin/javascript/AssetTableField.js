/**
 * File: AssetTableField.js
 */
(function($) {
	$.entwine('ss', function($){
		
		/**
		 * Class: .AssetTableField
		 */
		$('.AssetTableField').entwine({
			// Constructor: onmatch
			onmatch: function() {
				var self = this;
				
				// search button
				this.find('input#FileFilterButton').click(function(e) {
					var btn = $(this);
					$(this).addClass('loading');
					self.refresh(function() {btn.removeClass('loading');});
					return false;
				});
				
				// clear button
				this.find('input#FileFilterClearButton').click(function(e) {
					self.find('input#FileSearch').val('');
					self.find('input#FileFilterButton').click();
					return false;
				});
				
				// search field
				this.find('input#FileSearch').keypress(function(e) {
					if(e.keyCode == $.ui.keyCode.ENTER) {
						self.find('input#FileFilterButton').click();
					}
				});
				
				this._super();
			},
			
			/**
			 * Function: refresh
			 * 
			 * Parameters:
			 * (Function) callback
			 */
			refresh: function(callback) {
				var self = this;
				this.load(
					this.attr('href'),
					this.find(':input').serialize(),
					function(response, status, xmlhttp) {
						Behaviour.apply(self[0], true);
						if(callback) callback.apply(arguments);
					}
				);
			}
		});
		
		/**
		 * Class: .AssetTableField :checkbox
		 * 
		 * Checkboxes used to batch delete files
		 */
		$('.AssetTableField :checkbox').entwine({
			// Function: onchange
			onchange: function() {
				var container = this.parents('.AssetTableField');
				var input = container.find('input#deletemarked');
				if(container.find(':input[name=Files\[\]]:checked').length) {
					input.removeAttr('disabled');
				} else {
					input.attr('disabled', 'disabled');
				}
			}
		})
		
		/**
		 * Class: .AssetTableField input#deletemarked
		 * 
		 * Batch delete files marked by checkboxes in the table.
		 * Refreshes the form field afterwards via ajax.
		 */
		$('.AssetTableField input#deletemarked').entwine({
			// Constructor: onmatch
			onmatch: function() {
				this.attr('disabled', 'disabled');
				this._super();
			},
			
			/**
			 * Function: onclick
			 * 
			 * Parameters:
			 * (Event) e
			 */
			onclick: function(e) {
				if(!confirm(ss.i18n._t('AssetTableField.REALLYDELETE'))) return false;
				
				var container = this.parents('.AssetTableField');
				var self = this;
				this.addClass('loading');
				$.post(
					container.attr('href') + '/deletemarked',
					this.parents('form').serialize(),
					function(data, status) {
						self.removeClass('loading');
						container.refresh();
					}
				);
				return false;
			}
		});
	});
}(jQuery));

// TODO Implementation in Behaviour instead of entwine is necessary to overload TableListField
var AssetTableField = Class.create();
AssetTableField.applyTo('#Form_EditForm_Files');
AssetTableField.prototype = {
	initialize: function() {
		var rules = {};
		rules['#'+this.id+' table.data a.deletelink'] = {onclick: this.deleteRecord.bind(this)};
		Behaviour.register('ComplexTableField_'+this.id,rules);
	},
	
	deleteRecord: function(e) {
		var img = Event.element(e);
		var link = Event.findElement(e,"a");
		var row = Event.findElement(e,"tr");
		
		var linkCount = row.getElementsByClassName('linkCount')[0];
		if(linkCount) linkCount = linkCount.innerHTML;
		
		var confirmMessage = ss.i18n._t('TABLEFIELD.DELETECONFIRMMESSAGE', 'Are you sure you want to delete this record?');
		if(linkCount && linkCount > 0) confirmMessage += '\nThere are ' + linkCount + ' page(s) that use this file, please review the list of pages on the Links tab of the file before continuing.';

		// TODO ajaxErrorHandler and loading-image are dependent on cms, but formfield is in sapphire
		var confirmed = confirm(confirmMessage);
		if(confirmed)
		{
			img.setAttribute("src",'sapphire/admin/images/network-save.gif'); // TODO doesn't work
			new Ajax.Request(
				link.getAttribute("href"),
				{
					method: 'post', 
					postBody: 'forceajax=1' + ($('SecurityID') ? '&SecurityID=' + $('SecurityID').value : ''),
					onComplete: function(){
						Effect.Fade(
							row,
							{
								afterFinish: function(obj) {
									// remove row from DOM
									obj.element.parentNode.removeChild(obj.element);
									// recalculate summary if needed (assumes that TableListField.js is present)
									// TODO Proper inheritance
									if(this._summarise) this._summarise();
									// custom callback
									if(this.callback_deleteRecord) this.callback_deleteRecord(e);
								}.bind(this)
							}
						);
					}.bind(this),
					onFailure: this.ajaxErrorHandler
				}
			);
		}
		
		Event.stop(e);
	}
};