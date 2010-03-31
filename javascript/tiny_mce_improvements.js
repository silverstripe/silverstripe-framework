ToolbarForm = Class.create();
ToolbarForm.prototype = {
	toggle: function(ed) {
		if(this.style.display == 'block') this.close(ed);
		else this.open(ed);
	},
	close: function(ed) {
		if(this.style.display == 'block') {
			this.style.display = 'none';
			window.onresize();
		}
	},
	open: function(ed) {
		if(this.style.display != 'block') {
			this.style.display = 'block';
			window.onresize();
		}
	},
	onsubmit: function() {
		return false;
	}
}

LinkForm = Class.extend('ToolbarForm');
LinkForm.prototype = {
	initialize: function() {
		var i,item;
		for(i=0;item=this.elements.LinkType[i];i++) {
			item.parentForm = this;
			item.onclick = this.linkTypeChanged.bind(this);
		}
	},
	
	destroy: function() {
		this.ToolbarForm = null;
		this.onsubmit = null;
		
		var i,item;
		for(i=0;item=this.elements.LinkType[i];i++) {
			item.parentForm = null;
			item.onclick = null;
		}
	},
	
	linkTypeChanged: function(setDefaults) {
		var linkType = Form.Element.getValue(this.elements.LinkType);
		var list =  ['internal', 'external', 'file', 'email'];
		var i,item;
		for(i=0;item=list[i];i++) {
			$(item).style.display = (item == linkType) ? '' : 'none';
		}
		$('Anchor').style.display = (linkType == 'internal' || linkType == 'anchor') ? '' : 'none';
 		if($('Form_EditorToolbarLinkForm_TargetBlank')) {
 		    $('Form_EditorToolbarLinkForm_TargetBlank').disabled = (linkType == 'email');
 		    if(typeof setDefaults == 'undefined' || setDefaults) {
 			$('Form_EditorToolbarLinkForm_TargetBlank').checked = (linkType == 'file');
 		    }
		}
		
		if (jQuery('#Form_EditorToolbarLinkForm_AnchorSelector')) {
			if ( linkType=='anchor' ) {
				jQuery('#Form_EditorToolbarLinkForm_AnchorSelector').show();
				jQuery('#Form_EditorToolbarLinkForm_AnchorRefresh').show();
			} else {
				jQuery('#Form_EditorToolbarLinkForm_AnchorSelector').hide();
				jQuery('#Form_EditorToolbarLinkForm_AnchorRefresh').hide();
			}
		}
	},

	addAnchorSelector: function() {
		(function($) { 
			if (!$('#Form_EditorToolbarLinkForm_AnchorSelector').length) {
				// this function collects the anchors in the currently active editor and regenerates the dropdown
				var refreshAnchors = function(selector) {
					var anchors = new Array();
					// name attribute is defined as CDATA, should accept all characters and entities
					// http://www.w3.org/TR/1999/REC-html401-19991224/struct/links.html#h-12.2
					var raw = tinyMCE.activeEditor.getContent().match(/name="([^"]+?)"|name='([^']+?)'/gim);
					if (raw && raw.length) {
						for(var i = 0; i < raw.length; i++) {
							anchors.push(raw[i].substr(6).replace(/"$/, ''));
						}
					}

					selector.empty();
					selector.append($('<option value="" selected="1">Select an anchor</option>'));
					for (var i = 0; i < anchors.length; i++) {
						selector.append($('<option value="'+anchors[i]+'">'+anchors[i]+'</option>'));
					}
				}

				// refresh the anchor selector on click, or in case of IE - button click
				if( !tinymce.isIE ) {
					var anchorSelector = $('<select id="Form_EditorToolbarLinkForm_AnchorSelector"></select>');
					$('#Form_EditorToolbarLinkForm_Anchor').parent().append(anchorSelector);

					anchorSelector.focus(function(e) {
						refreshAnchors($(this));
					});
				} else {
					var buttonRefresh = $('<a id="Form_EditorToolbarLinkForm_AnchorRefresh" title="Refresh the anchor list" alt="Refresh the anchor list" class="buttonRefresh"><span></span></a>');
					var anchorSelector = $('<select id="Form_EditorToolbarLinkForm_AnchorSelector" class="hasRefreshButton"></select>');

					$('#Form_EditorToolbarLinkForm_Anchor').parent().append(buttonRefresh).append(anchorSelector);

					// :hover class does not work on IE6 on dynamically created elements, have to simulate
					buttonRefresh.mouseover(function(e) {
						buttonRefresh.addClass('buttonRefreshHover');
					});
					buttonRefresh.mouseout(function(e) {
						buttonRefresh.removeClass('buttonRefreshHover');
					});
					buttonRefresh.click(function(e) {
						var anchorSelector = $('#Form_EditorToolbarLinkForm_AnchorSelector');
						refreshAnchors(anchorSelector);
					});
				}

				// initialization
				refreshAnchors(anchorSelector);

				// copy the value from dropdown to the text field
				anchorSelector.change(function(e) {
					$('#Form_EditorToolbarLinkForm_Anchor').val($(this).val());
				});
			}
		})(jQuery);	
	},
	
	toggle: function(ed) {
		this.addAnchorSelector();
		this.ToolbarForm.toggle(ed);
		this.respondToNodeChange(ed);
	},
	
	open: function(ed) {
		this.ToolbarForm.open();
		
		this.originalSelection = null;
		var mceInst = tinyMCE.activeEditor;
	},
	
	updateSelection: function(ed) {
		if(ed.selection.getRng()) {
		    this.originalSelection = ed.selection.getRng();
	    }
    },
	
	respondToNodeChange: function(ed) {
	    if(ed == null) ed = tinyMCE.activeEditor;
	    
		if(this.style.display == 'block') {
			var i,data = this.getCurrentLink(ed);
			
			if(data) {
				for(i in data) {
					if(this.elements[i]) {
						Form.Element.setValue(this.elements[i], data[i]);
					}
				}
				
			// If we haven't selected an existing link, then just make sure we default to "internal" for the link
			// type.
			} else {
				if(!Form.Element.getValue(this.elements.LinkType)) Form.Element.setValue(this.elements.LinkType, 'internal');
			}
			this.linkTypeChanged(data ? false : true);
		}
	},
	
	handleaction_insert: function() {
		var href;
		var target = null;
		
		switch(Form.Element.getValue(this.elements.LinkType)) {
			case 'internal':
				href = '[sitetree_link id=' + this.elements.internal.value + ']';
				if(this.elements.Anchor.value) href += '#' + this.elements.Anchor.value;
				if($('Form_EditorToolbarLinkForm_TargetBlank')) {
					if($('Form_EditorToolbarLinkForm_TargetBlank').checked) target = '_blank';
				}
				break;

			case 'anchor':
				href = '#' + this.elements.Anchor.value; 
				if($('Form_EditorToolbarLinkForm_TargetBlank')) {
					if($('Form_EditorToolbarLinkForm_TargetBlank').checked) target = '_blank';
				}
				break;
			
			case 'file':
				href = this.elements.file.value;
				target = '_blank';
				break;
			
			case 'email':
				href = 'mailto:' + this.elements.email.value; 
				break;

			case 'external':
			default:
				href = this.elements.external.value;
				// Prefix the URL with "http://" if no prefix is found
				if(href.indexOf('://') == -1) {
					href = 'http://' + href;
				}
				if($('Form_EditorToolbarLinkForm_TargetBlank')) {
				    if($('Form_EditorToolbarLinkForm_TargetBlank').checked) target = '_blank';
				}
				break;
		}

		if(this.originalSelection) {
		    tinyMCE.activeEditor.selection.setRng(this.originalSelection);
		}
		
		var attributes = {
		    href : href, 
		    target : target, 
		    title : this.elements.Description.value,
		    innerHTML : this.elements.LinkText.value ? this.elements.LinkText.value : "Your Link"
		};

        // Add the new link
		this.ssInsertLink(tinyMCE.activeEditor, attributes, this);
	},
	
	/**
	 * Insert a link into the given editor.
	 * Replaces mceInsertLink in that innerHTML can also be set
	 */
	ssInsertLink: function(ed, attributes, linkFormObj) {
	    var v = attributes;
		var s = ed.selection, e = ed.dom.getParent(s.getNode(), 'A');

		if (tinymce.is(attributes, 'string'))
			attributes = {href : attributes};

		function set(e) {
			tinymce.each(attributes, function(v, k) {
			    if(k == 'innerHTML') e.innerHTML = v;
				else ed.dom.setAttrib(e, k, v);
			});
			try {
				s.select(e);
				if(linkFormObj) linkFormObj.updateSelection(ed);
			} catch(er) {}
		};
		
		function replace() {
			tinymce.each(ed.dom.select('a'), function(e) {
				if (e.href == 'javascript:mctmp(0);') set(e);
			});
		}

	    if(attributes.innerHTML && !ed.selection.getContent()) {
            if(tinymce.isIE) var rng = ed.selection.getRng();
	        e = ed.getDoc().createElement('a');
	        e.href = 'javascript:mctmp(0);';
	        s.setNode(e);
	        if(tinymce.isIE) tinyMCE.activeEditor.selection.setRng(rng);
			replace();
        }
		if (!e) {
			ed.execCommand('CreateLink', false, 'javascript:mctmp(0);');
			replace();
		} else {
			if (attributes.href)
				set(e);
			else
				ed.dom.remove(e, 1);
		}
		
		this.respondToNodeChange(ed);
	},
	
	handleaction_remove: function() {
		tinyMCE.activeEditor.execCommand('unlink', false);
	},
	
	/**
	 * Return information about the currently selected link, suitable for population of the link
	 * form.
	 */
	getCurrentLink: function(ed) {
	    if(ed == null) ed = tinyMCE.activeEditor;
	    
		var selectedText = "";
	    selectedText = ed.selection.getContent({format : 'text'});
	    var selectedElement = ed.selection.getNode();

        /*
		if ((selectedElement.nodeName.toLowerCase() != "img") && (selectedText.length <= 0)) {
		    return {};
	    }
	    */

		var href = "", target = "", title = "", onclick = "", action = "insert", style_class = "";
		
		// We use a separate field for linkDataSource from tinyMCE.linkElement.
		// If we have selected beyond the range of an <a> element, then use use that <a> element to get the link data source,
		// but we don't use it as the destination for the link insertion
		var linkDataSource = null;

		if(selectedElement && (selectedElement.nodeName.toLowerCase() == "a")) {
			linkDataSource = selectedElement;
    		ed.selection.select(linkDataSource);
		} else if(selectedElement && (selectedElement.getElementsByTagName('a').length > 0)) {
		    if(selectedElement.getElementsByTagName('a')[0]) {
		    	linkDataSource = selectedElement.getElementsByTagName('a')[0];
		    }
		} else {
			var sel = selectedElement;
			if(sel) {
				while((sel = sel.parentNode) && sel.tagName && sel.tagName.toLowerCase() != 'body') {
					if(sel.tagName.toLowerCase() == 'a') {
						linkDataSource = selectedElement = sel;
	            	ed.selection.select(linkDataSource);
						break;
					}
				}
			}
		}
		
		// Is anchor not a link
		if (linkDataSource != null && tinymce.DOM.getAttrib(linkDataSource, 'href') == "")
			linkDataSource = null;

		if (linkDataSource) {
			href = tinymce.DOM.getAttrib(linkDataSource, 'href');
			target = tinymce.DOM.getAttrib(linkDataSource, 'target');
			title = tinymce.DOM.getAttrib(linkDataSource, 'title');
              onclick = tinymce.DOM.getAttrib(linkDataSource, 'onclick');
			style_class = tinymce.DOM.getAttrib(linkDataSource, 'class');

			// Try old onclick to if copy/pasted content
			if (onclick == "")
				onclick = tinymce.DOM.getAttrib(linkDataSource, 'onclick');

            /*
			onclick = tinyMCE.cleanupEventStr(onclick);
			*/

            /*
			// Fix for drag-drop/copy paste bug in Mozilla
			mceRealHref = tinymce.DOM.getAttrib(linkDataSource, 'mce_real_href');
			if (mceRealHref != "")
				href = mceRealHref;
			*/

			href = eval(tinyMCE.settings['urlconverter_callback'] + "(href, linkDataSource, true);");
			action = "update";
		}
		
		// Turn into relative
		if(href.match(new RegExp('^' + tinyMCE.settings['document_base_url'] + '(.*)$'))) {
			href = RegExp.$1;
		}
		
		var linkText = ed.selection.getContent({format : 'html'}).replace(/<\/?a[^>]*>/ig,'');
		
		// Get rid of TinyMCE's temporary URLs
		if(href.match(/^javascript:\s*mctmp/)) href = '';
		
		if(href.match(/^mailto:(.*)$/)) {
			return {
				LinkType: 'email',
				email: RegExp.$1,
				LinkText: linkText,
				Description: title
			}
		} else if(href.match(/^(assets\/.*)$/)) {
			return {
				LinkType: 'file',
				file: RegExp.$1,
				LinkText: linkText,
				Description: title
			}
		} else if(href.match(/^#(.*)$/)) {
			return {
				LinkType: 'anchor',
				Anchor: RegExp.$1,
				LinkText: linkText,
				Description: title,
				TargetBlank: target ? true : false
			}
		} else if(href.match(/^\[sitetree_link id=([0-9]+)\]?(#.*)?$/)) {
			return {
				LinkType: 'internal',
				internal: RegExp.$1,
				Anchor: RegExp.$2 ? RegExp.$2.substr(1) : '',
				LinkText: linkText,
				Description: title,
				TargetBlank: target ? true : false
			}
		} else if(href) {
			return {
				LinkType: 'external',
				external: href,
				LinkText: linkText,
				Description: title,
				TargetBlank: target ? true : false
			}
		} else {
		    return {
				LinkType: 'internal',
		        LinkText: linkText
		    }
		}
	}		
	
}

SideFormAction = Class.create();
SideFormAction.prototype = {
	initialize: function() {
		this.parentForm = this.parentNode;
		while(this.parentForm && this.parentForm.tagName.toLowerCase() != 'form') {
			this.parentForm = this.parentForm.parentNode;
		}
	},
	destroy: function() {
		this.parentForm = null;
		this.onclick = null;
		
	},
	onclick: function() {
		if(this.parentForm['handle' + this.name]) {
			try {
				this.parentForm['handle' + this.name]();
			} catch(er) {
				alert("An error occurred.  Please try again, or reload the CMS if the problem persists.\n\nError details: " + er.message);
			}
		} else {
			alert("Couldn't find form method handle" + this.name);
		}
		return false;
	}
}

ImageForm = Class.extend('ToolbarForm');
ImageForm.prototype = {
	initialize: function() {
		var __form = this;

		this.elements.AltText.onkeyup =  function() { __form.update_params('AltText'); };
		this.elements.ImageTitle.onkeyup =  function() { __form.update_params('ImageTitle'); };
		this.elements.CaptionText.onkeyup =  function() { __form.update_params('CaptionText'); };
		this.elements.AltText.onchange = function() { __form.update_params('AltText'); };
		this.elements.Width.onchange = function() { __form.update_params('Width'); };
		this.elements.Height.onchange = function() { __form.update_params('Height'); };
	},
	toggle: function(ed) {
		this.ToolbarForm.toggle(ed);
		
		this.resetFields();
	},
	resetFields: function() {
		this.elements.AltText.value = '';
		this.elements.ImageTitle.value = '';
		this.elements.CSSClass.value = 'left';
		this.elements.CaptionText.value = '';
		this.elements.CaptionText.disabled = '';
		this.elements.CSSClass.disabled = '';
	},
	destroy: function() {
		this.ToolbarForm = null;
		this.onsubmit = null;

		this.elements.AltText.onkeyup = null;
		this.elements.ImageTitle.onkeyup = null;
		this.elements.CSSClass.onkeyup = null;
		this.elements.CSSClass.onclick = null;
		this.elements.Width.onchange = null;
		this.elements.Height.onchange = null;
	},
	update_params: function(updatedFieldName) {
		var ed = tinyMCE.activeEditor;
		var imgElement = ed.selection.getNode();
		if (!imgElement || imgElement.tagName != 'IMG') {
			imgElement = this.selectedNode;
		}
		if(imgElement && imgElement.tagName == 'IMG') {
			imgElement.alt = this.elements.AltText.value;
			imgElement.title = this.elements.ImageTitle.value;
			imgElement.className = this.elements.CSSClass.value;

			var captionElement = imgElement.nextSibling;
			if (captionElement && captionElement.tagName == 'P') {
				if (typeof(captionElement.textContent) != 'undefined') {
					captionElement.textContent = this.elements.CaptionText.value;
				} else {
					captionElement.innerText = this.elements.CaptionText.value;
				}
			}
			
			// Proportionate updating of heights
			if(updatedFieldName == 'Width') {
				imgElement.width = this.elements.Width.value;
				imgElement.removeAttribute('height');
				this.elements.Height.value = imgElement.height;
				
			} else if(updatedFieldName == 'Height') {
				imgElement.height = this.elements.Height.value;
				imgElement.removeAttribute('width');
				this.elements.Width.value = imgElement.width;
			}
		} else if (this.selectedImageWidth && this.selectedImageHeight) {
			// Proportionate updating of heights
			var w = this.elements.Width.value, h = this.elements.Height.value;
			var aspect = this.selectedImageHeight / this.selectedImageWidth;
			if(updatedFieldName == 'Width') {
				this.elements.Height.value = Math.floor(w * aspect);
			} else if(updatedFieldName == 'Height') {
				this.elements.Width.value = Math.floor(h / aspect);
			}
		}
	},
	respondToNodeChange: function(ed) {
		var imgElement = ed.selection.getNode();
		if(imgElement && imgElement.tagName == 'IMG') {
			this.selectedNode = imgElement;
			this.elements.AltText.value = imgElement.alt;
			var captionElement = imgElement.nextSibling;
			if (captionElement && captionElement.tagName == 'P') {
				this.elements.CaptionText.value = captionElement.innerText || captionElement.textContent;
			} else {
				this.elements.CaptionText.disabled = 'disabled';
			}
			this.elements.ImageTitle.value = imgElement.title;
			this.elements.CSSClass.value = imgElement.className;
			this.elements.CSSClass.disabled = 'disabled';
			this.elements.Width.value = imgElement.style.width ? parseInt(imgElement.style.width) : imgElement.width;
			this.elements.Height.value = imgElement.style.height ? parseInt(imgElement.style.height) : imgElement.height;
		} else {
			this.selectedNode = null;
		}
	},
	
	selectImage: function(image) {
		if(this.selectedImage) {
			this.selectedImage.setAttribute("class", "");
			this.selectedImage.className = "";
		}
		this.selectedImage = image;
		this.selectedImage.setAttribute("class", "selectedImage");
		this.selectedImage.className = "selectedImage";
		
		try {
			var imgTag = image.getElementsByTagName('img')[0];
			this.selectedImageWidth = $('Form_EditorToolbarImageForm_Width').value = imgTag.className.match(/destwidth=([0-9.\-]+)([, ]|$)/) ? RegExp.$1 : null;
			this.selectedImageHeight = $('Form_EditorToolbarImageForm_Height').value = imgTag.className.match(/destheight=([0-9.\-]+)([, ]|$)/) ? RegExp.$1 : null;
		} catch(er) {
		}
	},
	
	handleaction_insertimage: function() {
		if(this.selectedImage) {
			this.selectedImage.insert();
		}
	},
	
	handleaction_editimage: function() {
		if(this.selectedImage) {
			this.selectedImage.edit();
		}
	}
}

ImageThumbnail = Class.create();
ImageThumbnail.prototype = {
	destroy: function() {
		this.onclick = null;		
	},
	
	onclick: function(e) {
		$('Form_EditorToolbarImageForm').selectImage(this);
		return false;
	},
	
	edit: function() {
		var windowWidth = Element.getDimensions(window.top.document.body).width;
       var windowHeight = Element.getDimensions(window.top.document.body).height;
		var iframe = window.top.document.getElementById('imageEditorIframe');
		if(iframe != null) {
			iframe.parentNode.removeChild(iframe);
		}
		iframe = window.top.document.createElement('iframe');
		var fileToEdit = this.href;
		iframe.setAttribute("src","admin/ImageEditor?fileToEdit=" + fileToEdit);
		iframe.id = 'imageEditorIframe';
		iframe.style.width = windowWidth - 6 + 'px';
		iframe.style.height = windowHeight + 10 + 'px';
		iframe.style.zIndex = "1000";
		iframe.style.position = "absolute";
		iframe.style.top = "8px";
		iframe.style.left = "8px";
		window.top.document.body.appendChild(iframe);
		var divLeft = window.top.document.createElement('div');
		var divRight = window.top.document.createElement('div');
        divLeft.style.width = "8px";
        divLeft.style.height = "300%";
        divLeft.style.zIndex = "1000";
        divLeft.style.top = "0";
        divLeft.style.position = "absolute";
        divRight.style.width = "10px";
        divRight.style.height = "300%";
        divRight.style.zIndex = "1000";
        divRight.style.top = "0";
        divRight.style.position = "absolute";
        divRight.style.left = Element.getDimensions(divLeft).width + Element.getDimensions(iframe).width - 4 + 'px';
		window.top.document.body.appendChild(divLeft);
		window.top.document.body.appendChild(divRight);
		return;
	},
	
	insert: function() {
		var formObj = $('Form_EditorToolbarImageForm');
		var altText = formObj.elements.AltText.value;
		var titleText = formObj.elements.ImageTitle.value;
		var cssClass = formObj.elements.CSSClass.value;
		var baseURL = document.getElementsByTagName('base')[0].href;
		var relativeHref = this.href.substr(baseURL.length);
		var captionText = formObj.elements.CaptionText.value;
		
		if(!tinyMCE.selectedInstance) tinyMCE.selectedInstance = tinyMCE.activeEditor;
		if(tinyMCE.selectedInstance.contentWindow.focus) tinyMCE.selectedInstance.contentWindow.focus();
		
		this.ssInsertImage(tinyMCE.activeEditor, {
			'src' : relativeHref,
			'alt' : altText,
			'width' : $('Form_EditorToolbarImageForm_Width').value,
			'height' : $('Form_EditorToolbarImageForm_Height').value,
			'title' : titleText,
			'class' : cssClass
		}, captionText);
		
		return false;
	},
	
	/**
	 * Insert an image with the given attributes
	 */
	 ssInsertImage: function(ed, attributes, captionText) {
		el = ed.selection.getNode();
		var html;
		
		if(captionText) {
			html = '<div style="width: ' + attributes.width + 'px;" class="captionImage ' + attributes['class'] + '">';
			html += '<img id="__mce_tmp" />';
			html += '<p class="caption">' + captionText + '</p>';
			html += '</div>';
		} else {
			html = '<img id="__mce_tmp" />';
		}
		
		if(el && el.nodeName == 'IMG') {
			ed.dom.setAttribs(el, attributes);
		} else {
			// Focus gets saved in tinymce_ssbuttons when opening the sidebar.
			// Unless the focus has changed in the meantime, reset it to the previous position.
			// This is necessary because IE can lose its focus if any of the sidebar input fields are used.
			if(ed.ss_focus_bookmark) {
				ed.selection.moveToBookmark(ed.ss_focus_bookmark);
				delete ed.ss_focus_bookmark;
			}
			ed.execCommand('mceInsertContent', false, html, {
				skip_undo : 1
			});
			
			ed.dom.setAttribs('__mce_tmp', attributes);
			ed.dom.setAttrib('__mce_tmp', 'id', '');
			ed.undoManager.add();
		}
	}
	
}

var selectedimage = false;

function reselectImage(transport) {
		if(selectedimage) {
			links = $('Image').getElementsByTagName('a');
			for(i =0; link = links[i]; i++) {
				var quesmark = link.href.lastIndexOf('?');
				image = link.href.substring(0, quesmark);
				if(image == selectedimage) {
					link.className = 'selectedImage';
					$('Form_EditorToolbarImageForm').selectedImage = link;
					break;
				}
			}
		}

		$('Image').reapplyBehaviour();
      this.addToTinyMCE = this.addToTinyMCE.bind(this);
}

function imageEditorClosed() {
	if(self.refreshAsset) {
		refreshAsset();
	}
	if($('Form_EditorToolbarImageForm')) {
		if($('Form_EditorToolbarImageForm').style.display != "none") {
			// FInd the selected image
			links = $('Image').getElementsByTagName('a');
			for(i =0; link = links[i]; i++) {
				if(link.className == 'selectedImage') {
					var quesmark = link.href.lastIndexOf('?');
					selectedimage = link.href.substring(0, quesmark);
					break;
				}
			}
		
			// Trick the folder dropdown into registering a change, so the image thumbnails are reloaded
			folderID = $('Form_EditorToolbarImageForm_FolderID').value;
			$('Image').ajaxGetFiles(folderID, null, reselectImage);
		}
	}
}

FlashForm = Class.extend('ToolbarForm');
FlashForm.prototype = {
	initialize: function() {
	},
	destroy: function() {
		this.ToolbarForm = null;
		this.onsubmit = null;

	},
	update_params: function(event) {
		if(tinyMCE.imgElement) {
		}
	},
	respondToNodeChange: function() {
		if(tinyMCE.imgElement) {
		} else {
		}
	},
	selectFlash: function(flash) {
		if(this.selectedFlash) {
			this.selectedFlash.setAttribute("class", "");
		}
		this.selectedFlash = flash;
		this.selectedFlash.setAttribute("class", "selectedFlash");
	},
	handleaction_insertflash: function() {
		if(this.selectedFlash) {
			this.selectedFlash.insert();
		}
	}
}

FlashThumbnail = Class.create();
FlashThumbnail.prototype = {
	destroy: function() {
		this.onclick = null;		
	},
	
	onclick: function(e) {
		$('Form_EditorToolbarFlashForm').selectFlash(this);
		return false;
	},
	
	insert: function() {
		var formObj = $('Form_EditorToolbarFlashForm');
		var html = '';
		var baseURL = document.getElementsByTagName('base')[0].href;
		var relativeHref = this.href.substr(baseURL.length)
		var width = formObj.elements.Width.value;
		var height = formObj.elements.Height.value;
		
		if(!tinyMCE.selectedInstance) tinyMCE.selectedInstance = tinyMCE.activeEditor;
		if(tinyMCE.selectedInstance.contentWindow.focus) tinyMCE.selectedInstance.contentWindow.focus();

		if (width == "") width = 100;
		if (height == "") height = 100;
		
		html = '';
		html += '<object width="' + width +'" height="' + height +'" type="application/x-shockwave-flash" data="'+ relativeHref +'">';
		html += '<param value="'+ relativeHref +'" name="movie">';
		html += '</object>';
		
		tinyMCE.selectedInstance.execCommand("mceInsertContent", false, html);
		tinyMCE.selectedInstance.execCommand('mceRepaint');
	//	ed.execCommand('mceInsertContent', false, html, {skip_undo : 1}); 
		return false;
	}
}

LinkForm.applyTo('#Form_EditorToolbarLinkForm');
SideFormAction.applyTo('#Form_EditorToolbarLinkForm .Actions input');

ImageForm.applyTo('#Form_EditorToolbarImageForm');
ImageThumbnail.applyTo('#Form_EditorToolbarImageForm div.thumbnailstrip a');
SideFormAction.applyTo('#Form_EditorToolbarImageForm .Actions input');

FlashForm.applyTo('#Form_EditorToolbarFlashForm');
FlashThumbnail.applyTo('#Form_EditorToolbarFlashForm div.thumbnailstrip a');
SideFormAction.applyTo('#Form_EditorToolbarFlashForm .Actions input');

/**
 * Image resizing
 */
MCEImageResizer = Class.create();
MCEImageResizer.prototype = {
	initialize: function() {
		//TinyMCE.prototype.addEvent(this, 'click', this._onclick);
	},
	_onclick: function() {
		var form = $('Form_EditorToolbarImageForm');
		if(form) {
			form.elements.AltText.value = this.alt;
			form.elements.ImageTitle.value = this.title;
			form.elements.CSSClass.value = this.className;
		}
	},
	onresizestart: function() {
		this.prepareForResize();
		this.heightDiff = 0;
	},
	onresizeend: function() {
		this.resizeTo(this.style.width, this.style.height);
	},
	onmouseup: function() {
		if(this.parentNode.parentNode.className.match(/(^|\b)specialImage($|\b)/)) {
			this.ownerDoc().setActive();
			this.parentNode.parentNode.setActive();
		}
	},
	prepareForResize: function() {
		if(this.aspectRatio == null) {
			this.aspectRatio = this.height / this.width;
		}
	
		this.originalWidth = this.width;
		this.originalHeight = this.height;
	},

	ownerDoc: function() {
		var f =this.parentNode;
		while(f && f.tagName.toLowerCase() != 'body') f = f.parentNode;
		return f;
	},
	
	resizeTo: function(width, height) {
		var newWidth = parseInt(height);
		var newHeight = parseInt(height) - this.heightDiff;
		if(isNaN(newWidth)) newWidth = this.width;
		if(isNaN(newHeight)) newHeight = this.height;
		
		// Constrain to width of the window
		if((this.offsetLeft + this.offsetWidth + 20) > this.ownerDoc().offsetWidth)
			newWidth += (this.ownerDoc().offsetWidth - this.offsetLeft - this.offsetWidth - 20);
	
		if(this.aspectRatio) {
			// Figure out which dimension we have altered more
			var heightChange = this.originalHeight / this.height; 
			if(heightChange < 1) heightChange = 1/heightChange;
			
			var widthChange = this.originalWidth / this.width;
			if(widthChange < 1) widthChange = 1/widthChange;
			
			// Scale by the more constant dimension (so if you edit the height, change width to suit)
			if(widthChange > heightChange)
				newHeight = newWidth * this.aspectRatio;
			else
				newWidth = newHeight / this.aspectRatio;
		}

		this.style.width = newWidth + 'px';
		this.style.height = newHeight + 'px';
		this.width = newWidth;
		this.height = newHeight;	
		
		// Auto-size special image holders
		if(this.parentNode.parentNode.className.match(/(^|\b)specialImage($|\b)/)) {
			this.parentNode.parentNode.style.width = newWidth + 'px';
		}
	}
}

MCEDLResizer = Class.extend('MCEImageResize');
MCEDLResizer.prototype = {
	onresizestart: function() {
		var img = this.getElementsByTagName('img')[0];
		img.prepareForResize();
		img.heightDiff = this.offsetHeight - img.height;
	},
	onresizeend: function() {
		this.getElementsByTagName('img')[0].resizeTo(this.style.width, this.style.height);
	}
}

/**
 * These callback hook it into tinymce.  They need to be referenced in the TinyMCE config.
 */
function sapphiremce_setupcontent(editor_id, body, doc) {
	var allImages = body.getElementsByTagName('img');
	var i,img;
	for(i=0;img=allImages[i];i++) {
		behaveAs(img, MCEImageResizer);
	}

	var allDLs = body.getElementsByTagName('img');
	for(i=0;img=allDLs[i];i++) {
		if(img.className.match(/(^|\b)specialImage($|\b)/)) {
			behaveAs(img, MCEDLResizer);
		}
	}
}

function sapphiremce_cleanup(type, value) {
	if(type == 'get_from_editor') {
		// replace indented text with a <blockquote>
		value = value.replace(/<p [^>]*margin-left[^>]*>([^\n|\n\015|\015\n]*)<\/p>/ig,"<blockquote><p>$1</p></blockquote>");
	
		// replace VML pixel image references with image tags - experimental
		value = value.replace(/<[a-z0-9]+:imagedata[^>]+src="?([^> "]+)"?[^>]*>/ig,"<img src=\"$1\">");
		
		// Word comments
		value = value.replace(new RegExp('<(!--)([^>]*)(--)>', 'g'), ""); 
			
		// kill class=mso??? and on mouse* tags  
		value = value.replace(/([ \f\r\t\n\'\"])class=mso[a-z0-9]+[^ >]+/ig, "$1"); 
		value = value.replace(/([ \f\r\t\n\'\"]class=")mso[a-z0-9]+[^ ">]+ /ig, "$1"); 
		value = value.replace(/([ \f\r\t\n\'\"])class="mso[a-z0-9]+[^">]+"/ig, "$1"); 
		value = value.replace(/([ \f\r\t\n\'\"])on[a-z]+=[^ >]+/ig, "$1");
		value = value.replace(/ >/ig, ">"); 
	
		// remove everything that's in a closing tag
		value = value.replace(/<(\/[A-Za-z0-9]+)[ \f\r\t\n]+[^>]*>/ig,"<$1>");		
	}

	if(type == 'get_from_editor_dom') {
		var allImages =value.getElementsByTagName('img');
		var i,img;

		for(i=0;img=allImages[i];i++) {
			img.onresizestart = null;
			img.onresizeend = null;
			img.removeAttribute('onresizestart');
			img.removeAttribute('onresizeend');
		}

		var allDLs =value.getElementsByTagName('img');
		for(i=0;img=allDLs[i];i++) {
			if(img.className.match(/(^|\b)specialImage($|\b)/)) {
				img.onresizestart = null;
				img.onresizeend = null;
				img.removeAttribute('onresizestart');
				img.removeAttribute('onresizeend');
			}
		}
		
	}
	return value;
}

contentPanelCloseButton = Class.create();
contentPanelCloseButton.prototype = {
	onclick: function() {
	    tinyMCE.activeEditor.execCommand('ssclosesidepanel');
	}
}

contentPanelCloseButton.applyTo('#contentPanel h2 img');

