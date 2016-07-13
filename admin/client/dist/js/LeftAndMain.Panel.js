(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define('ss.LeftAndMain.Panel', ['jQuery'], factory);
  } else if (typeof exports !== "undefined") {
    factory(require('jQuery'));
  } else {
    var mod = {
      exports: {}
    };
    factory(global.jQuery);
    global.ssLeftAndMainPanel = mod.exports;
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
    $.entwine.warningLevel = $.entwine.WARN_LEVEL_BESTPRACTISE;

    $('.cms-panel').entwine({

      WidthExpanded: null,

      WidthCollapsed: null,

      canSetCookie: function canSetCookie() {
        return $.cookie !== void 0 && this.attr('id') !== void 0;
      },

      getPersistedCollapsedState: function getPersistedCollapsedState() {
        var isCollapsed, cookieValue;

        if (this.canSetCookie()) {
          cookieValue = $.cookie('cms-panel-collapsed-' + this.attr('id'));

          if (cookieValue !== void 0 && cookieValue !== null) {
            isCollapsed = cookieValue === 'true';
          }
        }

        return isCollapsed;
      },

      setPersistedCollapsedState: function setPersistedCollapsedState(newState) {
        if (this.canSetCookie()) {
          $.cookie('cms-panel-collapsed-' + this.attr('id'), newState, { path: '/', expires: 31 });
        }
      },

      clearPersistedCollapsedState: function clearPersistedCollapsedState() {
        if (this.canSetCookie()) {
          $.cookie('cms-panel-collapsed-' + this.attr('id'), '', { path: '/', expires: -1 });
        }
      },

      getInitialCollapsedState: function getInitialCollapsedState() {
        var isCollapsed = this.getPersistedCollapsedState();

        if (isCollapsed === void 0) {
          isCollapsed = this.hasClass('collapsed');
        }

        return isCollapsed;
      },

      onadd: function onadd() {
        var collapsedContent, container;

        if (!this.find('.cms-panel-content').length) throw new Exception('Content panel for ".cms-panel" not found');

        if (!this.find('.cms-panel-toggle').length) {
          container = $("<div class='cms-panel-toggle south'></div>").append('<a class="toggle-expand" href="#"><span>&raquo;</span></a>').append('<a class="toggle-collapse" href="#"><span>&laquo;</span></a>');

          this.append(container);
        }

        this.setWidthExpanded(this.find('.cms-panel-content').innerWidth());

        collapsedContent = this.find('.cms-panel-content-collapsed');
        this.setWidthCollapsed(collapsedContent.length ? collapsedContent.innerWidth() : this.find('.toggle-expand').innerWidth());

        this.togglePanel(!this.getInitialCollapsedState(), true, false);

        this._super();
      },

      togglePanel: function togglePanel(doExpand, silent, doSaveState) {
        var newWidth, collapsedContent;

        if (!silent) {
          this.trigger('beforetoggle.sspanel', doExpand);
          this.trigger(doExpand ? 'beforeexpand' : 'beforecollapse');
        }

        this.toggleClass('collapsed', !doExpand);
        newWidth = doExpand ? this.getWidthExpanded() : this.getWidthCollapsed();

        this.width(newWidth);
        collapsedContent = this.find('.cms-panel-content-collapsed');
        if (collapsedContent.length) {
          this.find('.cms-panel-content')[doExpand ? 'show' : 'hide']();
          this.find('.cms-panel-content-collapsed')[doExpand ? 'hide' : 'show']();
        }

        if (doSaveState !== false) {
          this.setPersistedCollapsedState(!doExpand);
        }

        this.trigger('toggle', doExpand);
        this.trigger(doExpand ? 'expand' : 'collapse');
      },

      expandPanel: function expandPanel(force) {
        if (!force && !this.hasClass('collapsed')) return;

        this.togglePanel(true);
      },

      collapsePanel: function collapsePanel(force) {
        if (!force && this.hasClass('collapsed')) return;

        this.togglePanel(false);
      }
    });

    $('.cms-panel.collapsed .cms-panel-toggle').entwine({
      onclick: function onclick(e) {
        this.expandPanel();
        e.preventDefault();
      }
    });

    $('.cms-panel *').entwine({
      getPanel: function getPanel() {
        return this.parents('.cms-panel:first');
      }
    });

    $('.cms-panel .toggle-expand').entwine({
      onclick: function onclick(e) {
        e.preventDefault();
        e.stopPropagation();

        this.getPanel().expandPanel();

        this._super(e);
      }
    });

    $('.cms-panel .toggle-collapse').entwine({
      onclick: function onclick(e) {
        e.preventDefault();
        e.stopPropagation();

        this.getPanel().collapsePanel();

        this._super(e);
      }
    });

    $('.cms-content-tools.collapsed').entwine({
      onclick: function onclick(e) {
        this.expandPanel();
        this._super(e);
      }
    });
  });
});