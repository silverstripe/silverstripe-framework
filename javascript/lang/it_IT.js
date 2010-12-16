if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('fr_FR', {
		'VALIDATOR.FIELDREQUIRED': 'Completare il campo "%s", che è obbligatorio.',
		'HASMANYFILEFIELD.UPLOADING': 'Invio file... %s',
		'TABLEFIELD.DELETECONFIRMMESSAGE': 'Si è sicuri di voler eliminare questo elemento?',
		'LOADING': 'caricamento...',
		'UNIQUEFIELD.SUGGESTED': "Cambiare il valore di '%s': %s",
		'UNIQUEFIELD.ENTERNEWVALUE': 'È necessario scegliere un\'altro valore per questo campo',
		'UNIQUEFIELD.CANNOTLEAVEEMPTY': 'Questo campo non può essere lasciato vuoto',
		'RESTRICTEDTEXTFIELD.CHARCANTBEUSED': "Il carattere '%s' non può essere utilizzato in questo campo",
		'UPDATEURL.CONFIRM': 'Volete cambiare l\'URL in:\n\n%s/\n\nClicca OK per cambiare l\'URL, clicca Annuler per lasciarla a:\n\n%s'
	});
}