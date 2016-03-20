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
				var cmd = e.command;
				var ui = e.ui;
				var val = e.value;
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
				var attrsFn = (attrs) => {
					return Object.keys(attrs)
						.map((name) => attrs[name] ? name + '="' + attrs[name] + '"' : null)
						.filter((el) => el !== null)
						.join(' ')
				};

				// Transform [embed] shortcodes
				content.find('.ss-htmleditorfield-file.embed').each(function() {
					var el = jQuery(this);
					var attrs = {
						width: el.attr('width'),
						class: el.attr('cssclass'),
						thumbnail: el.data('thumbnail')
					};
					var shortCode = '[embed ' + attrsFn(attrs) + ']' + el.data('url') + '[/embed]';
					el.replaceWith(shortCode);
				});

				// Transform [image] shortcodes
				content.find('img').each(function() {
					var el = jQuery(this);
					var attrs = {
						// TODO Don't store 'src' since its more volatile than 'id'.
						// Requires server-side preprocessing of HTML+shortcodes in HTMLValue
						src: el.attr('src'),
						id: el.data('id'),
						width: el.attr('width'),
						height: el.attr('height'),
						class: el.attr('class'),
						// don't save caption, since that's in the containing element
						title: el.attr('title'),
						alt: el.attr('alt')
					};
					var shortCode = '[image ' + attrsFn(attrs) + ']';
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
			ed.on('BeforeSetContent', function(o) {
				var matches;
				var content = o.content;
				var attrFromStrFn = (str) => {
					return str
						// Remove quotation marks and trim.
						.replace(/['"]/g, '')
						.replace(/(^\s+|\s+$)/g, '')
						// Extract the attrs and values into a key-value array,
						// or key-key if no value is set.
						.split(/\s+/)
						.reduce((coll, val) => {
							var pair = val.split('=');
							coll[pair[0]] = (pair.length == 1) ? pair[0] : pair[1];
							return coll;
						}, {});
				};

				// Transform [embed] tag
				var shortTagRegex = /\[embed(.*?)\](.+?)\[\/\s*embed\s*\]/gi;
				while((matches = shortTagRegex.exec(content))) {
					var attrs = attrFromStrFn(matches[1]);
					var el;

					el = jQuery('<img/>').attr({
						'src': attrs['thumbnail'],
						'width': attrs['width'],
						'height': attrs['height'],
						'class': attrs['class'],
						'data-url': matches[2]
					}).addClass('ss-htmleditorfield-file embed');
					attrs['cssclass'] = attrs['class'];

					Object.keys(attrs).forEach((key) => el.attr('data-' + key, attrs[key]));
					content = content.replace(matches[0], (jQuery('<div/>').append(el).html()));
				}

				// Transform [image] tag
				var shortTagRegex = /\[image(.*?)\]/gi;
				while((matches = shortTagRegex.exec(content))) {
					var attrs = attrFromStrFn(matches[1]);
					var el = jQuery('<img/>').attr({
						'src': attrs['src'],
						'width': attrs['width'],
						'height': attrs['height'],
						'class': attrs['class'],
						'alt': attrs['alt'],
						'title': attrs['title'],
						'data-id': attrs['id']
					});

					Object.keys(attrs).forEach((key) => el.attr('data-' + key, attrs[key]));
					content = content.replace(matches[0], (jQuery('<div/>').append(el).html()));
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
