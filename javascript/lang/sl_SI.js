if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('sl_SI', {
		'VALIDATOR.FIELDREQUIRED': 'Prosimo, izpolnite "%s". Polje je obvezno.',
		'HASMANYFILEFIELD.UPLOADING': 'Nalagam... %s',
		'TABLEFIELD.DELETECONFIRMMESSAGE': 'Ali ste prepričani, da želite izbrisati za zapis?',
		'LOADING': 'nalagam...',
		'UNIQUEFIELD.SUGGESTED': "Vrednost spremenjena v '%s' : %s",
		'UNIQUEFIELD.ENTERNEWVALUE': 'V to polje je potrebno vnesti novo vrednost',
		'UNIQUEFIELD.CANNOTLEAVEEMPTY': 'To polje ne sme biti prazno',
		'RESTRICTEDTEXTFIELD.CHARCANTBEUSED': "V tem polju ni dovoljen znak '%s'",
		'UPDATEURL.CONFIRM': 'Ali želite, da spremenim URL v:\n\n%s/\n\nKliknite Ok za spremembo, oziroma Cancel, da ostane:\n\n%s',
		'UPDATEURL.CONFIRM_V2': 'Ali želite, da spremenim URL tako, da bo ustrezal novemu naslovu strani?\n\nKliknite Ok za spremembo, oziroma Cancel, da ostane:\n\n%s', 
		'FILEIFRAMEFIELD.DELETEFILE': 'Izbriši datoteko',
		'FILEIFRAMEFIELD.UNATTACHFILE': 'Odstrani datoteko',
		'FILEIFRAMEFIELD.DELETEIMAGE': 'Izbriši sliko',
		'FILEIFRAMEFIELD.CONFIRMDELETE': 'Ali ste prepričani, da želite izbrisati datoteko?',
		'TINYMCEIMPROVEMENTS.SELECTANCHOR': 'Izberi sidro',
		'HtmlEditorField.HideUploadForm': 'Skrij obrazec za nalaganje',
		'HtmlEditorField.ShowUploadForm': 'Naloži datoteko'		
	});
}
