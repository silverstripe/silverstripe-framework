import $ from 'jQuery';

// entwine also required, but can't be included more than once without error
require('../../../thirdparty/jquery-ui/jquery-ui.js');

$.fn.extend({
  ssDatepicker: function(opts) {
    return $(this).each(function() {

      // disabled, readonly or already applied
      if ($(this).prop('disabled') || $(this).prop('readonly') || $(this).hasClass('hasDatepicker')) {
        return;
      }

      $(this).siblings("button").addClass("ui-icon ui-icon-calendar");

      let config = $.extend(
          {},
          opts || {},
          $(this).data(),
          $(this).data('jqueryuiconfig')
        );
      if(!config.showcalendar) {
        return;
      }

      if(config.locale && $.datepicker.regional[config.locale]) {
        // Note: custom config overrides regional settings
        config = $.extend({}, $.datepicker.regional[config.locale], config);
      }

      // Initialize and open a datepicker
      // live() doesn't have "onmatch", and jQuery.entwine is a bit too heavyweight
      // for this, so we need to do this onclick.
      $(this).datepicker(config);
    });
  }
});

$(document).on("click", ".field.date input.text,input.text.date", function() {
  $(this).ssDatepicker();

  if($(this).data('datepicker')) {
    $(this).datepicker('show');
  }
});
