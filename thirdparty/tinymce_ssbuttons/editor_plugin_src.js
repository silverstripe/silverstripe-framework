(function() {

	// TinyMCE will stop loading if it encounters non-existent external script file
	// when included through tiny_mce_gzip.php. Only load the external lang package if it is available.
	var availableLangs = ['en', 'de', 'nl'];
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
			ed.addButton('link', {
				title: ed.getLang('tinymce_ssbuttons', 'insertlink'),
				cmd: 'sslink',
				class: 'mce_link',
				onPostRender: function() {
					var ctrl = this;

					// Disable link button when no link is selected
					ed.on('nodechange', function(e) {
						ctrl.disabled(e.element.nodeName != 'A' && !ed.selection.getContent());
						ctrl.active(e.element.nodeName == 'A');
					});
				}
			});

			ed.addButton('image', {
				title: ed.getLang('tinymce_ssbuttons', 'insertmedia'),
				cmd: 'ssmedia',
				class: 'mce_image'
			});


			ed.addCommand('sslink', function(ed) {
				jQuery('#' + this.id).entwine('ss').openLinkDialog();
			});

			ed.addCommand('ssmedia', function(ed) {
				jQuery('#' + this.id).entwine('ss').openMediaDialog();
			});


			// Hide menu by default. Add toggle button.
			menubar = '.mce-container.mce-toolbar[role=menubar]';
			setTimeout(function(){
				jQuery('.mce-container.mce-toolbar[role=menubar]')
					.addClass('hidden')
					.css({display : 'none'});
			}, 50); // need a timeout, otherwise not triggered properly.

			ed.addButton('menubtn', {
				text: 'Menu',
				title: 'Menu',
				icon: false,
				onclick: function() {
					if(jQuery(menubar).hasClass('hidden')) {
						jQuery(menubar).removeClass('hidden').css({display : 'block'});
					} else {
						jQuery(menubar).addClass('hidden').css({display : 'none'});
					}
				}
			});

			/**
			 * optional fullscreen function, todo: onresize fix
			 */
			/*ed.on('FullscreenStateChanged', function(e) { console.log(jQuery('#pages-controller-cms-content').offset());
				if(e.state === true) {
					e.target.container.style.width = jQuery('#pages-controller-cms-content').width()+'px';
					e.target.container.style.left = jQuery('#pages-controller-cms-content').offset().left+'px';
				} else {
					e.target.container.style.width = '100%';
					e.target.container.style.left = 'auto';
				}
			});*/

			ed.on('SaveContent', function(e) {
				var content = jQuery(e.content);
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
				e.content = jQuery('<div />').append(content).html(); // Little hack to get outerHTML string
			});

			var shortTagRegex = /(.?)\[embed(.*?)\](.+?)\[\/\s*embed\s*\](.?)/gi;
			ed.on('BeforeSetContent', function(e) {
				var matches = null, content = e.content;
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
				e.content = content;
			});
		}
	});

	// Adds the plugin class to the list of available TinyMCE plugins
	tinymce.PluginManager.add("ssbuttons", tinymce.plugins.SSButtons);
})();
