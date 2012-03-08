/**
 * Functions for HtmlEditorFields in the back end.
 * Includes the JS for the ImageUpload forms. 
 * 
 * Relies on the jquery.form.js plugin to power the 
 * ajax / iframe submissions
 */

 var ss = ss || {};
/**
 * Wrapper for HTML WYSIWYG libraries, which abstracts library internals
 * from interface concerns like inserting and editing links.
 * Caution: Incomplete and unstable API.
 */
 ss.editorWrappers = {};
 ss.editorWrappers.tinyMCE = (function() {
	return {
		/**
		 * @return Mixed Implementation specific object
		 */
		getInstance: function() {
			return tinyMCE.activeEditor;
		},
		/**
		 * Invoked when a content-modifying UI is opened.
		 */
		onopen: function() {
		},
		/**
		 * Invoked when a content-modifying UI is closed.
		 */
		onclose: function() {
		},
		/**
		 * Write the HTML back to the original text area field.
		 */
		save: function() {
			tinyMCE.triggerSave();
		},
		/**
		 * Create a new instance based on a textarea field.
		 *
		 * @param String
		 * @param Object Implementation specific configuration
		 * @param Function
		 */
		create: function(domID, config, onSuccess) {
			var ed = new tinymce.Editor(domID, config);
			ed.onInit.add(onSuccess);
			ed.render();
		},
		/**
		 * Redraw the editor contents
		 */
		repaint: function() {
			tinyMCE.execCommand("mceRepaint");
		},
		/**
		 * @return boolean
		 */
		isDirty: function() {
			return this.getInstance().isDirty();
		},
		/**
		 * HTML representation of the edited content.
		 * 
		 * Returns: {String}
		 */
		getContent: function() {
			return this.getInstance().getContent();
		},
		/**
		 * DOM tree of the edited content
		 * 
		 * Returns: DOMElement
		 */
		getDOM: function() {
			return this.getInstance().dom;
		},
		/**
		 * Returns: DOMElement
		 */
		getContainer: function() {
			return this.getInstance().getContainer();
		},
		/**
		 * Get the closest node matching the current selection.
		 * 
		 * Returns: {jQuery} DOMElement
		 */
		getSelectedNode: function() {
			return this.getInstance().selection.getNode();
		},
		/**
		 * Select the given node within the editor DOM
		 * 
		 * Parameters: {DOMElement}
		 */
		selectNode: function(node) {
			this.getInstance().selection.select(node);
		},
		/**
		 * Insert content at the current caret position
		 * 
		 * @param String HTML
		 */
		insertContent: function(html, opts) {
			this.getInstance().execCommand('mceInsertContent', false, html, opts);
		},
		/**
		 * Replace currently selected content
		 * 
		 * @param {String} html
		 */
		replaceContent: function(html, opts) {
			this.getInstance().execCommand('mceReplaceContent', false, html, opts);
		},
		/**
		 * Insert or update a link in the content area (based on current editor selection)
		 * 
		 * Parameters: {Object} attrs
		 */
		insertLink: function(attrs, opts) {
			this.getInstance().execCommand("mceInsertLink", false, attrs, opts);
		},
		/**
		 * Remove the link from the currently selected node (if any).
		 */
		removeLink: function() {
			this.getInstance().execCommand('unlink', false);
		},
		/**
		 * Strip any editor-specific notation from link in order to make it presentable in the UI.
		 * 
		 * Parameters: 
		 *  {Object} 
		 *  {DOMElement}
		 */
		cleanLink: function(href, node) {
			var cb = tinyMCE.settings['urlconverter_callback'];
			if(cb) href = eval(cb + "(href, node, true);");

			// Turn into relative
			if(href.match(new RegExp('^' + tinyMCE.settings['document_base_url'] + '(.*)$'))) {
				href = RegExp.$1;
			}
			
			// Get rid of TinyMCE's temporary URLs
			if(href.match(/^javascript:\s*mctmp/)) href = '';

			return href;
		},
		/**
		 * Creates a bookmark for the currently selected range,
		 * which can be used to reselect this range at a later point.
		 * @return {mixed}
		 */
		createBookmark: function() {
			return this.getInstance().selection.getBookmark();
		},
		/**
		 * Selects a bookmarked range previously saved through createBookmark().
		 * @param  {mixed} bookmark
		 */
		moveToBookmark: function(bookmark) {
			this.getInstance().selection.moveToBookmark(bookmark);
		},
		/**
		 * Add new undo point with the current DOM content.
		 */
		addUndo: function() {
			this.getInstance().undoManager.add();
		}
	};
});
// Override this to switch editor wrappers
ss.editorWrappers['default'] = ss.editorWrappers.tinyMCE;


