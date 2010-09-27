if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('cs_CZ', {
		'VALIDATOR.FIELDREQUIRED': 'Vyplňte "%s", prosím, je vyžadováno.',
		'HASMANYFILEFIELD.UPLOADING': 'Nahrávání... %s',
		'TABLEFIELD.DELETECONFIRMMESSAGE': 'Určitě chcete smazat tento záznam?',
		'LOADING': 'natahování...',
		'UNIQUEFIELD.SUGGESTED': "Hodnota změněna na '%s' : %s",
		'UNIQUEFIELD.ENTERNEWVALUE': 'Pro toto pole musíte zadat novou hodnotu',
		'UNIQUEFIELD.CANNOTLEAVEEMPTY': 'Toto pole nesmí být prázdné',
		'RESTRICTEDTEXTFIELD.CHARCANTBEUSED': "V tomto poli znak '%s' nesmí být použit",
		'UPDATEURL.CONFIRM': 'Dovolite mi změnit URL na:\n\n%s\n\nKlikněte OK pro změnu URL, klikněte Cancel pro ponechání původního:\n\n%s',
		'FILEIFRAMEFIELD.DELETEFILE': 'Smazat soubor',
		'FILEIFRAMEFIELD.UNATTACHFILE': 'Odpojit soubor',
		'FILEIFRAMEFIELD.DELETEIMAGE': 'Smazat obrázek',
		'FILEIFRAMEFIELD.CONFIRMDELETE': 'Určitě chcete smazat tento soubor?'
	});
}