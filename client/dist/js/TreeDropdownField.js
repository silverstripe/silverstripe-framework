(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.TreeDropdownField', ['./jQuery', './i18n'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('./jQuery'), require('./i18n'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery, global.i18n);
		global.ssTreeDropdownField = mod.exports;
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

	_jQuery2.default.entwine('ss', function ($) {
		var windowWidth, windowHeight;
		$(window).bind('resize.treedropdownfield', function () {
			var cb = function cb() {
				$('.TreeDropdownField').closePanel();
			};

			if ($.browser.msie && parseInt($.browser.version, 10) < 9) {
				var newWindowWidth = $(window).width(),
				    newWindowHeight = $(window).height();
				if (newWindowWidth != windowWidth || newWindowHeight != windowHeight) {
					windowWidth = newWindowWidth;
					windowHeight = newWindowHeight;
					cb();
				}
			} else {
				cb();
			}
		});

		var strings = {
			'openlink': _i18n2.default._t('TreeDropdownField.OpenLink'),
			'fieldTitle': '(' + _i18n2.default._t('TreeDropdownField.FieldTitle') + ')',
			'searchFieldTitle': '(' + _i18n2.default._t('TreeDropdownField.SearchFieldTitle') + ')'
		};

		var _clickTestFn = function _clickTestFn(e) {
			if (!$(e.target).parents('.TreeDropdownField').length) $('.TreeDropdownField').closePanel();
		};

		$('.TreeDropdownField').entwine({
			CurrentXhr: null,

			onadd: function onadd() {
				this.append('<span class="treedropdownfield-title"></span>' + '<div class="treedropdownfield-toggle-panel-link"><a href="#" class="ui-icon ui-icon-triangle-1-s"></a></div>' + '<div class="treedropdownfield-panel"><div class="tree-holder"></div></div>');

				var linkTitle = strings.openLink;
				if (linkTitle) this.find("treedropdownfield-toggle-panel-link a").attr('title', linkTitle);
				if (this.data('title')) this.setTitle(this.data('title'));

				this.getPanel().hide();
				this._super();
			},
			getPanel: function getPanel() {
				return this.find('.treedropdownfield-panel');
			},
			openPanel: function openPanel() {
				$('.TreeDropdownField').closePanel();

				$('body').bind('click', _clickTestFn);

				var panel = this.getPanel(),
				    tree = this.find('.tree-holder');

				panel.css('width', this.width());

				panel.show();

				var toggle = this.find(".treedropdownfield-toggle-panel-link");
				toggle.addClass('treedropdownfield-open-tree');
				this.addClass("treedropdownfield-open-tree");

				toggle.find("a").removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-n');

				if (tree.is(':empty') && !panel.hasClass('loading')) {
					this.loadTree(null, this._riseUp);
				} else {
					this._riseUp();
				}

				this.trigger('panelshow');
			},
			_riseUp: function _riseUp() {
				var container = this,
				    dropdown = this.getPanel(),
				    toggle = this.find(".treedropdownfield-toggle-panel-link"),
				    offsetTop = toggle.innerHeight(),
				    elHeight,
				    elPos,
				    endOfWindow;

				if (toggle.length > 0) {
					endOfWindow = $(window).height() + $(document).scrollTop() - toggle.innerHeight();
					elPos = toggle.offset().top;
					elHeight = dropdown.innerHeight();

					if (elPos + elHeight > endOfWindow && elPos - elHeight > 0) {
						container.addClass('treedropdownfield-with-rise');
						offsetTop = -dropdown.outerHeight();
					} else {
						container.removeClass('treedropdownfield-with-rise');
					}
				}
				dropdown.css({ "top": offsetTop + "px" });
			},
			closePanel: function closePanel() {
				jQuery('body').unbind('click', _clickTestFn);

				var toggle = this.find(".treedropdownfield-toggle-panel-link");
				toggle.removeClass('treedropdownfield-open-tree');
				this.removeClass('treedropdownfield-open-tree treedropdownfield-with-rise');

				toggle.find("a").removeClass('ui-icon-triangle-1-n').addClass('ui-icon-triangle-1-s');

				this.getPanel().hide();
				this.trigger('panelhide');
			},
			togglePanel: function togglePanel() {
				this[this.getPanel().is(':visible') ? 'closePanel' : 'openPanel']();
			},
			setTitle: function setTitle(title) {
				title = title || this.data('title') || strings.fieldTitle;

				this.find('.treedropdownfield-title').html(title);
				this.data('title', title);
			},
			getTitle: function getTitle() {
				return this.find('.treedropdownfield-title').text();
			},

			updateTitle: function updateTitle() {
				var self = this,
				    tree = self.find('.tree-holder'),
				    val = this.getValue();
				var updateFn = function updateFn() {
					var val = self.getValue();
					if (val) {

						var node = tree.find('*[data-id="' + val + '"]'),
						    title = node.children('a').find("span.jstree_pageicon") ? node.children('a').find("span.item").html() : null;
						if (!title) title = node.length > 0 ? tree.jstree('get_text', node[0]) : null;

						if (title) {
							self.setTitle(title);
							self.data('title', title);
						}
						if (node) tree.jstree('select_node', node);
					} else {
						self.setTitle(self.data('empty-title'));
						self.removeData('title');
					}
				};

				if (!tree.is(':empty') || !val) updateFn();else this.loadTree({ forceValue: val }, updateFn);
			},
			setValue: function setValue(val) {
				this.data('metadata', $.extend(this.data('metadata'), { id: val }));
				this.find(':input:hidden').val(val).trigger('valueupdated').trigger('change');
			},
			getValue: function getValue() {
				return this.find(':input:hidden').val();
			},
			loadTree: function loadTree(params, callback) {
				var self = this,
				    panel = this.getPanel(),
				    treeHolder = $(panel).find('.tree-holder'),
				    params = params ? $.extend({}, this.getRequestParams(), params) : this.getRequestParams(),
				    xhr;

				if (this.getCurrentXhr()) this.getCurrentXhr().abort();
				panel.addClass('loading');
				xhr = $.ajax({
					url: this.data('urlTree'),
					data: params,
					complete: function complete(xhr, status) {
						panel.removeClass('loading');
					},
					success: function success(html, status, xhr) {
						treeHolder.html(html);
						var firstLoad = true;
						treeHolder.jstree('destroy').bind('loaded.jstree', function (e, data) {
							var val = self.getValue(),
							    selectNode = treeHolder.find('*[data-id="' + val + '"]'),
							    currentNode = data.inst.get_selected();
							if (val && selectNode != currentNode) data.inst.select_node(selectNode);
							firstLoad = false;
							if (callback) callback.apply(self);
						}).jstree(self.getTreeConfig()).bind('select_node.jstree', function (e, data) {
							var node = data.rslt.obj,
							    id = $(node).data('id');
							if (!firstLoad && self.getValue() == id) {
								self.data('metadata', null);
								self.setTitle(null);
								self.setValue(null);
								data.inst.deselect_node(node);
							} else {
								self.data('metadata', $.extend({ id: id }, $(node).getMetaData()));
								self.setTitle(data.inst.get_text(node));
								self.setValue(id);
							}

							if (!firstLoad) self.closePanel();
							firstLoad = false;
						});

						self.setCurrentXhr(null);
					}
				});
				this.setCurrentXhr(xhr);
			},
			getTreeConfig: function getTreeConfig() {
				var self = this;
				return {
					'core': {
						'html_titles': true,

						'animation': 0
					},
					'html_data': {
						'data': this.getPanel().find('.tree-holder').html(),
						'ajax': {
							'url': function url(node) {
								var url = $.path.parseUrl(self.data('urlTree')).hrefNoSearch;
								return url + '/' + ($(node).data("id") ? $(node).data("id") : 0);
							},
							'data': function data(node) {
								var query = $.query.load(self.data('urlTree')).keys;
								var params = self.getRequestParams();
								params = $.extend({}, query, params, { ajax: 1 });
								return params;
							}
						}
					},
					'ui': {
						"select_limit": 1,
						'initially_select': [this.getPanel().find('.current').attr('id')]
					},
					'themes': {
						'theme': 'apple'
					},
					'types': {
						'types': {
							'default': {
								'check_node': function check_node(node) {
									return !node.hasClass('disabled');
								},
								'uncheck_node': function uncheck_node(node) {
									return !node.hasClass('disabled');
								},
								'select_node': function select_node(node) {
									return !node.hasClass('disabled');
								},
								'deselect_node': function deselect_node(node) {
									return !node.hasClass('disabled');
								}
							}
						}
					},
					'plugins': ['html_data', 'ui', 'themes', 'types']
				};
			},

			getRequestParams: function getRequestParams() {
				return {};
			}
		});

		$('.TreeDropdownField .tree-holder li').entwine({
			getMetaData: function getMetaData() {
				var matches = this.attr('class').match(/class-([^\s]*)/i);
				var klass = matches ? matches[1] : '';
				return { ClassName: klass };
			}
		});

		$('.TreeDropdownField *').entwine({
			getField: function getField() {
				return this.parents('.TreeDropdownField:first');
			}
		});

		$('.TreeDropdownField').entwine({
			onclick: function onclick(e) {
				this.togglePanel();

				return false;
			}
		});

		$('.TreeDropdownField .treedropdownfield-panel').entwine({
			onclick: function onclick(e) {
				return false;
			}
		});

		$('.TreeDropdownField.searchable').entwine({
			onadd: function onadd() {
				this._super();
				var title = _i18n2.default._t('TreeDropdownField.ENTERTOSEARCH');
				this.find('.treedropdownfield-panel').prepend($('<input type="text" class="search treedropdownfield-search" data-skip-autofocus="true" placeholder="' + title + '" value="" />'));
			},
			search: function search(str, callback) {
				this.openPanel();
				this.loadTree({ search: str }, callback);
			},
			cancelSearch: function cancelSearch() {
				this.closePanel();
				this.loadTree();
			}
		});

		$('.TreeDropdownField.searchable input.search').entwine({
			onkeydown: function onkeydown(e) {
				var field = this.getField();
				if (e.keyCode == 13) {
					field.search(this.val());
					return false;
				} else if (e.keyCode == 27) {
					field.cancelSearch();
				}
			}
		});

		$('.TreeDropdownField.multiple').entwine({
			getTreeConfig: function getTreeConfig() {
				var cfg = this._super();
				cfg.checkbox = { override_ui: true, two_state: true };
				cfg.plugins.push('checkbox');
				cfg.ui.select_limit = -1;
				return cfg;
			},
			loadTree: function loadTree(params, callback) {
				var self = this,
				    panel = this.getPanel(),
				    treeHolder = $(panel).find('.tree-holder');
				var params = params ? $.extend({}, this.getRequestParams(), params) : this.getRequestParams(),
				    xhr;

				if (this.getCurrentXhr()) this.getCurrentXhr().abort();
				panel.addClass('loading');
				xhr = $.ajax({
					url: this.data('urlTree'),
					data: params,
					complete: function complete(xhr, status) {
						panel.removeClass('loading');
					},
					success: function success(html, status, xhr) {
						treeHolder.html(html);
						var firstLoad = true;
						self.setCurrentXhr(null);
						treeHolder.jstree('destroy').bind('loaded.jstree', function (e, data) {
							$.each(self.getValue(), function (i, val) {
								data.inst.check_node(treeHolder.find('*[data-id=' + val + ']'));
							});
							firstLoad = false;
							if (callback) callback.apply(self);
						}).jstree(self.getTreeConfig()).bind('uncheck_node.jstree check_node.jstree', function (e, data) {
							var nodes = data.inst.get_checked(null, true);
							self.setValue($.map(nodes, function (el, i) {
								return $(el).data('id');
							}));
							self.setTitle($.map(nodes, function (el, i) {
								return data.inst.get_text(el);
							}));
							self.data('metadata', $.map(nodes, function (el, i) {
								return { id: $(el).data('id'), metadata: $(el).getMetaData() };
							}));
						});
					}
				});
				this.setCurrentXhr(xhr);
			},
			getValue: function getValue() {
				var val = this._super();
				return val.split(/ *, */);
			},
			setValue: function setValue(val) {
				this._super($.isArray(val) ? val.join(',') : val);
			},
			setTitle: function setTitle(title) {
				this._super($.isArray(title) ? title.join(', ') : title);
			},
			updateTitle: function updateTitle() {}
		});

		$('.TreeDropdownField input[type=hidden]').entwine({
			onadd: function onadd() {
				this._super();
				this.bind('change.TreeDropdownField', function () {
					$(this).getField().updateTitle();
				});
			},
			onremove: function onremove() {
				this._super();
				this.unbind('.TreeDropdownField');
			}
		});
	});
});