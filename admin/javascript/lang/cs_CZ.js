if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('cs_CZ', {
		'ModelAdmin.SAVED': "Uloženo",
		'ModelAdmin.REALLYDELETE': "Skutečně chcete smazat?",
		'ModelAdmin.DELETED': "Smazáno",
		'LeftAndMain.PAGEWASDELETED': "Tato stránka byla smazána. Pro editaci stránky, vyberte ji vlevo.",
		'LeftAndMain.CONFIRMUNSAVED': "Určitě chcete opustit navigaci z této stránky?\n\nUPOZORNĚNÍ: Vaše změny nebyly uloženy.\n\nStlačte OK pro pokračovat, nebo Cancel, zůstanete na této stránce."
	});
}