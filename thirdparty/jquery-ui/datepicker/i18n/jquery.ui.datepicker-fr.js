/*
 * French (UTF-8) initialisation for the jQuery UI date picker plugin.
 * Adapted to match the Zend Data localization for SilverStripe CMS
 * See: README
 */
jQuery(function($){
	$.datepicker.regional['fr'] = {
		closeText: 'Fermer',
		prevText: '&#x3c;Préc',
		nextText: 'Suiv&#x3e;',
		currentText: 'Courant',
		monthNames: ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'],
		monthNamesShort: ['janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'],
		dayNames: ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'],
		dayNamesShort: ['dim','lun','mar','mer','jeu','ven','sam'],
		dayNamesMin: ['Di','Lu','Ma','Me','Je','Ve','Sa'],
		weekHeader: 'Sm',
		dateFormat: 'dd/mm/yy',
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: ''};
	$.datepicker.setDefaults($.datepicker.regional['fr']);
});
