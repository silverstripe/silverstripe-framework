Behaviour.register({
	'input.DataReport_ExportToCSVButton' : {
		onclick: function() {
			var pageID = $('Form_EditForm_ID').value;
			var idParts = this.id.split('_');
			var formName = idParts[idParts.length-2];
		
			var reportType = $('DataReport_Type').value;
			var url = baseHref() + 'admin/ReportField_Controller/exporttocsv/' + pageID + '/' + reportType + '/' + formName + '.csv';
			
			//alert( url );
			//var exportWindow = window.open( url, '_self' );
			//alert( exportWindow.location );
			window.location.href = url;
			
			return false;
		}
	}
});