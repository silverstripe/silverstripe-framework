if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('de_DE', {
		'VALIDATOR.FIELDREQUIRED': '"%s" wird benötigt',
		'HASMANYFILEFIELD.UPLOADING': 'Lädt hoch... %s',
		'TABLEFIELD.DELETECONFIRMMESSAGE': 'Sind Sie sicher, dass sie dieses Element löschen wollen?',
		'LOADING': 'Lädt...',
		'UNIQUEFIELD.SUGGESTED': "Der Wert wurde nach '%s' geändert : %s",
		'UNIQUEFIELD.ENTERNEWVALUE': 'Sie müssen einen neuen Wert für dieses Feld eingeben',
		'UNIQUEFIELD.CANNOTLEAVEEMPTY': 'Dieses Feld kann nicht leer sein',
		'RESTRICTEDTEXTFIELD.CHARCANTBEUSED': "Das Zeichen '%s' darf in diesem Feld nicht vewendet werden",
		'UPDATEURL.CONFIRM': 'Sollen wir die URL in diesen Wert ändern:\n\n%s/\n\nKlicken Sie OK, um den URL zu ändern, Abbrechen um ihn so zu lassen:\n\n%s',
		'UPDATEURL.CONFIRMURLCHANGED':'Die URL wurde geändert:\n"%s"',
		'FILEIFRAMEFIELD.DELETEFILE': 'Datei löschen',
		'FILEIFRAMEFIELD.UNATTACHFILE': 'Datei loslösen',
		'FILEIFRAMEFIELD.DELETEIMAGE': 'Bild löschen',
		'FILEIFRAMEFIELD.CONFIRMDELETE': 'Sind Sie sicher, dass sie diese Datei löschen wollen?',
		'LeftAndMain.IncompatBrowserWarning': 'Ihr Browser ist nicht kompatibel mit der CMS Benutzeroverfläche. Bitte benutzen sie Internet Explorer 7+, Google Chrome 10+ oder Mozilla Firefox 3.5+.',
		'GRIDFIELD.ERRORINTRANSACTION': 'Beim Laden der Daten vom Server ist ein Fehler aufgetretetn\n Bitte versuchen sie es später noch einmal.',
		'UploadField.ConfirmDelete': 'Sind sie sicher, dass sie diese Datei aus dem Dateisystem löschen wollen?',
		'UploadField.PHP_MAXFILESIZE': 'Die Dateigröße überschreitet upload_max_filesize (php.ini Einstellung)',
		'UploadField.HTML_MAXFILESIZE': 'Die Dateigröße überschreitet MAX_FILE_SIZE (HTML Form Einstellung)',
		'UploadField.ONLYPARTIALUPLOADED': 'Die Datei wurde nur teilweise hochgeladen',
		'UploadField.NOFILEUPLOADED': 'Keine Datei wurde hochgeladen',
		'UploadField.NOTMPFOLDER': 'Es wurde kein temporäres Verzeichnis gefunden',
		'UploadField.WRITEFAILED': 'Es konnte nicht auf die Festplatte geschrieben werden',
		'UploadField.STOPEDBYEXTENSION': 'Dateiupload wurde wegen einer nicht erlaubten Erweiterung gestoppt',
		'UploadField.TOOLARGE': 'Die Datei ist zu groß',
		'UploadField.TOOSMALL': 'Die Datei ist zu klein',
		'UploadField.INVALIDEXTENSION': 'Dateierweiterung ist nicht erlaubt',
		'UploadField.MAXNUMBEROFFILESSIMPLE': 'Maximal erlaubt Anzahl von Dateien überschritten',
		'UploadField.UPLOADEDBYTES': 'Hochgeladene Bytes überschreiten Dateigröße',
		'UploadField.EMPTYRESULT': 'Leere Datei erhalten',
		'UploadField.LOADING': 'Lädt ...',
		'UploadField.Editing': 'Bearbeite ...',
		'UploadField.Uploaded': 'Hochgeladen'
	});
}