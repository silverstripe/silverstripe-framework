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
			ed.addButton('ssimage', {title : ed.getLang('tinymce_ssbuttons.insertimage'), cmd : 'ssimage', 'class' : 'mce_image'}); 

			ed.addCommand("sslink", function(ed) {
				jQuery('#Form_EditorToolbarLinkForm').entwine('ss').open();
			});

			ed.addCommand("ssimage", function(ed) {
				jQuery('#Form_EditorToolbarMediaForm').entwine('ss').open();
			});
			
			// Disable link button when no link is selected
			ed.onNodeChange.add(function(ed, cm, n, co) {
				cm.setDisabled('sslink', co && n.nodeName != 'A');
				cm.setActive('sslink', n.nodeName == 'A' && !n.name);
			});
		}
	});

	// Adds the plugin class to the list of available TinyMCE plugins
	tinymce.PluginManager.add("ssbuttons", tinymce.plugins.SSButtons);
})();