$(document).ready(function () {

  /**
   * Hide all existing database warnings, and show only current one
   */
  $('#database_selection > li > label, #database_selection > li > input:radio').click(function () {
    $('.dbfields').hide();
    // only show fields if there's no db error
    if (!$('.databaseError', $(this).parent()).length) {
      $('.dbfields', $(this).parent()).show();
    }
    $('.databaseError').hide();
    $('.databaseError', $(this).parent()).show();
  });

  // Handle install button
  $('#install_button').click(function (e) {
    // Confirm on re-install
    if (
      $(this).hasClass('mustconfirm')
      && !confirm('Are you sure you wish to replace the existing installation config?')
    ) {
      e.preventDefault();
      return false;
    }

    // Process
    $('#saving_top').hide();
    $(this).val('Installing SilverStripe...');
    return true;
  });

  /**
   * Show all the requirements
   */
  $('h5.requirement a').click(function () {
    if ($(this).text() === 'Hide All Requirements') {
      // hide the shown requirements
      $(this).parents('h5').next('table.testResults').find('.good').hide();
      $(this).text('Show All Requirements');
    } else {
      // show the requirements.
      $(this).parents('h5').next('table.testResults').find('.good').show();
      $(this).text('Hide All Requirements');
    }

    return false;
  });
});
