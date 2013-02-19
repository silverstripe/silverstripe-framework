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
ss.editorWrappers.initial
ss.editorWrappers.tinyMCE = (function() {
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