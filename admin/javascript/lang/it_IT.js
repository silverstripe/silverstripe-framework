if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('it_IT', {
		'ModelAdmin.SAVED': "Salvato",
		'ModelAdmin.REALLYDELETE': "Si è sicuri di voler eliminare?",
		'ModelAdmin.DELETED': "Eliminato",
		'LeftAndMain.PAGEWASDELETED': "Questa pagina è stata eliminata. Per modificare questa pagine, selezionarla a sinistra.",
		'LeftAndMain.CONFIRMUNSAVED': "Siete sicuri di voler uscire da questa pagina?\n\nATTENZIONE: I vostri cambiamenti non sono stati salvati.\n\nCliccare OK per continuare, o su Annulla per rimanere sulla pagina corrente."
	});
}