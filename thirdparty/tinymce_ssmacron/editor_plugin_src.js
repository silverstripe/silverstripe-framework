(function() {
	var each = tinymce.each;

	// TinyMCE will stop loading if it encounters non-existent external script file
	// when included through tiny_mce_gzip.php. Only load the external lang package if it is available.
	var availableLangs = ['en', 'mi_NZ'];
	if(jQuery.inArray(tinymce.settings.language, availableLangs) != -1) {
		tinymce.PluginManager.requireLangPack("ssmacron");
	}

	/**
	 * Load via: 
	 * HtmlEditorConfig::get('cms')->enablePlugins(array('ssmacron', '../tinymce_ssmacron'))
	 * HtmlEditorConfig::get('cms')->insertButtonsAfter ('advcode', 'ssmacron');
	 */
	tinymce.create('tinymce.plugins.InsertMacron', {
		getInfo : function() {
			return {
				longname : 'Button to insert macrons',
				author : 'Hamish Friedlander. Heavily based on charmap that comes with TinyMCE',
				authorurl : 'http://www.siverstripe.com/',
				infourl : 'http://www.silverstripe.com/',
				version : "1.0"
			};
		},


		init : function(ed, url) {
			// Register commands
			ed.addCommand('mceInsertMacron', function() {
				ed.windowManager.open({
					file : url + '/macron.htm',
					width : 350 + parseInt(ed.getLang('advanced.charmap_delta_width', 0)),
					height : 150 + parseInt(ed.getLang('advanced.charmap_delta_height', 0)),
					inline : true
				}, {
					plugin_url : url
				});
			});
	
			// Register buttons
			ed.addButton('ssmacron', {
				title : ed.getLang('tinymce_ssmacron.insertmacron'),
				cmd : 'mceInsertMacron',
				image : url + '/img/macron.png'
			});
		}
	});

	// Adds the plugin class to the list of available TinyMCE plugins
	tinymce.PluginManager.add("ssmacron", tinymce.plugins.InsertMacron);
})();
