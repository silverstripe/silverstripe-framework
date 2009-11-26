tinyMCEPopup.requireLangPack();

var CodeDialog = {
  init : function() {
    cp = document.getElementById('codepress') ? document.getElementById('codepress') : document.getElementById('codepress_cp');
    cp.value = tinyMCEPopup.editor.getContent();
  },

  insert : function() {
	// Customized by SilverStripe 2009-01-05 - see dialog.html for details
	var content = (navigator.userAgent.match('KHTML')) ? document.getElementById('codepress').value : codepress.getCode();
    tinyMCEPopup.editor.setContent(content);
    tinyMCEPopup.close();
  }
};

tinyMCEPopup.onInit.add(CodeDialog.init, CodeDialog);