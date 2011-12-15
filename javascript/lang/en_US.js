if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('en_US', {
		'VALIDATOR.FIELDREQUIRED': 'Please fill out "%s", it is required.',
		'HASMANYFILEFIELD.UPLOADING': 'Uploading... %s',
		'TABLEFIELD.DELETECONFIRMMESSAGE': 'Are you sure you want to delete this record?',
		'LOADING': 'loading...',
		'UNIQUEFIELD.SUGGESTED': "Changed value to '%s' : %s",
		'UNIQUEFIELD.ENTERNEWVALUE': 'You will need to enter a new value for this field',
		'UNIQUEFIELD.CANNOTLEAVEEMPTY': 'This field cannot be left empty',
		'RESTRICTEDTEXTFIELD.CHARCANTBEUSED': "The character '%s' cannot be used in this field",
		'UPDATEURL.CONFIRM': 'Would you like me to change the URL to:\n\n%s/\n\nClick Ok to change the URL, click Cancel to leave it as:\n\n%s',
		'FILEIFRAMEFIELD.DELETEFILE': 'Delete File',
		'FILEIFRAMEFIELD.UNATTACHFILE': 'Un-Attach File',
		'FILEIFRAMEFIELD.DELETEIMAGE': 'Delete Image',
		'FILEIFRAMEFIELD.CONFIRMDELETE': 'Are you sure you want to delete this file?'
	});
}
