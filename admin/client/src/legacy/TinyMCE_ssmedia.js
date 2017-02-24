/* global tinymce */
/* eslint-disable no-cond-assign */
/* eslint-disable no-param-reassign */
/* eslint-disable func-names */

(() => {
  const ssmedia = {
    /**
     * Returns information about the plugin as a name/value array.
     * The current keys are longname, author, authorurl, infourl and version.
     *
     * @returns Name/value array containing information about the plugin.
     * @type Array
     */
    getInfo() {
      return {
        longname: 'Media Dialog for SilverStripe CMS',
        author: 'Sam Minnée',
        authorurl: 'http://www.siverstripe.com/',
        infourl: 'http://www.silverstripe.com/',
        version: '1.1',
      };
    },

    init(ed) {
      ed.addButton('ssmedia', {
        icon: 'image',
        title: 'Insert Media',
        cmd: 'ssmedia',
      });
      ed.addMenuItem('ssmedia', {
        icon: 'image',
        text: 'Insert Media',
        cmd: 'ssmedia',
      });


      ed.addCommand('ssmedia', () => {
        // See HtmlEditorField.js
        window.jQuery(`#${ed.id}`).entwine('ss').openMediaDialog();
      });

      // Replace the mceAdvImage and mceImage commands with the ssmedia command
      ed.on('BeforeExecCommand', (e) => {
        const cmd = e.command;
        const ui = e.ui;
        const val = e.value;
        if (cmd === 'mceAdvImage' || cmd === 'mceImage') {
          e.preventDefault();
          ed.execCommand('ssmedia', ui, val);
        }
      });

      ed.on('SaveContent', (o) => {
        const content = window.jQuery(o.content);
        const attrsFn = (attrs) => (
          Object.keys(attrs)
            .map((name) => (attrs[name] ? `${name}="${attrs[name]}"` : null))
            .filter((el) => el !== null)
            .join(' ')
        );

        // Transform [embed] shortcodes
        content.find('.ss-htmleditorfield-file.embed').each(function () {
          const el = window.jQuery(this);
          const attrs = {
            width: el.attr('width'),
            class: el.attr('cssclass'),
            thumbnail: el.data('thumbnail'),
          };
          const shortCode = `[embed ${attrsFn(attrs)}]${el.data('url')}[/embed]`;
          el.replaceWith(shortCode);
        });

        // Transform [image] shortcodes
        content.find('img').each(function () {
          const el = window.jQuery(this);
          const attrs = {
            // Requires server-side preprocessing of HTML+shortcodes in HTMLValue
            src: el.attr('src'),
            id: el.data('id'),
            width: el.attr('width'),
            height: el.attr('height'),
            class: el.attr('class'),
            // don't save caption, since that's in the containing element
            title: el.attr('title'),
            alt: el.attr('alt'),
          };
          const shortCode = `[image ${attrsFn(attrs)}]`;
          el.replaceWith(shortCode);
        });

        // Insert outerHTML in order to retain all nodes incl. <script>
        // tags which would've been filtered out with jQuery.html().
        // Note that <script> tags might be sanitized separately based on editor config.
        o.content = '';
        content.each(function () {
          if (this.outerHTML !== undefined) {
            o.content += this.outerHTML;
          }
        });
      });
      ed.on('BeforeSetContent', (o) => {
        let matches = null;
        let content = o.content;
        const attrFromStrFn = (str) => (
          str
          // Split on all attributes, quoted or not
            .match(/([^\s\/'"=,]+)\s*=\s*(('([^']+)')|("([^"]+)")|([^\s,\]]+))/g)
            .reduce((coll, val) => {
              const match
                = val.match(/^([^\s\/'"=,]+)\s*=\s*(?:(?:'([^']+)')|(?:"([^"]+)")|(?:[^\s,\]]+))$/);
              const key = match[1];
              const value = match[2] || match[3] || match[4]; // single, double, or unquoted match
              return Object.assign({}, coll, { [key]: value });
            }, {})
        );

        // Transform [embed] tag
        const shortTagEmbegRegex = /\[embed(.*?)](.+?)\[\/\s*embed\s*]/gi;
        while (matches = shortTagEmbegRegex.exec(content)) {
          const attrs = attrFromStrFn(matches[1]);
          const el = window.jQuery('<img/>').attr({
            src: attrs.thumbnail,
            width: attrs.width,
            height: attrs.height,
            class: attrs.class,
            'data-url': matches[2],
          }).addClass('ss-htmleditorfield-file embed');
          attrs.cssclass = attrs.class;

          Object.keys(attrs).forEach((key) => el.attr(`data-${key}`, attrs[key]));
          content = content.replace(matches[0], (window.jQuery('<div/>').append(el).html()));
        }

        // Transform [image] tag
        const shortTagImageRegex = /\[image(.*?)]/gi;
        while ((matches = shortTagImageRegex.exec(content))) {
          const attrs = attrFromStrFn(matches[1]);
          const el = window.jQuery('<img/>').attr({
            src: attrs.src,
            width: attrs.width,
            height: attrs.height,
            class: attrs.class,
            alt: attrs.alt,
            title: attrs.title,
            'data-id': attrs.id,
          });
          content = content.replace(matches[0], (window.jQuery('<div/>').append(el).html()));
        }

        o.content = content;
      });
    },
  };

  // Adds the plugin class to the list of available TinyMCE plugins
  tinymce.PluginManager.add('ssmedia', (editor) => ssmedia.init(editor));
})();
