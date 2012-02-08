ToolbarForm = Class.create();
ToolbarForm.prototype = {
	toggle: function(ed) {
		if(this.style.display == 'block') this.close(ed);
		else this.open(ed);
	},
	close: function(ed) {
		jQuery(this).dialog('close');
	},
	open: function(ed) {
		jQuery(this).dialog('open');
	},
	onsubmit: function() {
		return false;
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
			jQuery(this).parents('form').dialog('close');
		} else {
			alert("Couldn't find form method handle" + this.name);
		}
		return false;
	}
}

MediaForm = Class.extend('ToolbarForm');
MediaForm.prototype = {
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
			this.selectedImageWidth = $('Form_EditorToolbarMediaForm_Width').value = imgTag.className.match(/destwidth=([0-9.\-]+)([, ]|$)/) ? RegExp.$1 : null;
			this.selectedImageHeight = $('Form_EditorToolbarMediaForm_Height').value = imgTag.className.match(/destheight=([0-9.\-]+)([, ]|$)/) ? RegExp.$1 : null;
		} catch(er) {
		}
	},
	
	handleaction_insertimage: function() {
		if(this.selectedImage) {
			this.selectedImage.insert();
		}
	}
	
}

ImageThumbnail = Class.create();
ImageThumbnail.prototype = {
	destroy: function() {
		this.onclick = null;		
	},
	
	onclick: function(e) {
		$('Form_EditorToolbarMediaForm').selectImage(this);
		return false;
	},
	
	insert: function() {
		var formObj = $('Form_EditorToolbarMediaForm');
		var altText = formObj.elements.AltText.value;
		var titleText = formObj.elements.ImageTitle.value;
		var cssClass = formObj.elements.CSSClass.value;
		var baseURL = document.getElementsByTagName('base')[0].href;
		var relativeHref = this.href.substr(baseURL.length);
		var captionText = formObj.elements.CaptionText.value;
		
		if(!tinyMCE.selectedInstance) tinyMCE.selectedInstance = tinyMCE.activeEditor;
		if(tinyMCE.selectedInstance.contentWindow.focus) tinyMCE.selectedInstance.contentWindow.focus();
		
		var data = {
			'src' : relativeHref,
			'alt' : altText,
			'width' : $('Form_EditorToolbarMediaForm_Width').value,
			'height' : $('Form_EditorToolbarMediaForm_Height').value,
			'title' : titleText,
			'class' : cssClass
		};
		this.ssInsertImage(tinyMCE.activeEditor, data, captionText);
		
		jQuery(formObj).trigger('onafterinsert', data);
		
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
					$('Form_EditorToolbarMediaForm').selectedImage = link;
					break;
				}
			}
		}

		$('Image').reapplyBehaviour();
      this.addToTinyMCE = this.addToTinyMCE.bind(this);
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
	
		jQuery(formObj).trigger('onafterinsert', {html: html, href: relativeHref, width: width, height: height});
	
		return false;
	}
}

MediaForm.applyTo('#Form_EditorToolbarMediaForm');
ImageThumbnail.applyTo('#Form_EditorToolbarMediaForm div.thumbnailstrip a');
SideFormAction.applyTo('#Form_EditorToolbarMediaForm .Actions input');

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
		var form = $('Form_EditorToolbarMediaForm');
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
