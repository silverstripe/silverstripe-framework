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
	
	var instance;

	return {
		init: function(config) {
			if(!ss.editorWrappers.tinyMCE.initialized) {
				tinyMCE.init(config);

				ss.editorWrappers.tinyMCE.initialized = true;
			}
		},
		/**
		 * @return Mixed Implementation specific object
		 */
		getInstance: function() {
			return this.instance;
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
		 * Please proxy the events from your editor implementation into JS events
		 * on the textarea field. For events that do not map directly, use the 
		 * following naming scheme: editor<event>.
		 *
		 * @param String
		 * @param Object Implementation specific configuration
		 * @param Function
		 */
		create: function(domID, config) {
			this.instance = new tinymce.Editor(domID, config);

			// Patch TinyMCE events into underlying textarea field.
			this.instance.onInit.add(function(ed) {
				if(!ss.editorWrappers.tinyMCE.patched) {
					// Not ideal, but there's a memory leak we need to patch
					var originalDestroy = tinymce.themes.AdvancedTheme.prototype.destroy;

					tinymce.themes.AdvancedTheme.prototype.destroy = function() {
						originalDestroy.apply(this, arguments);

						if (this.statusKeyboardNavigation) {
							this.statusKeyboardNavigation.destroy();
							this.statusKeyboardNavigation = null;
						}
					}

					ss.editorWrappers.tinyMCE.patched = true;
				}

				jQuery(ed.getElement()).trigger('editorinit');

				// Periodically check for inline changes when focused,
				// since TinyMCE's onChange only fires on certain actions
				// like inserting a new paragraph, as opposed to any user input.
				// This also works around an issue where the "save" button
				// wouldn't trigger if the click is the cause of a "blur" event
				// after an (undetected) inline change. This "blur" causes onChange
				// to trigger, which will change the button markup to show "alternative" styles,
				// effectively cancelling the original click event.
				var interval;
				jQuery(ed.getBody()).on('focus', function() {
					interval = setInterval(function() {
						ed.save();
					}, 5000);
				});
				jQuery(ed.getBody()).on('blur', function() {
					clearInterval(interval);
				});
			});
			this.instance.onChange.add(function(ed, l) {
				// Update underlying textarea on every change, so external handlers
				// such as changetracker have a chance to trigger properly.
				ed.save();
				jQuery(ed.getElement()).trigger('change');
			});
			// Add more events here as needed.

			this.instance.render();
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
		 * Replace entire content
		 * 
		 * @param String HTML
		 * @param Object opts
		 */
		setContent: function(html, opts) {
			this.getInstance().execCommand('mceSetContent', false, html, opts);
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
			this.getInstance().focus();
		},
		/**
		 * Removes any selection & de-focuses this editor
		 */
		blur: function() {
			this.getInstance().selection.collapse();
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
			onadd: function() {
				var edClass = this.data('editor') || 'default', ed = ss.editorWrappers[edClass]();
				this.setEditor(ed);

				// Using a global config (generated through HTMLEditorConfig PHP logic).
				// Depending on browser cache load behaviour, entwine's DOMMaybeChanged
				// can be called before the bottom-most inline script tag is executed,
				// which defines the global. If that's the case, wait for the window load.
				if(typeof ssTinyMceConfig != 'undefined') this.redraw();

				this._super();
			},
			onremove: function() {
				var ed = tinyMCE.get(this.attr('id'));
				if (ed) {
					ed.remove();
					ed.destroy();

					// TinyMCE leaves behind events. We should really fix TinyMCE, but lets brute force it for now
					$.each(jQuery.cache, function(){
						var source = this.handle && this.handle.elem;
						if (!source) return;

						var parent = source;
						while (parent && parent.nodeType == 1) parent = parent.parentNode;

						if (!parent) $(source).unbind().remove();
					});
				}

				this._super();
			},

			getContainingForm: function(){
				return this.closest('form');
			},

			fromWindow: {
				onload: function(){
					this.redraw();
				}
			},

			redraw: function() {
				// Using a global config (generated through HTMLEditorConfig PHP logic)
				var config = ssTinyMceConfig, self = this, ed = this.getEditor();

				ed.init(config);

				// Create editor instance and render it.
				// Similar logic to adapter/jquery/jquery.tinymce.js, but doesn't rely on monkey-patching
				// jQuery methods, and avoids replicate the script lazyloading which is already in place with jQuery.ondemand.
				ed.create(this.attr('id'), config);

				this._super();
			},

			/**
			 * Make sure the editor has flushed all it's buffers before the form is submitted.
			 */
			'from .cms-edit-form': {
				onbeforesubmitform: function(e) {
					this.getEditor().save();
					this._super();
				}
			},

			oneditorinit: function() {
				// Delayed show because TinyMCE calls hide() via setTimeout on removing an element,
				// which is called in quick succession with adding a new editor after ajax loading new markup

				//storing the container object before setting timeout
				var redrawObj = $(this.getEditor().getInstance().getContainer());
				setTimeout(function() {
					redrawObj.show();
				}, 10);
			},

			'from .cms-container': {
				onbeforestatechange: function(){
					this.css('visibility', 'hidden');

					var ed = this.getEditor(), container = (ed && ed.getInstance()) ? ed.getContainer() : null;
					if(container && container.length) container.remove();
				}
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
			openLinkDialog: function() {
				this.openDialog('link');
			},
			openMediaDialog: function() {
				this.openDialog('media');
			},
			openDialog: function(type) {
				var capitalize = function(text) {
					return text.charAt(0).toUpperCase() + text.slice(1).toLowerCase();
				};

				var self = this, url = $('#cms-editor-dialogs').data('url' + capitalize(type) + 'form'),
					dialog = $('.htmleditorfield-' + type + 'dialog');

				if(dialog.length) {
					dialog.getForm().setElement(this);
					dialog.open();
				} else {
					// Show a placeholder for instant feedback. Will be replaced with actual
					// form dialog once its loaded.
					dialog = $('<div class="htmleditorfield-dialog htmleditorfield-' + type + 'dialog loading">');
					$('body').append(dialog);
					$.ajax({
						url: url,
						complete: function() {
							dialog.removeClass('loading');
						},
						success: function(html) {
							dialog.html(html);
							dialog.getForm().setElement(self);
							dialog.trigger('ssdialogopen');
						}
					});
				}
			}
		});

		$('.htmleditorfield-dialog').entwine({
			onadd: function() {
				// Create jQuery dialog
				if (!this.is('.ui-dialog-content')) {
					this.ssdialog({autoOpen: true});
				}

				this._super();
			},

			getForm: function() {
				return this.find('form');
			},
			open: function() {
				this.ssdialog('open');
			},
			close: function() {
				this.ssdialog('close');
			},
			toggle: function(bool) {
				if(this.is(':visible')) this.close();
				else this.open();
			}
		});

		/**
		 * Base form implementation for interactions with an editor instance,
		 * mostly geared towards modification and insertion of content.
		 */
		$('form.htmleditorfield-form').entwine({
			Selection: null,

			// Implementation-dependent serialization of the current editor selection state
			Bookmark: null,
			
			// DOMElement pointing to the currently active textarea
			Element: null,

			setSelection: function(node) {
				return this._super($(node));
			},

			onadd: function() {
				// Move title from headline to (jQuery compatible) title attribute
				var titleEl = this.find(':header:first');
				this.getDialog().attr('title', titleEl.text());

				this._super();
			},
			onremove: function() {
				this.setSelection(null);
				this.setBookmark(null);
				this.setElement(null);

				this._super();
			},

			getDialog: function() {
				// TODO Refactor to listen to form events to remove two-way coupling
				return this.closest('.htmleditorfield-dialog');
			},

			fromDialog: {
				onssdialogopen: function(){
					var ed = this.getEditor();
					ed.onopen();

					this.setSelection(ed.getSelectedNode());
					this.setBookmark(ed.createBookmark());

					ed.blur();

					this.find(':input:not(:submit)[data-skip-autofocus!="true"]').filter(':visible:enabled').eq(0).focus();

					this.updateFromEditor();
					this.redraw();
				},

				onssdialogclose: function(){
					var ed = this.getEditor();
					ed.onclose();

					ed.moveToBookmark(this.getBookmark());

					this.setSelection(null);
					this.setBookmark(null);

					this.resetFields();
				}
			},

			/**
			 * @return Object ss.editorWrapper instance
			 */
			getEditor: function(){
				return this.getElement().getEditor();
			},

			modifySelection: function(callback) {
				var ed = this.getEditor();

				ed.moveToBookmark(this.getBookmark());
				callback.call(this, ed);

				this.setSelection(ed.getSelectedNode());
				this.setBookmark(ed.createBookmark());

				ed.blur();
			},

			updateFromEditor: function() {
				/* NOP */
			},
			redraw: function() {
				/* NOP */
			},
			resetFields: function() {
				// Flush the tree drop down fields, as their content might get changed in other parts of the CMS, ie in Files and images
				this.find('.tree-holder').empty();
			}
		});

		/**
		 * Inserts and edits links in an html editor, including internal/external web links,
		 * links to files on the webserver, email addresses, and anchors in the existing html content.
		 * Every variation has its own fields (e.g. a "target" attribute doesn't make sense for an email link),
		 * which are toggled through a type dropdown. Variations share fields, so there's only one "title" field in the form.
		 */
		$('form.htmleditorfield-linkform').entwine({
			// TODO Entwine doesn't respect submits triggered by ENTER key
			onsubmit: function(e) {
				this.insertLink();
				this.getDialog().close();
				return false;
			},
			resetFields: function() {
				this._super();

				// Reset the form using a native call. This will also correctly reset checkboxes and radio buttons.
				this[0].reset();
			},
			redraw: function() {
				this._super();

				var linkType = this.find(':input[name=LinkType]:checked').val(), list = ['internal', 'external', 'file', 'email'];

				this.addAnchorSelector();

				// Toggle field visibility depending on the link type.
				this.find('div.content .field').hide();
				this.find('.field#LinkType').show();
				this.find('.field#' + linkType).show();
				if(linkType == 'internal' || linkType == 'anchor') this.find('.field#Anchor').show();
				if(linkType !== 'email') this.find('.field#TargetBlank').show();
				if(linkType == 'anchor') {
					this.find('.field#AnchorSelector').show();
					this.find('.field#AnchorRefresh').show();
				}
				this.find('.field#Description').show();
			},
			/**
			 * @return Object Keys: 'href', 'target', 'title'
			 */
			getLinkAttributes: function() {
				var href, target = null, anchor = this.find(':input[name=Anchor]').val();
				
				// Determine target
				if(this.find(':input[name=TargetBlank]').is(':checked')) target = '_blank';
				
				// All other attributes
				switch(this.find(':input[name=LinkType]:checked').val()) {
					case 'internal':
						href = '[sitetree_link,id=' + this.find(':input[name=internal]').val() + ']';
						if(anchor) href += '#' + anchor;
						break;

					case 'anchor':
						href = '#' + anchor; 
						break;
					
					case 'file':
						href = '[file_link,id=' + this.find(':input[name=file]').val() + ']';
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

				return {
					href : href, 
					target : target, 
					title : this.find(':input[name=Description]').val()
				};
			},
			insertLink: function() {
				this.modifySelection(function(ed){
					ed.insertLink(this.getLinkAttributes());
				});
				this.updateFromEditor();
			},
			removeLink: function() {
				this.modifySelection(function(ed){
					ed.removeLink();
				});
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
						self.refreshAnchors();
					});
				} else {
					var buttonRefresh = $('<a id="Form_EditorToolbarLinkForm_AnchorRefresh" title="Refresh the anchor list" alt="Refresh the anchor list" class="buttonRefresh"><span></span></a>');
					anchorSelector = $('<select id="Form_EditorToolbarLinkForm_AnchorSelector" class="hasRefreshButton" name="AnchorSelector"></select>');
					this.find(':input[name=Anchor]').parent().append(buttonRefresh).append(anchorSelector);

					buttonRefresh.click(function(e) {
						self.refreshAnchors();
					});
				}

				// initialization
				self.refreshAnchors();

				// copy the value from dropdown to the text field
				anchorSelector.change(function(e) {
					self.find(':input[name="Anchor"]').val($(this).val());
				});
			},
			// this function collects the anchors in the currently active editor and regenerates the dropdown
			refreshAnchors: function() {
				var selector = this.find(':input[name=AnchorSelector]'), anchors = [], ed = this.getEditor();
				// name attribute is defined as CDATA, should accept all characters and entities
				// http://www.w3.org/TR/1999/REC-html401-19991224/struct/links.html#h-12.2

				if(ed) {
					var raw = ed.getContent().match(/name="([^"]+?)"|name='([^']+?)'/gim);
					if (raw && raw.length) {
						for(var i = 0; i < raw.length; i++) {
							anchors.push(raw[i].substr(6).replace(/"$/, ''));
						}
					}
				}

				selector.empty();
				selector.append($(
					'<option value="" selected="1">' +
					ss.i18n._t('HtmlEditorField.SelectAnchor') +
					'</option>'
				));
				for (var j = 0; j < anchors.length; j++) {
					selector.append($('<option value="'+anchors[j]+'">'+anchors[j]+'</option>'));
				}
			},
			/**
			 * Updates the state of the dialog inputs to match the editor selection.
			 * If selection does not contain a link, resets the fields.
			 */
			updateFromEditor: function() {
				var htmlTagPattern = /<\S[^><]*>/g, fieldName, data = this.getCurrentLink();

				if(data) {
					for(fieldName in data) {
						var el = this.find(':input[name=' + fieldName + ']'), selected = data[fieldName];
						// Remove html tags in the selected text that occurs on IE browsers
						if(typeof(selected) == 'string') selected = selected.replace(htmlTagPattern, ''); 

						// Set values and invoke the triggers (e.g. for TreeDropdownField).
						if(el.is(':checkbox')) {
							el.prop('checked', selected).change();
						} else if(el.is(':radio')) {
							el.val([selected]).change();
						} else {
							el.val(selected).change();
						}
					}
				}
			},
		/**
		 * Return information about the currently selected link, suitable for population of the link form.
		 *
		 * Returns null if no link was currently selected.
		 */
		getCurrentLink: function() {
			var selectedEl = this.getSelection(),
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
			if(linkDataSource && linkDataSource.length) this.modifySelection(function(ed){
				ed.selectNode(linkDataSource[0]);
			});
			
			// Is anchor not a link
			if (!linkDataSource.attr('href')) linkDataSource = null;

			if (linkDataSource) {
				href = linkDataSource.attr('href');
				target = linkDataSource.attr('target');
				title = linkDataSource.attr('title');
				style_class = linkDataSource.attr('class');
				href = this.getEditor().cleanLink(href, linkDataSource);
				action = "update";
			}
			
			if(href.match(/^mailto:(.*)$/)) {
				return {
					LinkType: 'email',
					email: RegExp.$1,
					Description: title
				};
			} else if(href.match(/^(assets\/.*)$/) || href.match(/^\[file_link\s*(?:\s*|%20|,)?id=([0-9]+)\]?(#.*)?$/)) {
				return {
					LinkType: 'file',
					file: RegExp.$1,
					Description: title,
					TargetBlank: target ? true : false
				};
			} else if(href.match(/^#(.*)$/)) {
				return {
					LinkType: 'anchor',
					Anchor: RegExp.$1,
					Description: title,
					TargetBlank: target ? true : false
				};
			} else if(href.match(/^\[sitetree_link(?:\s*|%20|,)?id=([0-9]+)\]?(#.*)?$/i)) {
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
				// No link/invalid link selected.
				return null;
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
			toggleCloseButton: function(){
				var updateExisting = Boolean(this.find('.ss-htmleditorfield-file').length);
				this.find('.overview .action-delete')[updateExisting ? 'hide' : 'show']();
			},
			onsubmit: function() {				
				this.modifySelection(function(ed){
					this.find('.ss-htmleditorfield-file').each(function() {
						$(this).insertHTML(ed);
					});

					ed.repaint();
				});

				this.getDialog().close();
				return false;
			},
			updateFromEditor: function() {			
				var self = this, node = this.getSelection();

				// TODO Depends on managed mime type
				if(node.is('img')) {
					this.showFileView(node.data('url') || node.attr('src')).done(function(filefield) {
						filefield.updateFromNode(node);
						self.toggleCloseButton();
						self.redraw();
					});
				}
				this.redraw();
			},
			redraw: function(updateExisting) {
				this._super();
			
				var node = this.getSelection(),
					hasItems = Boolean(this.find('.ss-htmleditorfield-file').length),
					editingSelected = node.is('img'),
					header = this.find('.header-edit');

				// Only show second step if files are selected
				header[(hasItems) ? 'show' : 'hide']();

				// Disable "insert" button if no files are selected
				this.find('.Actions :submit')
					.button(hasItems ? 'enable' : 'disable')
					.toggleClass('ui-state-disabled', !hasItems); 
					
				// Hide file selection and step labels when editing an existing file
				this.find('#MediaFormInsertMediaTabs,.header-edit')[editingSelected ? 'hide' : 'show']();

				// TODO Way too much knowledge on UploadField internals, use viewfile URL directly instead
				this.find('.htmleditorfield-mediaform-heading.insert')[editingSelected ? 'hide' : 'show']();
				this.find('.ss-uploadfield-item-actions')[editingSelected ? 'hide' : 'show']();
				this.find('.ss-uploadfield-item-name')[editingSelected ? 'hide' : 'show']();
				this.find('.ss-uploadfield-item-preview')[editingSelected ? 'hide' : 'show']();
				this.find('.Actions .media-insert')[editingSelected ? 'hide' : 'show']();
				this.find('.htmleditorfield-mediaform-heading.update')[editingSelected ? 'show' : 'hide']();
				this.find('.Actions .media-update')[editingSelected ? 'show' : 'hide']();
				this.find('.ss-uploadfield-item-editform').toggleEditForm(editingSelected);
			},
			resetFields: function() {				
				this.find('.ss-htmleditorfield-file').remove(); // Remove any existing views
				this.find('.ss-gridfield-items .ui-selected').removeClass('ui-selected'); // Unselect all items
				this.find('li.ss-uploadfield-item').remove(); // Remove all selected items
				this.redraw();

				this._super();
			},
			getFileView: function(idOrUrl) {
				return this.find('.ss-htmleditorfield-file[data-id=' + idOrUrl + ']');
			},
			showFileView: function(idOrUrl) {
				var self = this, params = (Number(idOrUrl) == idOrUrl) ? {ID: idOrUrl} : {FileURL: idOrUrl};

				var item = $('<div class="ss-htmleditorfield-file loading" />');
				this.find('.content-edit').prepend(item);
				
				var dfr = $.Deferred();
				
				$.ajax({
					url: $.path.addSearchParams(this.attr('action').replace(/MediaForm/, 'viewfile'), params),
					success: function(html, status, xhr) {
						var newItem = $(html).filter('.ss-htmleditorfield-file');
						item.replaceWith(newItem);
						self.redraw();
						dfr.resolve(newItem);
					},
					error: function() {
						item.remove();
						dfr.reject();
					}
				});
				
				return dfr.promise();
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
		 * Show the second step after uploading an image
		 */
		$('form.htmleditorfield-form.htmleditorfield-mediaform div.ss-assetuploadfield').entwine({
			//the UploadField div.ss-uploadfield-editandorganize is hidden in CSS,
			// because we use the detail view for each individual file instead
			onfileuploadstop: function(e) {
				var form = this.closest('form');

				//update the editFields to show those Files that are newly uploaded
				var editFieldIDs = [];
				form.find('div.content-edit').find('div.ss-htmleditorfield-file').each(function(){
					//get the uploaded file ID when this event triggers, signaling the upload has compeleted successfully
					editFieldIDs.push($(this).data('id'));
				});
				// we only want this .ss-uploadfield-files - else we get all ss-uploadfield-files wich include the ones not related to #tinymce insertmedia
				var uploadedFiles = $('.ss-uploadfield-files', this).children('.ss-uploadfield-item');
				uploadedFiles.each(function(){
					var uploadedID = $(this).data('fileid');
					if (uploadedID && $.inArray(uploadedID, editFieldIDs) == -1) {
						//trigger the detail view for filling out details about the file we are about to insert into TinyMCE
						$(this).remove(); // Remove successfully added item from the queue
						form.showFileView(uploadedID);
					}
				});

				form.redraw();
			}

		});

		$('form.htmleditorfield-form.htmleditorfield-mediaform input.remoteurl').entwine({
			onadd: function() {
				this.validate();
			},

			onkeyup: function() {
				this.validate();
			},

			onchange: function() {
				this.validate();
			},

			getAddButton: function() {
				return this.closest('.CompositeField').find('button.add-url');
			},

			validate: function() {
				var val = this.val(), orig = val;

				val = val.replace(/^https?:\/\//i, '');
				if (orig !== val) this.val(val);

				this.getAddButton().button(!!val ? 'enable' : 'disable');
				return !!val;
			}
		});

		/**
		 * Show the second step after adding a URL
		 */
		$('form.htmleditorfield-form.htmleditorfield-mediaform .add-url').entwine({
			getURLField: function() {
				return this.closest('.CompositeField').find('input.remoteurl');
			},

			onclick: function(e) {
				var urlField = this.getURLField(), container = this.closest('.CompositeField'), form = this.closest('form');

				if (urlField.validate()) {
					container.addClass('loading');
					form.showFileView('http://' + urlField.val()).done(function() {
						container.removeClass('loading');
					});
					form.redraw();
				}

				return false;
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
				// Assumes UploadField markup structure
				return $('<div>').append(
					$('<a/>').attr({href: this.data('url')}).text(this.find('.name').text())
				).html();
			},
			/**
			 * Insert updated HTML content into the rich text editor
			 */
			insertHTML: function(ed) {
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
					'width' : width ? parseInt(width, 10) : null,
					'height' : height ? parseInt(height, 10) : null,
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
				/* NOP */
			},
			/**
			 * Logic similar to TinyMCE 'advimage' plugin, insertAndClose() method.
			 */
			insertHTML: function(ed) {
				var form = this.closest('form'), node = form.getSelection(), ed = form.getEditor();

				// Get the attributes & extra data
				var attrs = this.getAttributes(), extraData = this.getExtraData();

				// Find the element we are replacing - either the img, it's wrapper parent, or nothing (if creating)
				var replacee = (node && node.is('img')) ? node : null;
				if (replacee && replacee.parent().is('.captionImage')) replacee = replacee.parent();

				// Find the img node - either the existing img or a new one, and update it
				var img = (node && node.is('img')) ? node : $('<img />');
				img.attr(attrs);

				// Any existing figure or caption node
				var container = img.parent('.captionImage'), caption = container.find('.caption');

				// If we've got caption text, we need a wrapping div.captionImage and sibling p.caption
				if (extraData.CaptionText) {
					if (!container.length) {
						container = $('<div></div>');
					}

					container.attr('class', 'captionImage '+attrs['class']).css('width', attrs.width);

					if (!caption.length) {
						caption = $('<p class="caption"></p>').appendTo(container);
					}

					caption.attr('class', 'caption '+attrs['class']).text(extraData.CaptionText);
				}
				// Otherwise forget they exist
				else {
					container = caption = null;
				}

				// The element we are replacing the replacee with
				var replacer = container ? container : img;

				// If we're replacing something, and it's not with itself, do so
				if (replacee && replacee.not(replacer).length) {
					replacee.replaceWith(replacer);
				}

				// If we have a wrapper element, make sure the img is the first child - img might be the
				// replacee, and the wrapper the replacer, and we can't do this till after the replace has happened
				if (container) {
					container.prepend(img);
				}

				// If we don't have a replacee, then we need to insert the whole HTML
				if (!replacee) {
					// Otherwise insert the whole HTML content
					ed.repaint();
					ed.insertContent($('<div />').append(replacer).html(), {skip_undo : 1});
				}

				ed.addUndo();
				ed.repaint();
			},
			updateFromNode: function(node) {
				this.find(':input[name=AltText]').val(node.attr('alt'));
				this.find(':input[name=Title]').val(node.attr('title'));
				this.find(':input[name=CSSClass]').val(node.attr('class'));
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


		/**
		 * Insert an oembed object tag into the content.
		 * Requires the 'media' plugin for serialization of tags into <img> placeholders.
		 */
		$('form.htmleditorfield-mediaform .ss-htmleditorfield-file.embed').entwine({
			getAttributes: function() {
				var width = this.find(':input[name=Width]').val(),
					height = this.find(':input[name=Height]').val();
				return {
					'src' : this.find('.thumbnail-preview').attr('src'),
					'width' : width ? parseInt(width, 10) : null,
					'height' : height ? parseInt(height, 10) : null,
					'class' : this.find(':input[name=CSSClass]').val(),
					'alt' : this.find(':input[name=AltText]').val(),
					'title' : this.find(':input[name=Title]').val()
				};
			},
			getExtraData: function() {
				var width = this.find(':input[name=Width]').val(),
					height = this.find(':input[name=Height]').val();
				return {
					'CaptionText': this.find(':input[name=CaptionText]').val(),
					'Url': this.find(':input[name=URL]').val(),
					'thumbnail': this.find('.thumbnail-preview').attr('src'),
					'width' : width ? parseInt(width, 10) : null,
					'height' : height ? parseInt(height, 10) : null,
					'cssclass': this.find(':input[name=CSSClass]').val()
				};
			},
			getHTML: function() {
				var el,
					attrs = this.getAttributes(),
					extraData = this.getExtraData(),
					// imgEl = $('<img id="_ss_tmp_img" />');
					imgEl = $('<img />').attr(attrs).addClass('ss-htmleditorfield-file embed');

				$.each(extraData, function (key, value) {
					imgEl.attr('data-' + key, value)
				});

				if(extraData.CaptionText) {
					el = $('<div style="width: ' + attrs['width'] + 'px;" class="captionImage ' + attrs['class'] + '"><p class="caption">' + extraData.CaptionText + '</p></div>').prepend(imgEl);
				} else {
					el = imgEl;
				}
				return $('<div />').append(el).html(); // Little hack to get outerHTML string
			},
			updateFromNode: function(node) {
				this.find(':input[name=AltText]').val(node.attr('alt'));
				this.find(':input[name=Title]').val(node.attr('title'));
				this.find(':input[name=Width]').val(node.width());
				this.find(':input[name=Height]').val(node.height());
				this.find(':input[name=Title]').val(node.attr('title'));
				this.find(':input[name=CSSClass]').val(node.data('cssclass'));
			}
		});

		$('form.htmleditorfield-mediaform .ss-htmleditorfield-file .dimensions :input').entwine({
			OrigVal: null,
			onmatch: function () {
				this._super();

				this.setOrigVal(parseInt(this.val(), 10));

			},
			onunmatch: function() {
				this._super();
			},
			onfocusout: function(e) {
				this.closest('.ss-htmleditorfield-file').updateDimensions(this.attr('name'));
			}
		});

		/**
		 * Deselect item and remove the 'edit' view
		 */
		$('form.htmleditorfield-mediaform .ss-uploadfield-item .ss-uploadfield-item-cancel').entwine({
			onclick: function(e) {
				var form = this.closest('form'), file = this.closest('ss-uploadfield-item');
				form.find('.ss-gridfield-item[data-id=' + file.data('id') + ']').removeClass('ui-selected');
				this.closest('.ss-uploadfield-item').remove();
				form.redraw();
				e.preventDefault();
			}
		});

		$('div.ss-assetuploadfield .ss-uploadfield-item-edit, div.ss-assetuploadfield .ss-uploadfield-item-name').entwine({
			getEditForm: function() {
				return this.closest('.ss-uploadfield-item').find('.ss-uploadfield-item-editform');
			},

			fromEditForm: {
				onchange: function(e){
					var form = $(e.target);
					form.removeClass('edited'); //so edited class is only there once
					form.addClass('edited');
				}
			},

			onclick: function(e) {
				var editForm = this.getEditForm();
		
				// Make sure we're in an HtmlEditorField here, or fall-back to _super(). HtmlEditorField with 
				// AssetUploadField doesn't use iframes, so needs its own toggleEditForm() logic
				if (this.closest('.ss-uploadfield-item').hasClass('ss-htmleditorfield-file')) {
					editForm.parent('ss-uploadfield-item').removeClass('ui-state-warning');

					editForm.toggleEditForm();

					e.preventDefault(); // Avoid a form submit

					return false; // Avoid duplication from button
				}

				this._super(e);
			}
		});

		$('div.ss-assetuploadfield .ss-uploadfield-item-editform').entwine({
			toggleEditForm: function(bool) {
				var itemInfo = this.prev('.ss-uploadfield-item-info'), status = itemInfo.find('.ss-uploadfield-item-status');
				var text="";

				if(bool === true || (bool !== false && this.height() === 0)) {
					text = ss.i18n._t('UploadField.Editing', "Editing ...");
					this.height('auto');
					itemInfo.find('.toggle-details-icon').addClass('opened');					
					status.removeClass('ui-state-success-text').removeClass('ui-state-warning-text');
				} else {
					this.height(0);					
					itemInfo.find('.toggle-details-icon').removeClass('opened');
					if(!this.hasClass('edited')){
						text = ss.i18n._t('UploadField.NOCHANGES', 'No Changes')
						status.addClass('ui-state-success-text');
					}else{						
						text = ss.i18n._t('UploadField.CHANGESSAVED', 'Changes Made')
						this.removeClass('edited');
						status.addClass('ui-state-success-text');	
					}
				
				}
				status.attr('title',text).text(text);	
			}
		});


		$('form.htmleditorfield-mediaform #ParentID .TreeDropdownField').entwine({
			onadd: function() {
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
