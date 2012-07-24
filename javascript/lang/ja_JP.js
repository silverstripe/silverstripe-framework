if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('ja_JP', {
		'VALIDATOR.FIELDREQUIRED': '"%s"を入力してください，必須項目です．',
		'HASMANYFILEFIELD.UPLOADING': 'アップロード中です... %s',
		'TABLEFIELD.DELETECONFIRMMESSAGE': 'このレコードを本当に削除しますか?',
		'LOADING': '読み込み中...',
		'UNIQUEFIELD.SUGGESTED': "'%s'へ値を変更しました : %s",
		'UNIQUEFIELD.ENTERNEWVALUE': 'このフィールドに新しい値を入力する必要があります．',
		'UNIQUEFIELD.CANNOTLEAVEEMPTY': 'このフィールドは空にすることができません．',
		'RESTRICTEDTEXTFIELD.CHARCANTBEUSED': "文字'%s'はこのフィールドでは利用することができません．",
		'UPDATEURL.CONFIRM': 'URLを次へ変更しますか?:\n\n%s/\n\nOKをクリックするとURLが変更されます．キャンセルをクリックするとURLは保持されます:\n\n%s',
		'UPDATEURL.CONFIRMURLCHANGED':'URLは次へ変更されました\n"%s"',
		'FILEIFRAMEFIELD.DELETEFILE': 'ファイルを削除',
		'FILEIFRAMEFIELD.UNATTACHFILE': 'Un-Attach File',
		'FILEIFRAMEFIELD.DELETEIMAGE': '画像を削除',
		'FILEIFRAMEFIELD.CONFIRMDELETE': 'このファイルを本当に削除しても良いですか?',
		'LeftAndMain.IncompatBrowserWarning': 'ご利用のブラウザはCMSのインターフェイスと互換性がありません．Internet Explorer 7以上, Google Chrome 10以上またはMozilla Firefox 3.5以上をご利用ください',
		'GRIDFIELD.ERRORINTRANSACTION': 'サーバーからデータを取得中にエラーが発生しました．\n 後ほど改めてお試しください．',
		'UploadField.ConfirmDelete': 'サーバーのファイルシステムからこのファイルを本当に削除しても良いですか?',
		'UploadField.PHP_MAXFILESIZE': 'upload_max_filesize(最大アップロードファイルサイズ)をファイルが超えています．(php.iniで指定されています)',
		'UploadField.HTML_MAXFILESIZE': 'MAX_FILE_SIZE(最大ファイルサイズ)をファイルが超えています．(HTMLフォームで指定されています)',
		'UploadField.ONLYPARTIALUPLOADED': 'ファイルは部分的にアップロードされました．',
		'UploadField.NOFILEUPLOADED': 'ファイルはアップロードされませんでした．',
		'UploadField.NOTMPFOLDER': '一時フォルダがありません．',
		'UploadField.WRITEFAILED': 'ディスクへのファイル書き込みに失敗しました．',
		'UploadField.STOPEDBYEXTENSION': '拡張子によりファイルアップロードが停止しました．',
		'UploadField.TOOLARGE': 'ファイルサイズが大きすぎます．',
		'UploadField.TOOSMALL': 'ファイルサイズが小さすぎます．',
		'UploadField.INVALIDEXTENSION': '拡張子は許可されていません．',
		'UploadField.MAXNUMBEROFFILESSIMPLE': 'ファイルの最大数を超えました．',
		'UploadField.UPLOADEDBYTES': 'アップロードされたバイトはファイルサイズを超えました．',
		'UploadField.EMPTYRESULT': 'Empty file upload result',
		'UploadField.LOADING': '読み込み中...',
		'UploadField.Editing': '編集中...',
		'UploadField.Uploaded': 'アップロードしました．'
	});
}
