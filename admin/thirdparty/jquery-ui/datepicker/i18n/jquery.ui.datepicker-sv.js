/*
 * Swedish (UTF-8) initialisation for the jQuery UI date picker plugin.
 * Adapted to match the Zend Data localization for SilverStripe CMS
 * See: README
 */
jQuery(function($){
    $.datepicker.regional['sv'] = {
		closeText: 'Stäng',
        prevText: '&laquo;Förra',
		nextText: 'Nästa&raquo;',
		currentText: 'Idag',
        monthNames: ['januari','februari','mars','april','maj','juni','juli','augusti','september','oktober','november','december'],
        monthNamesShort: ['jan','feb','mar','apr','maj','jun','jul','aug','sep','okt','nov','dec'],
		dayNames: ['söndag','måndag','tisdag','onsdag','torsdag','fredag','lördag'],
		dayNamesShort: ['sön','mån','tis','ons','tor','fre','lör'],
		dayNamesMin: ['Sö','Må','Ti','On','To','Fr','Lö'],
		weekHeader: 'Ve',
        dateFormat: 'yy-mm-dd',
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: ''};
    $.datepicker.setDefaults($.datepicker.regional['sv']);
});
