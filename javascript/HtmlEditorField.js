/**
 * Simple TinyMCE initialisation
 */
if((typeof tinyMCE != 'undefined')) {
	tinyMCE.init({
		mode : "specific_textareas",
		editor_selector : "htmleditor",
		width: "100%",
		auto_resize : false,
		theme : "advanced",

		theme_advanced_layout_manager: "SimpleLayout",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_toolbar_parent : "right",
		plugins : "blockquote,contextmenu,table,emotions,paste,../../tinymce_advcode,spellchecker",	
		blockquote_clear_tag : "p",
		table_inline_editing : true,
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough,separator,justifyleft,justifycenter,justifyright,justifyfull,formatselect,separator,bullist,numlist,outdent,indent,blockquote,hr,charmap",
		theme_advanced_buttons2 : "undo,redo,separator,cut,copy,paste,pastetext,pasteword,spellchecker,separator,advcode,search,replace,selectall,visualaid,separator,tablecontrols",
		theme_advanced_buttons3 : "",

		safari_warning : false,
		relative_urls : true,
		verify_html : true
	});
}
