if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('ja_JP', {
		'LeftAndMain.CONFIRMUNSAVED': "このページから移動しても良いですか?\n\n警告: あなたの変更は保存されていません．\n\n続行するにはOKを押してください．キャンセルをクリックするとこのページにとどまります．",
		'LeftAndMain.CONFIRMUNSAVEDSHORT': "警告: あなたの変更は保存されていません．",
		'SecurityAdmin.BATCHACTIONSDELETECONFIRM': "%sグループを本当に削除しても良いですか?",
		'ModelAdmin.SAVED': "保存しました",
		'ModelAdmin.REALLYDELETE': "本当に削除しますか?",
		'ModelAdmin.DELETED': "削除しました",
		'ModelAdmin.VALIDATIONERROR': "検証エラー",
		'LeftAndMain.PAGEWASDELETED': "このページは削除されました．ページを編集するには，左から選択してください．"
	});
}
