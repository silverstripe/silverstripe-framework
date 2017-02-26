/* global tinymce */

(() => {
  const sslink = {

    /**
     * Initialise this plugin
     *
     * @param {Object} ed
     */
    init(ed) {
      ed.addButton('sslink', {
        icon: 'link',
        title: 'Insert Link',
        cmd: 'sslink',
      });
      ed.addMenuItem('sslink', {
        icon: 'link',
        text: 'Insert Link',
        cmd: 'sslink',
      });

      ed.addCommand('sslink', () => {
        // See HtmlEditorField.js
        window.jQuery(`#${ed.id}`).entwine('ss').openLinkDialog();
      });

      // Replace the mceAdvLink and mceLink commands with the sslink command, and
      // the mceAdvImage and mceImage commands with the ssmedia command
      ed.on('BeforeExecCommand', (e) => {
        const cmd = e.command;
        const ui = e.ui;
        const val = e.value;
        if (cmd === 'mceAdvLink' || cmd === 'mceLink') {
          e.preventDefault();
          ed.execCommand('sslink', ui, val);
        }
      });
    },
  };

  // Adds the plugin class to the list of available TinyMCE plugins
  tinymce.PluginManager.add('sslink', (editor) => sslink.init(editor));
})();
