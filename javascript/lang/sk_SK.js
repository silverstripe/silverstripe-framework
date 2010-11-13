if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('sk_SK', {
		'VALIDATOR.FIELDREQUIRED': 'Vyplňte "%s", prosím, je požadované.',
		'HASMANYFILEFIELD.UPLOADING': 'Nahrávanieí... %s',
		'TABLEFIELD.DELETECONFIRMMESSAGE': 'Určite chcete zmazať tento záznam?',
		'TABLEFIELD.DELETECONFIRMMESSAGEV2': '\nJe tu %s stránok, ktoré používajú tento súbor, zkontrolujte stránky na záložke Odkazy pred pokračovaním.',
		'LOADING': 'natahovanie...',
		'UNIQUEFIELD.SUGGESTED': "Hodnota bola zmenená na '%s' : %s",
		'UNIQUEFIELD.ENTERNEWVALUE': 'Pre toto pole musíte zadať novú hodnotu',
		'UNIQUEFIELD.CANNOTLEAVEEMPTY': 'Toto pole nesmie byť prázdne',
		'RESTRICTEDTEXTFIELD.CHARCANTBEUSED': "V tomto poli znak '%s' nesmie byť použité",
		'UPDATEURL.CONFIRM': 'Chceli by ste zmeniť URL na:\n\n%s\n\nKliknite OK pre zmenu URL, kliknite Cancel pre ponechanie pôvodného:\n\n%s',
		'UPDATEURL.CONFIRM_V2': 'Chceli by ste zmeniť URL tak, aby bolo podobné názvu stránky?\n\nKliknite OK pre zmenu URL, kliknite Cancel pre ponechanie pôvodného:\n\n%s',
		'FILEIFRAMEFIELD.DELETEFILE': 'Zmazať súbor',
		'FILEIFRAMEFIELD.UNATTACHFILE': 'Odpojiť súbor',
		'FILEIFRAMEFIELD.DELETEIMAGE': 'Zmazať obrázok',
		'FILEIFRAMEFIELD.CONFIRMDELETE': 'Určite chcete zmazať tento súbor?',
		'TABLEFIELD.SELECTDELETE': 'Vyberte, prosím, nejaké súbory na smazanie!',
		'TABLEFIELD.CONFIRMDELETEV2': 'Určite chcete smazať označené súbory?',
		'TABLEFIELD.SELECTUPLOAD': 'Vyberte, prosím, najmenej jeden súbor na nahranie.'
	});
}