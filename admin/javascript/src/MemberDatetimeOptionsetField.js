/* global jQuery */

import $ from 'jQuery'

$.entwine('ss', function ($) {
  $('.memberdatetimeoptionset').entwine({
    onmatch: function () {
      this.find('.description .toggle-content').hide()
      this._super()
    }
  })

  $('.memberdatetimeoptionset .toggle').entwine({
    onclick: function (e) {
      jQuery(this).closest('.description').find('.toggle-content').toggle()
      return false
    }
  })
})
