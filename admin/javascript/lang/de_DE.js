if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('de_DE', {
		'ModelAdmin.SAVED': "Gespeichert",
		'ModelAdmin.REALLYDELETE': "Wirklich löschen?",
		'ModelAdmin.DELETED': "Gelöscht",
		'ModelAdmin.VALIDATIONERROR': "Validationsfehler",
		'LeftAndMain.PAGEWASDELETED': "Diese Seite wurde gelöscht.",
		'LeftAndMain.CONFIRMUNSAVED': "Sind Sie sicher, dasß Sie die Seite verlassen möchten?\n\nWARNUNG: Ihre Änderungen werden nicht gespeichert.\n\nDrücken Sie \"OK\" um fortzufahren, oder \"Abbrechen\" um auf dieser Seite zu bleiben."
	});
}