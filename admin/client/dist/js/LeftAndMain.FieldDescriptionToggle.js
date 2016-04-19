(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define('ss.LeftAndMain.FieldDescriptionToggle', ['jQuery'], factory);
  } else if (typeof exports !== "undefined") {
    factory(require('jQuery'));
  } else {
    var mod = {
      exports: {}
    };
    factory(global.jQuery);
    global.ssLeftAndMainFieldDescriptionToggle = mod.exports;
  }
})(this, function (_jQuery) {
  'use strict';

  var _jQuery2 = _interopRequireDefault(_jQuery);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  _jQuery2.default.entwine('ss', function ($) {

    $('.cms-description-toggle').entwine({
      onadd: function onadd() {
        var shown = false,
            fieldId = this.prop('id').substr(0, this.prop('id').indexOf('_Holder')),
            $trigger = this.find('.cms-description-trigger'),
            $description = this.find('.description');

        if (this.hasClass('description-toggle-enabled')) {
          return;
        }

        if ($trigger.length === 0) {
          $trigger = this.find('.middleColumn').first().after('<label class="right" for="' + fieldId + '"><a class="cms-description-trigger" href="javascript:void(0)"><span class="btn-icon-information"></span></a></label>').next();
        }

        this.addClass('description-toggle-enabled');

        $trigger.on('click', function () {
          $description[shown ? 'hide' : 'show']();
          shown = !shown;
        });

        $description.hide();
      }
    });
  });
});