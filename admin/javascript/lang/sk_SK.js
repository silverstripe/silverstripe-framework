if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('sk_SK', {
		'ModelAdmin.SAVED': "Uložené",
		'ModelAdmin.REALLYDELETE': "Skutočně chcete zmazať?",
		'ModelAdmin.DELETED': "Zmazané",
		'LeftAndMain.PAGEWASDELETED': "Táto stránka bola zmazaná. Pre editáciu stránky, vyberte ju vľavo.",
		'LeftAndMain.CONFIRMUNSAVED': "Určite chcete opustiť navigáciu z tejto stránky?\n\nUPOZORNENIE: Vaše zmeny neboli uložené.\n\nStlačte OK pre pokračovať, alebo Cancel, ostanete na teto stránke."
	});
}