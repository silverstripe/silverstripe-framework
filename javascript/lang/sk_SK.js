if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('sk_SK', {
		'VALIDATOR.FIELDREQUIRED': 'Vyplňte "%s", prosím, je požadované.',
		'HASMANYFILEFIELD.UPLOADING': 'Nahrávanieí... %s',
		'TABLEFIELD.DELETECONFIRMMESSAGE': 'Určite chcete zmazať tento záznam?',
		'LOADING': 'natahovanie...',
		'UNIQUEFIELD.SUGGESTED': "Hodnota bola zmenená na '%s' : %s",
		'UNIQUEFIELD.ENTERNEWVALUE': 'Pre toto pole musíte zadať novú hodnotu',
		'UNIQUEFIELD.CANNOTLEAVEEMPTY': 'Toto pole nesmie byť prázdne',
		'RESTRICTEDTEXTFIELD.CHARCANTBEUSED': "V tomto poli znak '%s' nesmie byť použité",
		'UPDATEURL.CONFIRM': 'Dovolite mi zmeniť URL na:\n\n%s\n\nKliknite OK pre zmenu URL, kliknite Cancel pre ponechanie pôvodného:\n\n%s',
		'FILEIFRAMEFIELD.DELETEFILE': 'Zmazať súbor',
		'FILEIFRAMEFIELD.UNATTACHFILE': 'Odpojiť súbor',
		'FILEIFRAMEFIELD.DELETEIMAGE': 'Zmazaž obrázok',
		'FILEIFRAMEFIELD.CONFIRMDELETE': 'Určite chcete zmazaž tento súbor?'
	});
}