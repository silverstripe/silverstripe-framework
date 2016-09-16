/*
 * Spanish (UTF-8) initialisation for the jQuery UI date picker plugin.
 * Adapted to match the Zend Data localization for SilverStripe CMS
 * See: README
 */
jQuery(function($){
	$.datepicker.regional['es'] = {
		closeText: 'Cerrar',
		prevText: '&#x3c;Ant',
		nextText: 'Sig&#x3e;',
		currentText: 'Hoy',
		monthNames: ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'],
		monthNamesShort: ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'],
		dayNames: ['domingo','lunes','martes','mi&eacute;rcoles','jueves','viernes','s&aacute;bado'],
		dayNamesShort: ['dom','lun','mar','mi&eacute;','juv','vie','s&aacute;b'],
		dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','S&aacute;'],
		weekHeader: 'Sm',
		dateFormat: 'dd/mm/yy',
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: ''};
	$.datepicker.setDefaults($.datepicker.regional['es']);
});



