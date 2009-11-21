tinyMCEPopup.requireLangPack();

var CodeDialog = {
  init : function() {
    var f = document.forms[0];
    f.codepress.value = tinyMCEPopup.editor.getContent();
  },

  insert : function() {
    tinyMCEPopup.editor.setContent(codepress.getCode());
    tinyMCEPopup.close();
  }
};

tinyMCEPopup.onInit.add(CodeDialog.init, CodeDialog);