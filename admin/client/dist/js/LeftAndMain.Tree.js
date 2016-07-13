(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define('ss.LeftAndMain.Tree', ['jQuery'], factory);
  } else if (typeof exports !== "undefined") {
    factory(require('jQuery'));
  } else {
    var mod = {
      exports: {}
    };
    factory(global.jQuery);
    global.ssLeftAndMainTree = mod.exports;
  }
})(this, function (_jQuery) {
  'use strict';

  var _jQuery2 = _interopRequireDefault(_jQuery);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  _jQuery2.default.entwine('ss.tree', function ($) {

    $('.cms-tree').entwine({

      Hints: null,

      IsUpdatingTree: false,

      IsLoaded: false,

      onadd: function onadd() {
        this._super();

        if ($.isNumeric(this.data('jstree_instance_id'))) return;

        var hints = this.attr('data-hints');
        if (hints) this.setHints($.parseJSON(hints));

        var self = this;
        this.jstree(this.getTreeConfig()).bind('loaded.jstree', function (e, data) {
          self.setIsLoaded(true);

          data.inst._set_settings({ 'html_data': { 'ajax': {
                'url': self.data('urlTree'),
                'data': function data(node) {
                  var params = self.data('searchparams') || [];

                  params = $.grep(params, function (n, i) {
                    return n.name != 'ID' && n.name != 'value';
                  });
                  params.push({ name: 'ID', value: $(node).data("id") ? $(node).data("id") : 0 });
                  params.push({ name: 'ajax', value: 1 });
                  return params;
                }
              } } });

          self.updateFromEditForm();
          self.css('visibility', 'visible');

          data.inst.hide_checkboxes();
        }).bind('before.jstree', function (e, data) {
          if (data.func == 'start_drag') {
            if (!self.hasClass('draggable') || self.hasClass('multiselect')) {
              e.stopImmediatePropagation();
              return false;
            }
          }

          if ($.inArray(data.func, ['check_node', 'uncheck_node'])) {
            var node = $(data.args[0]).parents('li:first');
            var allowedChildren = node.find('li:not(.disabled)');

            if (node.hasClass('disabled') && allowedChildren == 0) {
              e.stopImmediatePropagation();
              return false;
            }
          }
        }).bind('move_node.jstree', function (e, data) {
          if (self.getIsUpdatingTree()) return;

          var movedNode = data.rslt.o,
              newParentNode = data.rslt.np,
              oldParentNode = data.inst._get_parent(movedNode),
              newParentID = $(newParentNode).data('id') || 0,
              nodeID = $(movedNode).data('id');
          var siblingIDs = $.map($(movedNode).siblings().andSelf(), function (el) {
            return $(el).data('id');
          });

          $.ajax({
            'url': $.path.addSearchParams(self.data('urlSavetreenode'), self.data('extraParams')),
            'type': 'POST',
            'data': {
              ID: nodeID,
              ParentID: newParentID,
              SiblingIDs: siblingIDs
            },
            success: function success() {
              if ($('.cms-edit-form :input[name=ID]').val() == nodeID) {
                $('.cms-edit-form :input[name=ParentID]').val(newParentID);
              }
              self.updateNodesFromServer([nodeID]);
            },
            statusCode: {
              403: function _() {
                $.jstree.rollback(data.rlbk);
              }
            }
          });
        }).bind('select_node.jstree check_node.jstree uncheck_node.jstree', function (e, data) {
          $(document).triggerHandler(e, data);
        });
      },
      onremove: function onremove() {
        this.jstree('destroy');
        this._super();
      },

      'from .cms-container': {
        onafterstatechange: function onafterstatechange(e) {
          this.updateFromEditForm();
        }
      },

      'from .cms-container form': {
        onaftersubmitform: function onaftersubmitform(e) {
          var id = $('.cms-edit-form :input[name=ID]').val();

          this.updateNodesFromServer([id]);
        }
      },

      getTreeConfig: function getTreeConfig() {
        var self = this;
        return {
          'core': {
            'initially_open': ['record-0'],
            'animation': 0,
            'html_titles': true
          },
          'html_data': {},
          'ui': {
            "select_limit": 1,
            'initially_select': [this.find('.current').attr('id')]
          },
          "crrm": {
            'move': {
              'check_move': function check_move(data) {
                var movedNode = $(data.o),
                    newParent = $(data.np),
                    isMovedOntoContainer = data.ot.get_container()[0] == data.np[0],
                    movedNodeClass = movedNode.getClassname(),
                    newParentClass = newParent.getClassname(),
                    hints = self.getHints(),
                    disallowedChildren = [],
                    hintKey = newParentClass ? newParentClass : 'Root',
                    hint = hints && typeof hints[hintKey] != 'undefined' ? hints[hintKey] : null;

                if (hint && movedNode.attr('class').match(/VirtualPage-([^\s]*)/)) movedNodeClass = RegExp.$1;

                if (hint) disallowedChildren = typeof hint.disallowedChildren != 'undefined' ? hint.disallowedChildren : [];
                var isAllowed = movedNode.data('id') !== 0 && !movedNode.hasClass('status-archived') && (!isMovedOntoContainer || data.p == 'inside') && !newParent.hasClass('nochildren') && (!disallowedChildren.length || $.inArray(movedNodeClass, disallowedChildren) == -1);

                return isAllowed;
              }
            }
          },
          'dnd': {
            "drop_target": false,
            "drag_target": false
          },
          'checkbox': {
            'two_state': true
          },
          'themes': {
            'theme': 'apple',
            'url': $('body').data('frameworkpath') + '/thirdparty/jstree/themes/apple/style.css'
          },

          'plugins': ['html_data', 'ui', 'dnd', 'crrm', 'themes', 'checkbox']
        };
      },

      search: function search(params, callback) {
        if (params) this.data('searchparams', params);else this.removeData('searchparams');
        this.jstree('refresh', -1, callback);
      },

      getNodeByID: function getNodeByID(id) {
        return this.find('*[data-id=' + id + ']');
      },

      createNode: function createNode(html, data, callback) {
        var self = this,
            parentNode = data.ParentID !== void 0 ? self.getNodeByID(data.ParentID) : false,
            newNode = $(html);

        var properties = { data: '' };
        if (newNode.hasClass('jstree-open')) {
          properties.state = 'open';
        } else if (newNode.hasClass('jstree-closed')) {
          properties.state = 'closed';
        }
        this.jstree('create_node', parentNode.length ? parentNode : -1, 'last', properties, function (node) {
          var origClasses = node.attr('class');

          for (var i = 0; i < newNode[0].attributes.length; i++) {
            var attr = newNode[0].attributes[i];
            node.attr(attr.name, attr.value);
          }

          node.addClass(origClasses).html(newNode.html());
          callback(node);
        });
      },

      updateNode: function updateNode(node, html, data) {
        var self = this,
            newNode = $(html);

        var nextNode = data.NextID ? this.getNodeByID(data.NextID) : false;
        var prevNode = data.PrevID ? this.getNodeByID(data.PrevID) : false;
        var parentNode = data.ParentID ? this.getNodeByID(data.ParentID) : false;

        $.each(['id', 'style', 'class', 'data-pagetype'], function (i, attrName) {
          node.attr(attrName, newNode.attr(attrName));
        });

        var origChildren = node.children('ul').detach();
        node.html(newNode.html()).append(origChildren);

        if (nextNode && nextNode.length) {
          this.jstree('move_node', node, nextNode, 'before');
        } else if (prevNode && prevNode.length) {
          this.jstree('move_node', node, prevNode, 'after');
        } else {
          this.jstree('move_node', node, parentNode.length ? parentNode : -1);
        }
      },

      updateFromEditForm: function updateFromEditForm() {
        var node,
            id = $('.cms-edit-form :input[name=ID]').val();
        if (id) {
          node = this.getNodeByID(id);
          if (node.length) {
            this.jstree('deselect_all');
            this.jstree('select_node', node);
          } else {
            this.updateNodesFromServer([id]);
          }
        } else {
          this.jstree('deselect_all');
        }
      },

      updateNodesFromServer: function updateNodesFromServer(ids) {
        if (this.getIsUpdatingTree() || !this.getIsLoaded()) return;

        var self = this,
            i,
            includesNewNode = false;
        this.setIsUpdatingTree(true);
        self.jstree('save_selected');

        var correctStateFn = function correctStateFn(node) {
          self.getNodeByID(node.data('id')).not(node).remove();

          self.jstree('deselect_all');
          self.jstree('select_node', node);
        };

        self.jstree('open_node', this.getNodeByID(0));
        self.jstree('save_opened');
        self.jstree('save_selected');

        $.ajax({
          url: $.path.addSearchParams(this.data('urlUpdatetreenodes'), 'ids=' + ids.join(',')),
          dataType: 'json',
          success: function success(data, xhr) {
            $.each(data, function (nodeId, nodeData) {
              var node = self.getNodeByID(nodeId);

              if (!nodeData) {
                self.jstree('delete_node', node);
                return;
              }

              if (node.length) {
                self.updateNode(node, nodeData.html, nodeData);
                setTimeout(function () {
                  correctStateFn(node);
                }, 500);
              } else {
                includesNewNode = true;

                if (nodeData.ParentID && !self.find('li[data-id=' + nodeData.ParentID + ']').length) {
                  self.jstree('load_node', -1, function () {
                    newNode = self.find('li[data-id=' + nodeId + ']');
                    correctStateFn(newNode);
                  });
                } else {
                  self.createNode(nodeData.html, nodeData, function (newNode) {
                    correctStateFn(newNode);
                  });
                }
              }
            });

            if (!includesNewNode) {
              self.jstree('deselect_all');
              self.jstree('reselect');
              self.jstree('reopen');
            }
          },
          complete: function complete() {
            self.setIsUpdatingTree(false);
          }
        });
      }

    });

    $('.cms-tree.multiple').entwine({
      onmatch: function onmatch() {
        this._super();
        this.jstree('show_checkboxes');
      },
      onunmatch: function onunmatch() {
        this._super();
        this.jstree('uncheck_all');
        this.jstree('hide_checkboxes');
      },

      getSelectedIDs: function getSelectedIDs() {
        return $(this).jstree('get_checked').not('.disabled').map(function () {
          return $(this).data('id');
        }).get();
      }
    });

    $('.cms-tree li').entwine({
      setEnabled: function setEnabled(bool) {
        this.toggleClass('disabled', !bool);
      },

      getClassname: function getClassname() {
        var matches = this.attr('class').match(/class-([^\s]*)/i);
        return matches ? matches[1] : '';
      },

      getID: function getID() {
        return this.data('id');
      }
    });
  });
});