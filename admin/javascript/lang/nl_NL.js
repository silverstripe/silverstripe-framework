if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('nl_NL', {
		'LeftAndMain.CONFIRMUNSAVED': "Weet u zeker dat u deze pagina wilt verlaten?\n\WAARSCHUWING: Uw veranderingen zijn niet opgeslagen.\n\nKies OK om te verlaten, of Cancel om op de huidige pagina te blijven.",
		'LeftAndMain.CONFIRMUNSAVEDSHORT': "WAARSCHUWING: Uw veranderingen zijn niet opgeslagen",
		'SecurityAdmin.BATCHACTIONSDELETECONFIRM': "Weet u zeker dat u deze groep %s wilt verwijderen?",
		'ModelAdmin.SAVED': "Opgeslagen",
		'ModelAdmin.REALLYDELETE': "Weet u zeker dat u wilt verwijderen?",
		'ModelAdmin.DELETED': "Verwijderd",
		'ModelAdmin.VALIDATIONERROR': "Validatie fout",
		'LeftAndMain.PAGEWASDELETED': "Deze pagina is verwijderd. Om een pagina aan te passen, selecteer pagina aan de linkerkant."
	});
}
