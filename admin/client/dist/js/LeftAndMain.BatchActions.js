(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define('ss.LeftAndMain.BatchActions', ['jQuery', 'i18n'], factory);
  } else if (typeof exports !== "undefined") {
    factory(require('jQuery'), require('i18n'));
  } else {
    var mod = {
      exports: {}
    };
    factory(global.jQuery, global.i18n);
    global.ssLeftAndMainBatchActions = mod.exports;
  }
})(this, function (_jQuery, _i18n) {
  'use strict';

  var _jQuery2 = _interopRequireDefault(_jQuery);

  var _i18n2 = _interopRequireDefault(_i18n);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  _jQuery2.default.entwine('ss.tree', function ($) {
    $('#Form_BatchActionsForm').entwine({
      Actions: [],

      getTree: function getTree() {
        return $('.cms-tree');
      },

      fromTree: {
        oncheck_node: function oncheck_node(e, data) {
          this.serializeFromTree();
        },
        onuncheck_node: function onuncheck_node(e, data) {
          this.serializeFromTree();
        }
      },

      registerDefault: function registerDefault() {
        this.register('admin/pages/batchactions/publish', function (ids) {
          var confirmed = confirm(_i18n2.default.inject(_i18n2.default._t("CMSMAIN.BATCH_PUBLISH_PROMPT", "You have {num} page(s) selected.\n\nDo you really want to publish?"), { 'num': ids.length }));
          return confirmed ? ids : false;
        });

        this.register('admin/pages/batchactions/unpublish', function (ids) {
          var confirmed = confirm(_i18n2.default.inject(_i18n2.default._t("CMSMAIN.BATCH_UNPUBLISH_PROMPT", "You have {num} page(s) selected.\n\nDo you really want to unpublish"), { 'num': ids.length }));
          return confirmed ? ids : false;
        });

        this.register('admin/pages/batchactions/delete', function (ids) {
          var confirmed = confirm(_i18n2.default.inject(_i18n2.default._t("CMSMAIN.BATCH_DELETE_PROMPT", "You have {num} page(s) selected.\n\nDo you really want to delete?"), { 'num': ids.length }));
          return confirmed ? ids : false;
        });

        this.register('admin/pages/batchactions/archive', function (ids) {
          var confirmed = confirm(_i18n2.default.inject(_i18n2.default._t("CMSMAIN.BATCH_ARCHIVE_PROMPT", "You have {num} page(s) selected.\n\nAre you sure you want to archive these pages?\n\nThese pages and all of their children pages will be unpublished and sent to the archive."), { 'num': ids.length }));
          return confirmed ? ids : false;
        });

        this.register('admin/pages/batchactions/restore', function (ids) {
          var confirmed = confirm(_i18n2.default.inject(_i18n2.default._t("CMSMAIN.BATCH_RESTORE_PROMPT", "You have {num} page(s) selected.\n\nDo you really want to restore to stage?\n\nChildren of archived pages will be restored to the root level, unless those pages are also being restored."), { 'num': ids.length }));
          return confirmed ? ids : false;
        });

        this.register('admin/pages/batchactions/deletefromlive', function (ids) {
          var confirmed = confirm(_i18n2.default.inject(_i18n2.default._t("CMSMAIN.BATCH_DELETELIVE_PROMPT", "You have {num} page(s) selected.\n\nDo you really want to delete these pages from live?"), { 'num': ids.length }));
          return confirmed ? ids : false;
        });
      },

      onadd: function onadd() {
        this.registerDefault();
        this._super();
      },

      register: function register(type, callback) {
        this.trigger('register', { type: type, callback: callback });
        var actions = this.getActions();
        actions[type] = callback;
        this.setActions(actions);
      },

      unregister: function unregister(type) {
        this.trigger('unregister', { type: type });

        var actions = this.getActions();
        if (actions[type]) delete actions[type];
        this.setActions(actions);
      },

      refreshSelected: function refreshSelected(rootNode) {
        var self = this,
            st = this.getTree(),
            ids = this.getIDs(),
            allIds = [],
            viewMode = $('.cms-content-batchactions-button'),
            actionUrl = this.find(':input[name=Action]').val();

        if (rootNode == null) rootNode = st;

        for (var idx in ids) {
          $($(st).getNodeByID(idx)).addClass('selected').attr('selected', 'selected');
        }

        if (!actionUrl || actionUrl == -1 || !viewMode.hasClass('active')) {
          $(rootNode).find('li').each(function () {
            $(this).setEnabled(true);
          });
          return;
        }

        $(rootNode).find('li').each(function () {
          allIds.push($(this).data('id'));
          $(this).addClass('treeloading').setEnabled(false);
        });

        var actionUrlParts = $.path.parseUrl(actionUrl);
        var applicablePagesUrl = actionUrlParts.hrefNoSearch + '/applicablepages/';
        applicablePagesUrl = $.path.addSearchParams(applicablePagesUrl, actionUrlParts.search);
        applicablePagesUrl = $.path.addSearchParams(applicablePagesUrl, { csvIDs: allIds.join(',') });
        jQuery.getJSON(applicablePagesUrl, function (applicableIDs) {
          jQuery(rootNode).find('li').each(function () {
            $(this).removeClass('treeloading');

            var id = $(this).data('id');
            if (id == 0 || $.inArray(id, applicableIDs) >= 0) {
              $(this).setEnabled(true);
            } else {
              $(this).removeClass('selected').setEnabled(false);
              $(this).prop('selected', false);
            }
          });

          self.serializeFromTree();
        });
      },

      serializeFromTree: function serializeFromTree() {
        var tree = this.getTree(),
            ids = tree.getSelectedIDs();

        this.setIDs(ids);

        return true;
      },

      setIDs: function setIDs(ids) {
        this.find(':input[name=csvIDs]').val(ids ? ids.join(',') : null);
      },

      getIDs: function getIDs() {
        var value = this.find(':input[name=csvIDs]').val();
        return value ? value.split(',') : [];
      },

      onsubmit: function onsubmit(e) {
        var self = this,
            ids = this.getIDs(),
            tree = this.getTree(),
            actions = this.getActions();

        if (!ids || !ids.length) {
          alert(_i18n2.default._t('CMSMAIN.SELECTONEPAGE', 'Please select at least one page'));
          e.preventDefault();
          return false;
        }

        var type = this.find(':input[name=Action]').val();
        if (actions[type]) {
          ids = this.getActions()[type].apply(this, [ids]);
        }

        if (!ids || !ids.length) {
          e.preventDefault();
          return false;
        }

        this.setIDs(ids);

        tree.find('li').removeClass('failed');

        var button = this.find(':submit:first');
        button.addClass('loading');

        jQuery.ajax({
          url: type,
          type: 'POST',
          data: this.serializeArray(),
          complete: function complete(xmlhttp, status) {
            button.removeClass('loading');

            tree.jstree('refresh', -1);
            self.setIDs([]);

            self.find(':input[name=Action]').val('').change();

            var msg = xmlhttp.getResponseHeader('X-Status');
            if (msg) statusMessage(decodeURIComponent(msg), status == 'success' ? 'good' : 'bad');
          },
          success: function success(data, status) {
            var id, node;

            if (data.modified) {
              var modifiedNodes = [];
              for (id in data.modified) {
                node = tree.getNodeByID(id);
                tree.jstree('set_text', node, data.modified[id]['TreeTitle']);
                modifiedNodes.push(node);
              }
              $(modifiedNodes).effect('highlight');
            }
            if (data.deleted) {
              for (id in data.deleted) {
                node = tree.getNodeByID(id);
                if (node.length) tree.jstree('delete_node', node);
              }
            }
            if (data.error) {
              for (id in data.error) {
                node = tree.getNodeByID(id);
                $(node).addClass('failed');
              }
            }
          },
          dataType: 'json'
        });

        e.preventDefault();
        return false;
      }

    });

    $('.cms-content-batchactions-button').entwine({
      onmatch: function onmatch() {
        this._super();
        this.updateTree();
      },
      onunmatch: function onunmatch() {
        this._super();
      },
      onclick: function onclick(e) {
        this.updateTree();
      },
      updateTree: function updateTree() {
        var tree = $('.cms-tree'),
            form = $('#Form_BatchActionsForm');

        this._super();

        if (this.data('active')) {
          tree.addClass('multiple');
          tree.removeClass('draggable');
          form.serializeFromTree();
        } else {
          tree.removeClass('multiple');
          tree.addClass('draggable');
        }

        $('#Form_BatchActionsForm').refreshSelected();
      }
    });

    $('#Form_BatchActionsForm select[name=Action]').entwine({
      onchange: function onchange(e) {
        var form = $(e.target.form),
            btn = form.find(':submit'),
            selected = $(e.target).val();
        if (!selected || selected == -1) {
          btn.attr('disabled', 'disabled').button('refresh');
        } else {
          btn.removeAttr('disabled').button('refresh');
        }

        $('#Form_BatchActionsForm').refreshSelected();

        this.trigger("chosen:updated");

        this._super(e);
      }
    });
  });
});