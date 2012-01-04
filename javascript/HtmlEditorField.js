/**
 * Functions for HtmlEditorFields in the back end.
 * Includes the JS for the ImageUpload forms. 
 * 
 * Relies on the jquery.form.js plugin to power the 
 * ajax / iframe submissions
 */

(function($) {
		
	$(document).ready(function() {
		
		// jQuery('#Form_EditorToolbarLinkForm').dialog('open')
		
		/**
		 * On page refresh load the initial images (in root)
		 */
		if($("#FolderImages").length > 0 && $("body.CMSMain").length > 0) loadImages(false);
		
		/**
		 * Show / Hide the Upload Form 
		 */
		$("#Form_EditorToolbarImageForm .showUploadField a").click(function() {
			if($(this).hasClass("showing")) {
				$("#Form_EditorToolbarImageForm_Files-0").parents('.file').hide();
				$(this).text(ss.i18n._t('HtmlEditorField.ShowUploadForm', 'Upload File')).removeClass("showing");	
			}
			else {
				$("#Form_EditorToolbarImageForm_Files-0").parents('.file').show();
				$(this).text(ss.i18n._t('HtmlEditorField.HideUploadForm', 'Hide Upload Form')).addClass("showing");
			}
			return false;
		}).show();
		
		/**
		 * On folder change - lookup the new images
		 */
		$("#Form_EditorToolbarImageForm_Files-0").change(function() {
			$(".cms-editor-dialogs #Form_EditorToolbarImageForm").ajaxForm({
				url: 'admin/assets/UploadForm?action_doUpload=1',
				iframe: true,
				dataType: 'json',
				beforeSubmit: function(data) {
					$("#UploadFormResponse").text("Uploading File...").addClass("loading").show();
					$("#Form_EditorToolbarImageForm_Files-0").parents('.file').hide();
				},
				success: function(data) {
					$("#UploadFormResponse").text("").removeClass("loading");
					$("#Form_EditorToolbarImageForm_Files-0").val("").parents('.file').show();
					
		 			$("#FolderImages").html('<h2>'+ ss.i18n._t('HtmlEditorField.Loading', 'Loading') + '</h2>');
					
					loadImages(data);
				}
			}).submit();
		});
		
		/**
		 * Loads images from getimages() to the thumbnail view. It's called on
		 */
		function loadImages(params) {
			$.get('admin/EditorToolbar/ImageForm', {
				action_callfieldmethod: "1",
				fieldName: "FolderImages",
				ajax: "1",
				methodName: "getimages",
				folderID: $("#Form_EditorToolbarImageForm_FolderID").val(),
				searchText: $("#Form_EditorToolbarImageForm_getimagesSearch").val(),
				cacheKillerDate: parseInt((new Date()).getTime()),
				cacheKillerRand: parseInt(10000 * Math.random())
			},
			function(data) {
				$("#FolderImages").html(data);
				
				$("#FolderImages").each(function() {
					Behaviour.apply(this);
				});
				
				if(params) {
					$("#FolderImages a[href*="+ params.Filename +"]").click();
				}
			});	
		}
	});

	$.entwine('ss', function($) {

		$('form.htmleditorfield-form').entwine({
			onmatch: function() {
				// Move title from headline to (jQuery compatible) title attribute
				var titleEl = this.find(':header:first');
				this.attr('title', titleEl.text());
				titleEl.remove();

				// Create jQuery dialog
				this.dialog({autoOpen: false, bgiframe: true, modal: true, height: 500, width: 500, ghost: true});
			},
			redraw: function() {
			},
			toggle: function() {
				if(this.is(':visible')) this.close();
				else this.open();
			},
			close: function() {
				this.dialog('close');
			},
			open: function() {
				this.dialog('open');
				this.redraw();
			},
			getEditor: function() {
				return tinyMCE.activeEditor;
			}
		});

		$('form.htmleditorfield-linkform').entwine({

			OriginalSelection: null,

			onmatch: function() {
				this._super();

				// this.bind('submit', function() {
				// 	self.insertLink();
				// 	self.close();
				// 	return false;
				// });
			},

			open: function() {
				this.respondToNodeChange();
				this.dialog('open');
				this.redraw();
				this.setOriginalSelection(null);
			},

			close: function() {
				this._super();

				this.resetFields();
			},

			// TODO Entwine doesn't respect submits triggered by ENTER key
			onsubmit: function(e) {
				this.insertLink();
				this.close();
				return false;
			},

			resetFields: function() {
				this.find('fieldset :input:not(:radio)').val('').change();
			},

			redraw: function(setDefaults) {
				this._super();

				var linkType = this.find(':input[name=LinkType]:checked').val(), list =  ['internal', 'external', 'file', 'email'], i, item;

				// If we haven't selected an existing link, then just make sure we default to "internal" for the link type.
				if(!linkType) {
					this.find(':input[name=LinkType]').val(['internal']);
					linkType = 'internal';
				}

				this.addAnchorSelector();

				// Toggle field visibility and state based on type selection
				for(i=0;item=list[i];i++) jQuery(this.find('.field#' + item)).toggle(item == linkType);
				jQuery(this.find('.field#Anchor')).toggle(linkType == 'internal' || linkType == 'anchor');
				jQuery(this.find('.field#AnchorSelector')).toggle(linkType=='anchor');
				jQuery(this.find('.field#AnchorRefresh')).toggle(linkType=='anchor');
				this.find(':input[name=TargetBlank]').attr('disabled', (linkType == 'email'));
				if(typeof setDefaults == 'undefined' || setDefaults) {
					this.find(':input[name=TargetBlank]').attr('checked', (linkType == 'file'));
				}
			},

			insertLink: function() {
				var href, target = null, anchor = this.find(':input[name=Anchor]').val();
				
				// Determine target
				if(this.find(':input[name=TargetBlank]').is(':checked')) target = '_blank';
				
				// All other attributes
				switch(this.find(':input[name=LinkType]:checked').val()) {
					case 'internal':
						href = '[sitetree_link id=' + this.find(':input[name=internal]').val() + ']';
						if(anchor) href += '#' + anchor;
						break;

					case 'anchor':
						href = '#' + anchor; 
						break;
					
					case 'file':
						href = this.find(':input[name=file]').val();
						target = '_blank';
						break;
					
					case 'email':
						href = 'mailto:' + this.find(':input[name=email]').val();
						target = null;
						break;

					case 'external':
					default:
						href = this.find(':input[name=external]').val();
						// Prefix the URL with "http://" if no prefix is found
						if(href.indexOf('://') == -1) href = 'http://' + href;
						break;
				}

				if(this.getOriginalSelection()) {
				  tinyMCE.activeEditor.selection.setRng(this.getOriginalSelection());
				}
				
				var linkText = this.find(':input[name=LinkText]').val();
				var attributes = {
					href : href, 
					target : target, 
					title : this.find(':input[name=Description]').val(),
					innerHTML : linkText ? linkText : "Your Link"
				};

				// Add the new link
				this._insertLink(attributes);
			},

			/**
			 * Insert a link into the given editor.
			 * Replaces mceInsertLink in that innerHTML can also be set
			 */
			_insertLink: function(attributes) {
			  var ed = this.getEditor(), v = attributes,
					s = ed.selection, e = ed.dom.getParent(s.getNode(), 'A');

				if(tinymce.is(attributes, 'string')) attributes = {href : attributes};

				function set(e) {
					tinymce.each(attributes, function(v, k) {
						if(k == 'innerHTML') e.innerHTML = v;
						else ed.dom.setAttrib(e, k, v);
					});
					try {
						s.select(e);
						this.updateSelection();
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
					if (attributes.href) set(e);
					else ed.dom.remove(e, 1);
				}
				
				this.trigger('onafterinsert', attributes);
				
				this.respondToNodeChange();
			},

			removeLink: function() {
				this.getEditor().execCommand('unlink', false);
				this.close();
			},

			updateSelection: function() {
				var ed = this.getEditor();
				if(ed.selection.getRng()) this.setOriginalSelection(ed.selection.getRng());
		  },

			addAnchorSelector: function() {
				// Avoid adding twice
				if(this.find(':input[name=AnchorSelector]').length) return;

				var self = this;

				// refresh the anchor selector on click, or in case of IE - button click
				if( !tinymce.isIE ) {
					var anchorSelector = $('<select id="Form_EditorToolbarLinkForm_AnchorSelector" name="AnchorSelector"></select>');
					this.find(':input[name=Anchor]').parent().append(anchorSelector);

					anchorSelector.focus(function(e) {
						self.refreshAnchors($(this));
					});
				} else {
					var buttonRefresh = $('<a id="Form_EditorToolbarLinkForm_AnchorRefresh" title="Refresh the anchor list" alt="Refresh the anchor list" class="buttonRefresh"><span></span></a>');
					var anchorSelector = $('<select id="Form_EditorToolbarLinkForm_AnchorSelector" class="hasRefreshButton" name="AnchorSelector"></select>');
					this.find(':input[name=Anchor]').parent().append(buttonRefresh).append(anchorSelector);

					buttonRefresh.click(function(e) {
						refreshAnchors(anchorSelector);
					});
				}

				// initialization
				this.refreshAnchors();

				// copy the value from dropdown to the text field
				anchorSelector.change(function(e) {
					self.find(':input[name="Anchor"]').val($(this).val());
				});
			},

			// this function collects the anchors in the currently active editor and regenerates the dropdown
			refreshAnchors: function() {
				var selector = this.find(':input[name=AnchorSelector]'), anchors = new Array();
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
			},

			respondToNodeChange: function() {
				var htmlTagPattern = /<\S[^><]*>/g, ed = this.getEditor();
				
				var fieldName,data = this.getCurrentLink();
				
				if(data) {
					for(fieldName in data) {
						var el = this.find(':input[name=' + fieldName + ']'), selected = data[fieldName];
						// Remove html tags in the selected text that occurs on IE browsers
						if(typeof(selected) == 'string') selected = selected.replace(htmlTagPattern, ''); 
						if(el.is(':radio')) {
							el.val([selected]).change(); // setting as an arry due to jQuery quirks
						} else {
							el.val(selected).change();
						}
					}
				}
			},

		/**
		 * Return information about the currently selected link, suitable for population of the link
		 * form.
		 */
		getCurrentLink: function() {
			var ed = this.getEditor(), selectedText = ed.selection.getContent({format : 'text'}),
				selectedEl = $(ed.selection.getNode()),
				href = "", target = "", title = "", action = "insert", style_class = "";
			
			// We use a separate field for linkDataSource from tinyMCE.linkElement.
			// If we have selected beyond the range of an <a> element, then use use that <a> element to get the link data source,
			// but we don't use it as the destination for the link insertion
			var linkDataSource = null;
			if(selectedEl.length) {
				if(selectedEl.is('a')) {
					// Element is a link
					linkDataSource = selectedEl;
				// TODO Limit to inline elements, otherwise will also apply to e.g. paragraphs which already contain one or more links
				// } else if((selectedEl.find('a').length)) {
					// 	// Element contains a link
					// 	var firstLinkEl = selectedEl.find('a:first');
					// 	if(firstLinkEl.length) linkDataSource = firstLinkEl;
				} else {
					// Element is a child of a link
					linkDataSource = selectedEl = selectedEl.parents('a:first');
				}				
			}
			if(linkDataSource && linkDataSource.length) ed.selection.select(linkDataSource[0]);
			
			// Is anchor not a link
			if (!linkDataSource.attr('href')) linkDataSource = null;

			if (linkDataSource) {
				href = linkDataSource.attr('href');
				target = linkDataSource.attr('target');
				title = linkDataSource.attr('title');
				style_class = linkDataSource.attr('class');
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
		});

		$('form.htmleditorfield-linkform input[name=LinkType]').entwine({
			onclick: function(e) {
				this.parents('form:first').redraw();
			},
			onchange: function() {
				this.parents('form:first').redraw();
			}
		});

		$('form.htmleditorfield-linkform input[name=action_remove]').entwine({
			onclick: function(e) {
				this.parents('form:first').removeLink();
				return false;
			}
		});
	});
})(jQuery);