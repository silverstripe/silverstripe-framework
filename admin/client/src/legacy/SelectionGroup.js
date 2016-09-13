import $ from 'jQuery';

require('../styles/legacy/SelectionGroup.scss');


$(document).ready(function() {
  // Support both bootstrap / legacy selectors
	$('ul.SelectionGroup input.selector, ul.selection-group input.selector')
    .live('click', function() {
      var li = $(this).closest('li');
      li.addClass('selected');

      var prev = li.prevAll('li.selected');
      if(prev.length) {
        prev.removeClass('selected');
      }
      var next = li.nextAll('li.selected');
      if(next.length) {
        next.removeClass('selected');
      }

      $(this).focus();
    }
  );
});
