// Shortcut-function (until we update to Prototye v1.5)
if(typeof $$ != "Function") $$ = document.getElementsBySelector;

GB_OpenerObj = {};
GB_RefreshLink = "";

ComplexTableField = Class.create();
ComplexTableField.prototype = {
	
	// These are defaults used if setPopupSize encounters errors
	defaultPopupWidth: 560,
	defaultPopupHeight: 390,
	
	initialize: function() {
		var rules = {};
		rules['#'+this.id+' table.data a.popuplink'] = {onclick: this.openPopup.bind(this)};
		
		// Assume that the delete link uses the deleteRecord method
		rules['#'+this.id+' table.data a.deletelink'] = {onclick: this.deleteRecord.bind(this)};
		
		// invoke row action-link based on default-action set in classname
		var defaultAction = this.getDefaultAction();

		if(defaultAction) {
			rules['#'+this.id+' table.data tbody td'] = {
				onclick: function(e) {
					var elt = Event.element(e);
					// Check the tag, as otherwise this
					// function can take over checkbox
					// click actions etc. See ticket #4737
					if (elt.tagName != 'TD' && elt.tagName != 'TR') {
						return;
					}
					
					var link = $$('.'+defaultAction, Event.element(e).parentNode)[0].href;
					this.openPopup(null, link);
					return false;
				}.bind(this)
			};
		}
		Behaviour.register('ComplexTableField_'+this.id,rules);
		
		this.setPopupSize();
		
		// HACK If already in a popup, we can't allow add (doesn't save existing relation correctly)
		if(window != top) $$('#'+this.id+' table.data a.addlink').each(function(el) {Element.hide(el);});
	},
	
	setPopupSize: function() {
		try {
			this.popupHeight = parseInt(document.getElementById(this.id + '_PopupHeight').value);
			this.popupWidth = parseInt(document.getElementById(this.id + '_PopupWidth').value);
		} catch (ex) {
			this.popupHeight = this.defaultPopupHeight;
			this.popupWidth = this.defaultPopupWidth;
		}
	},
	
	getDefaultAction: function() {
		// try to get link class from <td class="action default"><a href="...
		var links = $$('#'+this.id+' table.data tbody .default a');
		// fall back to first given link
		if(!links || !links[0]) links = $$('#'+this.id+' table.data tbody .action a');
		return (links && links[0]) ? $A(Element.classNames(links[0])).last() : false;
	},
	
	/**
	 * @param href, table Optional dom object (use for external triggering without an event)
	 */
	openPopup: function(e, _popupLink, _table) {
		// If already in a popup, simply open the link instead
		// of opening a nested lightwindow
		if(window != top) return true;
		
		this.setPopupSize();
		
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
					popupLink = link.href + (link.href.match(/\?/) ? "&ajax=1" : "?ajax=1");
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
		
		if(this.GB_Caption) {
			var title = this.GB_Caption;
		} else {
			// Getting the title from the URL is pretty ugly, but it works for now
			type = popupLink.match(/[0-9]+\/([^\/?&]*)([?&]|$)/);
			var title = (type && type[1]) ? type[1].ucfirst() : "";
		}
		
		// reset internal greybox callbacks, they are not properly unregistered
		// and fire multiple times on each subsequent popup close action otherwise
		if(GB_ONLY_ONE) GB_ONLY_ONE.callback_fn = [];
		
		GB_show(
			title, 
			popupLink, 
			this.popupHeight, 
			this.popupWidth,
			this.refresh.bind(this)
		);
		
		if(e) {
			Event.stop(e);
		}
		return false;
	}
}

ComplexTableField.applyTo('div.ComplexTableField');

/**
 * Get first letter as uppercase
 */
String.prototype.ucfirst = function () {
   var firstLetter = this.substr(0,1).toUpperCase()
   return this.substr(0,1).toUpperCase() + this.substr(1,this.length);
}