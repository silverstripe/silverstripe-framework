Behaviour.register({
	'input.ReportField_ExportToCSVButton' : {
		onclick: function() {
			var pageID = $('Form_EditForm_ID').value;
			var idParts = this.id.split('_');
			var formName = idParts[idParts.length-2];
			window.location.href = baseHref() + 'admin/ReportField/exporttocsv/' + pageID + '/ReportField/' + formName + '.csv';
			//var exportWindow = window.open( baseHref() + 'admin/ReportField/exporttocsv/' + pageID + '/ReportField/' + formName + '.csv', '_self' );
			
			return false;
		}
	}
});