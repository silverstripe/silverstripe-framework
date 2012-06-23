if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	console.error('Class ss.i18n not defined');
} else {
	ss.i18n.addDictionary('fr_FR', {
		'ModelAdmin.SAVED': "Sauvegardé",
		'ModelAdmin.REALLYDELETE': "Etes-vous sûr de vouloir supprimer ?",
		'ModelAdmin.DELETED': "Supprimé",
		'LeftAndMain.PAGEWASDELETED': "Cette page a été supprimée. Pour éditer cette page, veuillez la sélectionner à gauche.",
		'LeftAndMain.CONFIRMUNSAVED': "Etes-vous sûr de vouloir quitter cette page ?\n\nATTENTION: Vos changements n'ont pas été sauvegardés.\n\nCliquez sur OK pour continuer, ou sur Annuler pour rester sur la page actuelle."
	});
}