(function($) {

	$.entwine('ss', function($) {

		/**
		 * Class: textarea.htmleditor
		 * 
		 * Add tinymce to HtmlEditorFields within the CMS. Works in combination
		 * with a TinyMCE.init() call which is prepopulated with the used HTMLEditorConfig settings,
		 * and included in the page as an inline <script> tag.
		 */
		$('textarea.htmleditor').entwine({

			Editor: null,
			
			/**
			 * Constructor: onmatch
			 */
			onmatch : function() {
				var self = this, ed = ss.editorWrappers['default']();
				this.setEditor(ed);
				this.closest('form').bind('beforesave', function() {
					// TinyMCE modifies input, so change tracking might get false
					// positives when comparing string values - don't save if the editor doesn't think its dirty.
					if(self.isChanged()) {
						ed.save();

						// TinyMCE assigns value attr directly, which doesn't trigger change event
						self.trigger('change');
					}
				});

				// Only works after TinyMCE.init() has been invoked, see $(window).bind() call below for details.
				this.redraw();

				this._super();
			},

			redraw: function() {
				// Using a global config (generated through HTMLEditorConfig PHP logic)
				var config = ssTinyMceConfig, self = this, ed = this.getEditor();

				// Avoid flicker (also set in CSS to apply as early as possible)
				self.css('visibility', '');

				// Create editor instance and render it.
				// Similar logic to adapter/jquery/jquery.tinymce.js, but doesn't rely on monkey-patching
				// jQuery methods, and avoids replicate the script lazyloading which is already in place with jQuery.ondemand.

				ed.create(this.attr('id'), config, function() {
					self.css('visibility', 'visible');
				});
				
				// Handle editor de-registration by hooking into state changes.
				// TODO Move to onunmatch for less coupling (once we figure out how to work with detached DOM nodes in TinyMCE)
				$('.cms-container').bind('beforestatechange', function() {
					self.css('visibility', 'hidden');
					ed.getContainer();
					if(ed) $(ed).remove();
				});

				this._super();
			},

			isChanged: function() {
				var ed = this.getEditor();
				return (ed && ed.getInstance() && ed.isDirty());
			},

			resetChanged: function() {
				var ed = this.getEditor();
				if(typeof tinyMCE == 'undefined') return;

				// TODO Abstraction layer
				var inst = tinyMCE.getInstanceById(this.attr('id'));
				if (inst) inst.startContent = tinymce.trim(inst.getContent({format : 'raw', no_events : 1}));
			},

			onunmatch: function() {
				// TODO Throws exceptions in Firefox, most likely due to the element being removed from the DOM at this point
				// var ed = tinyMCE.get(this.attr('id'));
				// if(ed) ed.remove();

				this._super();
			}
		});

		/**
		 * Base form implementation for interactions with an editor instance,
		 * mostly geared towards modification and insertion of content.
		 */
		$('form.htmleditorfield-form').entwine({

			// Wrapper for various HTML editors
			Editor: null,

			// TODO Figure out how to keep bookmark reference in entwine, and still be allowed to delete the JS object
			// Bookmark: null,

			onmatch: function() {
				// Move title from headline to (jQuery compatible) title attribute
				var titleEl = this.find(':header:first');
				this.attr('title', titleEl.text());
				titleEl.remove();

				// Create jQuery dialog
				this.dialog({autoOpen: false, bgiframe: true, modal: true, height: 500, width: '80%', ghost: true});

				this.setEditor(ss.editorWrappers['default']());
			},
			redraw: function() {
			},
			toggle: function() {
				if(this.is(':visible')) this.close();
				else this.open();
			},
			close: function() {
				this.dialog('close');
				this.getEditor().onclose();
				if(typeof window._ss_htmleditorfield_bookmark != 'undefined') window._ss_htmleditorfield_bookmark = null;
			},
			open: function() {
				this.updateFromEditor();
				this.dialog('open');
				this.redraw();
				this.getEditor().onopen();
				window._ss_htmleditorfield_bookmark = this.getEditor().createBookmark();
			},
			/**
			 * Update the view state based on the current editor selection.
			 */
			updateFromEditor: function() {
			}
		});

		/**
		 * Inserts and edits links in an html editor, including internal/external web links,
		 * links to files on the webserver, email addresses, and anchors in the existing html content.
		 * Every variation has its own fields (e.g. a "target" attribute doesn't make sense for an email link),
		 * which are toggled through a type dropdown. Variations share fields, so there's only one "title" field in the form.
		 */
		$('form.htmleditorfield-linkform').entwine({

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
				for(i=0;item==list[i];i++) jQuery(this.find('.field#' + item)).toggle(item == linkType);
				jQuery(this.find('.field#Anchor')).toggle(linkType == 'internal' || linkType == 'anchor');
				jQuery(this.find('.field#AnchorSelector')).toggle(linkType=='anchor');
				jQuery(this.find('.field#AnchorRefresh')).toggle(linkType=='anchor');
				this.find(':input[name=TargetBlank]').attr('disabled', (linkType == 'email'));
				if(typeof setDefaults == 'undefined' || setDefaults) {
					this.find(':input[name=TargetBlank]').attr('checked', (linkType == 'file'));
				}
			},

			insertLink: function() {
				var href, target = null, anchor = this.find(':input[name=Anchor]').val(), ed = this.getEditor();
				
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
						href = '[file_link id=' + this.find(':input[name=file]').val() + ']';
						target = '_blank';
						break;
					
					case 'email':
						href = 'mailto:' + this.find(':input[name=email]').val();
						target = null;
						break;

					// case 'external':
					default:
						href = this.find(':input[name=external]').val();
						// Prefix the URL with "http://" if no prefix is found
						if(href.indexOf('://') == -1) href = 'http://' + href;
						break;
				}

				var attributes = {
					href : href, 
					target : target, 
					title : this.find(':input[name=Description]').val()
				};

				// Workaround for browsers losing focus, similar to tinyMCEPopup.restoreSelection
				ed.moveToBookmark(window._ss_htmleditorfield_bookmark);
				window._ss_htmleditorfield_bookmark = null;

				// Add the new link
				ed.insertLink(attributes);
				this.trigger('onafterinsert', attributes);
				this.updateFromEditor();
			},

			removeLink: function() {
				this.getEditor().removeLink();
				this.close();
			},

			addAnchorSelector: function() {
				// Avoid adding twice
				if(this.find(':input[name=AnchorSelector]').length) return;

				var self = this, anchorSelector;

				// refresh the anchor selector on click, or in case of IE - button click
				if( !$.browser.ie ) {
					anchorSelector = $('<select id="Form_EditorToolbarLinkForm_AnchorSelector" name="AnchorSelector"></select>');
					this.find(':input[name=Anchor]').parent().append(anchorSelector);

					anchorSelector.focus(function(e) {
						self.refreshAnchors($(this));
					});
				} else {
					var buttonRefresh = $('<a id="Form_EditorToolbarLinkForm_AnchorRefresh" title="Refresh the anchor list" alt="Refresh the anchor list" class="buttonRefresh"><span></span></a>');
					anchorSelector = $('<select id="Form_EditorToolbarLinkForm_AnchorSelector" class="hasRefreshButton" name="AnchorSelector"></select>');
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
				var selector = this.find(':input[name=AnchorSelector]'), anchors = [];
				// name attribute is defined as CDATA, should accept all characters and entities
				// http://www.w3.org/TR/1999/REC-html401-19991224/struct/links.html#h-12.2
				var raw = this.getEditor().getContent().match(/name="([^"]+?)"|name='([^']+?)'/gim);
				if (raw && raw.length) {
					for(var i = 0; i < raw.length; i++) {
						anchors.push(raw[i].substr(6).replace(/"$/, ''));
					}
				}

				selector.empty();
				selector.append($('<option value="" selected="1">Select an anchor</option>'));
				for (var j = 0; j < anchors.length; j++) {
					selector.append($('<option value="'+anchors[j]+'">'+anchors[j]+'</option>'));
				}
			},

			updateFromEditor: function() {
				var htmlTagPattern = /<\S[^><]*>/g, fieldName, data = this.getCurrentLink();
				
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
			var ed = this.getEditor(), selectedEl = $(ed.getSelectedNode()),
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
			if(linkDataSource && linkDataSource.length) ed.selectNode(linkDataSource[0]);
			
			// Is anchor not a link
			if (!linkDataSource.attr('href')) linkDataSource = null;

			if (linkDataSource) {
				href = linkDataSource.attr('href');
				target = linkDataSource.attr('target');
				title = linkDataSource.attr('title');
				style_class = linkDataSource.attr('class');
				href = ed.cleanLink(href, linkDataSource);
				action = "update";
			}
			
			if(href.match(/^mailto:(.*)$/)) {
				return {
					LinkType: 'email',
					email: RegExp.$1,
					Description: title
				};
			} else if(href.match(/^(assets\/.*)$/) || href.match(/^\[file_link\s*(?:%20)?id=([0-9]+)\]?(#.*)?$/)) {
				return {
					LinkType: 'file',
					file: RegExp.$1,
					Description: title
				};
			} else if(href.match(/^#(.*)$/)) {
				return {
					LinkType: 'anchor',
					Anchor: RegExp.$1,
					Description: title,
					TargetBlank: target ? true : false
				};
			} else if(href.match(/^\[sitetree_link\s*(?:%20)?id=([0-9]+)\]?(#.*)?$/)) {
				return {
					LinkType: 'internal',
					internal: RegExp.$1,
					Anchor: RegExp.$2 ? RegExp.$2.substr(1) : '',
					Description: title,
					TargetBlank: target ? true : false
				};
			} else if(href) {
				return {
					LinkType: 'external',
					external: href,
					Description: title,
					TargetBlank: target ? true : false
				};
			} else {
				return {
					LinkType: 'internal'
				};
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

		$('form.htmleditorfield-linkform :submit[name=action_remove]').entwine({
			onclick: function(e) {
				this.parents('form:first').removeLink();
				return false;
			}
		});

		/**
		 * Responsible for inserting media files, although only images are supported so far.
		 * Allows to select one or more files, and load form fields for each file via ajax.
		 * This allows us to tailor the form fields to the file type (e.g. different ones for images and flash),
		 * as well as add new form fields via framework extensions.
		 * The inputs on each of those files are used for constructing the HTML to insert into
		 * the rich text editor. Also allows editing the properties of existing files if any are selected in the editor.
		 * Note: Not each file has a representation on the webserver filesystem, supports insertion and editing
		 * of remove files as well.
		 */
		$('form.htmleditorfield-mediaform').entwine({
			onsubmit: function() {
				var self = this, ed = this.getEditor();

				// HACK: See ondialogopen()
				// jQuery(ed.getContainer()).show();


				this.find('.ss-htmleditorfield-file').each(function(el) {
					$(this).insertHTML();
				});
				ed.repaint();
				this.close();

				return false;
			},
			ondialogopen: function() {
				this.redraw();

				var self = this, ed = this.getEditor(), node = $(ed.getSelectedNode());
				// TODO Depends on managed mime type
				if(node.is('img')) {
					this.showFileView(node.attr('src'), function() {
						$(this).updateFromNode(node);
						self.redraw();
					});
				}

				this.redraw();

				// HACK: Hide selected node in IE because its drag handles on potentially selected elements
				// don't respect the z-index of the dialog overlay.
				// jQuery(ed.getContainer()).hide();
			},
			ondialogclose: function() {
				var ed = this.getEditor(), node = $(ed.getSelectedNode());

				// HACK: See ondialogopen()
				// jQuery(ed.getContainer()).show();

				this.find('.ss-htmleditorfield-file').remove(); // Remove any existing views
				this.find('.ss-gridfield-items .ui-selected').removeClass('ui-selected'); // Unselect all items
				this.redraw();
			},
			redraw: function() {
				this._super();

				var ed = this.getEditor(), node = $(ed.getSelectedNode()),
					hasItems = Boolean(this.find('.ss-htmleditorfield-file').length),
					editingSelected = node.is('img');

				// Only show second step if files are selected
				this.find('.header-edit')[(hasItems) ? 'show' : 'hide']();

				// Disable "insert" button if no files are selected
				this.find('.Actions :submit')
					.button(hasItems ? 'enable' : 'disable')
					.toggleClass('ui-state-disabled', !hasItems);

				// Hide file selection and step labels when editing an existing file
				this.find('.header-select,.content-select,.header-edit')[editingSelected ? 'hide' : 'show']();
			},
			getFileView: function(idOrUrl) {
				return this.find('.ss-htmleditorfield-file[data-id=' + idOrUrl + ']');
			},
			showFileView: function(idOrUrl, successCallback) {
				var self = this, params = (Number(idOrUrl) == idOrUrl) ? '?ID=' + idOrUrl : '?FileURL=' + idOrUrl,
					item = $('<div class="ss-htmleditorfield-file" />');

				item.addClass('loading');
				this.find('.content-edit').append(item);
				$.ajax({
					// url: this.data('urlViewfile') + '?ID=' + id,
					url: this.attr('action').replace(/MediaForm/, 'viewfile') + params,
					success: function(html, status, xhr) {
						var newItem = $(html);
						item.replaceWith(newItem);
						self.redraw();
						if(successCallback) successCallback.call(newItem, html, status, xhr);
					},
					error: function() {
						item.remove();
					}
				});
			}
		});

		$('form.htmleditorfield-mediaform .ss-gridfield-items').entwine({
			onselectableselected: function(e, ui) {
				var form = this.closest('form'), item = $(ui.selected);
				if(!item.is('.ss-gridfield-item')) return;
				form.closest('form').showFileView(item.data('id'));
				form.redraw();
			},
			onselectableunselected: function(e, ui) {
				var form = this.closest('form'), item = $(ui.unselected);
				if(!item.is('.ss-gridfield-item')) return;
				form.getFileView(item.data('id')).remove();
				form.redraw();
			}
		});

		/**
		 * Represents a single selected file, together with a set of form fields to edit its properties.
		 * Overload this based on the media type to determine how the HTML should be created.
		 */
		$('form.htmleditorfield-mediaform .ss-htmleditorfield-file').entwine({
			/**
			 * @return {Object} Map of HTML attributes which can be set on the created DOM node.
			 */
			getAttributes: function() {
			},
			/**
			 * @return {Object} Map of additional properties which can be evaluated
			 * by the specific media type.
			 */
			getExtraData: function() {
			},
			/**
			 * @return {String} HTML suitable for insertion into the rich text editor
			 */
			getHTML: function() {
			},
			/**
			 * Insert updated HTML content into the rich text editor
			 */
			insertHTML: function() {
				var form = this.closest('form'), ed = form.getEditor();

				// Workaround for browsers losing focus, similar to tinyMCEPopup.restoreSelection
				ed.moveToBookmark(window._ss_htmleditorfield_bookmark);
				window._ss_htmleditorfield_bookmark = null;

				// Insert content
				ed.replaceContent(this.getHTML());
			},
			/**
			 * Updates the form values from an existing node in the editor.
			 * 
			 * @param {DOMElement}
			 */
			updateFromNode: function(node) {
			},
			/**
			 * Transforms values set on the dimensions form fields based on two constraints:
			 * An aspect ration, and max width/height values. Writes back to the field properties as required.
			 * 
			 * @param {String} The dimension to constrain the other value by, if any ("Width" or "Height")
			 * @param {Int} Optional max width
			 * @param {Int} Optional max height
			 */
			updateDimensions: function(constrainBy, maxW, maxH) {
				var widthEl = this.find(':input[name=Width]'),
					heightEl = this.find(':input[name=Height]'),
					w = widthEl.val(),
					h = heightEl.val(),
					aspect;

				// Proportionate updating of heights, using the original values
				if(w && h) {
					if(constrainBy) {
						aspect = heightEl.getOrigVal() / widthEl.getOrigVal();
						// Uses floor() and ceil() to avoid both fields constantly lowering each other's values in rounding situations
						if(constrainBy == 'Width') {
							if(maxW && w > maxW) w = maxW;
							h = Math.floor(w * aspect);
						} else if(constrainBy == 'Height') {
							if(maxH && h > maxH) h = maxH;
							w = Math.ceil(h / aspect);
						}
					} else {
						if(maxW && w > maxW) w = maxW;
						if(maxH && h > maxH) h = maxH;
					}

					widthEl.val(w);
					heightEl.val(h);
				}
			}
		});

		$('form.htmleditorfield-mediaform .ss-htmleditorfield-file.image').entwine({
			getAttributes: function() {
				var width = this.find(':input[name=Width]').val(),
					height = this.find(':input[name=Height]').val();
				return {
					'src' : this.find(':input[name=URL]').val(),
					'alt' : this.find(':input[name=AltText]').val(),
					'width' : width ? parseInt(width, 10) + "px" : null,
					'height' : height ? parseInt(height, 10) + "px" : null,
					'title' : this.find(':input[name=Title]').val(),
					'class' : this.find(':input[name=CSSClass]').val()
				};
			},
			getExtraData: function() {
				return {
					'CaptionText': this.find(':input[name=CaptionText]').val()
				};
			},
			getHTML: function() {
				var el,
					attrs = this.getAttributes(),
					extraData = this.getExtraData(),
					// imgEl = $('<img id="_ss_tmp_img" />');
					imgEl = $('<img />').attr(attrs);
				
				if(extraData.CaptionText) {
					el = $('<div style="width: ' + attrs['width'] + 'px;" class="captionImage ' + attrs['class'] + '"><p class="caption">' + extraData.CaptionText + '</p></div>').prepend(imgEl);
				} else {
					el = imgEl;
				}
				return $('<div />').append(el).html(); // Little hack to get outerHTML string
			},
			/**
			 * Logic similar to TinyMCE 'advimage' plugin, insertAndClose() method.
			 */
			insertHTML: function() {
				var form = this.closest('form'), ed = form.getEditor(), 
					node = $(ed.getSelectedNode()), captionNode = node.closest('.captionImage');

				// Workaround for browsers losing focus, similar to tinyMCEPopup.restoreSelection.
				// TODO In TinyMCE core this is restricted to IE, but leaving it our also
				// breaks Firefox: It doesn't save the selection because it inserts into a temporary TinyMCE
				// marker element rather than the content DOM nodes
				ed.moveToBookmark(window._ss_htmleditorfield_bookmark);
				window._ss_htmleditorfield_bookmark = null;

				if(node && node.is('img')) {
					// If the image exists, update it to avoid complications with inserting TinyMCE HTML content
					var attrs = this.getAttributes(), extraData = this.getExtraData();
					node.attr(attrs);
					// TODO Doesn't allow adding a caption to image after it was first added
					if(captionNode.length) {
						captionNode.find('.caption').text(extraData.CaptionText);
						captionNode.css({width: attrs.width, height: attrs.height}).attr('class', attrs['class']);
					}
					// Undo needs to be added manually as we're doing direct DOM changes
					ed.addUndo();
				} else {
					// Otherwise insert the whole HTML content
					ed.repaint();
					ed.insertContent(this.getHTML(), {skip_undo : 1});	
					ed.addUndo(); // Not sure why undo is separate here, replicating TinyMCE logic
				}

				ed.repaint();
			},
			updateFromNode: function(node) {
				this.find(':input[name=AltText]').val(node.attr('alt'));
				this.find(':input[name=Title]').val(node.attr('title'));
				this.find(':input[name=CSSClass]').val(node.attr('class')).attr('disabled', 'disabled');
				this.find(':input[name=Width]').val(node.width());
				this.find(':input[name=Height]').val(node.height());
				this.find(':input[name=CaptionText]').val(node.siblings('.caption:first').text());
			}
		});


		/**
		 * Insert a flash object tag into the content.
		 * Requires the 'media' plugin for serialization of tags into <img> placeholders.
		 */
		$('form.htmleditorfield-mediaform .ss-htmleditorfield-file.flash').entwine({
			getAttributes: function() {
				var width = this.find(':input[name=Width]').val(),
					height = this.find(':input[name=Height]').val();
				return {
					'src' : this.find(':input[name=URL]').val(),
					'width' : width ? parseInt(width, 10) : null,
					'height' : height ? parseInt(height, 10) : null
				};
			},
			getHTML: function() {
				var attrs = this.getAttributes();

				// Emulate serialization from 'media' plugin
				var el = tinyMCE.activeEditor.plugins.media.dataToImg({
					'type': 'flash',
					'width': attrs.width,
					'height': attrs.height,
					'params': {'src': attrs.src},
					'video': {'sources': []}
				});
				
				return $('<div />').append(el).html(); // Little hack to get outerHTML string
			},
			updateFromNode: function(node) {
				// TODO Not implemented
			}
		});

		$('form.htmleditorfield-mediaform .ss-htmleditorfield-file .dimensions :input').entwine({
			OrigVal: null,
			onmatch: function () {
				this._super();

				this.setOrigVal(parseInt(this.val(), 10));

				// Default to a managable size for the HTML view. Can be overwritten by user after initialization
				if(this.attr('name') == 'Width') this.closest('.ss-htmleditorfield-file').updateDimensions('Width', 600);

			},
			onfocusout: function(e) {
				this.closest('.ss-htmleditorfield-file').updateDimensions(this.attr('name'));
			}
		});

		/**
		 * Deselect item and remove the 'edit' view
		 */
		$('form.htmleditorfield-mediaform .ss-htmleditorfield-file .action-delete').entwine({
			onclick: function(e) {
				var form = this.closest('form'), file = this.closest('.ss-htmleditorfield-file');
				form.find('.ss-gridfield-item[data-id=' + file.data('id') + ']').removeClass('ui-selected');
				this.closest('.ss-htmleditorfield-file').remove();
				form.redraw();
				e.preventDefault();
			}
		});

		$('form.htmleditorfield-mediaform #ParentID .TreeDropdownField').entwine({
			onmatch: function() {
				this._super();

				// TODO Custom event doesn't fire in IE if registered through object literal
				var self = this;
				this.bind('change', function() {
					var fileList = self.closest('form').find('.ss-gridfield');
					fileList.setState('ParentID', self.getValue());
					fileList.reload();
				});
			}
		});
		
	});
})(jQuery);


/**
 * These callback globals hook it into tinymce.  They need to be referenced in the TinyMCE config.
 */
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
		jQuery(value).find('img').each(function() {
			this.onresizestart = null;
			this.onresizeend = null;
			this.removeAttribute('onresizestart');
			this.removeAttribute('onresizeend');
		});
	}

	return value;
}
