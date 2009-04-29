if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('nl_NL', {
		'VALIDATOR.FIELDREQUIRED': 'Vul het veld "%s" in, dit is een verplicht veld.',
		'HASMANYFILEFIELD.UPLOADING': 'Uploading... %s',
		'TABLEFIELD.DELETECONFIRMMESSAGE': 'Weet u zeker dat u dit record wilt verwijderen?',
		'LOADING': 'laden...',
		'UNIQUEFIELD.SUGGESTED': "Waarde gewijzigd naar '%s' : %s",
		'UNIQUEFIELD.ENTERNEWVALUE': 'U zult een nieuwe waarde voor dit veld moeten invoeren',
		'UNIQUEFIELD.CANNOTLEAVEEMPTY': 'Dit veld mag niet leeg blijven',
		'RESTRICTEDTEXTFIELD.CHARCANTBEUSED': "Het karakter '%s' mag niet gebruikt worden in dit veld",
		'UPDATEURL.CONFIRM': 'Wilt u de URL wijzigen naar:\n\n%s/\n\nKlik Ok om de URL te wijzigen, Klik Cancel om het te laten zoals het is:\n\n%s'
	});
}
