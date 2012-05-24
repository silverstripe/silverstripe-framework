(function() {

	// TinyMCE will stop loading if it encounters non-existent external script file
	// when included through tiny_mce_gzip.php. Only load the external lang package if it is available.
	var availableLangs = ['en', 'de'];
	if(jQuery.inArray(tinymce.settings.language, availableLangs) != -1) {
		tinymce.PluginManager.requireLangPack("ssbuttons");
	}

	var each = tinymce.each;

	tinymce.create('tinymce.plugins.SSButtons', {
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

		init : function(ed, url) {
			ed.addButton('sslink', {title : ed.getLang('tinymce_ssbuttons.insertlink'), cmd : 'sslink', 'class' : 'mce_link'}); 
			ed.addButton('ssmedia', {title : ed.getLang('tinymce_ssbuttons.insertmedia'), cmd : 'ssmedia', 'class' : 'mce_image'}); 

			ed.addCommand('sslink', function(ed) {
				jQuery('#' + this.id).entwine('ss').openLinkDialog();
			});

			ed.addCommand('ssmedia', function(ed) {
				jQuery('#' + this.id).entwine('ss').openMediaDialog();
			});
			
			// Disable link button when no link is selected
			ed.onNodeChange.add(function(ed, cm, n, co) {
				cm.setDisabled('sslink', co && n.nodeName != 'A');
				cm.setActive('sslink', n.nodeName == 'A' && !n.name);
			});

			ed.onSaveContent.add(function(ed, o) {
				var content = jQuery(o.content);
				content.find('.ss-htmleditorfield-file.embed').each(function() {
					var el = jQuery(this);
					var shortCode = '[embed width=' + el.data('width')
										+ ' height=' + el.data('height')
										+ ' class=' + el.data('cssclass')
										+ ' thumbnail=' + el.data('thumbnail')
										+ ']' + el.data('url')
										+ '[/embed]';
					el.replaceWith(shortCode);
				});
				o.content = jQuery('<div />').append(content).html(); // Little hack to get outerHTML string
			});

			var shortTagRegex = /(.?)\[embed(.*?)\](.+?)\[\/\s*embed\s*\](.?)/gi;
			ed.onBeforeSetContent.add(function(ed, o) {
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
	});

	// Adds the plugin class to the list of available TinyMCE plugins
	tinymce.PluginManager.add("ssbuttons", tinymce.plugins.SSButtons);
})();
