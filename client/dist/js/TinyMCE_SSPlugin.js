(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.TinyMCE_SSPlugin', [], factory);
	} else if (typeof exports !== "undefined") {
		factory();
	} else {
		var mod = {
			exports: {}
		};
		factory();
		global.ssTinyMCE_SSPlugin = mod.exports;
	}
})(this, function () {
	'use strict';

	(function () {

		var ssbuttons = {
			getInfo: function getInfo() {
				return {
					longname: 'Special buttons for SilverStripe CMS',
					author: 'Sam Minnée',
					authorurl: 'http://www.siverstripe.com/',
					infourl: 'http://www.silverstripe.com/',
					version: "1.0"
				};
			},

			init: function init(ed) {
				ed.addButton('sslink', {
					icon: 'link',
					title: 'Insert Link',
					cmd: 'sslink'
				});
				ed.addMenuItem('sslink', {
					icon: 'link',
					text: 'Insert Link',
					cmd: 'sslink'
				});
				ed.addButton('ssmedia', {
					icon: 'image',
					title: 'Insert Media',
					cmd: 'ssmedia'
				});
				ed.addMenuItem('ssmedia', {
					icon: 'image',
					text: 'Insert Media',
					cmd: 'ssmedia'
				});

				ed.addCommand('sslink', function (ed) {
					jQuery('#' + this.id).entwine('ss').openLinkDialog();
				});

				ed.addCommand('ssmedia', function (ed) {
					jQuery('#' + this.id).entwine('ss').openMediaDialog();
				});

				ed.on('BeforeExecCommand', function (e) {
					var cmd = e.command;
					var ui = e.ui;
					var val = e.value;
					if (cmd == 'mceAdvLink' || cmd == 'mceLink') {
						e.preventDefault();
						ed.execCommand('sslink', ui, val);
					} else if (cmd == 'mceAdvImage' || cmd == 'mceImage') {
						e.preventDefault();
						ed.execCommand('ssmedia', ui, val);
					}
				});

				ed.on('SaveContent', function (o) {
					var content = jQuery(o.content);
					var attrsFn = function attrsFn(attrs) {
						return Object.keys(attrs).map(function (name) {
							return attrs[name] ? name + '="' + attrs[name] + '"' : null;
						}).filter(function (el) {
							return el !== null;
						}).join(' ');
					};

					content.find('.ss-htmleditorfield-file.embed').each(function () {
						var el = jQuery(this);
						var attrs = {
							width: el.attr('width'),
							class: el.attr('cssclass'),
							thumbnail: el.data('thumbnail')
						};
						var shortCode = '[embed ' + attrsFn(attrs) + ']' + el.data('url') + '[/embed]';
						el.replaceWith(shortCode);
					});

					content.find('img').each(function () {
						var el = jQuery(this);
						var attrs = {
							src: el.attr('src'),
							id: el.data('id'),
							width: el.attr('width'),
							height: el.attr('height'),
							class: el.attr('class'),

							title: el.attr('title'),
							alt: el.attr('alt')
						};
						var shortCode = '[image ' + attrsFn(attrs) + ']';
						el.replaceWith(shortCode);
					});

					o.content = '';
					content.each(function () {
						if (this.outerHTML !== undefined) {
							o.content += this.outerHTML;
						}
					});
				});
				ed.on('BeforeSetContent', function (o) {
					var matches;
					var content = o.content;
					var attrFromStrFn = function attrFromStrFn(str) {
						return str.match(/([^\s\/'"=,]+)\s*=\s*(('([^']+)')|("([^"]+)")|([^\s,\]]+))/g).reduce(function (coll, val) {
							var match = val.match(/^([^\s\/'"=,]+)\s*=\s*(?:(?:'([^']+)')|(?:"([^"]+)")|(?:[^\s,\]]+))$/),
							    key = match[1],
							    value = match[2] || match[3] || match[4];
							coll[key] = value;
							return coll;
						}, {});
					};

					var shortTagRegex = /\[embed(.*?)\](.+?)\[\/\s*embed\s*\]/gi;
					while (matches = shortTagRegex.exec(content)) {
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

						Object.keys(attrs).forEach(function (key) {
							return el.attr('data-' + key, attrs[key]);
						});
						content = content.replace(matches[0], jQuery('<div/>').append(el).html());
					}

					var shortTagRegex = /\[image(.*?)\]/gi;
					while (matches = shortTagRegex.exec(content)) {
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
						content = content.replace(matches[0], jQuery('<div/>').append(el).html());
					}

					o.content = content;
				});
			}
		};

		tinymce.PluginManager.add("ssbuttons", function (editor) {
			ssbuttons.init(editor);
		});
	})();
});