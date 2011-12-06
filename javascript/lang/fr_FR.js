if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('fr_FR', {
		'VALIDATOR.FIELDREQUIRED': 'Veuillez remplir "%s", c\'est un champ requis.',
		'HASMANYFILEFIELD.UPLOADING': 'Uploading... %s',
		'TABLEFIELD.DELETECONFIRMMESSAGE': 'Etes-vous sûr de vouloir supprimer cet enregistrement ?',
		'LOADING': 'chargement...',
		'UNIQUEFIELD.SUGGESTED': "Changez la valeur de '%s' : %s",
		'UNIQUEFIELD.ENTERNEWVALUE': 'You devez saisir une nouvelle valeur pou ce champ',
		'UNIQUEFIELD.CANNOTLEAVEEMPTY': 'Ce champ ne peut être laissé vide',
		'RESTRICTEDTEXTFIELD.CHARCANTBEUSED': "Le character '%s' ne peut être utilisé dans ce champ",
		'UPDATEURL.CONFIRM': 'Voulez-vous que je change l\'URL en:\n\n%s/\n\nCliquez Ok pour changer l\'URL, cliquez Annuler pour la laisser à:\n\n%s',
		'GRIDFIELD.ERRORINTRANSACTION': 'Une erreur est survenue durant la transaction avec le serveur\n Merci de reesayer plus tard.'
	});
}
