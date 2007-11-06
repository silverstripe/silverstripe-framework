GB_OpenerObj = {};
GB_RefreshLink = "";

ComplexTableField = Class.create();
ComplexTableField.prototype = {
	
	// TODO adjust dynamically
	popupWidth: 560,
	popupHeight: 390,
	
	deleteConfirmMessage: "Are you sure you want to delete this record?",
	
	initialize: function() {
		var rules = {};
		rules['#'+this.id+' table.data a.popuplink'] = {onclick: this.openPopup.bind(this)};
		rules['#'+this.id+' table.data a.deletelink'] = {onclick: this.deleteRecord.bind(this)};
		rules['#'+this.id+' table.data tbody td'] = {onclick: this.openPopup.bind(this)};
		
		Behaviour.register(rules);
	},
	
	/**
	 * Deletes the given dataobject record via an ajax request
	 * to complextablefield->Delete()
	 * @param {Object} e
	 */
	deleteRecord: function(e) {
		var img = Event.element(e);
		var link = Event.findElement(e,"a");
		var row = Event.findElement(e,"tr");
		
		// TODO ajaxErrorHandler and loading-image are dependent on cms, but formfield is in sapphire
		var confirmed = (this.deleteConfirmMessage != undefined) ? confirm(this.deleteConfirmMessage) : true;
		if(confirmed)
		{
			img.setAttribute("src",'cms/images/network-save.gif'); // TODO doesn't work
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
	},
	
	/**
	 * @param href, table Optional dom object (use for external triggering without an event)
	 */
	openPopup: function(e, _popupLink, _table) {
		var el,type;
		var popupLink = "";
		if(_popupLink) {
			popupLink = _popupLink;
			table = _table;
		} else {
			// if clicked item is an input-element, don't trigger popup
			var el = Event.element(e);
			var input = Event.findElement(e,"input");
			var tr = Event.findElement(e, "tr");
			
			// stop on non-found lines
			if(tr && Element.hasClassName(tr, 'notfound')) {
				Event.stop(e);
				return false;
			}
			
			// normal behaviour for input elements
			if(el.nodeName == "INPUT" || input.length > 0) {
				return true;
			}
			
			try {
				var table = Event.findElement(e,"table");
				if(Event.element(e).nodeName == "IMG") {
					link = Event.findElement(e,"a");
					popupLink = link.href+"&ajax=1";
				} else {
					el = Event.findElement(e,"tr");
					var link = $$("a",el)[0];
					popupLink = link.href;
				}
			} catch(err) {
				// no link found
				Event.stop(e);
				return false;
			}
			// no link found
			if(!link || popupLink.length == 0) {
				Event.stop(e);
				return false;
			}
		}
		
		if($('SecurityID')) {
			popupLink = popupLink + '&SecurityID=' + $('SecurityID').value;
		}

		GB_OpenerObj = this;
		// use same url to refresh the table after saving the popup, but use a generic rendering method
		GB_RefreshLink = popupLink;
		GB_RefreshLink = GB_RefreshLink.replace(/(methodName=)[^&]*/,"$1ajax_refresh");
		// dont include pagination index
		GB_RefreshLink = GB_RefreshLink.replace(/ctf\[start\][^&]*/,"");
		GB_RefreshLink += '&forcehtml=1';
		if(this.GB_Caption) {
			var title = this.GB_Caption;
		} else {
			type = popupLink.match(/methodName=([^&]*)/);
			var title = (type && type[1]) ? type[1].ucfirst() : "";
		}
		
		GB_show(title, popupLink, this.popupHeight, this.popupWidth);
		
		if(e) {
			Event.stop(e);
		}
		return false;
	}
}

ComplexTableField.applyTo('div.ComplexTableField');