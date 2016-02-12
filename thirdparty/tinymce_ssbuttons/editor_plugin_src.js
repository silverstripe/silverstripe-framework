(function() {

	var ssbuttons = {
		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @returns Name/value array containing information about the plugin.
		 * @type Array 
		 */
		getInfo : function() {
			return {
				longname : 'Special buttons for SilverStripe CMS',
				author : 'Sam Minnée',
				authorurl : 'http://www.siverstripe.com/',
				infourl : 'http://www.silverstripe.com/',
				version : "1.0"
			};
		},

		init : function(ed) {
			ed.addButton('sslink', {
				icon : 'link',
				title : 'Insert Link',
				cmd : 'sslink'
			});
			ed.addMenuItem('sslink', {
				icon : 'link',
				text : 'Insert Link',
				cmd : 'sslink'
			});
			ed.addButton('ssmedia', {
				icon : 'image',
				title : 'Insert Media',
				cmd : 'ssmedia'
			});
			ed.addMenuItem('ssmedia', {
				icon : 'image',
				text : 'Insert Media',
				cmd : 'ssmedia'
			});
			

			ed.addCommand('sslink', function(ed) {
				// See HtmlEditorField.js
				jQuery('#' + this.id).entwine('ss').openLinkDialog();
			});

			ed.addCommand('ssmedia', function(ed) {
				// See HtmlEditorField.js
				jQuery('#' + this.id).entwine('ss').openMediaDialog();
			});

			// Replace the mceAdvLink and mceLink commands with the sslink command, and
			// the mceAdvImage and mceImage commands with the ssmedia command
			ed.on('BeforeExecCommand', function(e){
				cmd = e.command;
				ui = e.ui;
				val = e.value;
				if (cmd == 'mceAdvLink' || cmd == 'mceLink'){
					e.preventDefault();
					ed.execCommand('sslink', ui, val);
				} else if (cmd == 'mceAdvImage' || cmd == 'mceImage'){
					e.preventDefault();
					ed.execCommand('ssmedia', ui, val);
				}
			});

			ed.on('SaveContent', function(o) {
				var content = jQuery(o.content);
				content.find('.ss-htmleditorfield-file.embed').each(function() {
					var el = jQuery(this);
					var shortCode = '[embed width="' + el.attr('width') + '"'
										+ ' height="' + el.attr('height') + '"'
										+ ' class="' + el.data('cssclass') + '"'
										+ ' thumbnail="' + el.data('thumbnail') + '"'
										+ ']' + el.data('url')
										+ '[/embed]';
					el.replaceWith(shortCode);
				});

				// Insert outerHTML in order to retain all nodes incl. <script>
				// tags which would've been filtered out with jQuery.html().
				// Note that <script> tags might be sanitized separately based on editor config.
				o.content = '';
				content.each(function() {
					if(this.outerHTML !== undefined) {
						o.content += this.outerHTML;
					}
				});
			});

			var shortTagRegex = /(.?)\[embed(.*?)\](.+?)\[\/\s*embed\s*\](.?)/gi;
			ed.on('BeforeSetContent', function(o) {
				var matches = null, content = o.content;
				var prefix, suffix, attributes, attributeString, url;
				var attrs, attr;
				var imgEl;
				// Match various parts of the embed tag
				while((matches = shortTagRegex.exec(content))) {
					prefix = matches[1];
					suffix = matches[4];
					if(prefix === '[' && suffix === ']') {
						continue;
					}
					attributes = {};
					// Remove quotation marks and trim.
					attributeString = matches[2].replace(/['"]/g, '').replace(/(^\s+|\s+$)/g, '');

					// Extract the attributes and values into a key-value array (or key-key if no value is set)
					attrs = attributeString.split(/\s+/);
					for(attribute in attrs) {
						attr = attrs[attribute].split('=');
						if(attr.length == 1) {
							attributes[attr[0]] = attr[0];
						} else {
							attributes[attr[0]] = attr[1];
						}
					}

					// Build HTML element from embed attributes.
					attributes.cssclass = attributes['class'];
					url = matches[3];
					imgEl = jQuery('<img/>').attr({
						'src': attributes['thumbnail'],
						'width': attributes['width'],
						'height': attributes['height'],
						'class': attributes['cssclass'],
						'data-url': url
					}).addClass('ss-htmleditorfield-file embed');

					jQuery.each(attributes, function (key, value) {
						imgEl.attr('data-' + key, value);
					});

					content = content.replace(matches[0], prefix + (jQuery('<div/>').append(imgEl).html()) + suffix);
				}
				o.content = content;
			});
		}
	};

	// Adds the plugin class to the list of available TinyMCE plugins
	tinymce.PluginManager.add("ssbuttons", function(editor) {
		ssbuttons.init(editor);
	});
})();
