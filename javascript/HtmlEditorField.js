/**
 * Functions for HtmlEditorFields in the back end.
 * Includes the JS for the ImageUpload forms. 
 * 
 * Relies on the jquery.form.js plugin to power the 
 * ajax / iframe submissions
 */

(function($) {
	$(document).ready(function() {

		$("#Form_EditorToolbarImageForm .showUploadField a").click(function() {
			if($(this).hasClass("showing")) {
				$("#Form_EditorToolbarImageForm_Files-0").parents('.file').hide();
				$(this).text(ss.i18n._t('HtmlEditorField.ShowUploadForm', 'Upload File')).removeClass("showing");	
			}
			else {
				$("#Form_EditorToolbarImageForm_Files-0").parents('.file').show();
				$(this).text(ss.i18n._t('HtmlEditorField.HideUploadForm', 'Hide Upload Form')).addClass("showing");
			}
		}).show();
		
		$("#Form_EditorToolbarImageForm_Files-0").change(function() {
			$("#contentPanel form").ajaxForm({
				url: 'admin/assets/UploadForm?action_doUpload=1',
				iframe: true,
				
				beforeSubmit: function(data) {
					$("#UploadFormResponse").text("Uploading File...").addClass("loading").show();
					$("#Form_EditorToolbarImageForm_Files-0").parents('.file').hide();
				},
				success: function(data) {
					$("#UploadFormResponse").text(data).removeClass("loading");
					$("#Form_EditorToolbarImageForm_Files-0").val("").parents('.file').show();
					
					$("#FolderImages").html('<h2>'+ ss.i18n._t('HtmlEditorField.Loading', 'Loading') + '</h2>');
					
					var ajaxURL = 'admin/EditorToolbar/ImageForm';
					
					$.get(ajaxURL, {
						action_callfieldmethod: "1",
						fieldName: "FolderImages",
						ajax: "1",
						methodName: "getimages",
						folderID: $("#Form_EditorToolbarImageForm_FolderID").val(),
						searchText: $("#Form_EditorToolbarImageForm_getimagesSearch").val(),
						cacheKillerDate: parseInt((new Date()).getTime()),
						cacheKillerRand: parseInt(10000 * Math.random())
					},
					function(data) {
						$("#FolderImages").html(data);
						
						$("#FolderImages").each(function() {
							Behaviour.apply(this);
						})
					});
				}
			}).submit();
		});
	});
})(jQuery);