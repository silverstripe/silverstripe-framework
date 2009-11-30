(function() {
  tinymce.PluginManager.requireLangPack('advcode');
  
  tinymce.create('tinymce.plugins.AdvancedCodePlugin', {
    init : function(ed, url) {
      // Register commands
      ed.addCommand('mceAdvancedCode', function() {
        ed.windowManager.open({
          file : url + '/dialog.html',
          width : 750 + parseInt(ed.getLang('advcode.delta_width', 0)),
          height : 450 + parseInt(ed.getLang('advcode.delta_height', 0)),
          inline : 1
        }, {
          plugin_url : url
        });
      });

      // Register buttons
      ed.addButton('advcode', {
        title : ed.getLang('advcode.desc', 0),
        cmd : 'mceAdvancedCode',
        image : url + '/img/html.png'
      });

      ed.onNodeChange.add(function(ed, cm, n) {});
    },

    getInfo : function() {
      return {
        longname : 'Advanced Code Editor',
        author : 'Daniel Insley',
        authorurl : 'http://www.danielinsley.com',
        infourl : 'http://github.com/dinsley/tinymce-codepress/tree/master',
        version : tinymce.majorVersion + "." + tinymce.minorVersion
      };
    }
  });

  // Register plugin
  tinymce.PluginManager.add('advcode', tinymce.plugins.AdvancedCodePlugin);
})();