(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define('ss.LeftAndMain.Layout', ['jQuery'], factory);
  } else if (typeof exports !== "undefined") {
    factory(require('jQuery'));
  } else {
    var mod = {
      exports: {}
    };
    factory(global.jQuery);
    global.ssLeftAndMainLayout = mod.exports;
  }
})(this, function (_jQuery) {
  'use strict';

  var _jQuery2 = _interopRequireDefault(_jQuery);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  _jQuery2.default.fn.layout.defaults.resize = false;

  jLayout = typeof jLayout === 'undefined' ? {} : jLayout;

  jLayout.threeColumnCompressor = function (spec, options) {
    if (typeof spec.menu === 'undefined' || typeof spec.content === 'undefined' || typeof spec.preview === 'undefined') {
      throw 'Spec is invalid. Please provide "menu", "content" and "preview" elements.';
    }
    if (typeof options.minContentWidth === 'undefined' || typeof options.minPreviewWidth === 'undefined' || typeof options.mode === 'undefined') {
      throw 'Spec is invalid. Please provide "minContentWidth", "minPreviewWidth", "mode"';
    }
    if (options.mode !== 'split' && options.mode !== 'content' && options.mode !== 'preview') {
      throw 'Spec is invalid. "mode" should be either "split", "content" or "preview"';
    }

    var obj = {
      options: options
    };

    var menu = _jQuery2.default.jLayoutWrap(spec.menu),
        content = _jQuery2.default.jLayoutWrap(spec.content),
        preview = _jQuery2.default.jLayoutWrap(spec.preview);

    obj.layout = function (container) {
      var size = container.bounds(),
          insets = container.insets(),
          top = insets.top,
          bottom = size.height - insets.bottom,
          left = insets.left,
          right = size.width - insets.right;

      var menuWidth = spec.menu.width(),
          contentWidth = 0,
          previewWidth = 0;

      if (this.options.mode === 'preview') {
        contentWidth = 0;
        previewWidth = right - left - menuWidth;
      } else if (this.options.mode === 'content') {
        contentWidth = right - left - menuWidth;
        previewWidth = 0;
      } else {
        contentWidth = (right - left - menuWidth) / 2;
        previewWidth = right - left - (menuWidth + contentWidth);

        if (contentWidth < this.options.minContentWidth) {
          contentWidth = this.options.minContentWidth;
          previewWidth = right - left - (menuWidth + contentWidth);
        } else if (previewWidth < this.options.minPreviewWidth) {
          previewWidth = this.options.minPreviewWidth;
          contentWidth = right - left - (menuWidth + previewWidth);
        }

        if (contentWidth < this.options.minContentWidth || previewWidth < this.options.minPreviewWidth) {
          contentWidth = right - left - menuWidth;
          previewWidth = 0;
        }
      }

      var prehidden = {
        content: spec.content.hasClass('column-hidden'),
        preview: spec.preview.hasClass('column-hidden')
      };

      var posthidden = {
        content: contentWidth === 0,
        preview: previewWidth === 0
      };

      spec.content.toggleClass('column-hidden', posthidden.content);
      spec.preview.toggleClass('column-hidden', posthidden.preview);

      menu.bounds({ 'x': left, 'y': top, 'height': bottom - top, 'width': menuWidth });
      menu.doLayout();

      left += menuWidth;

      content.bounds({ 'x': left, 'y': top, 'height': bottom - top, 'width': contentWidth });
      if (!posthidden.content) content.doLayout();

      left += contentWidth;

      preview.bounds({ 'x': left, 'y': top, 'height': bottom - top, 'width': previewWidth });
      if (!posthidden.preview) preview.doLayout();

      if (posthidden.content !== prehidden.content) spec.content.trigger('columnvisibilitychanged');
      if (posthidden.preview !== prehidden.preview) spec.preview.trigger('columnvisibilitychanged');

      if (contentWidth + previewWidth < options.minContentWidth + options.minPreviewWidth) {
        spec.preview.trigger('disable');
      } else {
        spec.preview.trigger('enable');
      }

      return container;
    };

    function typeLayout(type) {
      var func = type + 'Size';

      return function (container) {
        var menuSize = menu[func](),
            contentSize = content[func](),
            previewSize = preview[func](),
            insets = container.insets();

        width = menuSize.width + contentSize.width + previewSize.width;
        height = Math.max(menuSize.height, contentSize.height, previewSize.height);

        return {
          'width': insets.left + insets.right + width,
          'height': insets.top + insets.bottom + height
        };
      };
    }

    obj.preferred = typeLayout('preferred');
    obj.minimum = typeLayout('minimum');
    obj.maximum = typeLayout('maximum');

    return obj;
  };
});