/*
 * Norwegian (UTF-8) initialisation for the jQuery UI date picker plugin.
 * Adapted to match the Zend Data localization for SilverStripe CMS
 * See: README
 */
jQuery(function($){
    $.datepicker.regional['no'] = {
		closeText: 'Lukk',
        prevText: '&laquo;Forrige',
		nextText: 'Neste&raquo;',
		currentText: 'I dag',
        monthNames: ['januar','februar','mars','april','mai','juni','juli','august','september','oktober','november','desember'],
        monthNamesShort: ['jan.','feb.','mars','apr.','mai','juni','juli','aug.','sep.','okt.','nov.','des.'],
		dayNames: ['søndag','mandag','tirsdag','onsdag','torsdag','fredag','lørdag'],
		dayNamesShort: ['søn','man','tir','ons','tor','fre','lør'],
		dayNamesMin: ['Sø','Ma','Ti','On','To','Fr','Lø'],
		weekHeader: 'Uke',
        dateFormat: 'yy-mm-dd',
		firstDay: 0,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: ''};
    $.datepicker.setDefaults($.datepicker.regional['no']);
});
