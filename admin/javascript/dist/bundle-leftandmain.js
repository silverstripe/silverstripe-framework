(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

var _jQuery = require('jQuery');

var _jQuery2 = _interopRequireDefault(_jQuery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_jQuery2.default.entwine('ss', function ($) {
	$('.ss-tabset.ss-ui-action-tabset').entwine({
		IgnoreTabState: true,

		onadd: function onadd() {
			this._super();

			this.tabs({ 'collapsible': true, 'active': false });
		},

		onremove: function onremove() {
			var frame = $('.cms-container').find('iframe');
			frame.each(function (index, iframe) {
				try {
					$(iframe).contents().off('click.ss-ui-action-tabset');
				} catch (e) {
					console.warn('Unable to access iframe, possible https mis-match');
				}
			});
			$(document).off('click.ss-ui-action-tabset');

			this._super();
		},

		'ontabsbeforeactivate': function ontabsbeforeactivate(event, ui) {
			this.riseUp(event, ui);
		},

		onclick: function onclick(event, ui) {
			this.attachCloseHandler(event, ui);
		},

		attachCloseHandler: function attachCloseHandler(event, ui) {
			var that = this,
			    frame = $('.cms-container').find('iframe'),
			    _closeHandler;

			_closeHandler = function closeHandler(event) {
				var panel, frame;
				panel = $(event.target).closest('.ss-ui-action-tabset .ui-tabs-panel');

				if (!$(event.target).closest(that).length && !panel.length) {
					that.tabs('option', 'active', false);
					frame = $('.cms-container').find('iframe');
					frame.each(function (index, iframe) {
						$(iframe).contents().off('click.ss-ui-action-tabset', _closeHandler);
					});
					$(document).off('click.ss-ui-action-tabset', _closeHandler);
				}
			};

			$(document).on('click.ss-ui-action-tabset', _closeHandler);

			if (frame.length > 0) {
				frame.each(function (index, iframe) {
					$(iframe).contents().on('click.ss-ui-action-tabset', _closeHandler);
				});
			}
		},

		riseUp: function riseUp(event, ui) {
			var elHeight, trigger, endOfWindow, elPos, activePanel, activeTab, topPosition, containerSouth, padding;

			elHeight = $(this).find('.ui-tabs-panel').outerHeight();
			trigger = $(this).find('.ui-tabs-nav').outerHeight();
			endOfWindow = $(window).height() + $(document).scrollTop() - trigger;
			elPos = $(this).find('.ui-tabs-nav').offset().top;

			activePanel = ui.newPanel;
			activeTab = ui.newTab;

			if (elPos + elHeight >= endOfWindow && elPos - elHeight > 0) {
				this.addClass('rise-up');

				if (activeTab.position() !== null) {
					topPosition = -activePanel.outerHeight();
					containerSouth = activePanel.parents('.south');
					if (containerSouth) {
						padding = activeTab.offset().top - containerSouth.offset().top;
						topPosition = topPosition - padding;
					}
					$(activePanel).css('top', topPosition + "px");
				}
			} else {
				this.removeClass('rise-up');
				if (activeTab.position() !== null) {
					$(activePanel).css('top', '0px');
				}
			}
			return false;
		}
	});

	$('.cms-content-actions .ss-tabset.ss-ui-action-tabset').entwine({
		'ontabsbeforeactivate': function ontabsbeforeactivate(event, ui) {
			this._super(event, ui);

			if ($(ui.newPanel).length > 0) {
				$(ui.newPanel).css('left', ui.newTab.position().left + "px");
			}
		}
	});

	$('.cms-actions-row.ss-tabset.ss-ui-action-tabset').entwine({
		'ontabsbeforeactivate': function ontabsbeforeactivate(event, ui) {
			this._super(event, ui);

			$(this).closest('.ss-ui-action-tabset').removeClass('tabset-open tabset-open-last');
		}
	});

	$('.cms-content-fields .ss-tabset.ss-ui-action-tabset').entwine({
		'ontabsbeforeactivate': function ontabsbeforeactivate(event, ui) {
			this._super(event, ui);
			if ($(ui.newPanel).length > 0) {
				if ($(ui.newTab).hasClass("last")) {
					$(ui.newPanel).css({ 'left': 'auto', 'right': '0px' });

					$(ui.newPanel).parent().addClass('tabset-open-last');
				} else {
					$(ui.newPanel).css('left', ui.newTab.position().left + "px");

					if ($(ui.newTab).hasClass("first")) {
						$(ui.newPanel).css('left', "0px");
						$(ui.newPanel).parent().addClass('tabset-open');
					}
				}
			}
		}
	});

	$('.cms-tree-view-sidebar .cms-actions-row.ss-tabset.ss-ui-action-tabset').entwine({
		'from .ui-tabs-nav li': {
			onhover: function onhover(e) {
				$(e.target).parent().find('li .active').removeClass('active');
				$(e.target).find('a').addClass('active');
			}
		},

		'ontabsbeforeactivate': function ontabsbeforeactivate(event, ui) {
			this._super(event, ui);

			$(ui.newPanel).css({ 'left': 'auto', 'right': 'auto' });

			if ($(ui.newPanel).length > 0) {
				$(ui.newPanel).parent().addClass('tabset-open');
			}
		}
	});
});

},{"jQuery":"jQuery"}],2:[function(require,module,exports){
'use strict';

var _jQuery = require('jQuery');

var _jQuery2 = _interopRequireDefault(_jQuery);

var _i18n = require('i18n');

var _i18n2 = _interopRequireDefault(_i18n);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

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

			this.trigger("liszt:updated");

			this._super(e);
		}
	});
});

},{"i18n":"i18n","jQuery":"jQuery"}],3:[function(require,module,exports){
'use strict';

var _jQuery = require('jQuery');

var _jQuery2 = _interopRequireDefault(_jQuery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_jQuery2.default.entwine('ss', function ($) {
	$('.cms-content').entwine({

		onadd: function onadd() {
			var self = this;

			this.find('.cms-tabset').redrawTabs();
			this._super();
		},

		redraw: function redraw() {
			if (window.debug) console.log('redraw', this.attr('class'), this.get(0));

			this.add(this.find('.cms-tabset')).redrawTabs();
			this.find('.cms-content-header').redraw();
			this.find('.cms-content-actions').redraw();
		}
	});

	$('.cms-content .cms-tree').entwine({
		onadd: function onadd() {
			var self = this;

			this._super();

			this.bind('select_node.jstree', function (e, data) {
				var node = data.rslt.obj,
				    loadedNodeID = self.find(':input[name=ID]').val(),
				    origEvent = data.args[2],
				    container = $('.cms-container');

				if (!origEvent) {
					return false;
				}

				if ($(node).hasClass('disabled')) return false;

				if ($(node).data('id') == loadedNodeID) return;

				var url = $(node).find('a:first').attr('href');
				if (url && url != '#') {
					url = url.split('?')[0];

					self.jstree('deselect_all');
					self.jstree('uncheck_all');

					if ($.path.isExternal($(node).find('a:first'))) url = url = $.path.makeUrlAbsolute(url, $('base').attr('href'));

					if (document.location.search) url = $.path.addSearchParams(url, document.location.search.replace(/^\?/, ''));

					container.loadPanel(url);
				} else {
					self.removeForm();
				}
			});
		}
	});

	$('.cms-content .cms-content-fields').entwine({
		redraw: function redraw() {
			if (window.debug) console.log('redraw', this.attr('class'), this.get(0));
		}
	});

	$('.cms-content .cms-content-header, .cms-content .cms-content-actions').entwine({
		redraw: function redraw() {
			if (window.debug) console.log('redraw', this.attr('class'), this.get(0));

			this.height('auto');
			this.height(this.innerHeight() - this.css('padding-top') - this.css('padding-bottom'));
		}
	});
});

},{"jQuery":"jQuery"}],4:[function(require,module,exports){
'use strict';

var _jQuery = require('jQuery');

var _jQuery2 = _interopRequireDefault(_jQuery);

var _i18n = require('i18n');

var _i18n2 = _interopRequireDefault(_i18n);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

window.onbeforeunload = function (e) {
	var form = (0, _jQuery2.default)('.cms-edit-form');
	form.trigger('beforesubmitform');
	if (form.is('.changed') && !form.is('.discardchanges')) {
		return _i18n2.default._t('LeftAndMain.CONFIRMUNSAVEDSHORT');
	}
};

_jQuery2.default.entwine('ss', function ($) {
	$('.cms-edit-form').entwine({
		PlaceholderHtml: '',

		ChangeTrackerOptions: {
			ignoreFieldSelector: '.no-change-track, .ss-upload :input, .cms-navigator :input'
		},

		onadd: function onadd() {
			var self = this;

			this.attr("autocomplete", "off");

			this._setupChangeTracker();

			for (var overrideAttr in { 'action': true, 'method': true, 'enctype': true, 'name': true }) {
				var el = this.find(':input[name=' + '_form_' + overrideAttr + ']');
				if (el) {
					this.attr(overrideAttr, el.val());
					el.remove();
				}
			}

			if (this.hasClass('validationerror')) {
				var tabError = this.find('.message.validation, .message.required').first().closest('.tab');
				$('.cms-container').clearCurrentTabState();
				tabError.closest('.ss-tabset').tabs('option', 'active', tabError.index('.tab'));
			}

			this._super();
		},
		onremove: function onremove() {
			this.changetracker('destroy');
			this._super();
		},
		onmatch: function onmatch() {
			this._super();
		},
		onunmatch: function onunmatch() {
			this._super();
		},
		redraw: function redraw() {
			if (window.debug) console.log('redraw', this.attr('class'), this.get(0));

			this.add(this.find('.cms-tabset')).redrawTabs();
			this.find('.cms-content-header').redraw();
		},

		_setupChangeTracker: function _setupChangeTracker() {
			this.changetracker(this.getChangeTrackerOptions());
		},

		confirmUnsavedChanges: function confirmUnsavedChanges() {
			this.trigger('beforesubmitform');
			if (!this.is('.changed') || this.is('.discardchanges')) {
				return true;
			}
			var confirmed = confirm(_i18n2.default._t('LeftAndMain.CONFIRMUNSAVED'));
			if (confirmed) {
				this.addClass('discardchanges');
			}
			return confirmed;
		},

		onsubmit: function onsubmit(e, button) {
			if (this.prop("target") != "_blank") {
				if (button) this.closest('.cms-container').submitForm(this, button);
				return false;
			}
		},

		validate: function validate() {
			var isValid = true;
			this.trigger('validate', { isValid: isValid });

			return isValid;
		},

		'from .htmleditor': {
			oneditorinit: function oneditorinit(e) {
				var self = this,
				    field = $(e.target).closest('.field.htmleditor'),
				    editor = field.find('textarea.htmleditor').getEditor().getInstance();

				editor.onClick.add(function (e) {
					self.saveFieldFocus(field.attr('id'));
				});
			}
		},

		'from .cms-edit-form :input:not(:submit)': {
			onclick: function onclick(e) {
				this.saveFieldFocus($(e.target).attr('id'));
			},
			onfocus: function onfocus(e) {
				this.saveFieldFocus($(e.target).attr('id'));
			}
		},

		'from .cms-edit-form .treedropdown *': {
			onfocusin: function onfocusin(e) {
				var field = $(e.target).closest('.field.treedropdown');
				this.saveFieldFocus(field.attr('id'));
			}
		},

		'from .cms-edit-form .dropdown .chzn-container a': {
			onfocusin: function onfocusin(e) {
				var field = $(e.target).closest('.field.dropdown');
				this.saveFieldFocus(field.attr('id'));
			}
		},

		'from .cms-container': {
			ontabstaterestored: function ontabstaterestored(e) {
				this.restoreFieldFocus();
			}
		},

		saveFieldFocus: function saveFieldFocus(selected) {
			if (typeof window.sessionStorage == "undefined" || window.sessionStorage === null) return;

			var id = $(this).attr('id'),
			    focusElements = [];

			focusElements.push({
				id: id,
				selected: selected
			});

			if (focusElements) {
				try {
					window.sessionStorage.setItem(id, JSON.stringify(focusElements));
				} catch (err) {
					if (err.code === DOMException.QUOTA_EXCEEDED_ERR && window.sessionStorage.length === 0) {
						return;
					} else {
						throw err;
					}
				}
			}
		},

		restoreFieldFocus: function restoreFieldFocus() {
			if (typeof window.sessionStorage == "undefined" || window.sessionStorage === null) return;

			var self = this,
			    hasSessionStorage = typeof window.sessionStorage !== "undefined" && window.sessionStorage,
			    sessionData = hasSessionStorage ? window.sessionStorage.getItem(this.attr('id')) : null,
			    sessionStates = sessionData ? JSON.parse(sessionData) : false,
			    elementID,
			    tabbed = this.find('.ss-tabset').length !== 0,
			    activeTab,
			    elementTab,
			    toggleComposite,
			    scrollY;

			if (hasSessionStorage && sessionStates.length > 0) {
				$.each(sessionStates, function (i, sessionState) {
					if (self.is('#' + sessionState.id)) {
						elementID = $('#' + sessionState.selected);
					}
				});

				if ($(elementID).length < 1) {
					this.focusFirstInput();
					return;
				}

				activeTab = $(elementID).closest('.ss-tabset').find('.ui-tabs-nav .ui-tabs-active .ui-tabs-anchor').attr('id');
				elementTab = 'tab-' + $(elementID).closest('.ss-tabset .ui-tabs-panel').attr('id');

				if (tabbed && elementTab !== activeTab) {
					return;
				}

				toggleComposite = $(elementID).closest('.togglecomposite');

				if (toggleComposite.length > 0) {
					toggleComposite.accordion('activate', toggleComposite.find('.ui-accordion-header'));
				}

				scrollY = $(elementID).position().top;

				if (!$(elementID).is(':visible')) {
					elementID = '#' + $(elementID).closest('.field').attr('id');
					scrollY = $(elementID).position().top;
				}

				$(elementID).focus();

				if (scrollY > $(window).height() / 2) {
					self.find('.cms-content-fields').scrollTop(scrollY);
				}
			} else {
				this.focusFirstInput();
			}
		},

		focusFirstInput: function focusFirstInput() {
			this.find(':input:not(:submit)[data-skip-autofocus!="true"]').filter(':visible:first').focus();
		}
	});

	$('.cms-edit-form .Actions input.action[type=submit], .cms-edit-form .Actions button.action').entwine({
		onclick: function onclick(e) {
			if (this.hasClass('gridfield-button-delete') && !confirm(_i18n2.default._t('TABLEFIELD.DELETECONFIRMMESSAGE'))) {
				e.preventDefault();
				return false;
			}

			if (!this.is(':disabled')) {
				this.parents('form').trigger('submit', [this]);
			}
			e.preventDefault();
			return false;
		}
	});

	$('.cms-edit-form .Actions input.action[type=submit].ss-ui-action-cancel, .cms-edit-form .Actions button.action.ss-ui-action-cancel').entwine({
		onclick: function onclick(e) {
			if (window.history.length > 1) {
				window.history.back();
			} else {
				this.parents('form').trigger('submit', [this]);
			}
			e.preventDefault();
		}
	});

	$('.cms-edit-form .ss-tabset').entwine({
		onmatch: function onmatch() {
			if (!this.hasClass('ss-ui-action-tabset')) {
				var tabs = this.find("> ul:first");

				if (tabs.children("li").length == 1) {
					tabs.hide().parent().addClass("ss-tabset-tabshidden");
				}
			}

			this._super();
		},
		onunmatch: function onunmatch() {
			this._super();
		}
	});
});

},{"i18n":"i18n","jQuery":"jQuery"}],5:[function(require,module,exports){
'use strict';

var _jQuery = require('jQuery');

var _jQuery2 = _interopRequireDefault(_jQuery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

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

},{"jQuery":"jQuery"}],6:[function(require,module,exports){
'use strict';

var _jQuery = require('jQuery');

var _jQuery2 = _interopRequireDefault(_jQuery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_jQuery2.default.entwine('ss', function ($) {
	$(".cms .field.cms-description-tooltip").entwine({
		onmatch: function onmatch() {
			this._super();

			var descriptionEl = this.find('.description'),
			    inputEl,
			    tooltipEl;
			if (descriptionEl.length) {
				this.attr('title', descriptionEl.text()).tooltip({ content: descriptionEl.html() });
				descriptionEl.remove();
			}
		}
	});

	$(".cms .field.cms-description-tooltip :input").entwine({
		onfocusin: function onfocusin(e) {
			this.closest('.field').tooltip('open');
		},
		onfocusout: function onfocusout(e) {
			this.closest('.field').tooltip('close');
		}
	});
});

},{"jQuery":"jQuery"}],7:[function(require,module,exports){
'use strict';

var _jQuery = require('jQuery');

var _jQuery2 = _interopRequireDefault(_jQuery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

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

},{"jQuery":"jQuery"}],8:[function(require,module,exports){
'use strict';

var _jQuery = require('jQuery');

var _jQuery2 = _interopRequireDefault(_jQuery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_jQuery2.default.entwine('ss', function ($) {
	$('.cms-panel.cms-menu').entwine({
		togglePanel: function togglePanel(doExpand, silent, doSaveState) {
			$('.cms-menu-list').children('li').each(function () {
				if (doExpand) {
					$(this).children('ul').each(function () {
						$(this).removeClass('collapsed-flyout');
						if ($(this).data('collapse')) {
							$(this).removeData('collapse');
							$(this).addClass('collapse');
						}
					});
				} else {
					$(this).children('ul').each(function () {
						$(this).addClass('collapsed-flyout');
						$(this).hasClass('collapse');
						$(this).removeClass('collapse');
						$(this).data('collapse', true);
					});
				}
			});

			this.toggleFlyoutState(doExpand);

			this._super(doExpand, silent, doSaveState);
		},
		toggleFlyoutState: function toggleFlyoutState(bool) {
			if (bool) {
				$('.collapsed').find('li').show();

				$('.cms-menu-list').find('.child-flyout-indicator').hide();
			} else {
				$('.collapsed-flyout').find('li').each(function () {
					$(this).hide();
				});

				var par = $('.cms-menu-list ul.collapsed-flyout').parent();
				if (par.children('.child-flyout-indicator').length === 0) par.append('<span class="child-flyout-indicator"></span>').fadeIn();
				par.children('.child-flyout-indicator').fadeIn();
			}
		},
		siteTreePresent: function siteTreePresent() {
			return $('#cms-content-tools-CMSMain').length > 0;
		},

		getPersistedStickyState: function getPersistedStickyState() {
			var persistedState, cookieValue;

			if ($.cookie !== void 0) {
				cookieValue = $.cookie('cms-menu-sticky');

				if (cookieValue !== void 0 && cookieValue !== null) {
					persistedState = cookieValue === 'true';
				}
			}

			return persistedState;
		},

		setPersistedStickyState: function setPersistedStickyState(isSticky) {
			if ($.cookie !== void 0) {
				$.cookie('cms-menu-sticky', isSticky, { path: '/', expires: 31 });
			}
		},

		getEvaluatedCollapsedState: function getEvaluatedCollapsedState() {
			var shouldCollapse,
			    manualState = this.getPersistedCollapsedState(),
			    menuIsSticky = $('.cms-menu').getPersistedStickyState(),
			    automaticState = this.siteTreePresent();

			if (manualState === void 0) {
				shouldCollapse = automaticState;
			} else if (manualState !== automaticState && menuIsSticky) {
				shouldCollapse = manualState;
			} else {
				shouldCollapse = automaticState;
			}

			return shouldCollapse;
		},

		onadd: function onadd() {
			var self = this;

			setTimeout(function () {
				self.togglePanel(!self.getEvaluatedCollapsedState(), false, false);
			}, 0);

			$(window).on('ajaxComplete', function (e) {
				setTimeout(function () {
					self.togglePanel(!self.getEvaluatedCollapsedState(), false, false);
				}, 0);
			});

			this._super();
		}
	});

	$('.cms-menu-list').entwine({
		onmatch: function onmatch() {
			var self = this;

			this.find('li.current').select();

			this.updateItems();

			this._super();
		},
		onunmatch: function onunmatch() {
			this._super();
		},

		updateMenuFromResponse: function updateMenuFromResponse(xhr) {
			var controller = xhr.getResponseHeader('X-Controller');
			if (controller) {
				var item = this.find('li#Menu-' + controller.replace(/\\/g, '-').replace(/[^a-zA-Z0-9\-_:.]+/, ''));
				if (!item.hasClass('current')) item.select();
			}
			this.updateItems();
		},

		'from .cms-container': {
			onafterstatechange: function onafterstatechange(e, data) {
				this.updateMenuFromResponse(data.xhr);
			},
			onaftersubmitform: function onaftersubmitform(e, data) {
				this.updateMenuFromResponse(data.xhr);
			}
		},

		'from .cms-edit-form': {
			onrelodeditform: function onrelodeditform(e, data) {
				this.updateMenuFromResponse(data.xmlhttp);
			}
		},

		getContainingPanel: function getContainingPanel() {
			return this.closest('.cms-panel');
		},

		fromContainingPanel: {
			ontoggle: function ontoggle(e) {
				this.toggleClass('collapsed', $(e.target).hasClass('collapsed'));

				$('.cms-container').trigger('windowresize');

				if (this.hasClass('collapsed')) this.find('li.children.opened').removeClass('opened');

				if (!this.hasClass('collapsed')) {
					$('.toggle-children.opened').closest('li').addClass('opened');
				}
			}
		},

		updateItems: function updateItems() {
			var editPageItem = this.find('#Menu-CMSMain');

			editPageItem[editPageItem.is('.current') ? 'show' : 'hide']();

			var currentID = $('.cms-content input[name=ID]').val();
			if (currentID) {
				this.find('li').each(function () {
					if ($.isFunction($(this).setRecordID)) $(this).setRecordID(currentID);
				});
			}
		}
	});

	$('.cms-menu-list li').entwine({
		toggleFlyout: function toggleFlyout(bool) {
			var fly = $(this);

			if (fly.children('ul').first().hasClass('collapsed-flyout')) {
				if (bool) {
					if (!fly.children('ul').first().children('li').first().hasClass('clone')) {

						var li = fly.clone();
						li.addClass('clone').css({});

						li.children('ul').first().remove();

						li.find('span').not('.text').remove();

						li.find('a').first().unbind('click');

						fly.children('ul').prepend(li);
					}

					$('.collapsed-flyout').show();
					fly.addClass('opened');
					fly.children('ul').find('li').fadeIn('fast');
				} else {
					if (li) {
						li.remove();
					}
					$('.collapsed-flyout').hide();
					fly.removeClass('opened');
					fly.find('toggle-children').removeClass('opened');
					fly.children('ul').find('li').hide();
				}
			}
		}
	});

	$('.cms-menu-list li').hoverIntent(function () {
		$(this).toggleFlyout(true);
	}, function () {
		$(this).toggleFlyout(false);
	});

	$('.cms-menu-list .toggle').entwine({
		onclick: function onclick(e) {
			e.preventDefault();
			$(this).toogleFlyout(true);
		}
	});

	$('.cms-menu-list li').entwine({
		onmatch: function onmatch() {
			if (this.find('ul').length) {
				this.find('a:first').append('<span class="toggle-children"><span class="toggle-children-icon"></span></span>');
			}
			this._super();
		},
		onunmatch: function onunmatch() {
			this._super();
		},
		toggle: function toggle() {
			this[this.hasClass('opened') ? 'close' : 'open']();
		},

		open: function open() {
			var parent = this.getMenuItem();
			if (parent) parent.open();
			if (this.find('li.clone')) {
				this.find('li.clone').remove();
			}
			this.addClass('opened').find('ul').show();
			this.find('.toggle-children').addClass('opened');
		},
		close: function close() {
			this.removeClass('opened').find('ul').hide();
			this.find('.toggle-children').removeClass('opened');
		},
		select: function select() {
			var parent = this.getMenuItem();
			this.addClass('current').open();

			this.siblings().removeClass('current').close();
			this.siblings().find('li').removeClass('current');
			if (parent) {
				var parentSiblings = parent.siblings();
				parent.addClass('current');
				parentSiblings.removeClass('current').close();
				parentSiblings.find('li').removeClass('current').close();
			}

			this.getMenu().updateItems();

			this.trigger('select');
		}
	});

	$('.cms-menu-list *').entwine({
		getMenu: function getMenu() {
			return this.parents('.cms-menu-list:first');
		}
	});

	$('.cms-menu-list li *').entwine({
		getMenuItem: function getMenuItem() {
			return this.parents('li:first');
		}
	});

	$('.cms-menu-list li a').entwine({
		onclick: function onclick(e) {
			var isExternal = $.path.isExternal(this.attr('href'));
			if (e.which > 1 || isExternal) return;

			if (this.attr('target') == "_blank") {
				return;
			}

			e.preventDefault();

			var item = this.getMenuItem();

			var url = this.attr('href');
			if (!isExternal) url = $('base').attr('href') + url;

			var children = item.find('li');
			if (children.length) {
				children.first().find('a').click();
			} else {
				if (!$('.cms-container').loadPanel(url)) return false;
			}

			item.select();
		}
	});

	$('.cms-menu-list li .toggle-children').entwine({
		onclick: function onclick(e) {
			var li = this.closest('li');
			li.toggle();
			return false;
		}
	});

	$('.cms .profile-link').entwine({
		onclick: function onclick() {
			$('.cms-container').loadPanel(this.attr('href'));
			$('.cms-menu-list li').removeClass('current').close();
			return false;
		}
	});

	$('.cms-menu .sticky-toggle').entwine({

		onadd: function onadd() {
			var isSticky = $('.cms-menu').getPersistedStickyState() ? true : false;

			this.toggleCSS(isSticky);
			this.toggleIndicator(isSticky);

			this._super();
		},

		toggleCSS: function toggleCSS(isSticky) {
			this[isSticky ? 'addClass' : 'removeClass']('active');
		},

		toggleIndicator: function toggleIndicator(isSticky) {
			this.next('.sticky-status-indicator').text(isSticky ? 'fixed' : 'auto');
		},

		onclick: function onclick() {
			var $menu = this.closest('.cms-menu'),
			    persistedCollapsedState = $menu.getPersistedCollapsedState(),
			    persistedStickyState = $menu.getPersistedStickyState(),
			    newStickyState = persistedStickyState === void 0 ? !this.hasClass('active') : !persistedStickyState;

			if (persistedCollapsedState === void 0) {
				$menu.setPersistedCollapsedState($menu.hasClass('collapsed'));
			} else if (persistedCollapsedState !== void 0 && newStickyState === false) {
				$menu.clearPersistedCollapsedState();
			}

			$menu.setPersistedStickyState(newStickyState);

			this.toggleCSS(newStickyState);
			this.toggleIndicator(newStickyState);

			this._super();
		}
	});
});

},{"jQuery":"jQuery"}],9:[function(require,module,exports){
'use strict';

var _jQuery = require('jQuery');

var _jQuery2 = _interopRequireDefault(_jQuery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

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

},{"jQuery":"jQuery"}],10:[function(require,module,exports){
'use strict';

var _jQuery = require('jQuery');

var _jQuery2 = _interopRequireDefault(_jQuery);

var _i18n = require('i18n');

var _i18n2 = _interopRequireDefault(_i18n);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_jQuery2.default.entwine('ss.preview', function ($) {
	$('.cms-preview').entwine({
		AllowedStates: ['StageLink', 'LiveLink', 'ArchiveLink'],

		CurrentStateName: null,

		CurrentSizeName: 'auto',

		IsPreviewEnabled: false,

		DefaultMode: 'split',

		Sizes: {
			auto: {
				width: '100%',
				height: '100%'
			},
			mobile: {
				width: '335px',
				height: '568px'
			},
			mobileLandscape: {
				width: '583px',
				height: '320px'
			},
			tablet: {
				width: '783px',
				height: '1024px'
			},
			tabletLandscape: {
				width: '1039px',
				height: '768px'
			},
			desktop: {
				width: '1024px',
				height: '800px'
			}
		},

		changeState: function changeState(stateName, save) {
			var self = this,
			    states = this._getNavigatorStates();
			if (save !== false) {
				$.each(states, function (index, state) {
					self.saveState('state', stateName);
				});
			}

			this.setCurrentStateName(stateName);
			this._loadCurrentState();
			this.redraw();

			return this;
		},

		changeMode: function changeMode(modeName, save) {
			var container = $('.cms-container');

			if (modeName == 'split') {
				container.entwine('.ss').splitViewMode();
				this.setIsPreviewEnabled(true);
				this._loadCurrentState();
			} else if (modeName == 'content') {
				container.entwine('.ss').contentViewMode();
				this.setIsPreviewEnabled(false);
			} else if (modeName == 'preview') {
					container.entwine('.ss').previewMode();
					this.setIsPreviewEnabled(true);
					this._loadCurrentState();
				} else {
					throw 'Invalid mode: ' + modeName;
				}

			if (save !== false) this.saveState('mode', modeName);

			this.redraw();

			return this;
		},

		changeSize: function changeSize(sizeName) {
			var sizes = this.getSizes();

			this.setCurrentSizeName(sizeName);
			this.removeClass('auto desktop tablet mobile').addClass(sizeName);
			this.find('.preview-device-outer').width(sizes[sizeName].width).height(sizes[sizeName].height);
			this.find('.preview-device-inner').width(sizes[sizeName].width);

			this.saveState('size', sizeName);

			this.redraw();

			return this;
		},

		redraw: function redraw() {

			if (window.debug) console.log('redraw', this.attr('class'), this.get(0));

			var currentStateName = this.getCurrentStateName();
			if (currentStateName) {
				this.find('.cms-preview-states').changeVisibleState(currentStateName);
			}

			var layoutOptions = $('.cms-container').entwine('.ss').getLayoutOptions();
			if (layoutOptions) {
				$('.preview-mode-selector').changeVisibleMode(layoutOptions.mode);
			}

			var currentSizeName = this.getCurrentSizeName();
			if (currentSizeName) {
				this.find('.preview-size-selector').changeVisibleSize(this.getCurrentSizeName());
			}

			return this;
		},

		saveState: function saveState(name, value) {
			if (this._supportsLocalStorage()) window.localStorage.setItem('cms-preview-state-' + name, value);
		},

		loadState: function loadState(name) {
			if (this._supportsLocalStorage()) return window.localStorage.getItem('cms-preview-state-' + name);
		},

		disablePreview: function disablePreview() {
			this.setPendingURL(null);
			this._loadUrl('about:blank');
			this._block();
			this.changeMode('content', false);
			this.setIsPreviewEnabled(false);
			return this;
		},

		enablePreview: function enablePreview() {
			if (!this.getIsPreviewEnabled()) {
				this.setIsPreviewEnabled(true);

				if ($.browser.msie && $.browser.version.slice(0, 3) <= 7) {
					this.changeMode('content');
				} else {
					this.changeMode(this.getDefaultMode(), false);
				}
			}
			return this;
		},

		getOrAppendFontFixStyleElement: function getOrAppendFontFixStyleElement() {
			var style = $('#FontFixStyleElement');
			if (!style.length) {
				style = $('<style type="text/css" id="FontFixStyleElement" disabled="disabled">' + ':before,:after{content:none !important}' + '</style>').appendTo('head');
			}

			return style;
		},

		onadd: function onadd() {
			var self = this,
			    layoutContainer = this.parent(),
			    iframe = this.find('iframe');

			iframe.addClass('center');
			iframe.bind('load', function () {
				self._adjustIframeForPreview();

				self._loadCurrentPage();

				$(this).removeClass('loading');
			});

			if ($.browser.msie && 8 === parseInt($.browser.version, 10)) {
				iframe.bind('readystatechange', function (e) {
					if (iframe[0].readyState == 'interactive') {
						self.getOrAppendFontFixStyleElement().removeAttr('disabled');
						setTimeout(function () {
							self.getOrAppendFontFixStyleElement().attr('disabled', 'disabled');
						}, 0);
					}
				});
			}

			this.append('<div class="cms-preview-overlay ui-widget-overlay-light"></div>');
			this.find('.cms-preview-overlay').hide();

			this.disablePreview();

			this._super();
		},

		_supportsLocalStorage: function _supportsLocalStorage() {
			var uid = new Date();
			var storage;
			var result;
			try {
				(storage = window.localStorage).setItem(uid, uid);
				result = storage.getItem(uid) == uid;
				storage.removeItem(uid);
				return result && storage;
			} catch (exception) {
				console.warn('localStorge is not available due to current browser / system settings.');
			}
		},

		onenable: function onenable() {
			var $viewModeSelector = $('.preview-mode-selector');

			$viewModeSelector.removeClass('split-disabled');
			$viewModeSelector.find('.disabled-tooltip').hide();
		},

		ondisable: function ondisable() {
			var $viewModeSelector = $('.preview-mode-selector');

			$viewModeSelector.addClass('split-disabled');
			$viewModeSelector.find('.disabled-tooltip').show();
		},

		_block: function _block() {
			this.addClass('blocked');
			this.find('.cms-preview-overlay').show();
			return this;
		},

		_unblock: function _unblock() {
			this.removeClass('blocked');
			this.find('.cms-preview-overlay').hide();
			return this;
		},

		_initialiseFromContent: function _initialiseFromContent() {
			var mode, size;

			if (!$('.cms-previewable').length) {
				this.disablePreview();
			} else {
				mode = this.loadState('mode');
				size = this.loadState('size');

				this._moveNavigator();
				if (!mode || mode != 'content') {
					this.enablePreview();
					this._loadCurrentState();
				}
				this.redraw();

				if (mode) this.changeMode(mode);
				if (size) this.changeSize(size);
			}
			return this;
		},

		'from .cms-container': {
			onafterstatechange: function onafterstatechange(e, data) {
				if (data.xhr.getResponseHeader('X-ControllerURL')) return;

				this._initialiseFromContent();
			}
		},

		PendingURL: null,

		oncolumnvisibilitychanged: function oncolumnvisibilitychanged() {
			var url = this.getPendingURL();
			if (url && !this.is('.column-hidden')) {
				this.setPendingURL(null);
				this._loadUrl(url);
				this._unblock();
			}
		},

		'from .cms-container .cms-edit-form': {
			onaftersubmitform: function onaftersubmitform() {
				this._initialiseFromContent();
			}
		},

		_loadUrl: function _loadUrl(url) {
			this.find('iframe').addClass('loading').attr('src', url);
			return this;
		},

		_getNavigatorStates: function _getNavigatorStates() {
			var urlMap = $.map(this.getAllowedStates(), function (name) {
				var stateLink = $('.cms-preview-states .state-name[data-name=' + name + ']');
				if (stateLink.length) {
					return {
						name: name,
						url: stateLink.attr('data-link'),
						active: stateLink.is(':radio') ? stateLink.is(':checked') : stateLink.is(':selected')
					};
				} else {
					return null;
				}
			});

			return urlMap;
		},

		_loadCurrentState: function _loadCurrentState() {
			if (!this.getIsPreviewEnabled()) return this;

			var states = this._getNavigatorStates();
			var currentStateName = this.getCurrentStateName();
			var currentState = null;

			if (states) {
				currentState = $.grep(states, function (state, index) {
					return currentStateName === state.name || !currentStateName && state.active;
				});
			}

			var url = null;

			if (currentState[0]) {
				url = currentState[0].url;
			} else if (states.length) {
				this.setCurrentStateName(states[0].name);
				url = states[0].url;
			} else {
				this.setCurrentStateName(null);
			}

			url += (url.indexOf('?') === -1 ? '?' : '&') + 'CMSPreview=1';

			if (this.is('.column-hidden')) {
				this.setPendingURL(url);
				this._loadUrl('about:blank');
				this._block();
			} else {
				this.setPendingURL(null);

				if (url) {
					this._loadUrl(url);
					this._unblock();
				} else {
					this._block();
				}
			}

			return this;
		},

		_moveNavigator: function _moveNavigator() {
			var previewEl = $('.cms-preview .cms-preview-controls');
			var navigatorEl = $('.cms-edit-form .cms-navigator');

			if (navigatorEl.length && previewEl.length) {
				previewEl.html($('.cms-edit-form .cms-navigator').detach());
			} else {
				this._block();
			}
		},

		_loadCurrentPage: function _loadCurrentPage() {
			if (!this.getIsPreviewEnabled()) return;

			var doc,
			    containerEl = $('.cms-container');
			try {
				doc = this.find('iframe')[0].contentDocument;
			} catch (e) {
				console.warn('Unable to access iframe, possible https mis-match');
			}
			if (!doc) {
				return;
			}

			var id = $(doc).find('meta[name=x-page-id]').attr('content');
			var editLink = $(doc).find('meta[name=x-cms-edit-link]').attr('content');
			var contentPanel = $('.cms-content');

			if (id && contentPanel.find(':input[name=ID]').val() != id) {
				$('.cms-container').entwine('.ss').loadPanel(editLink);
			}
		},

		_adjustIframeForPreview: function _adjustIframeForPreview() {
			var iframe = this.find('iframe')[0],
			    doc;
			if (!iframe) {
				return;
			}

			try {
				doc = iframe.contentDocument;
			} catch (e) {
				console.warn('Unable to access iframe, possible https mis-match');
			}
			if (!doc) {
				return;
			}

			var links = doc.getElementsByTagName('A');
			for (var i = 0; i < links.length; i++) {
				var href = links[i].getAttribute('href');
				if (!href) continue;

				if (href.match(/^http:\/\//)) links[i].setAttribute('target', '_blank');
			}

			var navi = doc.getElementById('SilverStripeNavigator');
			if (navi) navi.style.display = 'none';
			var naviMsg = doc.getElementById('SilverStripeNavigatorMessage');
			if (naviMsg) naviMsg.style.display = 'none';

			this.trigger('afterIframeAdjustedForPreview', [doc]);
		}
	});

	$('.cms-edit-form').entwine({
		onadd: function onadd() {
			this._super();
			$('.cms-preview')._initialiseFromContent();
		}
	});

	$('.cms-preview-states').entwine({
		changeVisibleState: function changeVisibleState(state) {
			this.find('input[data-name="' + state + '"]').prop('checked', true);
		}
	});

	$('.cms-preview-states .state-name').entwine({
		onclick: function onclick(e) {
			this.parent().find('.active').removeClass('active');
			this.next('label').addClass('active');

			var targetStateName = $(this).attr('data-name');

			$('.cms-preview').changeState(targetStateName);
		}
	});

	$('.preview-mode-selector').entwine({
		changeVisibleMode: function changeVisibleMode(mode) {
			this.find('select').val(mode).trigger('liszt:updated')._addIcon();
		}
	});

	$('.preview-mode-selector select').entwine({
		onchange: function onchange(e) {
			this._super(e);
			e.preventDefault();

			var targetStateName = $(this).val();
			$('.cms-preview').changeMode(targetStateName);
		}
	});

	$('.preview-mode-selector .chzn-results li').entwine({
		onclick: function onclick(e) {
			if ($.browser.msie) {
				e.preventDefault();
				var index = this.index();
				var targetStateName = this.closest('.preview-mode-selector').find('select option:eq(' + index + ')').val();

				$('.cms-preview').changeMode(targetStateName);
			}
		}
	});

	$('.cms-preview.column-hidden').entwine({
		onmatch: function onmatch() {
			$('#preview-mode-dropdown-in-content').show();

			if ($('.cms-preview .result-selected').hasClass('font-icon-columns')) {
				statusMessage(_i18n2.default._t('LeftAndMain.DISABLESPLITVIEW', "Screen too small to show site preview in split mode"), "error");
			}
			this._super();
		},

		onunmatch: function onunmatch() {
			$('#preview-mode-dropdown-in-content').hide();
			this._super();
		}
	});

	$('#preview-mode-dropdown-in-content').entwine({
		onmatch: function onmatch() {
			if ($('.cms-preview').is('.column-hidden')) {
				this.show();
			} else {
				this.hide();
			}
			this._super();
		},
		onunmatch: function onunmatch() {
			this._super();
		}
	});

	$('.preview-size-selector').entwine({
		changeVisibleSize: function changeVisibleSize(size) {
			this.find('select').val(size).trigger('liszt:updated')._addIcon();
		}
	});

	$('.preview-size-selector select').entwine({
		onchange: function onchange(e) {
			e.preventDefault();

			var targetSizeName = $(this).val();
			$('.cms-preview').changeSize(targetSizeName);
		}
	});

	$('.preview-selector select.preview-dropdown').entwine({
		'onliszt:showing_dropdown': function onlisztShowing_dropdown() {
			this.siblings().find('.chzn-drop').addClass('open')._alignRight();
		},

		'onliszt:hiding_dropdown': function onlisztHiding_dropdown() {
			this.siblings().find('.chzn-drop').removeClass('open')._removeRightAlign();
		},

		'onliszt:ready': function onlisztReady() {
			this._super();
			this._addIcon();
		},

		_addIcon: function _addIcon() {
			var selected = this.find(':selected');
			var iconClass = selected.attr('data-icon');

			var target = this.parent().find('.chzn-container a.chzn-single');
			var oldIcon = target.attr('data-icon');
			if (typeof oldIcon !== 'undefined') {
				target.removeClass(oldIcon);
			}
			target.addClass(iconClass);
			target.attr('data-icon', iconClass);

			return this;
		}
	});

	$('.preview-selector .chzn-drop').entwine({
		_alignRight: function _alignRight() {
			var that = this;
			$(this).hide();

			setTimeout(function () {
				$(that).css({ left: 'auto', right: 0 });
				$(that).show();
			}, 100);
		},
		_removeRightAlign: function _removeRightAlign() {
			$(this).css({ right: 'auto' });
		}

	});

	$('.preview-mode-selector .chzn-drop li:last-child').entwine({
		onmatch: function onmatch() {
			if ($('.preview-mode-selector').hasClass('split-disabled')) {
				this.parent().append('<div class="disabled-tooltip"></div>');
			} else {
				this.parent().append('<div class="disabled-tooltip" style="display: none;"></div>');
			}
		}
	});

	$('.preview-scroll').entwine({
		ToolbarSize: 53,

		_redraw: function _redraw() {
			var toolbarSize = this.getToolbarSize();

			if (window.debug) console.log('redraw', this.attr('class'), this.get(0));
			var previewHeight = this.height() - toolbarSize;
			this.height(previewHeight);
		},

		onmatch: function onmatch() {
			this._redraw();
			this._super();
		},

		onunmatch: function onunmatch() {
			this._super();
		}
	});

	$('.preview-device-outer').entwine({
		onclick: function onclick() {
			this.toggleClass('rotate');
		}
	});
});

},{"i18n":"i18n","jQuery":"jQuery"}],11:[function(require,module,exports){
'use strict';

var _jQuery = require('jQuery');

var _jQuery2 = _interopRequireDefault(_jQuery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

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
					'url': self.data('urlSavetreenode'),
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
			    newNode = $(html),
			    origClasses = node.attr('class');

			var nextNode = data.NextID ? this.getNodeByID(data.NextID) : false;
			var prevNode = data.PrevID ? this.getNodeByID(data.PrevID) : false;
			var parentNode = data.ParentID ? this.getNodeByID(data.ParentID) : false;

			$.each(['id', 'style', 'class', 'data-pagetype'], function (i, attrName) {
				node.attr(attrName, newNode.attr(attrName));
			});

			origClasses = origClasses.replace(/status-[^\s]*/, '');

			var origChildren = node.children('ul').detach();
			node.addClass(origClasses).html(newNode.html()).append(origChildren);

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

},{"jQuery":"jQuery"}],12:[function(require,module,exports){
'use strict';

var _jQuery = require('jQuery');

var _jQuery2 = _interopRequireDefault(_jQuery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_jQuery2.default.entwine('ss', function ($) {
	$('.TreeDropdownField').entwine({
		'from .cms-container form': {
			onaftersubmitform: function onaftersubmitform(e) {
				this.find('.tree-holder').empty();
				this._super();
			}
		}
	});
});

},{"jQuery":"jQuery"}],13:[function(require,module,exports){
'use strict';

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol ? "symbol" : typeof obj; };

var _jQuery = require('jQuery');

var _jQuery2 = _interopRequireDefault(_jQuery);

var _router = require('router');

var _router2 = _interopRequireDefault(_router);

var _config = require('config');

var _config2 = _interopRequireDefault(_config);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var windowWidth, windowHeight;

_jQuery2.default.noConflict();

window.ss = window.ss || {};
window.ss.router = _router2.default;

window.ss.debounce = function (func, wait, immediate) {
	var timeout, context, args;

	var later = function later() {
		timeout = null;
		if (!immediate) func.apply(context, args);
	};

	return function () {
		var callNow = immediate && !timeout;

		context = this;
		args = arguments;

		clearTimeout(timeout);
		timeout = setTimeout(later, wait);

		if (callNow) {
			func.apply(context, args);
		}
	};
};

function getUrlPath(url) {
	var anchor = document.createElement('a');
	anchor.href = url;

	return anchor.pathname;
}

(0, _jQuery2.default)(window).bind('resize.leftandmain', function (e) {
	var cb = function cb() {
		(0, _jQuery2.default)('.cms-container').trigger('windowresize');
	};

	if (_jQuery2.default.browser.msie && parseInt(_jQuery2.default.browser.version, 10) < 9) {
		var newWindowWidth = (0, _jQuery2.default)(window).width(),
		    newWindowHeight = (0, _jQuery2.default)(window).height();
		if (newWindowWidth != windowWidth || newWindowHeight != windowHeight) {
			windowWidth = newWindowWidth;
			windowHeight = newWindowHeight;
			cb();
		}
	} else {
		cb();
	}
});

_jQuery2.default.entwine.warningLevel = _jQuery2.default.entwine.WARN_LEVEL_BESTPRACTISE;

_jQuery2.default.entwine('ss', function ($) {
	$(window).on("message", function (e) {
		var target,
		    event = e.originalEvent,
		    data = _typeof(event.data) === 'object' ? event.data : JSON.parse(event.data);

		if ($.path.parseUrl(window.location.href).domain !== $.path.parseUrl(event.origin).domain) return;

		target = typeof data.target === 'undefined' ? $(window) : $(data.target);

		switch (data.type) {
			case 'event':
				target.trigger(data.event, data.data);
				break;
			case 'callback':
				target[data.callback].call(target, data.data);
				break;
		}
	});

	var positionLoadingSpinner = function positionLoadingSpinner() {
		var offset = 120;
		var spinner = $('.ss-loading-screen .loading-animation');
		var top = ($(window).height() - spinner.height()) / 2;
		spinner.css('top', top + offset);
		spinner.show();
	};

	var applyChosen = function applyChosen(el) {
		if (el.is(':visible')) {
			el.addClass('has-chzn').chosen({
				allow_single_deselect: true,
				disable_search_threshold: 20
			});

			var title = el.prop('title');

			if (title) {
				el.siblings('.chzn-container').prop('title', title);
			}
		} else {
			setTimeout(function () {
				el.show();
				applyChosen(el);
			}, 500);
		}
	};

	var isSameUrl = function isSameUrl(url1, url2) {
		var baseUrl = $('base').attr('href');
		url1 = $.path.isAbsoluteUrl(url1) ? url1 : $.path.makeUrlAbsolute(url1, baseUrl), url2 = $.path.isAbsoluteUrl(url2) ? url2 : $.path.makeUrlAbsolute(url2, baseUrl);
		var url1parts = $.path.parseUrl(url1),
		    url2parts = $.path.parseUrl(url2);
		return url1parts.pathname.replace(/\/*$/, '') == url2parts.pathname.replace(/\/*$/, '') && url1parts.search == url2parts.search;
	};

	var ajaxCompleteEvent = window.ss.debounce(function () {
		$(window).trigger('ajaxComplete');
	}, 1000, true);

	$(window).bind('resize', positionLoadingSpinner).trigger('resize');

	$(document).ajaxComplete(function (e, xhr, settings) {
		var origUrl,
		    url = xhr.getResponseHeader('X-ControllerURL'),
		    destUrl = settings.url,
		    msg = xhr.getResponseHeader('X-Status') !== null ? xhr.getResponseHeader('X-Status') : xhr.statusText,
		    msgType = xhr.status < 200 || xhr.status > 399 ? 'bad' : 'good',
		    ignoredMessages = ['OK'];
		if (window.history.state) {
			origUrl = window.history.state.path;
		} else {
			origUrl = document.URL;
		}

		if (url !== null && (!isSameUrl(origUrl, url) || !isSameUrl(destUrl, url))) {
			_router2.default.show(url, {
				id: new Date().getTime() + String(Math.random()).replace(/\D/g, ''),
				pjax: xhr.getResponseHeader('X-Pjax') ? xhr.getResponseHeader('X-Pjax') : settings.headers['X-Pjax']
			});
		}

		if (xhr.getResponseHeader('X-Reauthenticate')) {
			$('.cms-container').showLoginDialog();
			return;
		}

		if (xhr.status !== 0 && msg && $.inArray(msg, ignoredMessages)) {
			statusMessage(decodeURIComponent(msg), msgType);
		}

		ajaxCompleteEvent(this);
	});

	$('.cms-container').entwine({
		StateChangeXHR: null,

		FragmentXHR: {},

		StateChangeCount: 0,

		LayoutOptions: {
			minContentWidth: 940,
			minPreviewWidth: 400,
			mode: 'content'
		},

		onadd: function onadd() {
			var self = this,
			    basePath = getUrlPath($('base')[0].href);

			if (basePath[basePath.length - 1] === '/') {
				basePath += 'admin';
			} else {
				basePath = '/admin';
			}

			_router2.default.base(basePath);

			_config2.default.getTopLevelRoutes().forEach(function (route) {
				(0, _router2.default)('/' + route + '/*', function (ctx, next) {
					if (document.readyState !== 'complete' || typeof ctx.state.__forceReferer === 'undefined') {
						return next();
					}

					self.handleStateChange(null, ctx.state).done(next);
				});
			});

			_router2.default.start();

			if ($.browser.msie && parseInt($.browser.version, 10) < 8) {
				$('.ss-loading-screen').append('<p class="ss-loading-incompat-warning"><span class="notice">' + 'Your browser is not compatible with the CMS interface. Please use Internet Explorer 8+, Google Chrome or Mozilla Firefox.' + '</span></p>').css('z-index', $('.ss-loading-screen').css('z-index') + 1);
				$('.loading-animation').remove();

				this._super();
				return;
			}

			this.redraw();

			$('.ss-loading-screen').hide();
			$('body').removeClass('loading');
			$(window).unbind('resize', positionLoadingSpinner);
			this.restoreTabState();
			this._super();
		},

		fromWindow: {
			onstatechange: function onstatechange(event, historyState) {
				this.handleStateChange(event, historyState);
			}
		},

		'onwindowresize': function onwindowresize() {
			this.redraw();
		},

		'from .cms-panel': {
			ontoggle: function ontoggle() {
				this.redraw();
			}
		},

		'from .cms-container': {
			onaftersubmitform: function onaftersubmitform() {
				this.redraw();
			}
		},

		'from .cms-menu-list li a': {
			onclick: function onclick(e) {
				var href = $(e.target).attr('href');
				if (e.which > 1 || href == this._tabStateUrl()) return;
				this.splitViewMode();
			}
		},

		updateLayoutOptions: function updateLayoutOptions(newSpec) {
			var spec = this.getLayoutOptions();

			var dirty = false;

			for (var k in newSpec) {
				if (spec[k] !== newSpec[k]) {
					spec[k] = newSpec[k];
					dirty = true;
				}
			}

			if (dirty) this.redraw();
		},

		splitViewMode: function splitViewMode() {
			this.updateLayoutOptions({
				mode: 'split'
			});
		},

		contentViewMode: function contentViewMode() {
			this.updateLayoutOptions({
				mode: 'content'
			});
		},

		previewMode: function previewMode() {
			this.updateLayoutOptions({
				mode: 'preview'
			});
		},

		RedrawSuppression: false,

		redraw: function redraw() {
			if (this.getRedrawSuppression()) return;

			if (window.debug) console.log('redraw', this.attr('class'), this.get(0));

			this.data('jlayout', jLayout.threeColumnCompressor({
				menu: this.children('.cms-menu'),
				content: this.children('.cms-content'),
				preview: this.children('.cms-preview')
			}, this.getLayoutOptions()));

			this.layout();

			this.find('.cms-panel-layout').redraw();
			this.find('.cms-content-fields[data-layout-type]').redraw();
			this.find('.cms-edit-form[data-layout-type]').redraw();
			this.find('.cms-preview').redraw();
			this.find('.cms-content').redraw();
		},

		checkCanNavigate: function checkCanNavigate(selectors) {
			var contentEls = this._findFragments(selectors || ['Content']),
			    trackedEls = contentEls.find(':data(changetracker)').add(contentEls.filter(':data(changetracker)')),
			    safe = true;

			if (!trackedEls.length) {
				return true;
			}

			trackedEls.each(function () {
				if (!$(this).confirmUnsavedChanges()) {
					safe = false;
				}
			});

			return safe;
		},

		loadPanel: function loadPanel(url) {
			var title = arguments.length <= 1 || arguments[1] === undefined ? '' : arguments[1];
			var data = arguments.length <= 2 || arguments[2] === undefined ? {} : arguments[2];
			var forceReload = arguments[3];
			var forceReferer = arguments.length <= 4 || arguments[4] === undefined ? window.history.state.path : arguments[4];

			if (!this.checkCanNavigate(data.pjax ? data.pjax.split(',') : ['Content'])) {
				return;
			}

			this.saveTabState();

			data.__forceReferer = forceReferer;

			if (forceReload) {
				data.__forceReload = Math.random();
			}

			_router2.default.show(url, data);
		},

		reloadCurrentPanel: function reloadCurrentPanel() {
			this.loadPanel(window.history.state.path, null, null, true);
		},

		submitForm: function submitForm(form, button, callback, ajaxOptions) {
			var self = this;

			if (!button) button = this.find('.Actions :submit[name=action_save]');

			if (!button) button = this.find('.Actions :submit:first');

			form.trigger('beforesubmitform');
			this.trigger('submitform', { form: form, button: button });

			$(button).addClass('loading');

			var validationResult = form.validate();
			if (typeof validationResult !== 'undefined' && !validationResult) {
				statusMessage("Validation failed.", "bad");

				$(button).removeClass('loading');

				return false;
			}

			var formData = form.serializeArray();

			formData.push({ name: $(button).attr('name'), value: '1' });

			formData.push({ name: 'BackURL', value: window.history.state.path.replace(/\/$/, '') });

			this.saveTabState();

			jQuery.ajax(jQuery.extend({
				headers: { "X-Pjax": "CurrentForm,Breadcrumbs" },
				url: form.attr('action'),
				data: formData,
				type: 'POST',
				complete: function complete() {
					$(button).removeClass('loading');
				},
				success: function success(data, status, xhr) {
					form.removeClass('changed');
					if (callback) callback(data, status, xhr);

					var newContentEls = self.handleAjaxResponse(data, status, xhr);
					if (!newContentEls) return;

					newContentEls.filter('form').trigger('aftersubmitform', { status: status, xhr: xhr, formData: formData });
				}
			}, ajaxOptions));

			return false;
		},

		LastState: null,

		PauseState: false,

		handleStateChange: function handleStateChange(event) {
			var historyState = arguments.length <= 1 || arguments[1] === undefined ? window.history.state : arguments[1];

			if (this.getPauseState()) {
				return;
			}

			if (this.getStateChangeXHR()) {
				this.getStateChangeXHR().abort();
			}

			var self = this,
			    fragments = historyState.pjax || 'Content',
			    headers = {},
			    fragmentsArr = fragments.split(','),
			    contentEls = this._findFragments(fragmentsArr);

			this.setStateChangeCount(this.getStateChangeCount() + 1);

			if (!this.checkCanNavigate()) {
				var lastState = this.getLastState();

				this.setPauseState(true);

				if (lastState !== null) {
					_router2.default.show(lastState.url);
				} else {
					_router2.default.back();
				}

				this.setPauseState(false);

				return;
			}

			this.setLastState(historyState);

			if (contentEls.length < fragmentsArr.length) {
				fragments = 'Content', fragmentsArr = ['Content'];
				contentEls = this._findFragments(fragmentsArr);
			}

			this.trigger('beforestatechange', { state: historyState, element: contentEls });

			headers['X-Pjax'] = fragments;

			if (typeof historyState.__forceReferer !== 'undefined') {
				var url = historyState.__forceReferer;

				try {
					url = decodeURI(url);
				} catch (e) {} finally {
					headers['X-Backurl'] = encodeURI(url);
				}
			}

			contentEls.addClass('loading');

			var promise = $.ajax({
				headers: headers,
				url: historyState.path
			}).done(function (data, status, xhr) {
				var els = self.handleAjaxResponse(data, status, xhr, historyState);
				self.trigger('afterstatechange', { data: data, status: status, xhr: xhr, element: els, state: historyState });
			}).always(function () {
				self.setStateChangeXHR(null);

				contentEls.removeClass('loading');
			});

			this.setStateChangeXHR(promise);

			return promise;
		},

		loadFragment: function loadFragment(url, pjaxFragments) {

			var self = this,
			    xhr,
			    headers = {},
			    baseUrl = $('base').attr('href'),
			    fragmentXHR = this.getFragmentXHR();

			if (typeof fragmentXHR[pjaxFragments] !== 'undefined' && fragmentXHR[pjaxFragments] !== null) {
				fragmentXHR[pjaxFragments].abort();
				fragmentXHR[pjaxFragments] = null;
			}

			url = $.path.isAbsoluteUrl(url) ? url : $.path.makeUrlAbsolute(url, baseUrl);
			headers['X-Pjax'] = pjaxFragments;

			xhr = $.ajax({
				headers: headers,
				url: url,
				success: function success(data, status, xhr) {
					var elements = self.handleAjaxResponse(data, status, xhr, null);

					self.trigger('afterloadfragment', { data: data, status: status, xhr: xhr, elements: elements });
				},
				error: function error(xhr, status, _error) {
					self.trigger('loadfragmenterror', { xhr: xhr, status: status, error: _error });
				},
				complete: function complete() {
					var fragmentXHR = self.getFragmentXHR();
					if (typeof fragmentXHR[pjaxFragments] !== 'undefined' && fragmentXHR[pjaxFragments] !== null) {
						fragmentXHR[pjaxFragments] = null;
					}
				}
			});

			fragmentXHR[pjaxFragments] = xhr;

			return xhr;
		},

		handleAjaxResponse: function handleAjaxResponse(data, status, xhr, state) {
			var self = this,
			    url,
			    selectedTabs,
			    guessFragment,
			    fragment,
			    $data;

			if (xhr.getResponseHeader('X-Reload') && xhr.getResponseHeader('X-ControllerURL')) {
				var baseUrl = $('base').attr('href'),
				    rawURL = xhr.getResponseHeader('X-ControllerURL'),
				    url = $.path.isAbsoluteUrl(rawURL) ? rawURL : $.path.makeUrlAbsolute(rawURL, baseUrl);

				document.location.href = url;
				return;
			}

			if (!data) return;

			var title = xhr.getResponseHeader('X-Title');
			if (title) document.title = decodeURIComponent(title.replace(/\+/g, ' '));

			var newFragments = {},
			    newContentEls;

			if (xhr.getResponseHeader('Content-Type').match(/^((text)|(application))\/json[ \t]*;?/i)) {
				newFragments = data;
			} else {
				fragment = document.createDocumentFragment();

				jQuery.clean([data], document, fragment, []);
				$data = $(jQuery.merge([], fragment.childNodes));

				guessFragment = 'Content';
				if ($data.is('form') && !$data.is('[data-pjax-fragment~=Content]')) guessFragment = 'CurrentForm';

				newFragments[guessFragment] = $data;
			}

			this.setRedrawSuppression(true);
			try {
				$.each(newFragments, function (newFragment, html) {
					var contentEl = $('[data-pjax-fragment]').filter(function () {
						return $.inArray(newFragment, $(this).data('pjaxFragment').split(' ')) != -1;
					}),
					    newContentEl = $(html);

					if (newContentEls) newContentEls.add(newContentEl);else newContentEls = newContentEl;

					if (newContentEl.find('.cms-container').length) {
						throw 'Content loaded via ajax is not allowed to contain tags matching the ".cms-container" selector to avoid infinite loops';
					}

					var origStyle = contentEl.attr('style');
					var origParent = contentEl.parent();
					var origParentLayoutApplied = typeof origParent.data('jlayout') !== 'undefined';
					var layoutClasses = ['east', 'west', 'center', 'north', 'south', 'column-hidden'];
					var elemClasses = contentEl.attr('class');
					var origLayoutClasses = [];
					if (elemClasses) {
						origLayoutClasses = $.grep(elemClasses.split(' '), function (val) {
							return $.inArray(val, layoutClasses) >= 0;
						});
					}

					newContentEl.removeClass(layoutClasses.join(' ')).addClass(origLayoutClasses.join(' '));
					if (origStyle) newContentEl.attr('style', origStyle);

					var styles = newContentEl.find('style').detach();
					if (styles.length) $(document).find('head').append(styles);

					contentEl.replaceWith(newContentEl);

					if (!origParent.is('.cms-container') && origParentLayoutApplied) {
						origParent.layout();
					}
				});

				var newForm = newContentEls.filter('form');
				if (newForm.hasClass('cms-tabset')) newForm.removeClass('cms-tabset').addClass('cms-tabset');
			} finally {
				this.setRedrawSuppression(false);
			}

			this.redraw();
			this.restoreTabState(state && typeof state.tabState !== 'undefined' ? state.tabState : null);

			return newContentEls;
		},

		_findFragments: function _findFragments(fragments) {
			return $('[data-pjax-fragment]').filter(function () {
				var i,
				    nodeFragments = $(this).data('pjaxFragment').split(' ');
				for (i in fragments) {
					if ($.inArray(fragments[i], nodeFragments) != -1) return true;
				}
				return false;
			});
		},

		refresh: function refresh() {
			$(window).trigger('statechange');

			$(this).redraw();
		},

		saveTabState: function saveTabState() {
			if (typeof window.sessionStorage == "undefined" || window.sessionStorage === null) return;

			var selectedTabs = [],
			    url = this._tabStateUrl();
			this.find('.cms-tabset,.ss-tabset').each(function (i, el) {
				var id = $(el).attr('id');
				if (!id) return;
				if (!$(el).data('tabs')) return;
				if ($(el).data('ignoreTabState') || $(el).getIgnoreTabState()) return;

				selectedTabs.push({ id: id, selected: $(el).tabs('option', 'selected') });
			});

			if (selectedTabs) {
				var tabsUrl = 'tabs-' + url;
				try {
					window.sessionStorage.setItem(tabsUrl, JSON.stringify(selectedTabs));
				} catch (err) {
					if (err.code === DOMException.QUOTA_EXCEEDED_ERR && window.sessionStorage.length === 0) {
						return;
					} else {
						throw err;
					}
				}
			}
		},

		restoreTabState: function restoreTabState(overrideStates) {
			var self = this,
			    url = this._tabStateUrl(),
			    hasSessionStorage = typeof window.sessionStorage !== "undefined" && window.sessionStorage,
			    sessionData = hasSessionStorage ? window.sessionStorage.getItem('tabs-' + url) : null,
			    sessionStates = sessionData ? JSON.parse(sessionData) : false;

			this.find('.cms-tabset, .ss-tabset').each(function () {
				var index,
				    tabset = $(this),
				    tabsetId = tabset.attr('id'),
				    tab,
				    forcedTab = tabset.find('.ss-tabs-force-active');

				if (!tabset.data('tabs')) {
					return;
				}

				tabset.tabs('refresh');

				if (forcedTab.length) {
					index = forcedTab.index();
				} else if (overrideStates && overrideStates[tabsetId]) {
					tab = tabset.find(overrideStates[tabsetId].tabSelector);
					if (tab.length) {
						index = tab.index();
					}
				} else if (sessionStates) {
					$.each(sessionStates, function (i, sessionState) {
						if (tabset.is('#' + sessionState.id)) {
							index = sessionState.selected;
						}
					});
				}
				if (index !== null) {
					tabset.tabs('option', 'active', index);
					self.trigger('tabstaterestored');
				}
			});
		},

		clearTabState: function clearTabState(url) {
			if (typeof window.sessionStorage == "undefined") return;

			var s = window.sessionStorage;
			if (url) {
				s.removeItem('tabs-' + url);
			} else {
				for (var i = 0; i < s.length; i++) {
					if (s.key(i).match(/^tabs-/)) s.removeItem(s.key(i));
				}
			}
		},

		clearCurrentTabState: function clearCurrentTabState() {
			this.clearTabState(this._tabStateUrl());
		},

		_tabStateUrl: function _tabStateUrl() {
			return window.history.state.path.replace(/\?.*/, '').replace(/#.*/, '').replace($('base').attr('href'), '');
		},

		showLoginDialog: function showLoginDialog() {
			var tempid = $('body').data('member-tempid'),
			    dialog = $('.leftandmain-logindialog'),
			    url = 'CMSSecurity/login';

			if (dialog.length) dialog.remove();

			url = $.path.addSearchParams(url, {
				'tempid': tempid,
				'BackURL': window.location.href
			});

			dialog = $('<div class="leftandmain-logindialog"></div>');
			dialog.attr('id', new Date().getTime());
			dialog.data('url', url);
			$('body').append(dialog);
		}
	});

	$('.leftandmain-logindialog').entwine({
		onmatch: function onmatch() {
			this._super();

			this.ssdialog({
				iframeUrl: this.data('url'),
				dialogClass: "leftandmain-logindialog-dialog",
				autoOpen: true,
				minWidth: 500,
				maxWidth: 500,
				minHeight: 370,
				maxHeight: 400,
				closeOnEscape: false,
				open: function open() {
					$('.ui-widget-overlay').addClass('leftandmain-logindialog-overlay');
				},
				close: function close() {
					$('.ui-widget-overlay').removeClass('leftandmain-logindialog-overlay');
				}
			});
		},
		onunmatch: function onunmatch() {
			this._super();
		},
		open: function open() {
			this.ssdialog('open');
		},
		close: function close() {
			this.ssdialog('close');
		},
		toggle: function toggle(bool) {
			if (this.is(':visible')) this.close();else this.open();
		},

		reauthenticate: function reauthenticate(data) {
			if (typeof data.SecurityID !== 'undefined') {
				$(':input[name=SecurityID]').val(data.SecurityID);
			}

			if (typeof data.TempID !== 'undefined') {
				$('body').data('member-tempid', data.TempID);
			}
			this.close();
		}
	});

	$('form.loading,.cms-content.loading,.cms-content-fields.loading,.cms-content-view.loading').entwine({
		onmatch: function onmatch() {
			this.append('<div class="cms-content-loading-overlay ui-widget-overlay-light"></div><div class="cms-content-loading-spinner"></div>');
			this._super();
		},
		onunmatch: function onunmatch() {
			this.find('.cms-content-loading-overlay,.cms-content-loading-spinner').remove();
			this._super();
		}
	});

	$('.cms input[type="submit"], .cms button, .cms input[type="reset"], .cms .ss-ui-button').entwine({
		onadd: function onadd() {
			this.addClass('ss-ui-button');
			if (!this.data('button')) this.button();
			this._super();
		},
		onremove: function onremove() {
			if (this.data('button')) this.button('destroy');
			this._super();
		}
	});

	$('.cms .cms-panel-link').entwine({
		onclick: function onclick(e) {
			if ($(this).hasClass('external-link')) {
				e.stopPropagation();

				return;
			}

			var href = this.attr('href'),
			    url = href && !href.match(/^#/) ? href : this.data('href'),
			    data = { pjax: this.data('pjaxTarget') };

			$('.cms-container').loadPanel(url, null, data);
			e.preventDefault();
		}
	});

	$('.cms .ss-ui-button-ajax').entwine({
		onclick: function onclick(e) {
			$(this).removeClass('ui-button-text-only');
			$(this).addClass('ss-ui-button-loading ui-button-text-icons');

			var loading = $(this).find(".ss-ui-loading-icon");

			if (loading.length < 1) {
				loading = $("<span></span>").addClass('ss-ui-loading-icon ui-button-icon-primary ui-icon');

				$(this).prepend(loading);
			}

			loading.show();

			var href = this.attr('href'),
			    url = href ? href : this.data('href');

			jQuery.ajax({
				url: url,

				complete: function complete(xmlhttp, status) {
					var msg = xmlhttp.getResponseHeader('X-Status') ? xmlhttp.getResponseHeader('X-Status') : xmlhttp.responseText;

					try {
						if (typeof msg != "undefined" && msg !== null) eval(msg);
					} catch (e) {}

					loading.hide();

					$(".cms-container").refresh();

					$(this).removeClass('ss-ui-button-loading ui-button-text-icons');
					$(this).addClass('ui-button-text-only');
				},
				dataType: 'html'
			});
			e.preventDefault();
		}
	});

	$('.cms .ss-ui-dialog-link').entwine({
		UUID: null,
		onmatch: function onmatch() {
			this._super();
			this.setUUID(new Date().getTime());
		},
		onunmatch: function onunmatch() {
			this._super();
		},
		onclick: function onclick() {
			this._super();

			var self = this,
			    id = 'ss-ui-dialog-' + this.getUUID();
			var dialog = $('#' + id);
			if (!dialog.length) {
				dialog = $('<div class="ss-ui-dialog" id="' + id + '" />');
				$('body').append(dialog);
			}

			var extraClass = this.data('popupclass') ? this.data('popupclass') : '';

			dialog.ssdialog({ iframeUrl: this.attr('href'), autoOpen: true, dialogExtraClass: extraClass });
			return false;
		}
	});

	$('.cms-content .Actions').entwine({
		onmatch: function onmatch() {
			this.find('.ss-ui-button').click(function () {
				var form = this.form;

				if (form) {
					form.clickedButton = this;

					setTimeout(function () {
						form.clickedButton = null;
					}, 10);
				}
			});

			this.redraw();
			this._super();
		},
		onunmatch: function onunmatch() {
			this._super();
		},
		redraw: function redraw() {
			if (window.debug) console.log('redraw', this.attr('class'), this.get(0));

			this.contents().filter(function () {
				return this.nodeType == 3 && !/\S/.test(this.nodeValue);
			}).remove();

			this.find('.ss-ui-button').each(function () {
				if (!$(this).data('button')) $(this).button();
			});

			this.find('.ss-ui-buttonset').buttonset();
		}
	});

	$('.cms .field.date input.text').entwine({
		onmatch: function onmatch() {
			var holder = $(this).parents('.field.date:first'),
			    config = holder.data();
			if (!config.showcalendar) {
				this._super();
				return;
			}

			config.showOn = 'button';
			if (config.locale && $.datepicker.regional[config.locale]) {
				config = $.extend(config, $.datepicker.regional[config.locale], {});
			}

			$(this).datepicker(config);


			this._super();
		},
		onunmatch: function onunmatch() {
			this._super();
		}
	});

	$('.cms .field.dropdown select, .cms .field select[multiple], .fieldholder-small select.dropdown').entwine({
		onmatch: function onmatch() {
			if (this.is('.no-chzn')) {
				this._super();
				return;
			}

			if (!this.data('placeholder')) this.data('placeholder', ' ');

			this.removeClass('has-chzn chzn-done');
			this.siblings('.chzn-container').remove();

			applyChosen(this);

			this._super();
		},
		onunmatch: function onunmatch() {
			this._super();
		}
	});

	$(".cms-panel-layout").entwine({
		redraw: function redraw() {
			if (window.debug) console.log('redraw', this.attr('class'), this.get(0));
		}
	});

	$('.cms .ss-gridfield').entwine({
		showDetailView: function showDetailView(url) {
			var params = window.location.search.replace(/^\?/, '');
			if (params) url = $.path.addSearchParams(url, params);
			$('.cms-container').loadPanel(url);
		}
	});

	$('.cms-search-form').entwine({
		onsubmit: function onsubmit(e) {
			var nonEmptyInputs, url;

			nonEmptyInputs = this.find(':input:not(:submit)').filter(function () {
				var vals = $.grep($(this).fieldValue(), function (val) {
					return val;
				});
				return vals.length;
			});

			url = this.attr('action');

			if (nonEmptyInputs.length) {
				url = $.path.addSearchParams(url, nonEmptyInputs.serialize());
			}

			var container = this.closest('.cms-container');
			container.find('.cms-edit-form').tabs('select', 0);
			container.loadPanel(url, "", {}, true);

			return false;
		}
	});

	$(".cms-search-form button[type=reset], .cms-search-form input[type=reset]").entwine({
		onclick: function onclick(e) {
			e.preventDefault();

			var form = $(this).parents('form');

			form.clearForm();
			form.find(".dropdown select").prop('selectedIndex', 0).trigger("liszt:updated");
			form.submit();
		}
	});

	window._panelDeferredCache = {};
	$('.cms-panel-deferred').entwine({
		onadd: function onadd() {
			this._super();
			this.redraw();
		},
		onremove: function onremove() {
			if (window.debug) console.log('saving', this.data('url'), this);

			if (!this.data('deferredNoCache')) window._panelDeferredCache[this.data('url')] = this.html();
			this._super();
		},
		redraw: function redraw() {
			if (window.debug) console.log('redraw', this.attr('class'), this.get(0));

			var self = this,
			    url = this.data('url');
			if (!url) throw 'Elements of class .cms-panel-deferred need a "data-url" attribute';

			this._super();

			if (!this.children().length) {
				if (!this.data('deferredNoCache') && typeof window._panelDeferredCache[url] !== 'undefined') {
					this.html(window._panelDeferredCache[url]);
				} else {
					this.addClass('loading');
					$.ajax({
						url: url,
						complete: function complete() {
							self.removeClass('loading');
						},
						success: function success(data, status, xhr) {
							self.html(data);
						}
					});
				}
			}
		}
	});

	$('.cms-tabset').entwine({
		onadd: function onadd() {
			this.redrawTabs();
			this._super();
		},
		onremove: function onremove() {
			if (this.data('tabs')) this.tabs('destroy');
			this._super();
		},
		redrawTabs: function redrawTabs() {
			this.rewriteHashlinks();

			var id = this.attr('id'),
			    activeTab = this.find('ul:first .ui-tabs-active');

			if (!this.data('uiTabs')) this.tabs({
				active: activeTab.index() != -1 ? activeTab.index() : 0,
				beforeLoad: function beforeLoad(e, ui) {
					return false;
				},
				activate: function activate(e, ui) {
					var actions = $(this).closest('form').find('.Actions');
					if ($(ui.newTab).closest('li').hasClass('readonly')) {
						actions.fadeOut();
					} else {
						actions.show();
					}
				}
			});
		},

		rewriteHashlinks: function rewriteHashlinks() {
			$(this).find('ul a').each(function () {
				if (!$(this).attr('href')) return;
				var matches = $(this).attr('href').match(/#.*/);
				if (!matches) return;
				$(this).attr('href', document.location.href.replace(/#.*/, '') + matches[0]);
			});
		}
	});

	$('#filters-button').entwine({
		onmatch: function onmatch() {
			this._super();

			this.data('collapsed', true);
			this.data('animating', false);
		},
		onunmatch: function onunmatch() {
			this._super();
		},
		showHide: function showHide() {
			var self = this,
			    $filters = $('.cms-content-filters').first(),
			    collapsed = this.data('collapsed');

			if (this.data('animating')) {
				return;
			}

			this.toggleClass('active');
			this.data('animating', true);

			$filters[collapsed ? 'slideDown' : 'slideUp']({
				complete: function complete() {
					self.data('collapsed', !collapsed);
					self.data('animating', false);
				}
			});
		},
		onclick: function onclick() {
			this.showHide();
		}
	});
});

var statusMessage = function statusMessage(text, type) {
	text = jQuery('<div/>').text(text).html();
	jQuery.noticeAdd({ text: text, type: type, stayTime: 5000, inEffect: { left: '0', opacity: 'show' } });
};

var errorMessage = function errorMessage(text) {
	jQuery.noticeAdd({ text: text, type: 'error', stayTime: 5000, inEffect: { left: '0', opacity: 'show' } });
};

},{"config":"config","jQuery":"jQuery","router":"router"}],14:[function(require,module,exports){
'use strict';

require('../../src/LeftAndMain.Layout.js');
require('../../src/LeftAndMain.js');
require('../../src/LeftAndMain.ActionTabSet.js');
require('../../src/LeftAndMain.Panel.js');
require('../../src/LeftAndMain.Tree.js');
require('../../src/LeftAndMain.Content.js');
require('../../src/LeftAndMain.EditForm.js');
require('../../src/LeftAndMain.Menu.js');
require('../../src/LeftAndMain.Preview.js');
require('../../src/LeftAndMain.BatchActions.js');
require('../../src/LeftAndMain.FieldHelp.js');
require('../../src/LeftAndMain.FieldDescriptionToggle.js');
require('../../src/LeftAndMain.TreeDropdownField.js');

},{"../../src/LeftAndMain.ActionTabSet.js":1,"../../src/LeftAndMain.BatchActions.js":2,"../../src/LeftAndMain.Content.js":3,"../../src/LeftAndMain.EditForm.js":4,"../../src/LeftAndMain.FieldDescriptionToggle.js":5,"../../src/LeftAndMain.FieldHelp.js":6,"../../src/LeftAndMain.Layout.js":7,"../../src/LeftAndMain.Menu.js":8,"../../src/LeftAndMain.Panel.js":9,"../../src/LeftAndMain.Preview.js":10,"../../src/LeftAndMain.Tree.js":11,"../../src/LeftAndMain.TreeDropdownField.js":12,"../../src/LeftAndMain.js":13}]},{},[14])
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm5vZGVfbW9kdWxlcy9icm93c2VyaWZ5L25vZGVfbW9kdWxlcy9icm93c2VyLXBhY2svX3ByZWx1ZGUuanMiLCJhZG1pbi9qYXZhc2NyaXB0L3NyYy9MZWZ0QW5kTWFpbi5BY3Rpb25UYWJTZXQuanMiLCJhZG1pbi9qYXZhc2NyaXB0L3NyYy9MZWZ0QW5kTWFpbi5CYXRjaEFjdGlvbnMuanMiLCJhZG1pbi9qYXZhc2NyaXB0L3NyYy9MZWZ0QW5kTWFpbi5Db250ZW50LmpzIiwiYWRtaW4vamF2YXNjcmlwdC9zcmMvTGVmdEFuZE1haW4uRWRpdEZvcm0uanMiLCJhZG1pbi9qYXZhc2NyaXB0L3NyYy9MZWZ0QW5kTWFpbi5GaWVsZERlc2NyaXB0aW9uVG9nZ2xlLmpzIiwiYWRtaW4vamF2YXNjcmlwdC9zcmMvTGVmdEFuZE1haW4uRmllbGRIZWxwLmpzIiwiYWRtaW4vamF2YXNjcmlwdC9zcmMvTGVmdEFuZE1haW4uTGF5b3V0LmpzIiwiYWRtaW4vamF2YXNjcmlwdC9zcmMvTGVmdEFuZE1haW4uTWVudS5qcyIsImFkbWluL2phdmFzY3JpcHQvc3JjL0xlZnRBbmRNYWluLlBhbmVsLmpzIiwiYWRtaW4vamF2YXNjcmlwdC9zcmMvTGVmdEFuZE1haW4uUHJldmlldy5qcyIsImFkbWluL2phdmFzY3JpcHQvc3JjL0xlZnRBbmRNYWluLlRyZWUuanMiLCJhZG1pbi9qYXZhc2NyaXB0L3NyYy9MZWZ0QW5kTWFpbi5UcmVlRHJvcGRvd25GaWVsZC5qcyIsImFkbWluL2phdmFzY3JpcHQvc3JjL0xlZnRBbmRNYWluLmpzIiwiYWRtaW4vamF2YXNjcmlwdC9zcmMvYnVuZGxlcy9sZWZ0YW5kbWFpbi5qcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTs7Ozs7Ozs7O0FDVUEsaUJBQUUsT0FBRixDQUFVLElBQVYsRUFBZ0IsVUFBUyxDQUFULEVBQVk7QUFNM0IsR0FBRSxnQ0FBRixFQUFvQyxPQUFwQyxDQUE0QztBQUUzQyxrQkFBZ0IsSUFBaEI7O0FBRUEsU0FB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QixFQUFQLENBRHdDO0FBRXhDLFFBQU8sU0FBUCxDQUFpQixFQUFDLE1BQU0sSUFBTixFQUFZLE1BQU0sSUFBTixFQUFZLFVBQVUsSUFBVixFQUFnQixVQUFVLEVBQUMsTUFBTSxHQUFOLEVBQVcsU0FBUyxNQUFULEVBQXRCLEVBQTFELEVBRndDO0NBQXJCOztBQUtwQixJQUFJLGVBQWUsU0FBZixZQUFlLENBQVMsSUFBVCxFQUFlO0FBQ2pDLFFBQU8sU0FBUCxDQUFpQixFQUFDLE1BQU0sSUFBTixFQUFZLE1BQU0sT0FBTixFQUFlLFVBQVUsSUFBVixFQUFnQixVQUFVLEVBQUMsTUFBTSxHQUFOLEVBQVcsU0FBUyxNQUFULEVBQXRCLEVBQTdELEVBRGlDO0NBQWY7Ozs7O0FDNytDbkIsUUFBUSxpQ0FBUjtBQUNBLFFBQVEsMEJBQVI7QUFDQSxRQUFRLHVDQUFSO0FBQ0EsUUFBUSxnQ0FBUjtBQUNBLFFBQVEsK0JBQVI7QUFDQSxRQUFRLGtDQUFSO0FBQ0EsUUFBUSxtQ0FBUjtBQUNBLFFBQVEsK0JBQVI7QUFDQSxRQUFRLGtDQUFSO0FBQ0EsUUFBUSx1Q0FBUjtBQUNBLFFBQVEsb0NBQVI7QUFDQSxRQUFRLGlEQUFSO0FBQ0EsUUFBUSw0Q0FBUiIsImZpbGUiOiJnZW5lcmF0ZWQuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlc0NvbnRlbnQiOlsiKGZ1bmN0aW9uIGUodCxuLHIpe2Z1bmN0aW9uIHMobyx1KXtpZighbltvXSl7aWYoIXRbb10pe3ZhciBhPXR5cGVvZiByZXF1aXJlPT1cImZ1bmN0aW9uXCImJnJlcXVpcmU7aWYoIXUmJmEpcmV0dXJuIGEobywhMCk7aWYoaSlyZXR1cm4gaShvLCEwKTt2YXIgZj1uZXcgRXJyb3IoXCJDYW5ub3QgZmluZCBtb2R1bGUgJ1wiK28rXCInXCIpO3Rocm93IGYuY29kZT1cIk1PRFVMRV9OT1RfRk9VTkRcIixmfXZhciBsPW5bb109e2V4cG9ydHM6e319O3Rbb11bMF0uY2FsbChsLmV4cG9ydHMsZnVuY3Rpb24oZSl7dmFyIG49dFtvXVsxXVtlXTtyZXR1cm4gcyhuP246ZSl9LGwsbC5leHBvcnRzLGUsdCxuLHIpfXJldHVybiBuW29dLmV4cG9ydHN9dmFyIGk9dHlwZW9mIHJlcXVpcmU9PVwiZnVuY3Rpb25cIiYmcmVxdWlyZTtmb3IodmFyIG89MDtvPHIubGVuZ3RoO28rKylzKHJbb10pO3JldHVybiBzfSkiLCIvKipcbiAqIEZpbGU6IExlZnRBbmRNYWluLkFjdGlvblRhYnNldC5qc1xuICpcbiAqIENvbnRhaW5zIHJ1bGVzIGZvciAuc3MtdWktYWN0aW9uLXRhYnNldCwgdXNlZCBmb3I6XG4gKiAqIFNpdGUgdHJlZSBhY3Rpb24gdGFicyAodG8gcGVyZm9ybSBhY3Rpb25zIG9uIHRoZSBzaXRlIHRyZWUpXG4gKiAqIEFjdGlvbnMgbWVudSAoRWRpdCBwYWdlIGFjdGlvbnMpXG4gKlxuICovXG5pbXBvcnQgJCBmcm9tICdqUXVlcnknO1xuXG4kLmVudHdpbmUoJ3NzJywgZnVuY3Rpb24oJCkge1xuXHQvKipcblx0ICogR2VuZXJpYyBydWxlcyBmb3IgYWxsIHNzLXVpLWFjdGlvbi10YWJzZXRzXG5cdCAqICogQWN0aW9uTWVudXNcblx0ICogKiBTaXRlVHJlZSBBY3Rpb25UYWJzXG5cdCAqL1xuXHQkKCcuc3MtdGFic2V0LnNzLXVpLWFjdGlvbi10YWJzZXQnKS5lbnR3aW5lKHtcblx0XHQvLyBJZ25vcmUgdGFiIHN0YXRlIHNvIGl0IHdpbGwgbm90IGJlIHJlb3BlbmVkIG9uIGZvcm0gc3VibWlzc2lvbi5cblx0XHRJZ25vcmVUYWJTdGF0ZTogdHJ1ZSxcblxuXHRcdG9uYWRkOiBmdW5jdGlvbigpIHtcblx0XHRcdC8vIE1ha2Ugc3VyZSB0aGUgLnNzLXRhYnNldCBpcyBhbHJlYWR5IGluaXRpYWxpc2VkIHRvIGFwcGx5IG91ciBtb2RpZmljYXRpb25zIG9uIHRvcC5cblx0XHRcdHRoaXMuX3N1cGVyKCk7XG5cdFx0XHQvL1NldCBhY3Rpb25UYWJzIHRvIGFsbG93IGNsb3NpbmcgYW5kIGJlIGNsb3NlZCBieSBkZWZhdWx0XG5cdFx0XHR0aGlzLnRhYnMoeydjb2xsYXBzaWJsZSc6IHRydWUsICdhY3RpdmUnOiBmYWxzZX0pO1xuXHRcdH0sXG5cblx0XHRvbnJlbW92ZTogZnVuY3Rpb24oKSB7XG5cdFx0XHQvLyBSZW1vdmUgYWxsIGJvdW5kIGV2ZW50cy5cblx0XHRcdC8vIFRoaXMgZ3VhcmRzIGFnYWluc3QgYW4gZWRnZSBjYXNlIHdoZXJlIHRoZSBjbGljayBoYW5kbGVycyBhcmUgbm90IHVuYm91bmRcblx0XHRcdC8vIGJlY2F1c2UgdGhlIHBhbmVsIGlzIHN0aWxsIG9wZW4gd2hlbiB0aGUgYWpheCBlZGl0IGZvcm0gcmVsb2Fkcy5cblx0XHRcdHZhciBmcmFtZSA9ICQoJy5jbXMtY29udGFpbmVyJykuZmluZCgnaWZyYW1lJyk7XG5cdFx0XHRmcmFtZS5lYWNoKGZ1bmN0aW9uKGluZGV4LCBpZnJhbWUpe1xuXHRcdFx0XHR0cnkge1xuXHRcdFx0XHRcdCQoaWZyYW1lKS5jb250ZW50cygpLm9mZignY2xpY2suc3MtdWktYWN0aW9uLXRhYnNldCcpO1xuXHRcdFx0XHR9IGNhdGNoIChlKSB7XG5cdFx0XHRcdFx0Y29uc29sZS53YXJuKCdVbmFibGUgdG8gYWNjZXNzIGlmcmFtZSwgcG9zc2libGUgaHR0cHMgbWlzLW1hdGNoJyk7XG5cdFx0XHRcdH1cblx0XHRcdH0pO1xuXHRcdFx0JChkb2N1bWVudCkub2ZmKCdjbGljay5zcy11aS1hY3Rpb24tdGFic2V0Jyk7XG5cblx0XHRcdHRoaXMuX3N1cGVyKCk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIERlYWwgd2l0aCBhdmFpbGFibGUgdmVydGljYWwgc3BhY2Vcblx0XHQgKi9cblx0XHQnb250YWJzYmVmb3JlYWN0aXZhdGUnOiBmdW5jdGlvbihldmVudCwgdWkpIHtcblx0XHRcdHRoaXMucmlzZVVwKGV2ZW50LCB1aSk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEhhbmRsZSBvcGVuaW5nIGFuZCBjbG9zaW5nIHRhYnNcblx0XHQgKi9cblx0XHRvbmNsaWNrOiBmdW5jdGlvbihldmVudCwgdWkpIHtcblx0XHRcdHRoaXMuYXR0YWNoQ2xvc2VIYW5kbGVyKGV2ZW50LCB1aSk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdlbmVyaWMgZnVuY3Rpb24gdG8gY2xvc2Ugb3BlbiB0YWJzLiBTdG9yZXMgZXZlbnQgaW4gYSBoYW5kbGVyLFxuXHRcdCAqIGFuZCByZW1vdmVzIHRoZSBib3VuZCBldmVudCBvbmNlIGFjdGl2YXRlZC5cblx0XHQgKlxuXHRcdCAqIE5vdGU6IFNob3VsZCBiZSBjYWxsZWQgYnkgYSBjbGljayBldmVudCBhdHRhY2hlZCB0byAndGhpcydcblx0XHQgKi9cblx0XHRhdHRhY2hDbG9zZUhhbmRsZXI6IGZ1bmN0aW9uKGV2ZW50LCB1aSkge1xuXHRcdFx0dmFyIHRoYXQgPSB0aGlzLCBmcmFtZSA9ICQoJy5jbXMtY29udGFpbmVyJykuZmluZCgnaWZyYW1lJyksIGNsb3NlSGFuZGxlcjtcblxuXHRcdFx0Ly8gQ3JlYXRlIGEgaGFuZGxlciBmb3IgdGhlIGNsaWNrIGV2ZW50IHNvIHdlIGNhbiBjbG9zZSB0YWJzXG5cdFx0XHQvLyBhbmQgZWFzaWx5IHJlbW92ZSB0aGUgZXZlbnQgb25jZSBkb25lXG5cdFx0XHRjbG9zZUhhbmRsZXIgPSBmdW5jdGlvbihldmVudCkge1xuXHRcdFx0XHR2YXIgcGFuZWwsIGZyYW1lO1xuXHRcdFx0XHRwYW5lbCA9ICQoZXZlbnQudGFyZ2V0KS5jbG9zZXN0KCcuc3MtdWktYWN0aW9uLXRhYnNldCAudWktdGFicy1wYW5lbCcpO1xuXG5cdFx0XHRcdC8vIElmIGFueXRoaW5nIGV4Y2VwdCB0aGUgdWktbmF2IGJ1dHRvbiBvciBwYW5lbCBpcyBjbGlja2VkLFxuXHRcdFx0XHQvLyBjbG9zZSBwYW5lbCBhbmQgcmVtb3ZlIGhhbmRsZXIuIFdlIGNhbid0IGNsb3NlIGlmIGNsaWNrIHdhc1xuXHRcdFx0XHQvLyB3aXRoaW4gcGFuZWwsIGFzIGl0IG1pZ2h0J3ZlIGNhdXNlZCBhIGJ1dHRvbiBhY3Rpb24sXG5cdFx0XHRcdC8vIGFuZCB3ZSBuZWVkIHRvIHNob3cgaXRzIGxvYWRpbmcgaW5kaWNhdG9yLlxuXHRcdFx0XHRpZiAoISQoZXZlbnQudGFyZ2V0KS5jbG9zZXN0KHRoYXQpLmxlbmd0aCAmJiAhcGFuZWwubGVuZ3RoKSB7XG5cdFx0XHRcdFx0dGhhdC50YWJzKCdvcHRpb24nLCAnYWN0aXZlJywgZmFsc2UpOyAvLyBjbG9zZSB0YWJzXG5cblx0XHRcdFx0XHQvLyByZW1vdmUgY2xpY2sgZXZlbnQgZnJvbSBvYmplY3RzIGl0IGlzIGJvdW5kIHRvIChpZnJhbWUncyBhbmQgZG9jdW1lbnQpXG5cdFx0XHRcdFx0ZnJhbWUgPSAkKCcuY21zLWNvbnRhaW5lcicpLmZpbmQoJ2lmcmFtZScpO1xuXHRcdFx0XHRcdGZyYW1lLmVhY2goZnVuY3Rpb24oaW5kZXgsIGlmcmFtZSl7XG5cdFx0XHRcdFx0XHQkKGlmcmFtZSkuY29udGVudHMoKS5vZmYoJ2NsaWNrLnNzLXVpLWFjdGlvbi10YWJzZXQnLCBjbG9zZUhhbmRsZXIpO1xuXHRcdFx0XHRcdH0pO1xuXHRcdFx0XHRcdCQoZG9jdW1lbnQpLm9mZignY2xpY2suc3MtdWktYWN0aW9uLXRhYnNldCcsIGNsb3NlSGFuZGxlcik7XG5cdFx0XHRcdH1cblx0XHRcdH07XG5cblx0XHRcdC8vIEJpbmQgY2xpY2sgZXZlbnQgdG8gZG9jdW1lbnQsIGFuZCB1c2UgY2xvc2VIYW5kbGVyIHRvIGhhbmRsZSB0aGUgZXZlbnRcblx0XHRcdCQoZG9jdW1lbnQpLm9uKCdjbGljay5zcy11aS1hY3Rpb24tdGFic2V0JywgY2xvc2VIYW5kbGVyKTtcblx0XHRcdC8vIE1ha2Ugc3VyZSBpZnJhbWUgY2xpY2sgYWxzbyBjbG9zZXMgdGFiXG5cdFx0XHQvLyBpZnJhbWUgbmVlZHMgYSBzcGVjaWFsIGNhc2UsIGVsc2UgdGhlIGNsaWNrIGV2ZW50IHdpbGwgbm90IHJlZ2lzdGVyIGhlcmVcblx0XHRcdGlmKGZyYW1lLmxlbmd0aCA+IDApe1xuXHRcdFx0XHRmcmFtZS5lYWNoKGZ1bmN0aW9uKGluZGV4LCBpZnJhbWUpIHtcblx0XHRcdFx0XHQkKGlmcmFtZSkuY29udGVudHMoKS5vbignY2xpY2suc3MtdWktYWN0aW9uLXRhYnNldCcsIGNsb3NlSGFuZGxlcik7XG5cdFx0XHRcdH0pO1xuXHRcdFx0fVxuXHRcdH0sXG5cdFx0LyoqXG5cdFx0ICogRnVuY3Rpb24gcmlzZVVwIGNoZWNrcyB0byBzZWUgaWYgYSB0YWIgc2hvdWxkIGJlIG9wZW5lZCB1cHdhcmRzXG5cdFx0ICogKGJhc2VkIG9uIHNwYWNlIGNvbmNlcm5zKS4gSWYgdHJ1ZSwgdGhlIHJpc2UtdXAgY2xhc3MgaXMgYXBwbGllZFxuXHRcdCAqIGFuZCBhIG5ldyBwb3NpdGlvbiBpcyBjYWxjdWxhdGVkIGFuZCBhcHBsaWVkIHRvIHRoZSBlbGVtZW50LlxuXHRcdCAqXG5cdFx0ICogTm90ZTogU2hvdWxkIGJlIGNhbGxlZCBieSBhIHRhYnNiZWZvcmVhY3RpdmF0ZSBldmVudFxuXHRcdCAqL1xuXHRcdHJpc2VVcDogZnVuY3Rpb24oZXZlbnQsIHVpKSB7XG5cdFx0XHR2YXIgZWxIZWlnaHQsIHRyaWdnZXIsIGVuZE9mV2luZG93LCBlbFBvcywgYWN0aXZlUGFuZWwsIGFjdGl2ZVRhYiwgdG9wUG9zaXRpb24sIGNvbnRhaW5lclNvdXRoLCBwYWRkaW5nO1xuXG5cdFx0XHQvLyBHZXQgdGhlIG51bWJlcnMgbmVlZGVkIHRvIGNhbGN1bGF0ZSBwb3NpdGlvbnNcblx0XHRcdGVsSGVpZ2h0ID0gJCh0aGlzKS5maW5kKCcudWktdGFicy1wYW5lbCcpLm91dGVySGVpZ2h0KCk7XG5cdFx0XHR0cmlnZ2VyID0gJCh0aGlzKS5maW5kKCcudWktdGFicy1uYXYnKS5vdXRlckhlaWdodCgpO1xuXHRcdFx0ZW5kT2ZXaW5kb3cgPSAoJCh3aW5kb3cpLmhlaWdodCgpICsgJChkb2N1bWVudCkuc2Nyb2xsVG9wKCkpIC0gdHJpZ2dlcjtcblx0XHRcdGVsUG9zID0gJCh0aGlzKS5maW5kKCcudWktdGFicy1uYXYnKS5vZmZzZXQoKS50b3A7XG5cblx0XHRcdGFjdGl2ZVBhbmVsID0gdWkubmV3UGFuZWw7XG5cdFx0XHRhY3RpdmVUYWIgPSB1aS5uZXdUYWI7XG5cblx0XHRcdGlmIChlbFBvcyArIGVsSGVpZ2h0ID49IGVuZE9mV2luZG93ICYmIGVsUG9zIC0gZWxIZWlnaHQgPiAwKXtcblx0XHRcdFx0dGhpcy5hZGRDbGFzcygncmlzZS11cCcpO1xuXG5cdFx0XHRcdGlmIChhY3RpdmVUYWIucG9zaXRpb24oKSAhPT0gbnVsbCl7XG5cdFx0XHRcdFx0dG9wUG9zaXRpb24gPSAtYWN0aXZlUGFuZWwub3V0ZXJIZWlnaHQoKTtcblx0XHRcdFx0XHRjb250YWluZXJTb3V0aCA9IGFjdGl2ZVBhbmVsLnBhcmVudHMoJy5zb3V0aCcpO1xuXHRcdFx0XHRcdGlmIChjb250YWluZXJTb3V0aCl7XG5cdFx0XHRcdFx0XHQvLyBJZiBjb250YWluZXIgaXMgdGhlIHNvdXRoZXJuIHBhbmVsLCBtYWtlIHRhYiBhcHBlYXIgZnJvbSB0aGUgdG9wIG9mIHRoZSBjb250YWluZXJcblx0XHRcdFx0XHRcdHBhZGRpbmcgPSBhY3RpdmVUYWIub2Zmc2V0KCkudG9wIC0gY29udGFpbmVyU291dGgub2Zmc2V0KCkudG9wO1xuXHRcdFx0XHRcdFx0dG9wUG9zaXRpb24gPSB0b3BQb3NpdGlvbi1wYWRkaW5nO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0XHQkKGFjdGl2ZVBhbmVsKS5jc3MoJ3RvcCcsdG9wUG9zaXRpb24rXCJweFwiKTtcblx0XHRcdFx0fVxuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0Ly8gZWxzZSByZW1vdmUgdGhlIHJpc2UtdXAgY2xhc3MgYW5kIHNldCB0b3AgdG8gMFxuXHRcdFx0XHR0aGlzLnJlbW92ZUNsYXNzKCdyaXNlLXVwJyk7XG5cdFx0XHRcdGlmIChhY3RpdmVUYWIucG9zaXRpb24oKSAhPT0gbnVsbCl7XG5cdFx0XHRcdFx0JChhY3RpdmVQYW5lbCkuY3NzKCd0b3AnLCcwcHgnKTtcblx0XHRcdFx0fVxuXHRcdFx0fVxuXHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdH1cblx0fSk7XG5cblxuXHQvKipcblx0ICogQWN0aW9uTWVudXNcblx0ICogKiBTcGVjaWZpYyBydWxlcyBmb3IgQWN0aW9uTWVudXMsIHVzZWQgZm9yIGVkaXQgcGFnZSBhY3Rpb25zXG5cdCAqL1xuXHQkKCcuY21zLWNvbnRlbnQtYWN0aW9ucyAuc3MtdGFic2V0LnNzLXVpLWFjdGlvbi10YWJzZXQnKS5lbnR3aW5lKHtcblx0XHQvKipcblx0XHQgKiBNYWtlIG5lY2Vzc2FyeSBhZGp1c3RtZW50cyBiZWZvcmUgdGFiIGlzIGFjdGl2YXRlZFxuXHRcdCAqL1xuXHRcdCdvbnRhYnNiZWZvcmVhY3RpdmF0ZSc6IGZ1bmN0aW9uKGV2ZW50LCB1aSkge1xuXHRcdFx0dGhpcy5fc3VwZXIoZXZlbnQsIHVpKTtcblx0XHRcdC8vU2V0IHRoZSBwb3NpdGlvbiBvZiB0aGUgb3BlbmluZyB0YWIgKGlmIGl0IGV4aXN0cylcblx0XHRcdGlmKCQodWkubmV3UGFuZWwpLmxlbmd0aCA+IDApe1xuXHRcdFx0XHQkKHVpLm5ld1BhbmVsKS5jc3MoJ2xlZnQnLCB1aS5uZXdUYWIucG9zaXRpb24oKS5sZWZ0K1wicHhcIik7XG5cdFx0XHR9XG5cdFx0fVxuXHR9KTtcblxuXHQvKipcblx0ICogU2l0ZVRyZWUgQWN0aW9uVGFic1xuXHQgKiBTcGVjaWZpYyBydWxlcyBmb3Igc2l0ZSB0cmVlIGFjdGlvbiB0YWJzLiBBcHBsaWVzIHRvIHRhYnNcblx0ICogd2l0aGluIHRoZSBleHBhbmRlZCBjb250ZW50IGFyZWEsIGFuZCB3aXRoaW4gdGhlIHNpZGViYXJcblx0ICovXG5cdCQoJy5jbXMtYWN0aW9ucy1yb3cuc3MtdGFic2V0LnNzLXVpLWFjdGlvbi10YWJzZXQnKS5lbnR3aW5lKHtcblx0XHQvKipcblx0XHQgKiBNYWtlIG5lY2Vzc2FyeSBhZGp1c3RtZW50cyBiZWZvcmUgdGFiIGlzIGFjdGl2YXRlZFxuXHRcdCAqL1xuXHRcdCdvbnRhYnNiZWZvcmVhY3RpdmF0ZSc6IGZ1bmN0aW9uKGV2ZW50LCB1aSkge1xuXHRcdFx0dGhpcy5fc3VwZXIoZXZlbnQsIHVpKTtcblx0XHRcdC8vIFJlbW92ZSB0YWJzZXQgb3BlbiBjbGFzc2VzIChMYXN0IGdldHMgYSB1bmlxdWUgY2xhc3Ncblx0XHRcdC8vIGluIHRoZSBiaWdnZXIgc2l0ZXRyZWUuIFJlbW92ZSB0aGlzIGlmIHdlIGhhdmUgaXQpXG5cdFx0XHQkKHRoaXMpLmNsb3Nlc3QoJy5zcy11aS1hY3Rpb24tdGFic2V0Jylcblx0XHRcdFx0XHQucmVtb3ZlQ2xhc3MoJ3RhYnNldC1vcGVuIHRhYnNldC1vcGVuLWxhc3QnKTtcblx0XHR9XG5cdH0pO1xuXG5cdC8qKlxuXHQgKiBTaXRlVHJlZSBBY3Rpb25UYWJzOiBleHBhbmRlZFxuXHQgKiAqIFNwZWNpZmljIHJ1bGVzIGZvciBzaXRlVHJlZSBhY3Rpb25zIHdpdGhpbiB0aGUgZXhwYW5kZWQgY29udGVudCBhcmVhLlxuXHQgKi9cblx0JCgnLmNtcy1jb250ZW50LWZpZWxkcyAuc3MtdGFic2V0LnNzLXVpLWFjdGlvbi10YWJzZXQnKS5lbnR3aW5lKHtcblx0XHQvKipcblx0XHQgKiBNYWtlIG5lY2Vzc2FyeSBhZGp1c3RtZW50cyBiZWZvcmUgdGFiIGlzIGFjdGl2YXRlZFxuXHRcdCAqL1xuXHRcdCdvbnRhYnNiZWZvcmVhY3RpdmF0ZSc6IGZ1bmN0aW9uKGV2ZW50LCB1aSkge1xuXHRcdFx0dGhpcy5fc3VwZXIoZXZlbnQsIHVpKTtcblx0XHRcdGlmKCQoIHVpLm5ld1BhbmVsKS5sZW5ndGggPiAwKXtcblx0XHRcdFx0aWYoJCh1aS5uZXdUYWIpLmhhc0NsYXNzKFwibGFzdFwiKSl7XG5cdFx0XHRcdFx0Ly8gQWxpZ24gb3BlbiB0YWIgdG8gdGhlIHJpZ2h0IChiZWNhdXNlIG9wZW5lZCB0YWIgaXMgbGFzdClcblx0XHRcdFx0XHQkKHVpLm5ld1BhbmVsKS5jc3MoeydsZWZ0JzogJ2F1dG8nLCAncmlnaHQnOiAnMHB4J30pO1xuXG5cdFx0XHRcdFx0Ly8gTGFzdCBuZWVkcyB0byBiZSBzdHlsZWQgZGlmZmVyZW50bHkgd2hlbiBvcGVuLCBzbyBhcHBseSBhIHVuaXF1ZSBjbGFzc1xuXHRcdFx0XHRcdCQodWkubmV3UGFuZWwpLnBhcmVudCgpLmFkZENsYXNzKCd0YWJzZXQtb3Blbi1sYXN0Jyk7XG5cdFx0XHRcdH1lbHNle1xuXHRcdFx0XHRcdC8vIEFzc2lnbiBwb3NpdGlvbiB0byB0YWJwYW5lbCBiYXNlZCBvbiBwb3NpdGlvbiBvZiByZWxpdmVudCBhY3RpdmUgdGFiIGl0ZW1cblx0XHRcdFx0XHQkKHVpLm5ld1BhbmVsKS5jc3MoJ2xlZnQnLCB1aS5uZXdUYWIucG9zaXRpb24oKS5sZWZ0K1wicHhcIik7XG5cblx0XHRcdFx0XHQvLyBJZiB0aGlzIGlzIHRoZSBmaXJzdCB0YWIsIG1ha2Ugc3VyZSB0aGUgcG9zaXRpb24gZG9lc24ndCBpbmNsdWRlIGJvcmRlclxuXHRcdFx0XHRcdC8vIChoYXJkIHNldCBwb3NpdGlvbiB0byAwICksIGFuZCBhZGQgdGhlIHRhYi1zZXQgb3BlbiBjbGFzc1xuXHRcdFx0XHRcdGlmKCQodWkubmV3VGFiKS5oYXNDbGFzcyhcImZpcnN0XCIpKXtcblx0XHRcdFx0XHRcdCQodWkubmV3UGFuZWwpLmNzcygnbGVmdCcsXCIwcHhcIik7XG5cdFx0XHRcdFx0XHQkKHVpLm5ld1BhbmVsKS5wYXJlbnQoKS5hZGRDbGFzcygndGFic2V0LW9wZW4nKTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH1cblx0XHRcdH1cblx0XHR9XG5cdH0pO1xuXG5cdC8qKlxuXHQgKiBTaXRlVHJlZSBBY3Rpb25UYWJzOiBzaWRlYmFyXG5cdCAqICogU3BlY2lmaWMgcnVsZXMgZm9yIHdoZW4gdGhlIHNpdGUgdHJlZSBhY3Rpb25zIHBhbmVsIGFwcGVhcnMgaW5cblx0ICogKiB0aGUgc2lkZS1iYXJcblx0ICovXG5cdCQoJy5jbXMtdHJlZS12aWV3LXNpZGViYXIgLmNtcy1hY3Rpb25zLXJvdy5zcy10YWJzZXQuc3MtdWktYWN0aW9uLXRhYnNldCcpLmVudHdpbmUoe1xuXG5cdFx0Ly8gSWYgYWN0aW9ucyBwYW5lbCBpcyB3aXRoaW4gdGhlIHNpZGViYXIsIGFwcGx5IGFjdGl2ZSBjbGFzc1xuXHRcdC8vIHRvIGhlbHAgYW5pbWF0ZSBvcGVuL2Nsb3NlIG9uIGhvdmVyXG5cdFx0J2Zyb20gLnVpLXRhYnMtbmF2IGxpJzoge1xuXHRcdFx0b25ob3ZlcjogZnVuY3Rpb24oZSkge1xuXHRcdFx0XHQkKGUudGFyZ2V0KS5wYXJlbnQoKS5maW5kKCdsaSAuYWN0aXZlJykucmVtb3ZlQ2xhc3MoJ2FjdGl2ZScpO1xuXHRcdFx0XHQkKGUudGFyZ2V0KS5maW5kKCdhJykuYWRkQ2xhc3MoJ2FjdGl2ZScpO1xuXHRcdFx0fVxuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBNYWtlIG5lY2Vzc2FyeSBhZGp1c3RtZW50cyBiZWZvcmUgdGFiIGlzIGFjdGl2YXRlZFxuXHRcdCAqL1xuXHRcdCdvbnRhYnNiZWZvcmVhY3RpdmF0ZSc6IGZ1bmN0aW9uKGV2ZW50LCB1aSkge1xuXHRcdFx0dGhpcy5fc3VwZXIoZXZlbnQsIHVpKTtcblx0XHRcdC8vIFJlc2V0IHBvc2l0aW9uIG9mIHRhYnMsIGVsc2UgYW55b25lIGdvaW5nIGJldHdlZW4gdGhlIGxhcmdlXG5cdFx0XHQvLyBhbmQgdGhlIHNtYWxsIHNpdGV0cmVlIHdpbGwgc2VlIGJyb2tlbiB0YWJzXG5cdFx0XHQvLyBBcHBseSBzdHlsZXMgd2l0aCAuY3NzLCB0byBhdm9pZCBvdmVycmlkaW5nIGN1cnJlbnRseSBhcHBsaWVkIHN0eWxlc1xuXHRcdFx0JCh1aS5uZXdQYW5lbCkuY3NzKHsnbGVmdCc6ICdhdXRvJywgJ3JpZ2h0JzogJ2F1dG8nfSk7XG5cblx0XHRcdGlmKCQodWkubmV3UGFuZWwpLmxlbmd0aCA+IDApe1xuXHRcdFx0XHQkKHVpLm5ld1BhbmVsKS5wYXJlbnQoKS5hZGRDbGFzcygndGFic2V0LW9wZW4nKTtcblx0XHRcdH1cblx0XHR9XG5cdH0pO1xuXG59KTtcbiIsIi8qKlxuICogRmlsZTogTGVmdEFuZE1haW4uQmF0Y2hBY3Rpb25zLmpzXG4gKi9cbmltcG9ydCAkIGZyb20gJ2pRdWVyeSc7XG5pbXBvcnQgaTE4biBmcm9tICdpMThuJztcblxuJC5lbnR3aW5lKCdzcy50cmVlJywgZnVuY3Rpb24oJCl7XG5cblx0LyoqXG5cdCAqIENsYXNzOiAjRm9ybV9CYXRjaEFjdGlvbnNGb3JtXG5cdCAqIFxuXHQgKiBCYXRjaCBhY3Rpb25zIHdoaWNoIHRha2UgYSBidW5jaCBvZiBzZWxlY3RlZCBwYWdlcyxcblx0ICogdXN1YWxseSBmcm9tIHRoZSBDTVMgdHJlZSBpbXBsZW1lbnRhdGlvbiwgYW5kIHBlcmZvcm0gc2VydmVyc2lkZVxuXHQgKiBjYWxsYmFja3Mgb24gdGhlIHdob2xlIHNldC4gV2UgbWFrZSB0aGUgdHJlZSBzZWxlY3RhYmxlIHdoZW4gdGhlIGpRdWVyeS5VSSB0YWJcblx0ICogZW5jbG9zaW5nIHRoaXMgZm9ybSBpcyBvcGVuZWQuXG5cdCAqIFxuXHQgKiBFdmVudHM6XG5cdCAqICByZWdpc3RlciAtIENhbGxlZCBiZWZvcmUgYW4gYWN0aW9uIGlzIGFkZGVkLlxuXHQgKiAgdW5yZWdpc3RlciAtIENhbGxlZCBiZWZvcmUgYW4gYWN0aW9uIGlzIHJlbW92ZWQuXG5cdCAqL1xuXHQkKCcjRm9ybV9CYXRjaEFjdGlvbnNGb3JtJykuZW50d2luZSh7XG5cblx0XHQvKipcblx0XHQgKiBWYXJpYWJsZTogQWN0aW9uc1xuXHRcdCAqIChBcnJheSkgU3RvcmVzIGFsbCBhY3Rpb25zIHRoYXQgY2FuIGJlIHBlcmZvcm1lZCBvbiB0aGUgY29sbGVjdGVkIElEcyBhc1xuXHRcdCAqIGZ1bmN0aW9uIGNsb3N1cmVzLiBUaGlzIG1pZ2h0IHRyaWdnZXIgZmlsdGVyaW5nIG9mIHRoZSBzZWxlY3RlZCBJRHMsXG5cdFx0ICogYSBjb25maXJtYXRpb24gbWVzc2FnZSwgZXRjLlxuXHRcdCAqL1xuXHRcdEFjdGlvbnM6IFtdLFxuXG5cdFx0Z2V0VHJlZTogZnVuY3Rpb24oKSB7XG5cdFx0XHRyZXR1cm4gJCgnLmNtcy10cmVlJyk7XG5cdFx0fSxcblxuXHRcdGZyb21UcmVlOiB7XG5cdFx0XHRvbmNoZWNrX25vZGU6IGZ1bmN0aW9uKGUsIGRhdGEpe1xuXHRcdFx0XHR0aGlzLnNlcmlhbGl6ZUZyb21UcmVlKCk7XG5cdFx0XHR9LFxuXHRcdFx0b251bmNoZWNrX25vZGU6IGZ1bmN0aW9uKGUsIGRhdGEpe1xuXHRcdFx0XHR0aGlzLnNlcmlhbGl6ZUZyb21UcmVlKCk7XG5cdFx0XHR9XG5cdFx0fSxcblx0XHRcblx0XHQvKipcblx0XHQgKiBAZnVuYyByZWdpc3RlckRlZmF1bHRcblx0XHQgKiBAZGVzYyBSZWdpc3RlciBkZWZhdWx0IGJ1bGsgY29uZmlybWF0aW9uIGRpYWxvZ3Ncblx0XHQgKi9cblx0XHRyZWdpc3RlckRlZmF1bHQ6IGZ1bmN0aW9uKCkge1xuXHRcdFx0Ly8gUHVibGlzaCBzZWxlY3RlZCBwYWdlcyBhY3Rpb25cblx0XHRcdHRoaXMucmVnaXN0ZXIoJ2FkbWluL3BhZ2VzL2JhdGNoYWN0aW9ucy9wdWJsaXNoJywgZnVuY3Rpb24oaWRzKSB7XG5cdFx0XHRcdHZhciBjb25maXJtZWQgPSBjb25maXJtKFxuXHRcdFx0XHRcdGkxOG4uaW5qZWN0KFxuXHRcdFx0XHRcdFx0aTE4bi5fdChcblx0XHRcdFx0XHRcdFx0XCJDTVNNQUlOLkJBVENIX1BVQkxJU0hfUFJPTVBUXCIsXG5cdFx0XHRcdFx0XHRcdFwiWW91IGhhdmUge251bX0gcGFnZShzKSBzZWxlY3RlZC5cXG5cXG5EbyB5b3UgcmVhbGx5IHdhbnQgdG8gcHVibGlzaD9cIlxuXHRcdFx0XHRcdFx0KSxcblx0XHRcdFx0XHRcdHsnbnVtJzogaWRzLmxlbmd0aH1cblx0XHRcdFx0XHQpXG5cdFx0XHRcdCk7XG5cdFx0XHRcdHJldHVybiAoY29uZmlybWVkKSA/IGlkcyA6IGZhbHNlO1xuXHRcdFx0fSk7XG5cblx0XHRcdC8vIFVucHVibGlzaCBzZWxlY3RlZCBwYWdlcyBhY3Rpb25cblx0XHRcdHRoaXMucmVnaXN0ZXIoJ2FkbWluL3BhZ2VzL2JhdGNoYWN0aW9ucy91bnB1Ymxpc2gnLCBmdW5jdGlvbihpZHMpIHtcblx0XHRcdFx0dmFyIGNvbmZpcm1lZCA9IGNvbmZpcm0oXG5cdFx0XHRcdFx0aTE4bi5pbmplY3QoXG5cdFx0XHRcdFx0XHRpMThuLl90KFxuXHRcdFx0XHRcdFx0XHRcIkNNU01BSU4uQkFUQ0hfVU5QVUJMSVNIX1BST01QVFwiLFxuXHRcdFx0XHRcdFx0XHRcIllvdSBoYXZlIHtudW19IHBhZ2Uocykgc2VsZWN0ZWQuXFxuXFxuRG8geW91IHJlYWxseSB3YW50IHRvIHVucHVibGlzaFwiXG5cdFx0XHRcdFx0XHQpLFxuXHRcdFx0XHRcdFx0eydudW0nOiBpZHMubGVuZ3RofVxuXHRcdFx0XHRcdClcblx0XHRcdFx0KTtcblx0XHRcdFx0cmV0dXJuIChjb25maXJtZWQpID8gaWRzIDogZmFsc2U7XG5cdFx0XHR9KTtcblxuXHRcdFx0Ly8gRGVsZXRlIHNlbGVjdGVkIHBhZ2VzIGFjdGlvblxuXHRcdFx0Ly8gQGRlcHJlY2F0ZWQgc2luY2UgNC4wIFVzZSBhcmNoaXZlIGluc3RlYWRcblx0XHRcdHRoaXMucmVnaXN0ZXIoJ2FkbWluL3BhZ2VzL2JhdGNoYWN0aW9ucy9kZWxldGUnLCBmdW5jdGlvbihpZHMpIHtcblx0XHRcdFx0dmFyIGNvbmZpcm1lZCA9IGNvbmZpcm0oXG5cdFx0XHRcdFx0aTE4bi5pbmplY3QoXG5cdFx0XHRcdFx0XHRpMThuLl90KFxuXHRcdFx0XHRcdFx0XHRcIkNNU01BSU4uQkFUQ0hfREVMRVRFX1BST01QVFwiLFxuXHRcdFx0XHRcdFx0XHRcIllvdSBoYXZlIHtudW19IHBhZ2Uocykgc2VsZWN0ZWQuXFxuXFxuRG8geW91IHJlYWxseSB3YW50IHRvIGRlbGV0ZT9cIlxuXHRcdFx0XHRcdFx0KSxcblx0XHRcdFx0XHRcdHsnbnVtJzogaWRzLmxlbmd0aH1cblx0XHRcdFx0XHQpXG5cdFx0XHRcdCk7XG5cdFx0XHRcdHJldHVybiAoY29uZmlybWVkKSA/IGlkcyA6IGZhbHNlO1xuXHRcdFx0fSk7XG5cblx0XHRcdC8vIERlbGV0ZSBzZWxlY3RlZCBwYWdlcyBhY3Rpb25cblx0XHRcdHRoaXMucmVnaXN0ZXIoJ2FkbWluL3BhZ2VzL2JhdGNoYWN0aW9ucy9hcmNoaXZlJywgZnVuY3Rpb24oaWRzKSB7XG5cdFx0XHRcdHZhciBjb25maXJtZWQgPSBjb25maXJtKFxuXHRcdFx0XHRcdGkxOG4uaW5qZWN0KFxuXHRcdFx0XHRcdFx0aTE4bi5fdChcblx0XHRcdFx0XHRcdFx0XCJDTVNNQUlOLkJBVENIX0FSQ0hJVkVfUFJPTVBUXCIsXG5cdFx0XHRcdFx0XHRcdFwiWW91IGhhdmUge251bX0gcGFnZShzKSBzZWxlY3RlZC5cXG5cXG5BcmUgeW91IHN1cmUgeW91IHdhbnQgdG8gYXJjaGl2ZSB0aGVzZSBwYWdlcz9cXG5cXG5UaGVzZSBwYWdlcyBhbmQgYWxsIG9mIHRoZWlyIGNoaWxkcmVuIHBhZ2VzIHdpbGwgYmUgdW5wdWJsaXNoZWQgYW5kIHNlbnQgdG8gdGhlIGFyY2hpdmUuXCJcblx0XHRcdFx0XHRcdCksXG5cdFx0XHRcdFx0XHR7J251bSc6IGlkcy5sZW5ndGh9XG5cdFx0XHRcdFx0KVxuXHRcdFx0XHQpO1xuXHRcdFx0XHRyZXR1cm4gKGNvbmZpcm1lZCkgPyBpZHMgOiBmYWxzZTtcblx0XHRcdH0pO1xuXG5cdFx0XHQvLyBSZXN0b3JlIHNlbGVjdGVkIGFyY2hpdmVkIHBhZ2VzXG5cdFx0XHR0aGlzLnJlZ2lzdGVyKCdhZG1pbi9wYWdlcy9iYXRjaGFjdGlvbnMvcmVzdG9yZScsIGZ1bmN0aW9uKGlkcykge1xuXHRcdFx0XHR2YXIgY29uZmlybWVkID0gY29uZmlybShcblx0XHRcdFx0XHRpMThuLmluamVjdChcblx0XHRcdFx0XHRcdGkxOG4uX3QoXG5cdFx0XHRcdFx0XHRcdFwiQ01TTUFJTi5CQVRDSF9SRVNUT1JFX1BST01QVFwiLFxuXHRcdFx0XHRcdFx0XHRcIllvdSBoYXZlIHtudW19IHBhZ2Uocykgc2VsZWN0ZWQuXFxuXFxuRG8geW91IHJlYWxseSB3YW50IHRvIHJlc3RvcmUgdG8gc3RhZ2U/XFxuXFxuQ2hpbGRyZW4gb2YgYXJjaGl2ZWQgcGFnZXMgd2lsbCBiZSByZXN0b3JlZCB0byB0aGUgcm9vdCBsZXZlbCwgdW5sZXNzIHRob3NlIHBhZ2VzIGFyZSBhbHNvIGJlaW5nIHJlc3RvcmVkLlwiXG5cdFx0XHRcdFx0XHQpLFxuXHRcdFx0XHRcdFx0eydudW0nOiBpZHMubGVuZ3RofVxuXHRcdFx0XHRcdClcblx0XHRcdFx0KTtcblx0XHRcdFx0cmV0dXJuIChjb25maXJtZWQpID8gaWRzIDogZmFsc2U7XG5cdFx0XHR9KTtcblxuXHRcdFx0Ly8gRGVsZXRlIHNlbGVjdGVkIHBhZ2VzIGZyb20gbGl2ZSBhY3Rpb25cblx0XHRcdHRoaXMucmVnaXN0ZXIoJ2FkbWluL3BhZ2VzL2JhdGNoYWN0aW9ucy9kZWxldGVmcm9tbGl2ZScsIGZ1bmN0aW9uKGlkcykge1xuXHRcdFx0XHR2YXIgY29uZmlybWVkID0gY29uZmlybShcblx0XHRcdFx0XHRpMThuLmluamVjdChcblx0XHRcdFx0XHRcdGkxOG4uX3QoXG5cdFx0XHRcdFx0XHRcdFwiQ01TTUFJTi5CQVRDSF9ERUxFVEVMSVZFX1BST01QVFwiLFxuXHRcdFx0XHRcdFx0XHRcIllvdSBoYXZlIHtudW19IHBhZ2Uocykgc2VsZWN0ZWQuXFxuXFxuRG8geW91IHJlYWxseSB3YW50IHRvIGRlbGV0ZSB0aGVzZSBwYWdlcyBmcm9tIGxpdmU/XCJcblx0XHRcdFx0XHRcdCksXG5cdFx0XHRcdFx0XHR7J251bSc6IGlkcy5sZW5ndGh9XG5cdFx0XHRcdFx0KVxuXHRcdFx0XHQpO1xuXHRcdFx0XHRyZXR1cm4gKGNvbmZpcm1lZCkgPyBpZHMgOiBmYWxzZTtcblx0XHRcdH0pO1xuXHRcdH0sXG5cblx0XHRvbmFkZDogZnVuY3Rpb24oKSB7XG5cdFx0XHR0aGlzLnJlZ2lzdGVyRGVmYXVsdCgpO1xuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogQGZ1bmMgcmVnaXN0ZXJcblx0XHQgKiBAcGFyYW0ge3N0cmluZ30gdHlwZVxuXHRcdCAqIEBwYXJhbSB7ZnVuY3Rpb259IGNhbGxiYWNrXG5cdFx0ICovXG5cdFx0cmVnaXN0ZXI6IGZ1bmN0aW9uKHR5cGUsIGNhbGxiYWNrKSB7XG5cdFx0XHR0aGlzLnRyaWdnZXIoJ3JlZ2lzdGVyJywge3R5cGU6IHR5cGUsIGNhbGxiYWNrOiBjYWxsYmFja30pO1xuXHRcdFx0dmFyIGFjdGlvbnMgPSB0aGlzLmdldEFjdGlvbnMoKTtcblx0XHRcdGFjdGlvbnNbdHlwZV0gPSBjYWxsYmFjaztcblx0XHRcdHRoaXMuc2V0QWN0aW9ucyhhY3Rpb25zKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogQGZ1bmMgdW5yZWdpc3RlclxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSB0eXBlXG5cdFx0ICogQGRlc2MgUmVtb3ZlIGFuIGV4aXN0aW5nIGFjdGlvbi5cblx0XHQgKi9cblx0XHR1bnJlZ2lzdGVyOiBmdW5jdGlvbih0eXBlKSB7XG5cdFx0XHR0aGlzLnRyaWdnZXIoJ3VucmVnaXN0ZXInLCB7dHlwZTogdHlwZX0pO1xuXG5cdFx0XHR2YXIgYWN0aW9ucyA9IHRoaXMuZ2V0QWN0aW9ucygpO1xuXHRcdFx0aWYoYWN0aW9uc1t0eXBlXSkgZGVsZXRlIGFjdGlvbnNbdHlwZV07XG5cdFx0XHR0aGlzLnNldEFjdGlvbnMoYWN0aW9ucyk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEBmdW5jIHJlZnJlc2hTZWxlY3RlZFxuXHRcdCAqIEBwYXJhbSB7b2JqZWN0fSByb290Tm9kZVxuXHRcdCAqIEBkZXNjIEFqYXggY2FsbGJhY2tzIGRldGVybWluZSB3aGljaCBwYWdlcyBpcyBzZWxlY3RhYmxlIGluIGEgY2VydGFpbiBiYXRjaCBhY3Rpb24uXG5cdFx0ICovXG5cdFx0cmVmcmVzaFNlbGVjdGVkIDogZnVuY3Rpb24ocm9vdE5vZGUpIHtcblx0XHRcdHZhciBzZWxmID0gdGhpcyxcblx0XHRcdFx0c3QgPSB0aGlzLmdldFRyZWUoKSxcblx0XHRcdFx0aWRzID0gdGhpcy5nZXRJRHMoKSxcblx0XHRcdFx0YWxsSWRzID0gW10sXG5cdFx0XHRcdHZpZXdNb2RlID0gJCgnLmNtcy1jb250ZW50LWJhdGNoYWN0aW9ucy1idXR0b24nKSxcblx0XHRcdFx0YWN0aW9uVXJsID0gdGhpcy5maW5kKCc6aW5wdXRbbmFtZT1BY3Rpb25dJykudmFsKCk7XG5cdFx0XG5cdFx0XHQvLyBEZWZhdWx0IHRvIHJlZnJlc2hpbmcgdGhlIGVudGlyZSB0cmVlXG5cdFx0XHRpZihyb290Tm9kZSA9PSBudWxsKSByb290Tm9kZSA9IHN0O1xuXG5cdFx0XHRmb3IodmFyIGlkeCBpbiBpZHMpIHtcblx0XHRcdFx0JCgkKHN0KS5nZXROb2RlQnlJRChpZHgpKS5hZGRDbGFzcygnc2VsZWN0ZWQnKS5hdHRyKCdzZWxlY3RlZCcsICdzZWxlY3RlZCcpO1xuXHRcdFx0fVxuXG5cdFx0XHQvLyBJZiBubyBhY3Rpb24gaXMgc2VsZWN0ZWQsIGVuYWJsZSBhbGwgbm9kZXNcblx0XHRcdGlmKCFhY3Rpb25VcmwgfHwgYWN0aW9uVXJsID09IC0xIHx8ICF2aWV3TW9kZS5oYXNDbGFzcygnYWN0aXZlJykpIHtcblx0XHRcdFx0JChyb290Tm9kZSkuZmluZCgnbGknKS5lYWNoKGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdCQodGhpcykuc2V0RW5hYmxlZCh0cnVlKTtcblx0XHRcdFx0fSk7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0Ly8gRGlzYWJsZSB0aGUgbm9kZXMgd2hpbGUgdGhlIGFqYXggcmVxdWVzdCBpcyBiZWluZyBwcm9jZXNzZWRcblx0XHRcdCQocm9vdE5vZGUpLmZpbmQoJ2xpJykuZWFjaChmdW5jdGlvbigpIHtcblx0XHRcdFx0YWxsSWRzLnB1c2goJCh0aGlzKS5kYXRhKCdpZCcpKTtcblx0XHRcdFx0JCh0aGlzKS5hZGRDbGFzcygndHJlZWxvYWRpbmcnKS5zZXRFbmFibGVkKGZhbHNlKTtcblx0XHRcdH0pO1xuXHRcdFx0XG5cdFx0XHQvLyBQb3N0IHRvIHRoZSBzZXJ2ZXIgdG8gYXNrIHdoaWNoIHBhZ2VzIGNhbiBoYXZlIHRoaXMgYmF0Y2ggYWN0aW9uIGFwcGxpZWRcblx0XHRcdC8vIFJldGFpbiBleGlzdGluZyBxdWVyeSBwYXJhbWV0ZXJzIGluIFVSTCBiZWZvcmUgYXBwZW5kaW5nIHBhdGhcblx0XHRcdHZhciBhY3Rpb25VcmxQYXJ0cyA9ICQucGF0aC5wYXJzZVVybChhY3Rpb25VcmwpO1xuXHRcdFx0dmFyIGFwcGxpY2FibGVQYWdlc1VybCA9IGFjdGlvblVybFBhcnRzLmhyZWZOb1NlYXJjaCArICcvYXBwbGljYWJsZXBhZ2VzLyc7XG5cdFx0XHRhcHBsaWNhYmxlUGFnZXNVcmwgPSAkLnBhdGguYWRkU2VhcmNoUGFyYW1zKGFwcGxpY2FibGVQYWdlc1VybCwgYWN0aW9uVXJsUGFydHMuc2VhcmNoKTtcblx0XHRcdGFwcGxpY2FibGVQYWdlc1VybCA9ICQucGF0aC5hZGRTZWFyY2hQYXJhbXMoYXBwbGljYWJsZVBhZ2VzVXJsLCB7Y3N2SURzOiBhbGxJZHMuam9pbignLCcpfSk7XG5cdFx0XHRqUXVlcnkuZ2V0SlNPTihhcHBsaWNhYmxlUGFnZXNVcmwsIGZ1bmN0aW9uKGFwcGxpY2FibGVJRHMpIHtcblx0XHRcdFx0Ly8gU2V0IGEgQ1NTIGNsYXNzIG9uIGVhY2ggdHJlZSBub2RlIGluZGljYXRpbmcgd2hpY2ggY2FuIGJlIGJhdGNoLWFjdGlvbmVkIGFuZCB3aGljaCBjYW4ndFxuXHRcdFx0XHRqUXVlcnkocm9vdE5vZGUpLmZpbmQoJ2xpJykuZWFjaChmdW5jdGlvbigpIHtcblx0XHRcdFx0XHQkKHRoaXMpLnJlbW92ZUNsYXNzKCd0cmVlbG9hZGluZycpO1xuXG5cdFx0XHRcdFx0dmFyIGlkID0gJCh0aGlzKS5kYXRhKCdpZCcpO1xuXHRcdFx0XHRcdGlmKGlkID09IDAgfHwgJC5pbkFycmF5KGlkLCBhcHBsaWNhYmxlSURzKSA+PSAwKSB7XG5cdFx0XHRcdFx0XHQkKHRoaXMpLnNldEVuYWJsZWQodHJ1ZSk7XG5cdFx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRcdC8vIERlLXNlbGVjdCB0aGUgbm9kZSBpZiBpdCdzIG5vbi1hcHBsaWNhYmxlXG5cdFx0XHRcdFx0XHQkKHRoaXMpLnJlbW92ZUNsYXNzKCdzZWxlY3RlZCcpLnNldEVuYWJsZWQoZmFsc2UpO1xuXHRcdFx0XHRcdFx0JCh0aGlzKS5wcm9wKCdzZWxlY3RlZCcsIGZhbHNlKTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH0pO1xuXHRcdFx0XHRcblx0XHRcdFx0c2VsZi5zZXJpYWxpemVGcm9tVHJlZSgpO1xuXHRcdFx0fSk7XG5cdFx0fSxcblx0XHRcblx0XHQvKipcblx0XHQgKiBAZnVuYyBzZXJpYWxpemVGcm9tVHJlZVxuXHRcdCAqIEByZXR1cm4ge2Jvb2xlYW59XG5cdFx0ICovXG5cdFx0c2VyaWFsaXplRnJvbVRyZWU6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dmFyIHRyZWUgPSB0aGlzLmdldFRyZWUoKSwgaWRzID0gdHJlZS5nZXRTZWxlY3RlZElEcygpO1xuXHRcdFx0XG5cdFx0XHQvLyB3cml0ZSBJRHMgdG8gdGhlIGhpZGRlbiBmaWVsZFxuXHRcdFx0dGhpcy5zZXRJRHMoaWRzKTtcblx0XHRcdFxuXHRcdFx0cmV0dXJuIHRydWU7XG5cdFx0fSxcblx0XHRcblx0XHQvKipcblx0XHQgKiBAZnVuYyBzZXRJRFNcblx0XHQgKiBAcGFyYW0ge2FycmF5fSBpZHNcblx0XHQgKi9cblx0XHRzZXRJRHM6IGZ1bmN0aW9uKGlkcykge1xuXHRcdFx0dGhpcy5maW5kKCc6aW5wdXRbbmFtZT1jc3ZJRHNdJykudmFsKGlkcyA/IGlkcy5qb2luKCcsJykgOiBudWxsKTtcblx0XHR9LFxuXHRcdFxuXHRcdC8qKlxuXHRcdCAqIEBmdW5jIGdldElEU1xuXHRcdCAqIEByZXR1cm4ge2FycmF5fVxuXHRcdCAqL1xuXHRcdGdldElEczogZnVuY3Rpb24oKSB7XG5cdFx0XHQvLyBNYXAgZW1wdHkgdmFsdWUgdG8gZW1wdHkgYXJyYXlcblx0XHRcdHZhciB2YWx1ZSA9IHRoaXMuZmluZCgnOmlucHV0W25hbWU9Y3N2SURzXScpLnZhbCgpO1xuXHRcdFx0cmV0dXJuIHZhbHVlXG5cdFx0XHRcdD8gdmFsdWUuc3BsaXQoJywnKVxuXHRcdFx0XHQ6IFtdO1xuXHRcdH0sXG5cblx0XHRvbnN1Ym1pdDogZnVuY3Rpb24oZSkge1xuXHRcdFx0dmFyIHNlbGYgPSB0aGlzLCBpZHMgPSB0aGlzLmdldElEcygpLCB0cmVlID0gdGhpcy5nZXRUcmVlKCksIGFjdGlvbnMgPSB0aGlzLmdldEFjdGlvbnMoKTtcblx0XHRcdFxuXHRcdFx0Ly8gaWYgbm8gbm9kZXMgYXJlIHNlbGVjdGVkLCByZXR1cm4gd2l0aCBhbiBlcnJvclxuXHRcdFx0aWYoIWlkcyB8fCAhaWRzLmxlbmd0aCkge1xuXHRcdFx0XHRhbGVydChpMThuLl90KCdDTVNNQUlOLlNFTEVDVE9ORVBBR0UnLCAnUGxlYXNlIHNlbGVjdCBhdCBsZWFzdCBvbmUgcGFnZScpKTtcblx0XHRcdFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXHRcdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0XHR9XG5cdFx0XHRcblx0XHRcdC8vIGFwcGx5IGNhbGxiYWNrLCB3aGljaCBtaWdodCBtb2RpZnkgdGhlIElEc1xuXHRcdFx0dmFyIHR5cGUgPSB0aGlzLmZpbmQoJzppbnB1dFtuYW1lPUFjdGlvbl0nKS52YWwoKTtcblx0XHRcdGlmKGFjdGlvbnNbdHlwZV0pIHtcblx0XHRcdFx0aWRzID0gdGhpcy5nZXRBY3Rpb25zKClbdHlwZV0uYXBwbHkodGhpcywgW2lkc10pO1xuXHRcdFx0fVxuXHRcdFx0XG5cdFx0XHQvLyBEaXNjb250aW51ZSBwcm9jZXNzaW5nIGlmIHRoZXJlIGFyZSBubyBmdXJ0aGVyIGl0ZW1zXG5cdFx0XHRpZighaWRzIHx8ICFpZHMubGVuZ3RoKSB7XG5cdFx0XHRcdGUucHJldmVudERlZmF1bHQoKTtcblx0XHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdFx0fVxuXHRcdFxuXHRcdFx0Ly8gd3JpdGUgKHBvc3NpYmx5IG1vZGlmaWVkKSBJRHMgYmFjayBpbnRvIHRvIHRoZSBoaWRkZW4gZmllbGRcblx0XHRcdHRoaXMuc2V0SURzKGlkcyk7XG5cdFx0XHRcblx0XHRcdC8vIFJlc2V0IGZhaWx1cmUgc3RhdGVzXG5cdFx0XHR0cmVlLmZpbmQoJ2xpJykucmVtb3ZlQ2xhc3MoJ2ZhaWxlZCcpO1xuXHRcdFxuXHRcdFx0dmFyIGJ1dHRvbiA9IHRoaXMuZmluZCgnOnN1Ym1pdDpmaXJzdCcpO1xuXHRcdFx0YnV0dG9uLmFkZENsYXNzKCdsb2FkaW5nJyk7XG5cdFx0XG5cdFx0XHRqUXVlcnkuYWpheCh7XG5cdFx0XHRcdC8vIGRvbid0IHVzZSBvcmlnaW5hbCBmb3JtIHVybFxuXHRcdFx0XHR1cmw6IHR5cGUsXG5cdFx0XHRcdHR5cGU6ICdQT1NUJyxcblx0XHRcdFx0ZGF0YTogdGhpcy5zZXJpYWxpemVBcnJheSgpLFxuXHRcdFx0XHRjb21wbGV0ZTogZnVuY3Rpb24oeG1saHR0cCwgc3RhdHVzKSB7XG5cdFx0XHRcdFx0YnV0dG9uLnJlbW92ZUNsYXNzKCdsb2FkaW5nJyk7XG5cblx0XHRcdFx0XHQvLyBSZWZyZXNoIHRoZSB0cmVlLlxuXHRcdFx0XHRcdC8vIE1ha2VzIHN1cmUgYWxsIG5vZGVzIGhhdmUgdGhlIGNvcnJlY3QgQ1NTIGNsYXNzZXMgYXBwbGllZC5cblx0XHRcdFx0XHR0cmVlLmpzdHJlZSgncmVmcmVzaCcsIC0xKTtcblx0XHRcdFx0XHRzZWxmLnNldElEcyhbXSk7XG5cblx0XHRcdFx0XHQvLyBSZXNldCBhY3Rpb25cblx0XHRcdFx0XHRzZWxmLmZpbmQoJzppbnB1dFtuYW1lPUFjdGlvbl0nKS52YWwoJycpLmNoYW5nZSgpO1xuXHRcdFx0XHRcblx0XHRcdFx0XHQvLyBzdGF0dXMgbWVzc2FnZSAoZGVjb2RlIGludG8gVVRGLTgsIEhUVFAgaGVhZGVycyBkb24ndCBhbGxvdyBtdWx0aWJ5dGUpXG5cdFx0XHRcdFx0dmFyIG1zZyA9IHhtbGh0dHAuZ2V0UmVzcG9uc2VIZWFkZXIoJ1gtU3RhdHVzJyk7XG5cdFx0XHRcdFx0aWYobXNnKSBzdGF0dXNNZXNzYWdlKGRlY29kZVVSSUNvbXBvbmVudChtc2cpLCAoc3RhdHVzID09ICdzdWNjZXNzJykgPyAnZ29vZCcgOiAnYmFkJyk7XG5cdFx0XHRcdH0sXG5cdFx0XHRcdHN1Y2Nlc3M6IGZ1bmN0aW9uKGRhdGEsIHN0YXR1cykge1xuXHRcdFx0XHRcdHZhciBpZCwgbm9kZTtcblx0XHRcdFx0XHRcblx0XHRcdFx0XHRpZihkYXRhLm1vZGlmaWVkKSB7XG5cdFx0XHRcdFx0XHR2YXIgbW9kaWZpZWROb2RlcyA9IFtdO1xuXHRcdFx0XHRcdFx0Zm9yKGlkIGluIGRhdGEubW9kaWZpZWQpIHtcblx0XHRcdFx0XHRcdFx0bm9kZSA9IHRyZWUuZ2V0Tm9kZUJ5SUQoaWQpO1xuXHRcdFx0XHRcdFx0XHR0cmVlLmpzdHJlZSgnc2V0X3RleHQnLCBub2RlLCBkYXRhLm1vZGlmaWVkW2lkXVsnVHJlZVRpdGxlJ10pO1xuXHRcdFx0XHRcdFx0XHRtb2RpZmllZE5vZGVzLnB1c2gobm9kZSk7XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHQkKG1vZGlmaWVkTm9kZXMpLmVmZmVjdCgnaGlnaGxpZ2h0Jyk7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHRcdGlmKGRhdGEuZGVsZXRlZCkge1xuXHRcdFx0XHRcdFx0Zm9yKGlkIGluIGRhdGEuZGVsZXRlZCkge1xuXHRcdFx0XHRcdFx0XHRub2RlID0gdHJlZS5nZXROb2RlQnlJRChpZCk7XG5cdFx0XHRcdFx0XHRcdGlmKG5vZGUubGVuZ3RoKVx0dHJlZS5qc3RyZWUoJ2RlbGV0ZV9ub2RlJywgbm9kZSk7XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHRcdGlmKGRhdGEuZXJyb3IpIHtcblx0XHRcdFx0XHRcdGZvcihpZCBpbiBkYXRhLmVycm9yKSB7XG5cdFx0XHRcdFx0XHRcdG5vZGUgPSB0cmVlLmdldE5vZGVCeUlEKGlkKTtcblx0XHRcdFx0XHRcdFx0JChub2RlKS5hZGRDbGFzcygnZmFpbGVkJyk7XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9LFxuXHRcdFx0XHRkYXRhVHlwZTogJ2pzb24nXG5cdFx0XHR9KTtcblx0XHRcblx0XHRcdC8vIE5ldmVyIHByb2Nlc3MgdGhpcyBhY3Rpb247IE9ubHkgaW52b2tlIHZpYSBhamF4XG5cdFx0XHRlLnByZXZlbnREZWZhdWx0KCk7XG5cdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0fVxuXHRcblx0fSk7XG5cblx0JCgnLmNtcy1jb250ZW50LWJhdGNoYWN0aW9ucy1idXR0b24nKS5lbnR3aW5lKHtcblx0XHRvbm1hdGNoOiBmdW5jdGlvbiAoKSB7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdFx0dGhpcy51cGRhdGVUcmVlKCk7XG5cdFx0fSxcblx0XHRvbnVubWF0Y2g6IGZ1bmN0aW9uICgpIHtcblx0XHRcdHRoaXMuX3N1cGVyKCk7XG5cdFx0fSxcblx0XHRvbmNsaWNrOiBmdW5jdGlvbiAoZSkge1xuXHRcdFx0dGhpcy51cGRhdGVUcmVlKCk7XG5cdFx0fSxcblx0XHR1cGRhdGVUcmVlOiBmdW5jdGlvbiAoKSB7XG5cdFx0XHR2YXIgdHJlZSA9ICQoJy5jbXMtdHJlZScpLFxuXHRcdFx0XHRmb3JtID0gJCgnI0Zvcm1fQmF0Y2hBY3Rpb25zRm9ybScpO1xuXG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXG5cdFx0XHRpZih0aGlzLmRhdGEoJ2FjdGl2ZScpKSB7XG5cdFx0XHRcdHRyZWUuYWRkQ2xhc3MoJ211bHRpcGxlJyk7XG5cdFx0XHRcdHRyZWUucmVtb3ZlQ2xhc3MoJ2RyYWdnYWJsZScpO1xuXHRcdFx0XHRmb3JtLnNlcmlhbGl6ZUZyb21UcmVlKCk7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHR0cmVlLnJlbW92ZUNsYXNzKCdtdWx0aXBsZScpO1xuXHRcdFx0XHR0cmVlLmFkZENsYXNzKCdkcmFnZ2FibGUnKTtcblx0XHRcdH1cblx0XHRcdFxuXHRcdFx0JCgnI0Zvcm1fQmF0Y2hBY3Rpb25zRm9ybScpLnJlZnJlc2hTZWxlY3RlZCgpO1xuXHRcdH1cblx0fSk7XG5cblx0LyoqXG5cdCAqIENsYXNzOiAjRm9ybV9CYXRjaEFjdGlvbnNGb3JtIDpzZWxlY3RbbmFtZT1BY3Rpb25dXG5cdCAqL1xuXHQkKCcjRm9ybV9CYXRjaEFjdGlvbnNGb3JtIHNlbGVjdFtuYW1lPUFjdGlvbl0nKS5lbnR3aW5lKHtcblx0XHRvbmNoYW5nZTogZnVuY3Rpb24oZSkge1xuXHRcdFx0dmFyIGZvcm0gPSAkKGUudGFyZ2V0LmZvcm0pLFxuXHRcdFx0XHRidG4gPSBmb3JtLmZpbmQoJzpzdWJtaXQnKSxcblx0XHRcdFx0c2VsZWN0ZWQgPSAkKGUudGFyZ2V0KS52YWwoKTtcblx0XHRcdGlmKCFzZWxlY3RlZCB8fCBzZWxlY3RlZCA9PSAtMSkge1xuXHRcdFx0XHRidG4uYXR0cignZGlzYWJsZWQnLCAnZGlzYWJsZWQnKS5idXR0b24oJ3JlZnJlc2gnKTtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdGJ0bi5yZW1vdmVBdHRyKCdkaXNhYmxlZCcpLmJ1dHRvbigncmVmcmVzaCcpO1xuXHRcdFx0fVxuXHRcdFx0XG5cdFx0XHQvLyBSZWZyZXNoIHNlbGVjdGVkIC8gZW5hYmxlZCBub2Rlc1xuXHRcdFx0JCgnI0Zvcm1fQmF0Y2hBY3Rpb25zRm9ybScpLnJlZnJlc2hTZWxlY3RlZCgpO1xuXG5cdFx0XHQvLyBUT0RPIFNob3VsZCB3b3JrIGJ5IHRyaWdnZXJpbmcgY2hhbmdlKCkgYWxvbmcsIGJ1dCBkb2Vzbid0IC0gZW50d2luZSBldmVudCBidWJibGluZz9cblx0XHRcdHRoaXMudHJpZ2dlcihcImxpc3p0OnVwZGF0ZWRcIik7XG5cblx0XHRcdHRoaXMuX3N1cGVyKGUpO1xuXHRcdH1cblx0fSk7XG59KTtcbiIsImltcG9ydCAkIGZyb20gJ2pRdWVyeSc7XG5cbiQuZW50d2luZSgnc3MnLCBmdW5jdGlvbigkKXtcblx0XG5cdC8qKlxuXHQgKiBUaGUgXCJjb250ZW50XCIgYXJlYSBjb250YWlucyBhbGwgb2YgdGhlIHNlY3Rpb24gc3BlY2lmaWMgVUkgKGV4Y2x1ZGluZyB0aGUgbWVudSkuXG5cdCAqIFRoaXMgYXJlYSBjYW4gYmUgYSBmb3JtIGl0c2VsZiwgYXMgd2VsbCBhcyBjb250YWluIG9uZSBvciBtb3JlIGZvcm1zLlxuXHQgKiBGb3IgZXhhbXBsZSwgYSBwYWdlIGVkaXQgZm9ybSBtaWdodCBmaWxsIHRoZSB3aG9sZSBhcmVhLCBcblx0ICogd2hpbGUgYSBNb2RlbEFkbWluIGxheW91dCBzaG93cyBhIHNlYXJjaCBmb3JtIG9uIHRoZSBsZWZ0LCBhbmQgZWRpdCBmb3JtIG9uIHRoZSByaWdodC5cblx0ICovXG5cdCQoJy5jbXMtY29udGVudCcpLmVudHdpbmUoe1xuXHRcdFxuXHRcdG9uYWRkOiBmdW5jdGlvbigpIHtcblx0XHRcdHZhciBzZWxmID0gdGhpcztcblx0XHRcdFxuXHRcdFx0Ly8gRm9yY2UgaW5pdGlhbGl6YXRpb24gb2YgY2VydGFpbiBVSSBlbGVtZW50cyB0byBhdm9pZCBsYXlvdXQgZ2xpdGNoZXNcblx0XHRcdHRoaXMuZmluZCgnLmNtcy10YWJzZXQnKS5yZWRyYXdUYWJzKCk7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXG5cdFx0fSxcblxuXHRcdHJlZHJhdzogZnVuY3Rpb24oKSB7XG5cdFx0XHRpZih3aW5kb3cuZGVidWcpIGNvbnNvbGUubG9nKCdyZWRyYXcnLCB0aGlzLmF0dHIoJ2NsYXNzJyksIHRoaXMuZ2V0KDApKTtcblx0XHRcdFxuXHRcdFx0Ly8gRm9yY2UgaW5pdGlhbGl6YXRpb24gb2YgY2VydGFpbiBVSSBlbGVtZW50cyB0byBhdm9pZCBsYXlvdXQgZ2xpdGNoZXNcblx0XHRcdHRoaXMuYWRkKHRoaXMuZmluZCgnLmNtcy10YWJzZXQnKSkucmVkcmF3VGFicygpO1xuXHRcdFx0dGhpcy5maW5kKCcuY21zLWNvbnRlbnQtaGVhZGVyJykucmVkcmF3KCk7XG5cdFx0XHR0aGlzLmZpbmQoJy5jbXMtY29udGVudC1hY3Rpb25zJykucmVkcmF3KCk7XG5cdFx0fVxuXHR9KTtcblxuXHQvKipcblx0ICogTG9hZCBlZGl0IGZvcm0gZm9yIHRoZSBzZWxlY3RlZCBub2RlIHdoZW4gaXRzIGNsaWNrZWQuXG5cdCAqL1xuXHQkKCcuY21zLWNvbnRlbnQgLmNtcy10cmVlJykuZW50d2luZSh7XG5cdFx0b25hZGQ6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dmFyIHNlbGYgPSB0aGlzO1xuXG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXG5cdFx0XHR0aGlzLmJpbmQoJ3NlbGVjdF9ub2RlLmpzdHJlZScsIGZ1bmN0aW9uKGUsIGRhdGEpIHtcblx0XHRcdFx0dmFyIG5vZGUgPSBkYXRhLnJzbHQub2JqLCBsb2FkZWROb2RlSUQgPSBzZWxmLmZpbmQoJzppbnB1dFtuYW1lPUlEXScpLnZhbCgpLCBvcmlnRXZlbnQgPSBkYXRhLmFyZ3NbMl0sIGNvbnRhaW5lciA9ICQoJy5jbXMtY29udGFpbmVyJyk7XG5cdFx0XHRcdFxuXHRcdFx0XHQvLyBEb24ndCB0cmlnZ2VyIHVubGVzcyBjb21pbmcgZnJvbSBhIGNsaWNrIGV2ZW50LlxuXHRcdFx0XHQvLyBBdm9pZHMgcHJvYmxlbXMgd2l0aCBhdXRvbWF0ZWQgc2VjdGlvbiBzd2l0Y2hlcyBmcm9tIHRyZWUgdG8gZGV0YWlsIHZpZXdcblx0XHRcdFx0Ly8gd2hlbiBKU1RyZWUgYXV0by1zZWxlY3RzIGVsZW1lbnRzIG9uIGZpcnN0IGxvYWQuXG5cdFx0XHRcdGlmKCFvcmlnRXZlbnQpIHtcblx0XHRcdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0XHRcdH1cblx0XHRcdFx0XG5cdFx0XHRcdC8vIERvbid0IGFsbG93IGNoZWNraW5nIGRpc2FibGVkIG5vZGVzXG5cdFx0XHRcdGlmKCQobm9kZSkuaGFzQ2xhc3MoJ2Rpc2FibGVkJykpIHJldHVybiBmYWxzZTtcblxuXHRcdFx0XHQvLyBEb24ndCBhbGxvdyByZWxvYWRpbmcgb2YgY3VycmVudGx5IHNlbGVjdGVkIG5vZGUsXG5cdFx0XHRcdC8vIG1haW5seSB0byBhdm9pZCBkb2luZyBhbiBhamF4IHJlcXVlc3Qgb24gaW5pdGlhbCBwYWdlIGxvYWRcblx0XHRcdFx0aWYoJChub2RlKS5kYXRhKCdpZCcpID09IGxvYWRlZE5vZGVJRCkgcmV0dXJuO1xuXG5cdFx0XHRcdHZhciB1cmwgPSAkKG5vZGUpLmZpbmQoJ2E6Zmlyc3QnKS5hdHRyKCdocmVmJyk7XG5cdFx0XHRcdGlmKHVybCAmJiB1cmwgIT0gJyMnKSB7XG5cdFx0XHRcdFx0Ly8gc3RyaXAgcG9zc2libGUgcXVlcnlzdHJpbmdzIGZyb20gdGhlIHVybCB0byBhdm9pZCBkdXBsaWNhdGVpbmcgZG9jdW1lbnQubG9jYXRpb24uc2VhcmNoXG5cdFx0XHRcdFx0dXJsID0gdXJsLnNwbGl0KCc/JylbMF07XG5cdFx0XHRcdFx0XG5cdFx0XHRcdFx0Ly8gRGVzZWxlY3QgYWxsIG5vZGVzICh3aWxsIGJlIHJlc2VsZWN0ZWQgYWZ0ZXIgbG9hZCBhY2NvcmRpbmcgdG8gZm9ybSBzdGF0ZSlcblx0XHRcdFx0XHRzZWxmLmpzdHJlZSgnZGVzZWxlY3RfYWxsJyk7XG5cdFx0XHRcdFx0c2VsZi5qc3RyZWUoJ3VuY2hlY2tfYWxsJyk7XG5cblx0XHRcdFx0XHQvLyBFbnN1cmUgVVJMIGlzIGFic29sdXRlIChpbXBvcnRhbnQgZm9yIElFKVxuXHRcdFx0XHRcdGlmKCQucGF0aC5pc0V4dGVybmFsKCQobm9kZSkuZmluZCgnYTpmaXJzdCcpKSkgdXJsID0gdXJsID0gJC5wYXRoLm1ha2VVcmxBYnNvbHV0ZSh1cmwsICQoJ2Jhc2UnKS5hdHRyKCdocmVmJykpO1xuXHRcdFx0XHRcdC8vIFJldGFpbiBzZWFyY2ggcGFyYW1ldGVyc1xuXHRcdFx0XHRcdGlmKGRvY3VtZW50LmxvY2F0aW9uLnNlYXJjaCkgdXJsID0gJC5wYXRoLmFkZFNlYXJjaFBhcmFtcyh1cmwsIGRvY3VtZW50LmxvY2F0aW9uLnNlYXJjaC5yZXBsYWNlKC9eXFw/LywgJycpKTtcblx0XHRcdFx0XHQvLyBMb2FkIG5ldyBwYWdlXG5cdFx0XHRcdFx0Y29udGFpbmVyLmxvYWRQYW5lbCh1cmwpO1x0XG5cdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0c2VsZi5yZW1vdmVGb3JtKCk7XG5cdFx0XHRcdH1cblx0XHRcdH0pO1xuXHRcdH1cblx0fSk7XG5cblx0JCgnLmNtcy1jb250ZW50IC5jbXMtY29udGVudC1maWVsZHMnKS5lbnR3aW5lKHtcblx0XHRyZWRyYXc6IGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYod2luZG93LmRlYnVnKSBjb25zb2xlLmxvZygncmVkcmF3JywgdGhpcy5hdHRyKCdjbGFzcycpLCB0aGlzLmdldCgwKSk7XG5cdFx0fVxuXHR9KTtcblxuXHQkKCcuY21zLWNvbnRlbnQgLmNtcy1jb250ZW50LWhlYWRlciwgLmNtcy1jb250ZW50IC5jbXMtY29udGVudC1hY3Rpb25zJykuZW50d2luZSh7XG5cdFx0cmVkcmF3OiBmdW5jdGlvbigpIHtcblx0XHRcdGlmKHdpbmRvdy5kZWJ1ZykgY29uc29sZS5sb2coJ3JlZHJhdycsIHRoaXMuYXR0cignY2xhc3MnKSwgdGhpcy5nZXQoMCkpO1xuXG5cdFx0XHQvLyBGaXggZGltZW5zaW9ucyB0byBhY3R1YWwgZXh0ZW50cywgaW4gcHJlcGFyYXRpb24gZm9yIGEgcmVsYXlvdXQgdmlhIGpzbGF5b3V0LlxuXHRcdFx0dGhpcy5oZWlnaHQoJ2F1dG8nKTtcblx0XHRcdHRoaXMuaGVpZ2h0KHRoaXMuaW5uZXJIZWlnaHQoKS10aGlzLmNzcygncGFkZGluZy10b3AnKS10aGlzLmNzcygncGFkZGluZy1ib3R0b20nKSk7XG5cdFx0fVxuXHR9KTtcblxuXHRcbn0pO1xuIiwiLyoqXG4gKiBGaWxlOiBMZWZ0QW5kTWFpbi5FZGl0Rm9ybS5qc1xuICovXG5pbXBvcnQgJCBmcm9tICdqUXVlcnknO1xuaW1wb3J0IGkxOG4gZnJvbSAnaTE4bic7XG5cbi8vIENhbid0IGJpbmQgdGhpcyB0aHJvdWdoIGpRdWVyeVxud2luZG93Lm9uYmVmb3JldW5sb2FkID0gZnVuY3Rpb24oZSkge1xuXHR2YXIgZm9ybSA9ICQoJy5jbXMtZWRpdC1mb3JtJyk7XG5cdGZvcm0udHJpZ2dlcignYmVmb3Jlc3VibWl0Zm9ybScpO1xuXHRpZihmb3JtLmlzKCcuY2hhbmdlZCcpICYmICEgZm9ybS5pcygnLmRpc2NhcmRjaGFuZ2VzJykpIHtcblx0XHRyZXR1cm4gaTE4bi5fdCgnTGVmdEFuZE1haW4uQ09ORklSTVVOU0FWRURTSE9SVCcpO1xuXHR9XG59O1xuXG4kLmVudHdpbmUoJ3NzJywgZnVuY3Rpb24oJCl7XG5cblx0LyoqXG5cdCAqIENsYXNzOiAuY21zLWVkaXQtZm9ybVxuXHQgKlxuXHQgKiBCYXNlIGVkaXQgZm9ybSwgcHJvdmlkZXMgYWpheGlmaWVkIHNhdmluZ1xuXHQgKiBhbmQgcmVsb2FkaW5nIGl0c2VsZiB0aHJvdWdoIHRoZSBhamF4IHJldHVybiB2YWx1ZXMuXG5cdCAqIFRha2VzIGNhcmUgb2YgcmVzaXppbmcgdGFic2V0cyB3aXRoaW4gdGhlIGxheW91dCBjb250YWluZXIuXG5cdCAqXG5cdCAqIENoYW5nZSB0cmFja2luZyBpcyBlbmFibGVkIG9uIGFsbCBmaWVsZHMgd2l0aGluIHRoZSBmb3JtLiBJZiB5b3Ugd2FudFxuXHQgKiB0byBkaXNhYmxlIGNoYW5nZSB0cmFja2luZyBmb3IgYSBzcGVjaWZpYyBmaWVsZCwgYWRkIGEgXCJuby1jaGFuZ2UtdHJhY2tcIlxuXHQgKiBjbGFzcyB0byBpdC5cblx0ICpcblx0ICogQG5hbWUgc3MuRm9ybV9FZGl0Rm9ybVxuXHQgKiBAcmVxdWlyZSBqcXVlcnkuY2hhbmdldHJhY2tlclxuXHQgKlxuXHQgKiBFdmVudHM6XG5cdCAqICBhamF4c3VibWl0IC0gRm9ybSBpcyBhYm91dCB0byBiZSBzdWJtaXR0ZWQgdGhyb3VnaCBhamF4XG5cdCAqICB2YWxpZGF0ZSAtIENvbnRhaW5zIHZhbGlkYXRpb24gcmVzdWx0XG5cdCAqICBsb2FkIC0gRm9ybSBpcyBhYm91dCB0byBiZSBsb2FkZWQgdGhyb3VnaCBhamF4XG5cdCAqL1xuXHQkKCcuY21zLWVkaXQtZm9ybScpLmVudHdpbmUoLyoqIEBsZW5kcyBzcy5Gb3JtX0VkaXRGb3JtICove1xuXHRcdC8qKlxuXHRcdCAqIFZhcmlhYmxlOiBQbGFjZWhvbGRlckh0bWxcblx0XHQgKiAoU3RyaW5nXyBIVE1MIHRleHQgdG8gc2hvdyB3aGVuIG5vIGZvcm0gY29udGVudCBpcyBjaG9zZW4uXG5cdFx0ICogV2lsbCBzaG93IGluc2lkZSB0aGUgPGZvcm0+IHRhZy5cblx0XHQgKi9cblx0XHRQbGFjZWhvbGRlckh0bWw6ICcnLFxuXG5cdFx0LyoqXG5cdFx0ICogVmFyaWFibGU6IENoYW5nZVRyYWNrZXJPcHRpb25zXG5cdFx0ICogKE9iamVjdClcblx0XHQgKi9cblx0XHRDaGFuZ2VUcmFja2VyT3B0aW9uczoge1xuXHRcdFx0aWdub3JlRmllbGRTZWxlY3RvcjogJy5uby1jaGFuZ2UtdHJhY2ssIC5zcy11cGxvYWQgOmlucHV0LCAuY21zLW5hdmlnYXRvciA6aW5wdXQnXG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIENvbnN0cnVjdG9yOiBvbm1hdGNoXG5cdFx0ICovXG5cdFx0b25hZGQ6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dmFyIHNlbGYgPSB0aGlzO1xuXG5cdFx0XHQvLyBUdXJuIG9mZiBhdXRvY29tcGxldGUgdG8gZml4IHRoZSBhY2Nlc3MgdGFiIHJhbmRvbWx5IHN3aXRjaGluZyByYWRpbyBidXR0b25zIGluIEZpcmVmb3hcblx0XHRcdC8vIHdoZW4gcmVmcmVzaCB0aGUgcGFnZSB3aXRoIGFuIGFuY2hvciB0YWcgaW4gdGhlIFVSTC4gRS5nOiAvYWRtaW4jUm9vdF9BY2Nlc3MuXG5cdFx0XHQvLyBBdXRvY29tcGxldGUgaW4gdGhlIENNUyBhbHNvIGNhdXNlcyBzdHJhbmdlbmVzcyBpbiBvdGhlciBicm93c2Vycyxcblx0XHRcdC8vIGZpbGxpbmcgb3V0IHNlY3Rpb25zIG9mIHRoZSBmb3JtIHRoYXQgdGhlIHVzZXIgZG9lcyBub3Qgd2FudCB0byBiZSBmaWxsZWQgb3V0LFxuXHRcdFx0Ly8gc28gdGhpcyB0dXJucyBpdCBvZmYgZm9yIGFsbCBicm93c2Vycy5cblx0XHRcdC8vIFNlZSB0aGUgZm9sbG93aW5nIHBhZ2UgZm9yIGRlbW8gYW5kIGV4cGxhbmF0aW9uIG9mIHRoZSBGaXJlZm94IGJ1Zzpcblx0XHRcdC8vICBodHRwOi8vd3d3LnJ5YW5jcmFtZXIuY29tL2pvdXJuYWwvZW50cmllcy9yYWRpb19idXR0b25zX2ZpcmVmb3gvXG5cdFx0XHR0aGlzLmF0dHIoXCJhdXRvY29tcGxldGVcIiwgXCJvZmZcIik7XG5cblx0XHRcdHRoaXMuX3NldHVwQ2hhbmdlVHJhY2tlcigpO1xuXG5cdFx0XHQvLyBDYXRjaCBuYXZpZ2F0aW9uIGV2ZW50cyBiZWZvcmUgdGhleSByZWFjaCBoYW5kbGVTdGF0ZUNoYW5nZSgpLFxuXHRcdFx0Ly8gaW4gb3JkZXIgdG8gYXZvaWQgY2hhbmdpbmcgdGhlIG1lbnUgc3RhdGUgaWYgdGhlIGFjdGlvbiBpcyBjYW5jZWxsZWQgYnkgdGhlIHVzZXJcblx0XHRcdC8vICQoJy5jbXMtbWVudScpXG5cblx0XHRcdC8vIE9wdGlvbmFsbHkgZ2V0IHRoZSBmb3JtIGF0dHJpYnV0ZXMgZnJvbSBlbWJlZGRlZCBmaWVsZHMsIHNlZSBGb3JtLT5mb3JtSHRtbENvbnRlbnQoKVxuXHRcdFx0Zm9yKHZhciBvdmVycmlkZUF0dHIgaW4geydhY3Rpb24nOnRydWUsJ21ldGhvZCc6dHJ1ZSwnZW5jdHlwZSc6dHJ1ZSwnbmFtZSc6dHJ1ZX0pIHtcblx0XHRcdFx0dmFyIGVsID0gdGhpcy5maW5kKCc6aW5wdXRbbmFtZT0nKyAnX2Zvcm1fJyArIG92ZXJyaWRlQXR0ciArICddJyk7XG5cdFx0XHRcdGlmKGVsKSB7XG5cdFx0XHRcdFx0dGhpcy5hdHRyKG92ZXJyaWRlQXR0ciwgZWwudmFsKCkpO1xuXHRcdFx0XHRcdGVsLnJlbW92ZSgpO1xuXHRcdFx0XHR9XG5cdFx0XHR9XG5cblx0XHRcdC8vIFRPRE9cblx0XHRcdC8vIC8vIFJld3JpdGUgIyBsaW5rc1xuXHRcdFx0Ly8gaHRtbCA9IGh0bWwucmVwbGFjZSgvKDxhW14+XStocmVmICo9ICpcIikjL2csICckMScgKyB3aW5kb3cubG9jYXRpb24uaHJlZi5yZXBsYWNlKC8jLiokLywnJykgKyAnIycpO1xuXHRcdFx0Ly9cblx0XHRcdC8vIC8vIFJld3JpdGUgaWZyYW1lIGxpbmtzIChmb3IgSUUpXG5cdFx0XHQvLyBodG1sID0gaHRtbC5yZXBsYWNlKC8oPGlmcmFtZVtePl0qc3JjPVwiKShbXlwiXSspKFwiW14+XSo+KS9nLCAnJDEnICsgJCgnYmFzZScpLmF0dHIoJ2hyZWYnKSArICckMiQzJyk7XG5cblx0XHRcdC8vIFNob3cgdmFsaWRhdGlvbiBlcnJvcnMgaWYgbmVjZXNzYXJ5XG5cdFx0XHRpZih0aGlzLmhhc0NsYXNzKCd2YWxpZGF0aW9uZXJyb3InKSkge1xuXHRcdFx0XHQvLyBFbnN1cmUgdGhlIGZpcnN0IHZhbGlkYXRpb24gZXJyb3IgaXMgdmlzaWJsZVxuXHRcdFx0XHR2YXIgdGFiRXJyb3IgPSB0aGlzLmZpbmQoJy5tZXNzYWdlLnZhbGlkYXRpb24sIC5tZXNzYWdlLnJlcXVpcmVkJykuZmlyc3QoKS5jbG9zZXN0KCcudGFiJyk7XG5cdFx0XHRcdCQoJy5jbXMtY29udGFpbmVyJykuY2xlYXJDdXJyZW50VGFiU3RhdGUoKTsgLy8gY2xlYXIgc3RhdGUgdG8gYXZvaWQgb3ZlcnJpZGUgbGF0ZXIgb25cblx0XHRcdFx0dGFiRXJyb3IuY2xvc2VzdCgnLnNzLXRhYnNldCcpLnRhYnMoJ29wdGlvbicsICdhY3RpdmUnLCB0YWJFcnJvci5pbmRleCgnLnRhYicpKTtcblx0XHRcdH1cblxuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9LFxuXHRcdG9ucmVtb3ZlOiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMuY2hhbmdldHJhY2tlcignZGVzdHJveScpO1xuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9LFxuXHRcdG9ubWF0Y2g6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9LFxuXHRcdG9udW5tYXRjaDogZnVuY3Rpb24oKSB7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdH0sXG5cdFx0cmVkcmF3OiBmdW5jdGlvbigpIHtcblx0XHRcdGlmKHdpbmRvdy5kZWJ1ZykgY29uc29sZS5sb2coJ3JlZHJhdycsIHRoaXMuYXR0cignY2xhc3MnKSwgdGhpcy5nZXQoMCkpO1xuXG5cdFx0XHQvLyBGb3JjZSBpbml0aWFsaXphdGlvbiBvZiB0YWJzZXRzIHRvIGF2b2lkIGxheW91dCBnbGl0Y2hlc1xuXHRcdFx0dGhpcy5hZGQodGhpcy5maW5kKCcuY21zLXRhYnNldCcpKS5yZWRyYXdUYWJzKCk7XG5cdFx0XHR0aGlzLmZpbmQoJy5jbXMtY29udGVudC1oZWFkZXInKS5yZWRyYXcoKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogRnVuY3Rpb246IF9zZXR1cENoYW5nZVRyYWNrZXJcblx0XHQgKi9cblx0XHRfc2V0dXBDaGFuZ2VUcmFja2VyOiBmdW5jdGlvbigpIHtcblx0XHRcdC8vIERvbid0IGJpbmQgYW55IGV2ZW50cyBoZXJlLCBhcyB3ZSBkb250IHJlcGxhY2UgdGhlXG5cdFx0XHQvLyBmdWxsIDxmb3JtPiB0YWcgYnkgYW55IGFqYXggdXBkYXRlcyB0aGV5IHdvbid0IGF1dG9tYXRpY2FsbHkgcmVhcHBseVxuXHRcdFx0dGhpcy5jaGFuZ2V0cmFja2VyKHRoaXMuZ2V0Q2hhbmdlVHJhY2tlck9wdGlvbnMoKSk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEZ1bmN0aW9uOiBjb25maXJtVW5zYXZlZENoYW5nZXNcblx0XHQgKlxuXHRcdCAqIENoZWNrcyB0aGUganF1ZXJ5LmNoYW5nZXRyYWNrZXIgcGx1Z2luIHN0YXR1cyBmb3IgdGhpcyBmb3JtLFxuXHRcdCAqIGFuZCBhc2tzIHRoZSB1c2VyIGZvciBjb25maXJtYXRpb24gdmlhIGEgYnJvd3NlciBkaWFsb2cgaWYgY2hhbmdlcyBhcmUgZGV0ZWN0ZWQuXG5cdFx0ICogRG9lc24ndCBjYW5jZWwgYW55IHVubG9hZCBvciBmb3JtIHJlbW92YWwgZXZlbnRzLCB5b3UnbGwgbmVlZCB0byBpbXBsZW1lbnQgdGhpcyBiYXNlZCBvbiB0aGUgcmV0dXJuXG5cdFx0ICogdmFsdWUgb2YgdGhpcyBtZXNzYWdlLlxuXHRcdCAqXG5cdFx0ICogSWYgY2hhbmdlcyBhcmUgY29uZmlybWVkIGZvciBkaXNjYXJkLCB0aGUgJ2NoYW5nZWQnIGZsYWcgaXMgcmVzZXQuXG5cdFx0ICpcblx0XHQgKiBSZXR1cm5zOlxuXHRcdCAqICAoQm9vbGVhbikgRkFMU0UgaWYgdGhlIHVzZXIgd2FudHMgdG8gYWJvcnQgd2l0aCBjaGFuZ2VzIHByZXNlbnQsIFRSVUUgaWYgbm8gY2hhbmdlcyBhcmUgZGV0ZWN0ZWRcblx0XHQgKiAgb3IgdGhlIHVzZXIgd2FudHMgdG8gZGlzY2FyZCB0aGVtLlxuXHRcdCAqL1xuXHRcdGNvbmZpcm1VbnNhdmVkQ2hhbmdlczogZnVuY3Rpb24oKSB7XG5cdFx0XHR0aGlzLnRyaWdnZXIoJ2JlZm9yZXN1Ym1pdGZvcm0nKTtcblx0XHRcdGlmKCF0aGlzLmlzKCcuY2hhbmdlZCcpIHx8IHRoaXMuaXMoJy5kaXNjYXJkY2hhbmdlcycpKSB7XG5cdFx0XHRcdHJldHVybiB0cnVlO1xuXHRcdFx0fVxuXHRcdFx0dmFyIGNvbmZpcm1lZCA9IGNvbmZpcm0oaTE4bi5fdCgnTGVmdEFuZE1haW4uQ09ORklSTVVOU0FWRUQnKSk7XG5cdFx0XHRpZihjb25maXJtZWQpIHtcblx0XHRcdFx0Ly8gRW5zdXJlcyB0aGF0IG9uY2UgYSBmb3JtIGlzIGNvbmZpcm1lZCwgc3Vic2VxdWVudFxuXHRcdFx0XHQvLyBjaGFuZ2VzIHRvIHRoZSB1bmRlcmx5aW5nIGZvcm0gZG9uJ3QgdHJpZ2dlclxuXHRcdFx0XHQvLyBhZGRpdGlvbmFsIGNoYW5nZSBjb25maXJtYXRpb24gcmVxdWVzdHNcblx0XHRcdFx0dGhpcy5hZGRDbGFzcygnZGlzY2FyZGNoYW5nZXMnKTtcblx0XHRcdH1cblx0XHRcdHJldHVybiBjb25maXJtZWQ7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEZ1bmN0aW9uOiBvbnN1Ym1pdFxuXHRcdCAqXG5cdFx0ICogU3VwcHJlc3Mgc3VibWlzc2lvbiB1bmxlc3MgaXQgaXMgaGFuZGxlZCB0aHJvdWdoIGFqYXhTdWJtaXQoKS5cblx0XHQgKi9cblx0XHRvbnN1Ym1pdDogZnVuY3Rpb24oZSwgYnV0dG9uKSB7XG5cdFx0XHQvLyBPbmx5IHN1Ym1pdCBpZiBhIGJ1dHRvbiBpcyBwcmVzZW50LlxuXHRcdFx0Ly8gVGhpcyBzdXByZXNzZWQgc3VibWl0cyBmcm9tIEVOVEVSIGtleXMgaW4gaW5wdXQgZmllbGRzLFxuXHRcdFx0Ly8gd2hpY2ggbWVhbnMgdGhlIGJyb3dzZXIgYXV0by1zZWxlY3RzIHRoZSBmaXJzdCBhdmFpbGFibGUgZm9ybSBidXR0b24uXG5cdFx0XHQvLyBUaGlzIG1pZ2h0IGJlIGFuIHVucmVsYXRlZCBidXR0b24gb2YgdGhlIGZvcm0gZmllbGQsXG5cdFx0XHQvLyBvciBhIGRlc3RydWN0aXZlIGFjdGlvbiAoaWYgXCJzYXZlXCIgaXMgbm90IGF2YWlsYWJsZSwgb3Igbm90IG9uIGZpcnN0IHBvc2l0aW9uKS5cblx0XHRcdGlmKHRoaXMucHJvcChcInRhcmdldFwiKSAhPSBcIl9ibGFua1wiKSB7XG5cdFx0XHRcdGlmKGJ1dHRvbikgdGhpcy5jbG9zZXN0KCcuY21zLWNvbnRhaW5lcicpLnN1Ym1pdEZvcm0odGhpcywgYnV0dG9uKTtcblx0XHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdFx0fVxuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBGdW5jdGlvbjogdmFsaWRhdGVcblx0XHQgKlxuXHRcdCAqIEhvb2sgaW4gKG9wdGlvbmFsKSB2YWxpZGF0aW9uIHJvdXRpbmVzLlxuXHRcdCAqIEN1cnJlbnRseSBjbGllbnRzaWRlIHZhbGlkYXRpb24gaXMgbm90IHN1cHBvcnRlZCBvdXQgb2YgdGhlIGJveCBpbiB0aGUgQ01TLlxuXHRcdCAqXG5cdFx0ICogVG9kbzpcblx0XHQgKiAgUGxhY2Vob2xkZXIgaW1wbGVtZW50YXRpb25cblx0XHQgKlxuXHRcdCAqIFJldHVybnM6XG5cdFx0ICogIHtib29sZWFufVxuXHRcdCAqL1xuXHRcdHZhbGlkYXRlOiBmdW5jdGlvbigpIHtcblx0XHRcdHZhciBpc1ZhbGlkID0gdHJ1ZTtcblx0XHRcdHRoaXMudHJpZ2dlcigndmFsaWRhdGUnLCB7aXNWYWxpZDogaXNWYWxpZH0pO1xuXG5cdFx0XHRyZXR1cm4gaXNWYWxpZDtcblx0XHR9LFxuXHRcdC8qXG5cdFx0ICogVHJhY2sgZm9jdXMgb24gaHRtbGVkaXRvciBmaWVsZHNcblx0XHQgKi9cblx0XHQnZnJvbSAuaHRtbGVkaXRvcic6IHtcblx0XHRcdG9uZWRpdG9yaW5pdDogZnVuY3Rpb24oZSl7XG5cdFx0XHRcdHZhciBzZWxmID0gdGhpcyxcblx0XHRcdFx0XHRmaWVsZCA9ICQoZS50YXJnZXQpLmNsb3Nlc3QoJy5maWVsZC5odG1sZWRpdG9yJyksXG5cdFx0XHRcdFx0ZWRpdG9yID0gZmllbGQuZmluZCgndGV4dGFyZWEuaHRtbGVkaXRvcicpLmdldEVkaXRvcigpLmdldEluc3RhbmNlKCk7XG5cblx0XHRcdFx0Ly8gVGlueU1DRSA0IHdpbGwgYWRkIGEgZm9jdXMgZXZlbnQsIGJ1dCBmb3Igbm93LCB1c2UgY2xpY2tcblx0XHRcdFx0ZWRpdG9yLm9uQ2xpY2suYWRkKGZ1bmN0aW9uKGUpe1xuXHRcdFx0XHRcdHNlbGYuc2F2ZUZpZWxkRm9jdXMoZmllbGQuYXR0cignaWQnKSk7XG5cdFx0XHRcdH0pO1xuXHRcdFx0fVxuXHRcdH0sXG5cdFx0Lypcblx0XHQgKiBUcmFjayBmb2N1cyBvbiBpbnB1dHNcblx0XHQgKi9cblx0XHQnZnJvbSAuY21zLWVkaXQtZm9ybSA6aW5wdXQ6bm90KDpzdWJtaXQpJzoge1xuXHRcdFx0b25jbGljazogZnVuY3Rpb24oZSl7XG5cdFx0XHRcdHRoaXMuc2F2ZUZpZWxkRm9jdXMoJChlLnRhcmdldCkuYXR0cignaWQnKSk7XG5cdFx0XHR9LFxuXHRcdFx0b25mb2N1czogZnVuY3Rpb24oZSl7XG5cdFx0XHRcdHRoaXMuc2F2ZUZpZWxkRm9jdXMoJChlLnRhcmdldCkuYXR0cignaWQnKSk7XG5cdFx0XHR9XG5cdFx0fSxcblx0XHQvKlxuXHRcdCAqIFRyYWNrIGZvY3VzIG9uIHRyZWVkcm9wZG93bmZpZWxkcy5cblx0XHQgKi9cblx0XHQnZnJvbSAuY21zLWVkaXQtZm9ybSAudHJlZWRyb3Bkb3duIConOiB7XG5cdFx0XHRvbmZvY3VzaW46IGZ1bmN0aW9uKGUpe1xuXHRcdFx0XHR2YXIgZmllbGQgPSAkKGUudGFyZ2V0KS5jbG9zZXN0KCcuZmllbGQudHJlZWRyb3Bkb3duJyk7XG5cdFx0XHRcdHRoaXMuc2F2ZUZpZWxkRm9jdXMoZmllbGQuYXR0cignaWQnKSk7XG5cdFx0XHR9XG5cdFx0fSxcblx0XHQvKlxuXHRcdCAqIFRyYWNrIGZvY3VzIG9uIGNob3NlbiBzZWxlY3RzXG5cdFx0ICovXG5cdFx0J2Zyb20gLmNtcy1lZGl0LWZvcm0gLmRyb3Bkb3duIC5jaHpuLWNvbnRhaW5lciBhJzoge1xuXHRcdFx0b25mb2N1c2luOiBmdW5jdGlvbihlKXtcblx0XHRcdFx0dmFyIGZpZWxkID0gJChlLnRhcmdldCkuY2xvc2VzdCgnLmZpZWxkLmRyb3Bkb3duJyk7XG5cdFx0XHRcdHRoaXMuc2F2ZUZpZWxkRm9jdXMoZmllbGQuYXR0cignaWQnKSk7XG5cdFx0XHR9XG5cdFx0fSxcblx0XHQvKlxuXHRcdCAqIFJlc3RvcmUgZmllbGRzIGFmdGVyIHRhYnMgYXJlIHJlc3RvcmVkXG5cdFx0ICovXG5cdFx0J2Zyb20gLmNtcy1jb250YWluZXInOiB7XG5cdFx0XHRvbnRhYnN0YXRlcmVzdG9yZWQ6IGZ1bmN0aW9uKGUpe1xuXHRcdFx0XHR0aGlzLnJlc3RvcmVGaWVsZEZvY3VzKCk7XG5cdFx0XHR9XG5cdFx0fSxcblx0XHQvKlxuXHRcdCAqIFNhdmVzIGZvY3VzIGluIFdpbmRvdyBzZXNzaW9uIHN0b3JhZ2Ugc28gaXQgdGhhdCBjYW4gYmUgcmVzdG9yZWQgb24gcGFnZSBsb2FkXG5cdFx0ICovXG5cdFx0c2F2ZUZpZWxkRm9jdXM6IGZ1bmN0aW9uKHNlbGVjdGVkKXtcblx0XHRcdGlmKHR5cGVvZih3aW5kb3cuc2Vzc2lvblN0b3JhZ2UpPT1cInVuZGVmaW5lZFwiIHx8IHdpbmRvdy5zZXNzaW9uU3RvcmFnZSA9PT0gbnVsbCkgcmV0dXJuO1xuXG5cdFx0XHR2YXIgaWQgPSAkKHRoaXMpLmF0dHIoJ2lkJyksXG5cdFx0XHRcdGZvY3VzRWxlbWVudHMgPSBbXTtcblxuXHRcdFx0Zm9jdXNFbGVtZW50cy5wdXNoKHtcblx0XHRcdFx0aWQ6aWQsXG5cdFx0XHRcdHNlbGVjdGVkOnNlbGVjdGVkXG5cdFx0XHR9KTtcblxuXHRcdFx0aWYoZm9jdXNFbGVtZW50cykge1xuXHRcdFx0XHR0cnkge1xuXHRcdFx0XHRcdHdpbmRvdy5zZXNzaW9uU3RvcmFnZS5zZXRJdGVtKGlkLCBKU09OLnN0cmluZ2lmeShmb2N1c0VsZW1lbnRzKSk7XG5cdFx0XHRcdH0gY2F0Y2goZXJyKSB7XG5cdFx0XHRcdFx0aWYgKGVyci5jb2RlID09PSBET01FeGNlcHRpb24uUVVPVEFfRVhDRUVERURfRVJSICYmIHdpbmRvdy5zZXNzaW9uU3RvcmFnZS5sZW5ndGggPT09IDApIHtcblx0XHRcdFx0XHRcdC8vIElmIHRoaXMgZmFpbHMgd2UgaWdub3JlIHRoZSBlcnJvciBhcyB0aGUgb25seSBpc3N1ZSBpcyB0aGF0IGl0XG5cdFx0XHRcdFx0XHQvLyBkb2VzIG5vdCByZW1lbWJlciB0aGUgZm9jdXMgc3RhdGUuXG5cdFx0XHRcdFx0XHQvLyBUaGlzIGlzIGEgU2FmYXJpIGJ1ZyB3aGljaCBoYXBwZW5zIHdoZW4gcHJpdmF0ZSBicm93c2luZyBpcyBlbmFibGVkLlxuXHRcdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0XHR0aHJvdyBlcnI7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9XG5cdFx0XHR9XG5cdFx0fSxcblx0XHQvKipcblx0XHQgKiBTZXQgZm9jdXMgb3Igd2luZG93IHRvIHByZXZpb3VzbHkgc2F2ZWQgZmllbGRzLlxuXHRcdCAqIFJlcXVpcmVzIEhUTUw1IHNlc3Npb25TdG9yYWdlIHN1cHBvcnQuXG5cdFx0ICpcblx0XHQgKiBNdXN0IGZvbGxvdyB0YWIgcmVzdG9yYXRpb24sIGFzIHJlbGlhbnQgb24gYWN0aXZlIHRhYlxuXHRcdCAqL1xuXHRcdHJlc3RvcmVGaWVsZEZvY3VzOiBmdW5jdGlvbigpe1xuXHRcdFx0aWYodHlwZW9mKHdpbmRvdy5zZXNzaW9uU3RvcmFnZSk9PVwidW5kZWZpbmVkXCIgfHwgd2luZG93LnNlc3Npb25TdG9yYWdlID09PSBudWxsKSByZXR1cm47XG5cblx0XHRcdHZhciBzZWxmID0gdGhpcyxcblx0XHRcdFx0aGFzU2Vzc2lvblN0b3JhZ2UgPSAodHlwZW9mKHdpbmRvdy5zZXNzaW9uU3RvcmFnZSkhPT1cInVuZGVmaW5lZFwiICYmIHdpbmRvdy5zZXNzaW9uU3RvcmFnZSksXG5cdFx0XHRcdHNlc3Npb25EYXRhID0gaGFzU2Vzc2lvblN0b3JhZ2UgPyB3aW5kb3cuc2Vzc2lvblN0b3JhZ2UuZ2V0SXRlbSh0aGlzLmF0dHIoJ2lkJykpIDogbnVsbCxcblx0XHRcdFx0c2Vzc2lvblN0YXRlcyA9IHNlc3Npb25EYXRhID8gSlNPTi5wYXJzZShzZXNzaW9uRGF0YSkgOiBmYWxzZSxcblx0XHRcdFx0ZWxlbWVudElELFxuXHRcdFx0XHR0YWJiZWQgPSAodGhpcy5maW5kKCcuc3MtdGFic2V0JykubGVuZ3RoICE9PSAwKSxcblx0XHRcdFx0YWN0aXZlVGFiLFxuXHRcdFx0XHRlbGVtZW50VGFiLFxuXHRcdFx0XHR0b2dnbGVDb21wb3NpdGUsXG5cdFx0XHRcdHNjcm9sbFk7XG5cblx0XHRcdGlmKGhhc1Nlc3Npb25TdG9yYWdlICYmIHNlc3Npb25TdGF0ZXMubGVuZ3RoID4gMCl7XG5cdFx0XHRcdCQuZWFjaChzZXNzaW9uU3RhdGVzLCBmdW5jdGlvbihpLCBzZXNzaW9uU3RhdGUpIHtcblx0XHRcdFx0XHRpZihzZWxmLmlzKCcjJyArIHNlc3Npb25TdGF0ZS5pZCkpe1xuXHRcdFx0XHRcdFx0ZWxlbWVudElEID0gJCgnIycgKyBzZXNzaW9uU3RhdGUuc2VsZWN0ZWQpO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSk7XG5cblx0XHRcdFx0Ly8gSWYgdGhlIGVsZW1lbnQgSURzIHNhdmVkIGluIHNlc3Npb24gc3RhdGVzIGRvbid0IG1hdGNoIHVwIHRvIGFueXRoaW5nIGluIHRoaXMgcGFydGljdWxhciBmb3JtXG5cdFx0XHRcdC8vIHRoYXQgcHJvYmFibHkgbWVhbnMgd2UgaGF2ZW4ndCBlbmNvdW50ZXJlZCB0aGlzIGZvcm0geWV0LCBzbyBmb2N1cyBvbiB0aGUgZmlyc3QgaW5wdXRcblx0XHRcdFx0aWYoJChlbGVtZW50SUQpLmxlbmd0aCA8IDEpe1xuXHRcdFx0XHRcdHRoaXMuZm9jdXNGaXJzdElucHV0KCk7XG5cdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHR9XG5cblx0XHRcdFx0YWN0aXZlVGFiID0gJChlbGVtZW50SUQpLmNsb3Nlc3QoJy5zcy10YWJzZXQnKS5maW5kKCcudWktdGFicy1uYXYgLnVpLXRhYnMtYWN0aXZlIC51aS10YWJzLWFuY2hvcicpLmF0dHIoJ2lkJyk7XG5cdFx0XHRcdGVsZW1lbnRUYWIgID0gJ3RhYi0nICsgJChlbGVtZW50SUQpLmNsb3Nlc3QoJy5zcy10YWJzZXQgLnVpLXRhYnMtcGFuZWwnKS5hdHRyKCdpZCcpO1xuXG5cdFx0XHRcdC8vIExhc3QgZm9jdXNzZWQgZWxlbWVudCBkaWZmZXJzIHRvIGxhc3Qgc2VsZWN0ZWQgdGFiLCBkbyBub3RoaW5nXG5cdFx0XHRcdGlmKHRhYmJlZCAmJiBlbGVtZW50VGFiICE9PSBhY3RpdmVUYWIpe1xuXHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdHRvZ2dsZUNvbXBvc2l0ZSA9ICQoZWxlbWVudElEKS5jbG9zZXN0KCcudG9nZ2xlY29tcG9zaXRlJyk7XG5cblx0XHRcdFx0Ly9SZW9wZW4gdG9nZ2xlIGZpZWxkc1xuXHRcdFx0XHRpZih0b2dnbGVDb21wb3NpdGUubGVuZ3RoID4gMCl7XG5cdFx0XHRcdFx0dG9nZ2xlQ29tcG9zaXRlLmFjY29yZGlvbignYWN0aXZhdGUnLCB0b2dnbGVDb21wb3NpdGUuZmluZCgnLnVpLWFjY29yZGlvbi1oZWFkZXInKSk7XG5cdFx0XHRcdH1cblxuXHRcdFx0XHQvL0NhbGN1bGF0ZSBwb3NpdGlvbiBmb3Igc2Nyb2xsXG5cdFx0XHRcdHNjcm9sbFkgPSAkKGVsZW1lbnRJRCkucG9zaXRpb24oKS50b3A7XG5cblx0XHRcdFx0Ly9GYWxsIGJhY2sgdG8gbmVhcmVzdCB2aXNpYmxlIGVsZW1lbnQgaWYgaGlkZGVuIChmb3Igc2VsZWN0IHR5cGUgZmllbGRzKVxuXHRcdFx0XHRpZighJChlbGVtZW50SUQpLmlzKCc6dmlzaWJsZScpKXtcblx0XHRcdFx0XHRlbGVtZW50SUQgPSAnIycgKyAkKGVsZW1lbnRJRCkuY2xvc2VzdCgnLmZpZWxkJykuYXR0cignaWQnKTtcblx0XHRcdFx0XHRzY3JvbGxZID0gJChlbGVtZW50SUQpLnBvc2l0aW9uKCkudG9wO1xuXHRcdFx0XHR9XG5cblx0XHRcdFx0Ly9zZXQgZm9jdXMgdG8gZm9jdXMgdmFyaWFibGUgaWYgZWxlbWVudCBmb2N1c2FibGVcblx0XHRcdFx0JChlbGVtZW50SUQpLmZvY3VzKCk7XG5cblx0XHRcdFx0Ly8gU2Nyb2xsIGZhbGxiYWNrIHdoZW4gZWxlbWVudCBpcyBub3QgZm9jdXNhYmxlXG5cdFx0XHRcdC8vIE9ubHkgc2Nyb2xsIGlmIGVsZW1lbnQgYXQgbGVhc3QgaGFsZiB3YXkgZG93biB3aW5kb3dcblx0XHRcdFx0aWYoc2Nyb2xsWSA+ICQod2luZG93KS5oZWlnaHQoKSAvIDIpe1xuXHRcdFx0XHRcdHNlbGYuZmluZCgnLmNtcy1jb250ZW50LWZpZWxkcycpLnNjcm9sbFRvcChzY3JvbGxZKTtcblx0XHRcdFx0fVxuXG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHQvLyBJZiBzZXNzaW9uIHN0b3JhZ2UgaXMgbm90IHN1cHBvcnRlZCBvciB0aGVyZSBpcyBub3RoaW5nIHN0b3JlZCB5ZXQsIGZvY3VzIG9uIHRoZSBmaXJzdCBpbnB1dFxuXHRcdFx0XHR0aGlzLmZvY3VzRmlyc3RJbnB1dCgpO1xuXHRcdFx0fVxuXHRcdH0sXG5cdFx0LyoqXG5cdFx0ICogU2tpcCBpZiBhbiBlbGVtZW50IGluIHRoZSBmb3JtIGlzIGFscmVhZHkgZm9jdXNlZC4gRXhjbHVkZSBlbGVtZW50cyB3aGljaCBzcGVjaWZpY2FsbHlcblx0XHQgKiBvcHQtb3V0IG9mIHRoaXMgYmVoYXZpb3VyIHZpYSBcImRhdGEtc2tpcC1hdXRvZm9jdXNcIi4gVGhpcyBvcHQtb3V0IGlzIHVzZWZ1bCBpZiB0aGVcblx0XHQgKiBmaXJzdCB2aXNpYmxlIGZpZWxkIGlzIHNob3duIGZhciBkb3duIGEgc2Nyb2xsYWJsZSBhcmVhLCBmb3IgZXhhbXBsZSBmb3IgdGhlIHBhZ2luYXRpb25cblx0XHQgKiBpbnB1dCBmaWVsZCBhZnRlciBhIGxvbmcgR3JpZEZpZWxkIGxpc3RpbmcuXG5cdFx0ICovXG5cdFx0Zm9jdXNGaXJzdElucHV0OiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMuZmluZCgnOmlucHV0Om5vdCg6c3VibWl0KVtkYXRhLXNraXAtYXV0b2ZvY3VzIT1cInRydWVcIl0nKS5maWx0ZXIoJzp2aXNpYmxlOmZpcnN0JykuZm9jdXMoKTtcblx0XHR9XG5cdH0pO1xuXG5cdC8qKlxuXHQgKiBDbGFzczogLmNtcy1lZGl0LWZvcm0gLkFjdGlvbnMgOnN1Ym1pdFxuXHQgKlxuXHQgKiBBbGwgYnV0dG9ucyBpbiB0aGUgcmlnaHQgQ01TIGZvcm0gZ28gdGhyb3VnaCBoZXJlIGJ5IGRlZmF1bHQuXG5cdCAqIFdlIG5lZWQgdGhpcyBvbmNsaWNrIG92ZXJsb2FkaW5nIGJlY2F1c2Ugd2UgY2FuJ3QgZ2V0IHRvIHRoZVxuXHQgKiBjbGlja2VkIGJ1dHRvbiBmcm9tIGEgZm9ybS5vbnN1Ym1pdCBldmVudC5cblx0ICovXG5cdCQoJy5jbXMtZWRpdC1mb3JtIC5BY3Rpb25zIGlucHV0LmFjdGlvblt0eXBlPXN1Ym1pdF0sIC5jbXMtZWRpdC1mb3JtIC5BY3Rpb25zIGJ1dHRvbi5hY3Rpb24nKS5lbnR3aW5lKHtcblx0XHQvKipcblx0XHQgKiBGdW5jdGlvbjogb25jbGlja1xuXHRcdCAqL1xuXHRcdG9uY2xpY2s6IGZ1bmN0aW9uKGUpIHtcblx0XHRcdC8vIENvbmZpcm1hdGlvbiBvbiBkZWxldGUuXG5cdFx0XHRpZihcblx0XHRcdFx0dGhpcy5oYXNDbGFzcygnZ3JpZGZpZWxkLWJ1dHRvbi1kZWxldGUnKVxuXHRcdFx0XHQmJiAhY29uZmlybShpMThuLl90KCdUQUJMRUZJRUxELkRFTEVURUNPTkZJUk1NRVNTQUdFJykpXG5cdFx0XHQpIHtcblx0XHRcdFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXHRcdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0XHR9XG5cblx0XHRcdGlmKCF0aGlzLmlzKCc6ZGlzYWJsZWQnKSkge1xuXHRcdFx0XHR0aGlzLnBhcmVudHMoJ2Zvcm0nKS50cmlnZ2VyKCdzdWJtaXQnLCBbdGhpc10pO1xuXHRcdFx0fVxuXHRcdFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdH1cblx0fSk7XG5cblx0LyoqXG5cdCAqIElmIHdlJ3ZlIGEgaGlzdG9yeSBzdGF0ZSB0byBnbyBiYWNrIHRvLCBnbyBiYWNrLCBvdGhlcndpc2UgZmFsbCBiYWNrIHRvXG5cdCAqIHN1Ym1pdHRpbmcgdGhlIGZvcm0gd2l0aCB0aGUgJ2RvQ2FuY2VsJyBhY3Rpb24uXG5cdCAqL1xuXHQkKCcuY21zLWVkaXQtZm9ybSAuQWN0aW9ucyBpbnB1dC5hY3Rpb25bdHlwZT1zdWJtaXRdLnNzLXVpLWFjdGlvbi1jYW5jZWwsIC5jbXMtZWRpdC1mb3JtIC5BY3Rpb25zIGJ1dHRvbi5hY3Rpb24uc3MtdWktYWN0aW9uLWNhbmNlbCcpLmVudHdpbmUoe1xuXHRcdG9uY2xpY2s6IGZ1bmN0aW9uKGUpIHtcblx0XHRcdGlmICh3aW5kb3cuaGlzdG9yeS5sZW5ndGggPiAxKSB7XG5cdFx0XHRcdHdpbmRvdy5oaXN0b3J5LmJhY2soKTtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdHRoaXMucGFyZW50cygnZm9ybScpLnRyaWdnZXIoJ3N1Ym1pdCcsIFt0aGlzXSk7XG5cdFx0XHR9XG5cdFx0XHRlLnByZXZlbnREZWZhdWx0KCk7XG5cdFx0fVxuXHR9KTtcblxuXHQvKipcblx0ICogSGlkZSB0YWJzIHdoZW4gb25seSBvbmUgaXMgYXZhaWxhYmxlLlxuXHQgKiBTcGVjaWFsIGNhc2UgaXMgYWN0aW9udGFicyAtIHRhYnMgYmV0d2VlbiBidXR0b25zLCB3aGVyZSB3ZSB3YW50IHRvIGhhdmVcblx0ICogZXh0cmEgb3B0aW9ucyBoaWRkZW4gd2l0aGluIGEgdGFiIChldmVuIGlmIG9ubHkgb25lKSBieSBkZWZhdWx0LlxuXHQgKi9cblx0JCgnLmNtcy1lZGl0LWZvcm0gLnNzLXRhYnNldCcpLmVudHdpbmUoe1xuXHRcdG9ubWF0Y2g6IGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYgKCF0aGlzLmhhc0NsYXNzKCdzcy11aS1hY3Rpb24tdGFic2V0JykpIHtcblx0XHRcdFx0dmFyIHRhYnMgPSB0aGlzLmZpbmQoXCI+IHVsOmZpcnN0XCIpO1xuXG5cdFx0XHRcdGlmKHRhYnMuY2hpbGRyZW4oXCJsaVwiKS5sZW5ndGggPT0gMSkge1xuXHRcdFx0XHRcdHRhYnMuaGlkZSgpLnBhcmVudCgpLmFkZENsYXNzKFwic3MtdGFic2V0LXRhYnNoaWRkZW5cIik7XG5cdFx0XHRcdH1cblx0XHRcdH1cblxuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9LFxuXHRcdG9udW5tYXRjaDogZnVuY3Rpb24oKSB7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdH1cblx0fSk7XG5cbn0pO1xuIiwiLyoqXG4gKiBFbmFibGUgdG9nZ2xpbmcgKHNob3cvaGlkZSkgb2YgdGhlIGZpZWxkJ3MgZGVzY3JpcHRpb24uXG4gKi9cblxuaW1wb3J0ICQgZnJvbSAnalF1ZXJ5JztcblxuJC5lbnR3aW5lKCdzcycsIGZ1bmN0aW9uICgkKSB7XG5cbiAgICAkKCcuY21zLWRlc2NyaXB0aW9uLXRvZ2dsZScpLmVudHdpbmUoe1xuICAgICAgICBvbmFkZDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgdmFyIHNob3duID0gZmFsc2UsIC8vIEN1cnJlbnQgc3RhdGUgb2YgdGhlIGRlc2NyaXB0aW9uLlxuICAgICAgICAgICAgICAgIGZpZWxkSWQgPSB0aGlzLnByb3AoJ2lkJykuc3Vic3RyKDAsIHRoaXMucHJvcCgnaWQnKS5pbmRleE9mKCdfSG9sZGVyJykpLFxuICAgICAgICAgICAgICAgICR0cmlnZ2VyID0gdGhpcy5maW5kKCcuY21zLWRlc2NyaXB0aW9uLXRyaWdnZXInKSwgLy8gQ2xpY2sgdGFyZ2V0IGZvciB0b2dnbGluZyB0aGUgZGVzY3JpcHRpb24uXG4gICAgICAgICAgICAgICAgJGRlc2NyaXB0aW9uID0gdGhpcy5maW5kKCcuZGVzY3JpcHRpb24nKTtcblxuICAgICAgICAgICAgLy8gUHJldmVudCBtdWx0aXBsZSBldmVudHMgYmVpbmcgYWRkZWQuXG4gICAgICAgICAgICBpZiAodGhpcy5oYXNDbGFzcygnZGVzY3JpcHRpb24tdG9nZ2xlLWVuYWJsZWQnKSkge1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgLy8gSWYgYSBjdXN0b20gdHJpZ2dlciBoYW4ndCBiZWVuIHN1cHBsaWVkIHVzZSBhIHNlbnNpYmxlIGRlZmF1bHQuXG4gICAgICAgICAgICBpZiAoJHRyaWdnZXIubGVuZ3RoID09PSAwKSB7XG4gICAgICAgICAgICAgICAgJHRyaWdnZXIgPSB0aGlzXG4gICAgICAgICAgICAgICAgICAgIC5maW5kKCcubWlkZGxlQ29sdW1uJylcbiAgICAgICAgICAgICAgICAgICAgLmZpcnN0KCkgLy8gR2V0IHRoZSBmaXJzdCBtaWRkbGVDb2x1bW4gc28gd2UgZG9uJ3QgYWRkIG11bHRpcGxlIHRyaWdnZXJzIG9uIGNvbXBvc2l0ZSBmaWVsZCB0eXBlcy5cbiAgICAgICAgICAgICAgICAgICAgLmFmdGVyKCc8bGFiZWwgY2xhc3M9XCJyaWdodFwiIGZvcj1cIicgKyBmaWVsZElkICsgJ1wiPjxhIGNsYXNzPVwiY21zLWRlc2NyaXB0aW9uLXRyaWdnZXJcIiBocmVmPVwiamF2YXNjcmlwdDp2b2lkKDApXCI+PHNwYW4gY2xhc3M9XCJidG4taWNvbi1pbmZvcm1hdGlvblwiPjwvc3Bhbj48L2E+PC9sYWJlbD4nKVxuICAgICAgICAgICAgICAgICAgICAubmV4dCgpO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICB0aGlzLmFkZENsYXNzKCdkZXNjcmlwdGlvbi10b2dnbGUtZW5hYmxlZCcpO1xuXG4gICAgICAgICAgICAvLyBUb2dnbGUgbmV4dCBkZXNjcmlwdGlvbiB3aGVuIGJ1dHRvbiBpcyBjbGlja2VkLlxuICAgICAgICAgICAgJHRyaWdnZXIub24oJ2NsaWNrJywgZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgJGRlc2NyaXB0aW9uW3Nob3duID8gJ2hpZGUnIDogJ3Nob3cnXSgpO1xuICAgICAgICAgICAgICAgIHNob3duID0gIXNob3duO1xuICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgIC8vIEhpZGUgbmV4dCBkZXNjcmlwdGlvbiBieSBkZWZhdWx0LlxuICAgICAgICAgICAgJGRlc2NyaXB0aW9uLmhpZGUoKTtcbiAgICAgICAgfVxuICAgIH0pO1xuXG59KTtcbiIsImltcG9ydCAkIGZyb20gJ2pRdWVyeSc7XG5cbiQuZW50d2luZSgnc3MnLCBmdW5jdGlvbigkKSB7XG5cdC8qKlxuXHQgKiBDb252ZXJ0cyBhbiBpbmxpbmUgZmllbGQgZGVzY3JpcHRpb24gaW50byBhIHRvb2x0aXBcblx0ICogd2hpY2ggaXMgc2hvd24gb24gaG92ZXIgb3ZlciBhbnkgcGFydCBvZiB0aGUgZmllbGQgY29udGFpbmVyLFxuXHQgKiBhcyB3ZWxsIGFzIHdoZW4gZm9jdXNpbmcgaW50byBhbiBpbnB1dCBlbGVtZW50IHdpdGhpbiB0aGUgZmllbGQgY29udGFpbmVyLlxuXHQgKlxuXHQgKiBOb3RlIHRoYXQgc29tZSBmaWVsZHMgZG9uJ3QgaGF2ZSBkaXN0aW5jdCBmb2N1c2FibGVcblx0ICogaW5wdXQgZmllbGRzIChlLmcuIEdyaWRGaWVsZCksIGFuZCBhcmVuJ3QgY29tcGF0aWJsZVxuXHQgKiB3aXRoIHNob3dpbmcgdG9vbHRpcHMuXG5cdCAqL1xuXHQkKFwiLmNtcyAuZmllbGQuY21zLWRlc2NyaXB0aW9uLXRvb2x0aXBcIikuZW50d2luZSh7XG5cdFx0b25tYXRjaDogZnVuY3Rpb24oKSB7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXG5cdFx0XHR2YXIgZGVzY3JpcHRpb25FbCA9IHRoaXMuZmluZCgnLmRlc2NyaXB0aW9uJyksIGlucHV0RWwsIHRvb2x0aXBFbDtcblx0XHRcdGlmKGRlc2NyaXB0aW9uRWwubGVuZ3RoKSB7XG5cdFx0XHRcdHRoaXNcblx0XHRcdFx0XHQvLyBUT0RPIFJlbW92ZSB0aXRsZSBzZXR0aW5nLCBzaG91bGRuJ3QgYmUgbmVjZXNzYXJ5XG5cdFx0XHRcdFx0LmF0dHIoJ3RpdGxlJywgZGVzY3JpcHRpb25FbC50ZXh0KCkpXG5cdFx0XHRcdFx0LnRvb2x0aXAoe2NvbnRlbnQ6IGRlc2NyaXB0aW9uRWwuaHRtbCgpfSk7XG5cdFx0XHRcdGRlc2NyaXB0aW9uRWwucmVtb3ZlKCk7XG5cdFx0XHR9XG5cdFx0fSxcblx0fSk7XG5cblx0JChcIi5jbXMgLmZpZWxkLmNtcy1kZXNjcmlwdGlvbi10b29sdGlwIDppbnB1dFwiKS5lbnR3aW5lKHtcblx0XHRvbmZvY3VzaW46IGZ1bmN0aW9uKGUpIHtcblx0XHRcdHRoaXMuY2xvc2VzdCgnLmZpZWxkJykudG9vbHRpcCgnb3BlbicpO1xuXHRcdH0sXG5cdFx0b25mb2N1c291dDogZnVuY3Rpb24oZSkge1xuXHRcdFx0dGhpcy5jbG9zZXN0KCcuZmllbGQnKS50b29sdGlwKCdjbG9zZScpO1xuXHRcdFx0fVxuXHR9KTtcblxufSk7XG4iLCIvKipcbiAqIEZpbGU6IExlZnRBbmRNYWluLkxheW91dC5qc1xuICovXG5cbmltcG9ydCAkIGZyb20gJ2pRdWVyeSc7XG5cbiQuZm4ubGF5b3V0LmRlZmF1bHRzLnJlc2l6ZSA9IGZhbHNlO1xuXG4vKipcbiAqIEFjY2Nlc3MgdGhlIGdsb2JhbCB2YXJpYWJsZSBpbiB0aGUgc2FtZSB3YXkgdGhlIHBsdWdpbiBkb2VzIGl0LlxuICovXG5qTGF5b3V0ID0gKHR5cGVvZiBqTGF5b3V0ID09PSAndW5kZWZpbmVkJykgPyB7fSA6IGpMYXlvdXQ7XG5cbi8qKlxuICogRmFjdG9yeSBmdW5jdGlvbiBmb3IgZ2VuZXJhdGluZyBuZXcgdHlwZSBvZiBhbGdvcml0aG0gZm9yIG91ciBDTVMuXG4gKlxuICogU3BlYyByZXF1aXJlcyBhIGRlZmluaXRpb24gb2YgdGhyZWUgY29sdW1uIGVsZW1lbnRzOlxuICogLSBgbWVudWAgb24gdGhlIGxlZnRcbiAqIC0gYGNvbnRlbnRgIGFyZWEgaW4gdGhlIG1pZGRsZSAoaW5jbHVkZXMgdGhlIEVkaXRGb3JtLCBzaWRlIHRvb2wgcGFuZWwsIGFjdGlvbnMsIGJyZWFkY3J1bWJzIGFuZCB0YWJzKVxuICogLSBgcHJldmlld2Agb24gdGhlIHJpZ2h0ICh3aWxsIGJlIHNob3duIGlmIHRoZXJlIGlzIGVub3VnaCBzcGFjZSlcbiAqXG4gKiBSZXF1aXJlZCBvcHRpb25zOlxuICogLSBgbWluQ29udGVudFdpZHRoYDogbWluaW11bSBzaXplIGZvciB0aGUgY29udGVudCBkaXNwbGF5IGFzIGxvbmcgYXMgdGhlIHByZXZpZXcgaXMgdmlzaWJsZVxuICogLSBgbWluUHJldmlld1dpZHRoYDogcHJldmlldyB3aWxsIG5vdCBiZSBkaXNwbGF5ZWQgYmVsb3cgdGhpcyBzaXplXG4gKiAtIGBtb2RlYDogb25lIG9mIFwic3BsaXRcIiwgXCJjb250ZW50XCIgb3IgXCJwcmV2aWV3XCJcbiAqXG4gKiBUaGUgYWxnb3JpdGhtIGZpcnN0IGNoZWNrcyB3aGljaCBjb2x1bW5zIGFyZSB0byBiZSB2aXNpYmxlIGFuZCB3aGljaCBoaWRkZW4uXG4gKlxuICogSW4gdGhlIGNhc2Ugd2hlcmUgYm90aCBwcmV2aWV3IGFuZCBjb250ZW50IHNob3VsZCBiZSBzaG93biBpdCBmaXJzdCB0cmllcyB0byBhc3NpZ24gaGFsZiBvZiBub24tbWVudSBzcGFjZSB0b1xuICogcHJldmlldyBhbmQgdGhlIG90aGVyIGhhbGYgdG8gY29udGVudC4gVGhlbiBpZiB0aGVyZSBpcyBub3QgZW5vdWdoIHNwYWNlIGZvciBlaXRoZXIgY29udGVudCBvciBwcmV2aWV3LCBpdCB0cmllc1xuICogdG8gYWxsb2NhdGUgdGhlIG1pbmltdW0gYWNjZXB0YWJsZSBzcGFjZSB0byB0aGF0IGNvbHVtbiwgYW5kIHRoZSByZXN0IHRvIHRoZSBvdGhlciBvbmUuIElmIHRoZSBtaW5pbXVtXG4gKiByZXF1aXJlbWVudHMgYXJlIHN0aWxsIG5vdCBtZXQsIGl0IGZhbGxzIGJhY2sgdG8gc2hvd2luZyBjb250ZW50IG9ubHkuXG4gKlxuICogQHBhcmFtIHNwZWMgQSBzdHJ1Y3R1cmUgZGVmaW5pbmcgY29sdW1ucyBhbmQgcGFyYW1ldGVycyBhcyBwZXIgYWJvdmUuXG4gKi9cbmpMYXlvdXQudGhyZWVDb2x1bW5Db21wcmVzc29yID0gZnVuY3Rpb24gKHNwZWMsIG9wdGlvbnMpIHtcblx0Ly8gU3BlYyBzYW5pdHkgY2hlY2tzLlxuXHRpZiAodHlwZW9mIHNwZWMubWVudT09PSd1bmRlZmluZWQnIHx8XG5cdFx0dHlwZW9mIHNwZWMuY29udGVudD09PSd1bmRlZmluZWQnIHx8XG5cdFx0dHlwZW9mIHNwZWMucHJldmlldz09PSd1bmRlZmluZWQnKSB7XG5cdFx0dGhyb3cgJ1NwZWMgaXMgaW52YWxpZC4gUGxlYXNlIHByb3ZpZGUgXCJtZW51XCIsIFwiY29udGVudFwiIGFuZCBcInByZXZpZXdcIiBlbGVtZW50cy4nO1xuXHR9XG5cdGlmICh0eXBlb2Ygb3B0aW9ucy5taW5Db250ZW50V2lkdGg9PT0ndW5kZWZpbmVkJyB8fFxuXHRcdHR5cGVvZiBvcHRpb25zLm1pblByZXZpZXdXaWR0aD09PSd1bmRlZmluZWQnIHx8XG5cdFx0dHlwZW9mIG9wdGlvbnMubW9kZT09PSd1bmRlZmluZWQnKSB7XG5cdFx0dGhyb3cgJ1NwZWMgaXMgaW52YWxpZC4gUGxlYXNlIHByb3ZpZGUgXCJtaW5Db250ZW50V2lkdGhcIiwgXCJtaW5QcmV2aWV3V2lkdGhcIiwgXCJtb2RlXCInO1xuXHR9XG5cdGlmIChvcHRpb25zLm1vZGUhPT0nc3BsaXQnICYmIG9wdGlvbnMubW9kZSE9PSdjb250ZW50JyAmJiBvcHRpb25zLm1vZGUhPT0ncHJldmlldycpIHtcblx0XHR0aHJvdyAnU3BlYyBpcyBpbnZhbGlkLiBcIm1vZGVcIiBzaG91bGQgYmUgZWl0aGVyIFwic3BsaXRcIiwgXCJjb250ZW50XCIgb3IgXCJwcmV2aWV3XCInO1xuXHR9XG5cblx0Ly8gSW5zdGFuY2Ugb2YgdGhlIGFsZ29yaXRobSBiZWluZyBwcm9kdWNlZC5cblx0dmFyIG9iaiA9IHtcblx0XHRvcHRpb25zOiBvcHRpb25zXG5cdH07XG5cblx0Ly8gSW50ZXJuYWwgY29sdW1uIGhhbmRsZXMsIGFsc28gaW1wbGVtZW50aW5nIGxheW91dC5cblx0dmFyIG1lbnUgPSAkLmpMYXlvdXRXcmFwKHNwZWMubWVudSksXG5cdFx0Y29udGVudCA9ICQuakxheW91dFdyYXAoc3BlYy5jb250ZW50KSxcblx0XHRwcmV2aWV3ID0gJC5qTGF5b3V0V3JhcChzcGVjLnByZXZpZXcpO1xuXG5cdC8qKlxuXHQgKiBSZXF1aXJlZCBpbnRlcmZhY2UgaW1wbGVtZW50YXRpb25zIGZvbGxvdy5cblx0ICogUmVmZXIgdG8gaHR0cHM6Ly9naXRodWIuY29tL2JyYW1zdGVpbi9qbGF5b3V0I2xheW91dC1hbGdvcml0aG1zIGZvciB0aGUgaW50ZXJmYWNlIHNwZWMuXG5cdCAqL1xuXHRvYmoubGF5b3V0ID0gZnVuY3Rpb24gKGNvbnRhaW5lcikge1xuXHRcdHZhciBzaXplID0gY29udGFpbmVyLmJvdW5kcygpLFxuXHRcdFx0aW5zZXRzID0gY29udGFpbmVyLmluc2V0cygpLFxuXHRcdFx0dG9wID0gaW5zZXRzLnRvcCxcblx0XHRcdGJvdHRvbSA9IHNpemUuaGVpZ2h0IC0gaW5zZXRzLmJvdHRvbSxcblx0XHRcdGxlZnQgPSBpbnNldHMubGVmdCxcblx0XHRcdHJpZ2h0ID0gc2l6ZS53aWR0aCAtIGluc2V0cy5yaWdodDtcblxuXHRcdHZhciBtZW51V2lkdGggPSBzcGVjLm1lbnUud2lkdGgoKSwgXG5cdFx0XHRjb250ZW50V2lkdGggPSAwLFxuXHRcdFx0cHJldmlld1dpZHRoID0gMDtcblxuXHRcdGlmICh0aGlzLm9wdGlvbnMubW9kZT09PSdwcmV2aWV3Jykge1xuXHRcdFx0Ly8gQWxsIG5vbi1tZW51IHNwYWNlIGFsbG9jYXRlZCB0byBwcmV2aWV3LlxuXHRcdFx0Y29udGVudFdpZHRoID0gMDtcblx0XHRcdHByZXZpZXdXaWR0aCA9IHJpZ2h0IC0gbGVmdCAtIG1lbnVXaWR0aDtcblx0XHR9IGVsc2UgaWYgKHRoaXMub3B0aW9ucy5tb2RlPT09J2NvbnRlbnQnKSB7XG5cdFx0XHQvLyBBbGwgbm9uLW1lbnUgc3BhY2UgYWxsb2NhdGVkIHRvIGNvbnRlbnQuXG5cdFx0XHRjb250ZW50V2lkdGggPSByaWdodCAtIGxlZnQgLSBtZW51V2lkdGg7XG5cdFx0XHRwcmV2aWV3V2lkdGggPSAwO1xuXHRcdH0gZWxzZSB7IC8vID09PSdzcGxpdCdcblx0XHRcdC8vIFNwbGl0IHZpZXcgLSBmaXJzdCB0cnkgNTAtNTAgZGlzdHJpYnV0aW9uLlxuXHRcdFx0Y29udGVudFdpZHRoID0gKHJpZ2h0IC0gbGVmdCAtIG1lbnVXaWR0aCkgLyAyO1xuXHRcdFx0cHJldmlld1dpZHRoID0gcmlnaHQgLSBsZWZ0IC0gKG1lbnVXaWR0aCArIGNvbnRlbnRXaWR0aCk7XG5cblx0XHRcdC8vIElmIHZpb2xhdGluZyBvbmUgb2YgdGhlIG1pbmltYSwgdHJ5IHRvIHJlYWRqdXN0IHRvd2FyZHMgc2F0aXNmeWluZyBpdC5cblx0XHRcdGlmIChjb250ZW50V2lkdGggPCB0aGlzLm9wdGlvbnMubWluQ29udGVudFdpZHRoKSB7XG5cdFx0XHRcdGNvbnRlbnRXaWR0aCA9IHRoaXMub3B0aW9ucy5taW5Db250ZW50V2lkdGg7XG5cdFx0XHRcdHByZXZpZXdXaWR0aCA9IHJpZ2h0IC0gbGVmdCAtIChtZW51V2lkdGggKyBjb250ZW50V2lkdGgpO1xuXHRcdFx0fSBlbHNlIGlmIChwcmV2aWV3V2lkdGggPCB0aGlzLm9wdGlvbnMubWluUHJldmlld1dpZHRoKSB7XG5cdFx0XHRcdHByZXZpZXdXaWR0aCA9IHRoaXMub3B0aW9ucy5taW5QcmV2aWV3V2lkdGg7XG5cdFx0XHRcdGNvbnRlbnRXaWR0aCA9IHJpZ2h0IC0gbGVmdCAtIChtZW51V2lkdGggKyBwcmV2aWV3V2lkdGgpO1xuXHRcdFx0fVxuXG5cdFx0XHQvLyBJZiBzdGlsbCB2aW9sYXRpbmcgb25lIG9mIHRoZSAob3RoZXIpIG1pbmltYSwgcmVtb3ZlIHRoZSBwcmV2aWV3IGFuZCBhbGxvY2F0ZSBldmVyeXRoaW5nIHRvIGNvbnRlbnQuXG5cdFx0XHRpZiAoY29udGVudFdpZHRoIDwgdGhpcy5vcHRpb25zLm1pbkNvbnRlbnRXaWR0aCB8fCBwcmV2aWV3V2lkdGggPCB0aGlzLm9wdGlvbnMubWluUHJldmlld1dpZHRoKSB7XG5cdFx0XHRcdGNvbnRlbnRXaWR0aCA9IHJpZ2h0IC0gbGVmdCAtIG1lbnVXaWR0aDtcblx0XHRcdFx0cHJldmlld1dpZHRoID0gMDtcblx0XHRcdH1cblx0XHR9XG5cblx0XHQvLyBDYWxjdWxhdGUgd2hhdCBjb2x1bW5zIGFyZSBhbHJlYWR5IGhpZGRlbiBwcmUtbGF5b3V0XG5cdFx0dmFyIHByZWhpZGRlbiA9IHtcblx0XHRcdGNvbnRlbnQ6IHNwZWMuY29udGVudC5oYXNDbGFzcygnY29sdW1uLWhpZGRlbicpLFxuXHRcdFx0cHJldmlldzogc3BlYy5wcmV2aWV3Lmhhc0NsYXNzKCdjb2x1bW4taGlkZGVuJylcblx0XHR9O1xuXG5cdFx0Ly8gQ2FsY3VsYXRlIHdoYXQgY29sdW1ucyB3aWxsIGJlIGhpZGRlbiAoemVybyB3aWR0aCkgcG9zdC1sYXlvdXRcblx0XHR2YXIgcG9zdGhpZGRlbiA9IHtcblx0XHRcdGNvbnRlbnQ6IGNvbnRlbnRXaWR0aCA9PT0gMCxcblx0XHRcdHByZXZpZXc6IHByZXZpZXdXaWR0aCA9PT0gMFxuXHRcdH07XG5cblx0XHQvLyBBcHBseSBjbGFzc2VzIGZvciBlbGVtZW50cyB0aGF0IG1pZ2h0IG5vdCBiZSB2aXNpYmxlIGF0IGFsbC5cblx0XHRzcGVjLmNvbnRlbnQudG9nZ2xlQ2xhc3MoJ2NvbHVtbi1oaWRkZW4nLCBwb3N0aGlkZGVuLmNvbnRlbnQpO1xuXHRcdHNwZWMucHJldmlldy50b2dnbGVDbGFzcygnY29sdW1uLWhpZGRlbicsIHBvc3RoaWRkZW4ucHJldmlldyk7XG5cblx0XHQvLyBBcHBseSB0aGUgd2lkdGhzIHRvIGNvbHVtbnMsIGFuZCBjYWxsIHN1Ym9yZGluYXRlIGxheW91dHMgdG8gYXJyYW5nZSB0aGUgY2hpbGRyZW4uXG5cdFx0bWVudS5ib3VuZHMoeyd4JzogbGVmdCwgJ3knOiB0b3AsICdoZWlnaHQnOiBib3R0b20gLSB0b3AsICd3aWR0aCc6IG1lbnVXaWR0aH0pO1xuXHRcdG1lbnUuZG9MYXlvdXQoKTtcblxuXHRcdGxlZnQgKz0gbWVudVdpZHRoO1xuXG5cdFx0Y29udGVudC5ib3VuZHMoeyd4JzogbGVmdCwgJ3knOiB0b3AsICdoZWlnaHQnOiBib3R0b20gLSB0b3AsICd3aWR0aCc6IGNvbnRlbnRXaWR0aH0pO1xuXHRcdGlmICghcG9zdGhpZGRlbi5jb250ZW50KSBjb250ZW50LmRvTGF5b3V0KCk7XG5cblx0XHRsZWZ0ICs9IGNvbnRlbnRXaWR0aDtcblxuXHRcdHByZXZpZXcuYm91bmRzKHsneCc6IGxlZnQsICd5JzogdG9wLCAnaGVpZ2h0JzogYm90dG9tIC0gdG9wLCAnd2lkdGgnOiBwcmV2aWV3V2lkdGh9KTtcblx0XHRpZiAoIXBvc3RoaWRkZW4ucHJldmlldykgcHJldmlldy5kb0xheW91dCgpO1xuXG5cdFx0aWYgKHBvc3RoaWRkZW4uY29udGVudCAhPT0gcHJlaGlkZGVuLmNvbnRlbnQpIHNwZWMuY29udGVudC50cmlnZ2VyKCdjb2x1bW52aXNpYmlsaXR5Y2hhbmdlZCcpO1xuXHRcdGlmIChwb3N0aGlkZGVuLnByZXZpZXcgIT09IHByZWhpZGRlbi5wcmV2aWV3KSBzcGVjLnByZXZpZXcudHJpZ2dlcignY29sdW1udmlzaWJpbGl0eWNoYW5nZWQnKTtcblxuXHRcdC8vIENhbGN1bGF0ZSB3aGV0aGVyIHByZXZpZXcgaXMgcG9zc2libGUgaW4gc3BsaXQgbW9kZVxuXHRcdGlmIChjb250ZW50V2lkdGggKyBwcmV2aWV3V2lkdGggPCBvcHRpb25zLm1pbkNvbnRlbnRXaWR0aCArIG9wdGlvbnMubWluUHJldmlld1dpZHRoKSB7XG5cdFx0XHRzcGVjLnByZXZpZXcudHJpZ2dlcignZGlzYWJsZScpO1xuXHRcdH0gZWxzZSB7XG5cdFx0XHRzcGVjLnByZXZpZXcudHJpZ2dlcignZW5hYmxlJyk7XG5cdFx0fVxuXG5cdFx0cmV0dXJuIGNvbnRhaW5lcjtcblx0fTtcblxuXHQvKipcblx0ICogSGVscGVyIHRvIGdlbmVyYXRlIHRoZSByZXF1aXJlZCBgcHJlZmVycmVkYCwgYG1pbmltdW1gIGFuZCBgbWF4aW11bWAgaW50ZXJmYWNlIGZ1bmN0aW9ucy5cblx0ICovXG5cdGZ1bmN0aW9uIHR5cGVMYXlvdXQodHlwZSkge1xuXHRcdHZhciBmdW5jID0gdHlwZSArICdTaXplJztcblxuXHRcdHJldHVybiBmdW5jdGlvbiAoY29udGFpbmVyKSB7XG5cdFx0XHR2YXIgbWVudVNpemUgPSBtZW51W2Z1bmNdKCksXG5cdFx0XHRcdGNvbnRlbnRTaXplID0gY29udGVudFtmdW5jXSgpLFxuXHRcdFx0XHRwcmV2aWV3U2l6ZSA9IHByZXZpZXdbZnVuY10oKSxcblx0XHRcdFx0aW5zZXRzID0gY29udGFpbmVyLmluc2V0cygpO1xuXG5cdFx0XHR3aWR0aCA9IG1lbnVTaXplLndpZHRoICsgY29udGVudFNpemUud2lkdGggKyBwcmV2aWV3U2l6ZS53aWR0aDtcblx0XHRcdGhlaWdodCA9IE1hdGgubWF4KG1lbnVTaXplLmhlaWdodCwgY29udGVudFNpemUuaGVpZ2h0LCBwcmV2aWV3U2l6ZS5oZWlnaHQpO1xuXG5cdFx0XHRyZXR1cm4ge1xuXHRcdFx0XHQnd2lkdGgnOiBpbnNldHMubGVmdCArIGluc2V0cy5yaWdodCArIHdpZHRoLFxuXHRcdFx0XHQnaGVpZ2h0JzogaW5zZXRzLnRvcCArIGluc2V0cy5ib3R0b20gKyBoZWlnaHRcblx0XHRcdH07XG5cdFx0fTtcblx0fVxuXG5cdC8vIEdlbmVyYXRlIGludGVyZmFjZSBmdW5jdGlvbnMuXG5cdG9iai5wcmVmZXJyZWQgPSB0eXBlTGF5b3V0KCdwcmVmZXJyZWQnKTtcblx0b2JqLm1pbmltdW0gPSB0eXBlTGF5b3V0KCdtaW5pbXVtJyk7XG5cdG9iai5tYXhpbXVtID0gdHlwZUxheW91dCgnbWF4aW11bScpO1xuXG5cdHJldHVybiBvYmo7XG59O1xuIiwiaW1wb3J0ICQgZnJvbSAnalF1ZXJ5JztcblxuJC5lbnR3aW5lKCdzcycsIGZ1bmN0aW9uKCQpe1xuXHRcdFxuXHQvKipcblx0ICogVmVydGljYWwgQ01TIG1lbnUgd2l0aCB0d28gbGV2ZWxzLCBidWlsdCBmcm9tIGEgbmVzdGVkIHVub3JkZXJlZCBsaXN0LiBcblx0ICogVGhlIChvcHRpb25hbCkgc2Vjb25kIGxldmVsIGlzIGNvbGxhcHNpYmxlLCBoaWRpbmcgaXRzIGNoaWxkcmVuLlxuXHQgKiBUaGUgd2hvbGUgbWVudSAoaW5jbHVkaW5nIHNlY29uZCBsZXZlbHMpIGlzIGNvbGxhcHNpYmxlIGFzIHdlbGwsXG5cdCAqIGV4cG9zaW5nIG9ubHkgYSBwcmV2aWV3IGZvciBldmVyeSBtZW51IGl0ZW0gaW4gb3JkZXIgdG8gc2F2ZSBzcGFjZS5cblx0ICogSW4gdGhpcyBcInByZXZpZXcvY29sbGFwc2VkXCIgbW9kZSwgdGhlIHNlY29uZGFyeSBtZW51IGhvdmVycyBvdmVyIHRoZSBtZW51IGl0ZW0sXG5cdCAqIHJhdGhlciB0aGFuIGV4cGFuZGluZyBpdC5cblx0ICogXG5cdCAqIEV4YW1wbGU6XG5cdCAqIFxuXHQgKiA8dWwgY2xhc3M9XCJjbXMtbWVudS1saXN0XCI+XG5cdCAqICA8bGk+PGEgaHJlZj1cIiNcIj5JdGVtIDE8L2E+PC9saT5cblx0ICogIDxsaSBjbGFzcz1cImN1cnJlbnQgb3BlbmVkXCI+XG5cdCAqICAgIDxhIGhyZWY9XCIjXCI+SXRlbSAyPC9hPlxuXHQgKiAgICA8dWw+XG5cdCAqICAgICAgPGxpIGNsYXNzPVwiY3VycmVudCBvcGVuZWRcIj48YSBocmVmPVwiI1wiPkl0ZW0gMi4xPC9hPjwvbGk+XG5cdCAqICAgICAgPGxpPjxhIGhyZWY9XCIjXCI+SXRlbSAyLjI8L2E+PC9saT5cblx0ICogICAgPC91bD5cblx0ICogIDwvbGk+XG5cdCAqIDwvdWw+XG5cdCAqIFxuXHQgKiBDdXN0b20gRXZlbnRzOlxuXHQgKiAtICdzZWxlY3QnOiBGaXJlcyB3aGVuIGEgbWVudSBpdGVtIGlzIHNlbGVjdGVkIChvbiBhbnkgbGV2ZWwpLlxuXHQgKi9cblx0JCgnLmNtcy1wYW5lbC5jbXMtbWVudScpLmVudHdpbmUoe1xuXHRcdHRvZ2dsZVBhbmVsOiBmdW5jdGlvbihkb0V4cGFuZCwgc2lsZW50LCBkb1NhdmVTdGF0ZSkge1xuXHRcdFx0Ly9hcHBseSBvciB1bmFwcGx5IHRoZSBmbHlvdXQgZm9ybWF0dGluZywgc2hvdWxkIG9ubHkgYXBwbHkgdG8gY21zLW1lbnUtbGlzdCB3aGVuIHRoZSBjdXJyZW50IGNvbGxhcHNlZCBwYW5hbCBpcyB0aGUgY21zIG1lbnUuXG5cdFx0XHQkKCcuY21zLW1lbnUtbGlzdCcpLmNoaWxkcmVuKCdsaScpLmVhY2goZnVuY3Rpb24oKXtcblx0XHRcdFx0aWYgKGRvRXhwYW5kKSB7IC8vZXhwYW5kXG5cdFx0XHRcdFx0JCh0aGlzKS5jaGlsZHJlbigndWwnKS5lYWNoKGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdFx0JCh0aGlzKS5yZW1vdmVDbGFzcygnY29sbGFwc2VkLWZseW91dCcpO1xuXHRcdFx0XHRcdFx0aWYgKCQodGhpcykuZGF0YSgnY29sbGFwc2UnKSkge1xuXHRcdFx0XHRcdFx0XHQkKHRoaXMpLnJlbW92ZURhdGEoJ2NvbGxhcHNlJyk7XG5cdFx0XHRcdFx0XHRcdCQodGhpcykuYWRkQ2xhc3MoJ2NvbGxhcHNlJyk7XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0fSk7XG5cdFx0XHRcdH0gZWxzZSB7ICAgIC8vY29sbGFwc2Vcblx0XHRcdFx0XHQkKHRoaXMpLmNoaWxkcmVuKCd1bCcpLmVhY2goZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0XHQkKHRoaXMpLmFkZENsYXNzKCdjb2xsYXBzZWQtZmx5b3V0Jyk7XG5cdFx0XHRcdFx0XHQkKHRoaXMpLmhhc0NsYXNzKCdjb2xsYXBzZScpO1xuXHRcdFx0XHRcdFx0JCh0aGlzKS5yZW1vdmVDbGFzcygnY29sbGFwc2UnKTtcblx0XHRcdFx0XHRcdCQodGhpcykuZGF0YSgnY29sbGFwc2UnLCB0cnVlKTtcblx0XHRcdFx0XHR9KTtcblx0XHRcdFx0fVxuXHRcdFx0fSk7XG5cblx0XHRcdHRoaXMudG9nZ2xlRmx5b3V0U3RhdGUoZG9FeHBhbmQpO1xuXG5cdFx0XHR0aGlzLl9zdXBlcihkb0V4cGFuZCwgc2lsZW50LCBkb1NhdmVTdGF0ZSk7XG5cdFx0fSxcblx0XHR0b2dnbGVGbHlvdXRTdGF0ZTogZnVuY3Rpb24oYm9vbCkge1xuXHRcdFx0aWYgKGJvb2wpIHsgLy9leHBhbmRcblx0XHRcdFx0Ly9zaG93IHRoZSBmbHlvdXRcblx0XHRcdFx0JCgnLmNvbGxhcHNlZCcpLmZpbmQoJ2xpJykuc2hvdygpO1xuXG5cdFx0XHRcdC8vaGlkZSBhbGwgdGhlIGZseW91dC1pbmRpY2F0b3Jcblx0XHRcdFx0JCgnLmNtcy1tZW51LWxpc3QnKS5maW5kKCcuY2hpbGQtZmx5b3V0LWluZGljYXRvcicpLmhpZGUoKTtcblx0XHRcdH0gZWxzZSB7ICAgIC8vY29sbGFwc2Vcblx0XHRcdFx0Ly9oaWRlIHRoZSBmbHlvdXQgb25seSBpZiBpdCBpcyBub3QgdGhlIGN1cnJlbnQgc2VjdGlvblxuXHRcdFx0XHQkKCcuY29sbGFwc2VkLWZseW91dCcpLmZpbmQoJ2xpJykuZWFjaChmdW5jdGlvbigpIHtcblx0XHRcdFx0XHQvL2lmICghJCh0aGlzKS5oYXNDbGFzcygnY3VycmVudCcpKVxuXHRcdFx0XHRcdCQodGhpcykuaGlkZSgpO1xuXHRcdFx0XHR9KTtcblxuXHRcdFx0XHQvL3Nob3cgYWxsIHRoZSBmbHlvdXQtaW5kaWNhdG9yc1xuXHRcdFx0XHR2YXIgcGFyID0gJCgnLmNtcy1tZW51LWxpc3QgdWwuY29sbGFwc2VkLWZseW91dCcpLnBhcmVudCgpO1xuXHRcdFx0XHRpZiAocGFyLmNoaWxkcmVuKCcuY2hpbGQtZmx5b3V0LWluZGljYXRvcicpLmxlbmd0aCA9PT0gMCkgcGFyLmFwcGVuZCgnPHNwYW4gY2xhc3M9XCJjaGlsZC1mbHlvdXQtaW5kaWNhdG9yXCI+PC9zcGFuPicpLmZhZGVJbigpO1xuXHRcdFx0XHRwYXIuY2hpbGRyZW4oJy5jaGlsZC1mbHlvdXQtaW5kaWNhdG9yJykuZmFkZUluKCk7XG5cdFx0XHR9XG5cdFx0fSxcblx0XHRzaXRlVHJlZVByZXNlbnQ6IGZ1bmN0aW9uICgpIHtcblx0XHRcdHJldHVybiAkKCcjY21zLWNvbnRlbnQtdG9vbHMtQ01TTWFpbicpLmxlbmd0aCA+IDA7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEBmdW5jIGdldFBlcnNpc3RlZFN0aWNreVN0YXRlXG5cdFx0ICogQHJldHVybiB7Ym9vbGVhbnx1bmRlZmluZWR9IC0gUmV0dXJucyB0cnVlIGlmIHRoZSBtZW51IGlzIHN0aWNreSwgZmFsc2UgaWYgdW5zdGlja3kuIFJldHVybnMgdW5kZWZpbmVkIGlmIHRoZXJlIGlzIG5vIGNvb2tpZSBzZXQuXG5cdFx0ICogQGRlc2MgR2V0IHRoZSBzdGlja3kgc3RhdGUgb2YgdGhlIG1lbnUgYWNjb3JkaW5nIHRvIHRoZSBjb29raWUuXG5cdFx0ICovXG5cdFx0Z2V0UGVyc2lzdGVkU3RpY2t5U3RhdGU6IGZ1bmN0aW9uICgpIHtcblx0XHRcdHZhciBwZXJzaXN0ZWRTdGF0ZSwgY29va2llVmFsdWU7XG5cblx0XHRcdGlmICgkLmNvb2tpZSAhPT0gdm9pZCAwKSB7XG5cdFx0XHRcdGNvb2tpZVZhbHVlID0gJC5jb29raWUoJ2Ntcy1tZW51LXN0aWNreScpO1xuXG5cdFx0XHRcdGlmIChjb29raWVWYWx1ZSAhPT0gdm9pZCAwICYmIGNvb2tpZVZhbHVlICE9PSBudWxsKSB7XG5cdFx0XHRcdFx0cGVyc2lzdGVkU3RhdGUgPSBjb29raWVWYWx1ZSA9PT0gJ3RydWUnO1xuXHRcdFx0XHR9XG5cdFx0XHR9XG5cblx0XHRcdHJldHVybiBwZXJzaXN0ZWRTdGF0ZTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogQGZ1bmMgc2V0UGVyc2lzdGVkU3RpY2t5U3RhdGVcblx0XHQgKiBAcGFyYW0ge2Jvb2xlYW59IGlzU3RpY2t5IC0gUGFzcyB0cnVlIGlmIHlvdSB3YW50IHRoZSBwYW5lbCB0byBiZSBzdGlja3ksIGZhbHNlIGZvciB1bnN0aWNreS5cblx0XHQgKiBAZGVzYyBTZXQgdGhlIGNvbGxhcHNlZCB2YWx1ZSBvZiB0aGUgcGFuZWwsIHN0b3JlZCBpbiBjb29raWVzLlxuXHRcdCAqL1xuXHRcdHNldFBlcnNpc3RlZFN0aWNreVN0YXRlOiBmdW5jdGlvbiAoaXNTdGlja3kpIHtcblx0XHRcdGlmICgkLmNvb2tpZSAhPT0gdm9pZCAwKSB7XG5cdFx0XHRcdCQuY29va2llKCdjbXMtbWVudS1zdGlja3knLCBpc1N0aWNreSwgeyBwYXRoOiAnLycsIGV4cGlyZXM6IDMxIH0pO1xuXHRcdFx0fVxuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBAZnVuYyBnZXRFdmFsdWF0ZWRDb2xsYXBzZWRTdGF0ZVxuXHRcdCAqIEByZXR1cm4ge2Jvb2xlYW59IC0gUmV0dXJucyB0cnVlIGlmIHRoZSBtZW51IHNob3VsZCBiZSBjb2xsYXBzZWQsIGZhbHNlIGlmIGV4cGFuZGVkLlxuXHRcdCAqIEBkZXNjIEV2YWx1YXRlIHdoZXRoZXIgdGhlIG1lbnUgc2hvdWxkIGJlIGNvbGxhcHNlZC5cblx0XHQgKiAgICAgICBUaGUgYmFzaWMgcnVsZSBpcyBcIklmIHRoZSBTaXRlVHJlZSAobWlkZGxlIGNvbHVtbikgaXMgcHJlc2VudCwgY29sbGFwc2UgdGhlIG1lbnUsIG90aGVyd2lzZSBleHBhbmQgdGhlIG1lbnVcIi5cblx0XHQgKiAgICAgICBUaGlzIHJlYXNvbiBiZWhpbmQgdGhpcyBpcyB0byBnaXZlIHRoZSBjb250ZW50IGFyZWEgbW9yZSByZWFsIGVzdGF0ZSB3aGVuIHRoZSBTaXRlVHJlZSBpcyBwcmVzZW50LlxuXHRcdCAqICAgICAgIFRoZSB1c2VyIG1heSB3aXNoIHRvIG92ZXJyaWRlIHRoaXMgYXV0b21hdGljIGJlaGF2aW91ciBhbmQgaGF2ZSB0aGUgbWVudSBleHBhbmRlZCBvciBjb2xsYXBzZWQgYXQgYWxsIHRpbWVzLlxuXHRcdCAqICAgICAgIFNvIHVubGlrZSBtYW51YWxseSB0b2dnbGluZyB0aGUgbWVudSwgdGhlIGF1dG9tYXRpYyBiZWhhdmlvdXIgbmV2ZXIgdXBkYXRlcyB0aGUgbWVudSdzIGNvb2tpZSB2YWx1ZS5cblx0XHQgKiAgICAgICBIZXJlIHdlIHVzZSB0aGUgbWFudWFsbHkgc2V0IHN0YXRlIGFuZCB0aGUgYXV0b21hdGljIGJlaGF2aW91ciB0byBldmFsdWF0ZSB3aGF0IHRoZSBjb2xsYXBzZWQgc3RhdGUgc2hvdWxkIGJlLlxuXHRcdCAqL1xuXHRcdGdldEV2YWx1YXRlZENvbGxhcHNlZFN0YXRlOiBmdW5jdGlvbiAoKSB7XG5cdFx0XHR2YXIgc2hvdWxkQ29sbGFwc2UsXG5cdFx0XHRcdG1hbnVhbFN0YXRlID0gdGhpcy5nZXRQZXJzaXN0ZWRDb2xsYXBzZWRTdGF0ZSgpLFxuXHRcdFx0XHRtZW51SXNTdGlja3kgPSAkKCcuY21zLW1lbnUnKS5nZXRQZXJzaXN0ZWRTdGlja3lTdGF0ZSgpLFxuXHRcdFx0XHRhdXRvbWF0aWNTdGF0ZSA9IHRoaXMuc2l0ZVRyZWVQcmVzZW50KCk7XG5cblx0XHRcdGlmIChtYW51YWxTdGF0ZSA9PT0gdm9pZCAwKSB7XG5cdFx0XHRcdC8vIFRoZXJlIGlzIG5vIG1hbnVhbCBzdGF0ZSwgdXNlIGF1dG9tYXRpYyBzdGF0ZS5cblx0XHRcdFx0c2hvdWxkQ29sbGFwc2UgPSBhdXRvbWF0aWNTdGF0ZTtcblx0XHRcdH0gZWxzZSBpZiAobWFudWFsU3RhdGUgIT09IGF1dG9tYXRpY1N0YXRlICYmIG1lbnVJc1N0aWNreSkge1xuXHRcdFx0XHQvLyBUaGUgbWFudWFsIGFuZCBhdXRvbWF0aWMgc3RhdGVhIGNvbmZsaWN0LCB1c2UgbWFudWFsIHN0YXRlLlxuXHRcdFx0XHRzaG91bGRDb2xsYXBzZSA9IG1hbnVhbFN0YXRlO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0Ly8gVXNlIGF1dG9tYXRpYyBzdGF0ZS5cblx0XHRcdFx0c2hvdWxkQ29sbGFwc2UgPSBhdXRvbWF0aWNTdGF0ZTtcblx0XHRcdH1cblxuXHRcdFx0cmV0dXJuIHNob3VsZENvbGxhcHNlO1xuXHRcdH0sXG5cblx0XHRvbmFkZDogZnVuY3Rpb24gKCkge1xuXHRcdFx0dmFyIHNlbGYgPSB0aGlzO1xuXG5cdFx0XHRzZXRUaW1lb3V0KGZ1bmN0aW9uICgpIHtcblx0XHRcdFx0Ly8gVXNlIGEgdGltZW91dCBzbyB0aGlzIGhhcHBlbnMgYWZ0ZXIgdGhlIHJlZHJhdy5cblx0XHRcdFx0Ly8gVHJpZ2dlcmluZyBhIHRvZ2dsZSBiZWZvcmUgcmVkcmF3IHdpbGwgcmVzdWx0IGluIGFuIGluY29ycmVjdFxuXHRcdFx0XHQvLyBtZW51ICdleHBhbmRlZCB3aWR0aCcgYmVpbmcgY2FsY3VsYXRlZCB3aGVuIHRoZW4gbWVudVxuXHRcdFx0XHQvLyBpcyBhZGRlZCBpbiBhIGNvbGxhcHNlZCBzdGF0ZS5cblx0XHRcdFx0c2VsZi50b2dnbGVQYW5lbCghc2VsZi5nZXRFdmFsdWF0ZWRDb2xsYXBzZWRTdGF0ZSgpLCBmYWxzZSwgZmFsc2UpO1xuXHRcdFx0fSwgMCk7XG5cblx0XHRcdC8vIFNldHVwIGF1dG9tYXRpYyBleHBhbmQgLyBjb2xsYXBzZSBiZWhhdmlvdXIuXG5cdFx0XHQkKHdpbmRvdykub24oJ2FqYXhDb21wbGV0ZScsIGZ1bmN0aW9uIChlKSB7XG5cdFx0XHRcdHNldFRpbWVvdXQoZnVuY3Rpb24gKCkgeyAvLyBVc2UgYSB0aW1lb3V0IHNvIHRoaXMgaGFwcGVucyBhZnRlciB0aGUgcmVkcmF3XG5cdFx0XHRcdFx0c2VsZi50b2dnbGVQYW5lbCghc2VsZi5nZXRFdmFsdWF0ZWRDb2xsYXBzZWRTdGF0ZSgpLCBmYWxzZSwgZmFsc2UpO1xuXHRcdFx0XHR9LCAwKTtcblx0XHRcdH0pO1xuXG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdH1cblx0fSk7XG5cblx0JCgnLmNtcy1tZW51LWxpc3QnKS5lbnR3aW5lKHtcblx0XHRvbm1hdGNoOiBmdW5jdGlvbigpIHtcblx0XHRcdHZhciBzZWxmID0gdGhpcztcblxuXHRcdFx0Ly8gU2VsZWN0IGRlZmF1bHQgZWxlbWVudCAod2hpY2ggbWlnaHQgcmV2ZWFsIGNoaWxkcmVuIGluIGhpZGRlbiBwYXJlbnRzKVxuXHRcdFx0dGhpcy5maW5kKCdsaS5jdXJyZW50Jykuc2VsZWN0KCk7XG5cblx0XHRcdHRoaXMudXBkYXRlSXRlbXMoKTtcblxuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9LFxuXHRcdG9udW5tYXRjaDogZnVuY3Rpb24oKSB7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdH0sXG5cblx0XHR1cGRhdGVNZW51RnJvbVJlc3BvbnNlOiBmdW5jdGlvbih4aHIpIHtcblx0XHRcdHZhciBjb250cm9sbGVyID0geGhyLmdldFJlc3BvbnNlSGVhZGVyKCdYLUNvbnRyb2xsZXInKTtcblx0XHRcdGlmKGNvbnRyb2xsZXIpIHtcblx0XHRcdFx0dmFyIGl0ZW0gPSB0aGlzLmZpbmQoJ2xpI01lbnUtJyArIGNvbnRyb2xsZXIucmVwbGFjZSgvXFxcXC9nLCAnLScpLnJlcGxhY2UoL1teYS16QS1aMC05XFwtXzouXSsvLCAnJykpO1xuXHRcdFx0XHRpZighaXRlbS5oYXNDbGFzcygnY3VycmVudCcpKSBpdGVtLnNlbGVjdCgpO1xuXHRcdFx0fVxuXHRcdFx0dGhpcy51cGRhdGVJdGVtcygpO1xuXHRcdH0sXG5cblx0XHQnZnJvbSAuY21zLWNvbnRhaW5lcic6IHtcblx0XHRcdG9uYWZ0ZXJzdGF0ZWNoYW5nZTogZnVuY3Rpb24oZSwgZGF0YSl7XG5cdFx0XHRcdHRoaXMudXBkYXRlTWVudUZyb21SZXNwb25zZShkYXRhLnhocik7XG5cdFx0XHR9LFxuXHRcdFx0b25hZnRlcnN1Ym1pdGZvcm06IGZ1bmN0aW9uKGUsIGRhdGEpe1xuXHRcdFx0XHR0aGlzLnVwZGF0ZU1lbnVGcm9tUmVzcG9uc2UoZGF0YS54aHIpO1xuXHRcdFx0fVxuXHRcdH0sXG5cblx0XHQnZnJvbSAuY21zLWVkaXQtZm9ybSc6IHtcblx0XHRcdG9ucmVsb2RlZGl0Zm9ybTogZnVuY3Rpb24oZSwgZGF0YSl7XG5cdFx0XHRcdHRoaXMudXBkYXRlTWVudUZyb21SZXNwb25zZShkYXRhLnhtbGh0dHApO1xuXHRcdFx0fVxuXHRcdH0sXG5cblx0XHRnZXRDb250YWluaW5nUGFuZWw6IGZ1bmN0aW9uKCl7XG5cdFx0XHRyZXR1cm4gdGhpcy5jbG9zZXN0KCcuY21zLXBhbmVsJyk7XG5cdFx0fSxcblxuXHRcdGZyb21Db250YWluaW5nUGFuZWw6IHtcblx0XHRcdG9udG9nZ2xlOiBmdW5jdGlvbihlKXtcblx0XHRcdFx0dGhpcy50b2dnbGVDbGFzcygnY29sbGFwc2VkJywgJChlLnRhcmdldCkuaGFzQ2xhc3MoJ2NvbGxhcHNlZCcpKTtcblxuXHRcdFx0XHQvLyBUcmlnZ2VyIHN5bnRoZXRpYyByZXNpemUgZXZlbnQuIEF2b2lkIG5hdGl2ZSB3aW5kb3cucmVzaXplIGV2ZW50XG5cdFx0XHRcdC8vIHNpbmNlIGl0IGNhdXNlcyBvdGhlciBiZWhhdmlvdXIgd2hpY2ggc2hvdWxkIGJlIHJlc2VydmVkIGZvciBhY3R1YWwgd2luZG93IGRpbWVuc2lvbiBjaGFuZ2VzLlxuXHRcdFx0XHQkKCcuY21zLWNvbnRhaW5lcicpLnRyaWdnZXIoJ3dpbmRvd3Jlc2l6ZScpO1xuXG5cdFx0XHRcdC8vSWYgcGFuZWwgaXMgY2xvc2luZ1xuXHRcdFx0XHRpZiAodGhpcy5oYXNDbGFzcygnY29sbGFwc2VkJykpIHRoaXMuZmluZCgnbGkuY2hpbGRyZW4ub3BlbmVkJykucmVtb3ZlQ2xhc3MoJ29wZW5lZCcpO1xuXG5cdFx0XHRcdC8vSWYgcGFuZWwgaXMgb3BlbmluZ1xuXHRcdFx0XHRpZighdGhpcy5oYXNDbGFzcygnY29sbGFwc2VkJykpIHtcblx0XHRcdFx0XHQkKCcudG9nZ2xlLWNoaWxkcmVuLm9wZW5lZCcpLmNsb3Nlc3QoJ2xpJykuYWRkQ2xhc3MoJ29wZW5lZCcpO1xuXHRcdFx0XHR9XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdHVwZGF0ZUl0ZW1zOiBmdW5jdGlvbigpIHtcblx0XHRcdC8vIEhpZGUgXCJlZGl0IHBhZ2VcIiBjb21tYW5kcyB1bmxlc3MgdGhlIHNlY3Rpb24gaXMgYWN0aXZhdGVkXG5cdFx0XHR2YXIgZWRpdFBhZ2VJdGVtID0gdGhpcy5maW5kKCcjTWVudS1DTVNNYWluJyk7XG5cdFx0XHRcblx0XHRcdGVkaXRQYWdlSXRlbVtlZGl0UGFnZUl0ZW0uaXMoJy5jdXJyZW50JykgPyAnc2hvdycgOiAnaGlkZSddKCk7XG5cdFx0XHRcblx0XHRcdC8vIFVwZGF0ZSB0aGUgbWVudSBsaW5rcyB0byByZWZsZWN0IHRoZSBwYWdlIElEIGlmIHRoZSBwYWdlIGhhcyBjaGFuZ2VkIHRoZSBVUkwuXG5cdFx0XHR2YXIgY3VycmVudElEID0gJCgnLmNtcy1jb250ZW50IGlucHV0W25hbWU9SURdJykudmFsKCk7XG5cdFx0XHRpZihjdXJyZW50SUQpIHtcblx0XHRcdFx0dGhpcy5maW5kKCdsaScpLmVhY2goZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0aWYoJC5pc0Z1bmN0aW9uKCQodGhpcykuc2V0UmVjb3JkSUQpKSAkKHRoaXMpLnNldFJlY29yZElEKGN1cnJlbnRJRCk7XG5cdFx0XHRcdH0pO1xuXHRcdFx0fVxuXHRcdH1cblx0fSk7XG5cblx0LyoqIFRvZ2dsZSB0aGUgZmx5b3V0IHBhbmVsIHRvIGFwcGVhci9kaXNhcHBlYXIgd2hlbiBtb3VzZSBvdmVyICovXG5cdCQoJy5jbXMtbWVudS1saXN0IGxpJykuZW50d2luZSh7XG5cdFx0dG9nZ2xlRmx5b3V0OiBmdW5jdGlvbihib29sKSB7XG5cdFx0XHR2YXIgZmx5ID0gJCh0aGlzKTtcblxuXHRcdFx0aWYgKGZseS5jaGlsZHJlbigndWwnKS5maXJzdCgpLmhhc0NsYXNzKCdjb2xsYXBzZWQtZmx5b3V0JykpIHtcblx0XHRcdFx0aWYgKGJvb2wpIHsgLy9leHBhbmRcblx0XHRcdFx0XHQvLyBjcmVhdGUgdGhlIGNsb25lIG9mIHRoZSBsaXN0IGl0ZW0gdG8gYmUgZGlzcGxheWVkXG5cdFx0XHRcdFx0Ly8gb3ZlciB0aGUgZXhpc3Rpbmcgb25lXG5cdFx0XHRcdFx0aWYgKFxuXHRcdFx0XHRcdFx0IWZseS5jaGlsZHJlbigndWwnKVxuXHRcdFx0XHRcdFx0XHQuZmlyc3QoKVxuXHRcdFx0XHRcdFx0XHQuY2hpbGRyZW4oJ2xpJylcblx0XHRcdFx0XHRcdFx0LmZpcnN0KClcblx0XHRcdFx0XHRcdFx0Lmhhc0NsYXNzKCdjbG9uZScpXG5cdFx0XHRcdFx0KSB7XG5cblx0XHRcdFx0XHRcdHZhciBsaSA9IGZseS5jbG9uZSgpO1xuXHRcdFx0XHRcdFx0bGkuYWRkQ2xhc3MoJ2Nsb25lJykuY3NzKHtcblxuXHRcdFx0XHRcdFx0fSk7XG5cblx0XHRcdFx0XHRcdGxpLmNoaWxkcmVuKCd1bCcpLmZpcnN0KCkucmVtb3ZlKCk7XG5cblx0XHRcdFx0XHRcdGxpLmZpbmQoJ3NwYW4nKS5ub3QoJy50ZXh0JykucmVtb3ZlKCk7XG5cblx0XHRcdFx0XHRcdGxpLmZpbmQoJ2EnKS5maXJzdCgpLnVuYmluZCgnY2xpY2snKTtcblxuXHRcdFx0XHRcdFx0Zmx5LmNoaWxkcmVuKCd1bCcpLnByZXBlbmQobGkpO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdCQoJy5jb2xsYXBzZWQtZmx5b3V0Jykuc2hvdygpO1xuXHRcdFx0XHRcdGZseS5hZGRDbGFzcygnb3BlbmVkJyk7XG5cdFx0XHRcdFx0Zmx5LmNoaWxkcmVuKCd1bCcpLmZpbmQoJ2xpJykuZmFkZUluKCdmYXN0Jyk7XG5cdFx0XHRcdH0gZWxzZSB7ICAgIC8vY29sbGFwc2Vcblx0XHRcdFx0XHRpZihsaSkge1xuXHRcdFx0XHRcdFx0bGkucmVtb3ZlKCk7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHRcdCQoJy5jb2xsYXBzZWQtZmx5b3V0JykuaGlkZSgpO1xuXHRcdFx0XHRcdGZseS5yZW1vdmVDbGFzcygnb3BlbmVkJyk7XG5cdFx0XHRcdFx0Zmx5LmZpbmQoJ3RvZ2dsZS1jaGlsZHJlbicpLnJlbW92ZUNsYXNzKCdvcGVuZWQnKTtcblx0XHRcdFx0XHRmbHkuY2hpbGRyZW4oJ3VsJykuZmluZCgnbGknKS5oaWRlKCk7XG5cdFx0XHRcdH1cblx0XHRcdH1cblx0XHR9XG5cdH0pO1xuXHQvL3NsaWdodCBkZWxheSB0byBwcmV2ZW50IGZseW91dCBjbG9zaW5nIGZyb20gXCJzbG9wcHkgbW91c2UgbW92ZW1lbnRcIlxuXHQkKCcuY21zLW1lbnUtbGlzdCBsaScpLmhvdmVySW50ZW50KGZ1bmN0aW9uKCl7JCh0aGlzKS50b2dnbGVGbHlvdXQodHJ1ZSk7fSxmdW5jdGlvbigpeyQodGhpcykudG9nZ2xlRmx5b3V0KGZhbHNlKTt9KTtcblx0XG5cdCQoJy5jbXMtbWVudS1saXN0IC50b2dnbGUnKS5lbnR3aW5lKHtcblx0XHRvbmNsaWNrOiBmdW5jdGlvbihlKSB7XG5cdFx0XHRlLnByZXZlbnREZWZhdWx0KCk7XG5cdFx0XHQkKHRoaXMpLnRvb2dsZUZseW91dCh0cnVlKTtcblx0XHR9XG5cdH0pO1xuXHRcblx0JCgnLmNtcy1tZW51LWxpc3QgbGknKS5lbnR3aW5lKHtcblx0XHRvbm1hdGNoOiBmdW5jdGlvbigpIHtcblx0XHRcdGlmKHRoaXMuZmluZCgndWwnKS5sZW5ndGgpIHtcblx0XHRcdFx0dGhpcy5maW5kKCdhOmZpcnN0JykuYXBwZW5kKCc8c3BhbiBjbGFzcz1cInRvZ2dsZS1jaGlsZHJlblwiPjxzcGFuIGNsYXNzPVwidG9nZ2xlLWNoaWxkcmVuLWljb25cIj48L3NwYW4+PC9zcGFuPicpO1xuXHRcdFx0fVxuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9LFxuXHRcdG9udW5tYXRjaDogZnVuY3Rpb24oKSB7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdH0sXG5cdFx0dG9nZ2xlOiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXNbdGhpcy5oYXNDbGFzcygnb3BlbmVkJykgPyAnY2xvc2UnIDogJ29wZW4nXSgpO1xuXHRcdH0sXG5cdFx0LyoqXG5cdFx0ICogXCJPcGVuXCIgaXMganVzdCBhIHZpc3VhbCBzdGF0ZSwgYW5kIHVucmVsYXRlZCB0byBcImN1cnJlbnRcIi5cblx0XHQgKiBNb3JlIHRoYW4gb25lIGl0ZW0gY2FuIGJlIG9wZW4gYXQgdGhlIHNhbWUgdGltZS5cblx0XHQgKi9cblx0XHRvcGVuOiBmdW5jdGlvbigpIHtcblx0XHRcdHZhciBwYXJlbnQgPSB0aGlzLmdldE1lbnVJdGVtKCk7XG5cdFx0XHRpZihwYXJlbnQpIHBhcmVudC5vcGVuKCk7XG5cdFx0XHRpZiggdGhpcy5maW5kKCdsaS5jbG9uZScpICkge1xuXHRcdFx0XHR0aGlzLmZpbmQoJ2xpLmNsb25lJykucmVtb3ZlKCk7XG5cdFx0XHR9XG5cdFx0XHR0aGlzLmFkZENsYXNzKCdvcGVuZWQnKS5maW5kKCd1bCcpLnNob3coKTtcblx0XHRcdHRoaXMuZmluZCgnLnRvZ2dsZS1jaGlsZHJlbicpLmFkZENsYXNzKCdvcGVuZWQnKTtcblx0XHR9LFxuXHRcdGNsb3NlOiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMucmVtb3ZlQ2xhc3MoJ29wZW5lZCcpLmZpbmQoJ3VsJykuaGlkZSgpO1xuXHRcdFx0dGhpcy5maW5kKCcudG9nZ2xlLWNoaWxkcmVuJykucmVtb3ZlQ2xhc3MoJ29wZW5lZCcpO1xuXHRcdH0sXG5cdFx0c2VsZWN0OiBmdW5jdGlvbigpIHtcblx0XHRcdHZhciBwYXJlbnQgPSB0aGlzLmdldE1lbnVJdGVtKCk7XG5cdFx0XHR0aGlzLmFkZENsYXNzKCdjdXJyZW50Jykub3BlbigpO1xuXG5cdFx0XHQvLyBSZW1vdmUgXCJjdXJyZW50XCIgY2xhc3MgZnJvbSBhbGwgc2libGluZ3MgYW5kIHRoZWlyIGNoaWxkcmVuXG5cdFx0XHR0aGlzLnNpYmxpbmdzKCkucmVtb3ZlQ2xhc3MoJ2N1cnJlbnQnKS5jbG9zZSgpO1xuXHRcdFx0dGhpcy5zaWJsaW5ncygpLmZpbmQoJ2xpJykucmVtb3ZlQ2xhc3MoJ2N1cnJlbnQnKTtcblx0XHRcdGlmKHBhcmVudCkge1xuXHRcdFx0XHR2YXIgcGFyZW50U2libGluZ3MgPSBwYXJlbnQuc2libGluZ3MoKTtcblx0XHRcdFx0cGFyZW50LmFkZENsYXNzKCdjdXJyZW50Jyk7XG5cdFx0XHRcdHBhcmVudFNpYmxpbmdzLnJlbW92ZUNsYXNzKCdjdXJyZW50JykuY2xvc2UoKTtcblx0XHRcdFx0cGFyZW50U2libGluZ3MuZmluZCgnbGknKS5yZW1vdmVDbGFzcygnY3VycmVudCcpLmNsb3NlKCk7XG5cdFx0XHR9XG5cdFx0XHRcblx0XHRcdHRoaXMuZ2V0TWVudSgpLnVwZGF0ZUl0ZW1zKCk7XG5cblx0XHRcdHRoaXMudHJpZ2dlcignc2VsZWN0Jyk7XG5cdFx0fVxuXHR9KTtcblx0XG5cdCQoJy5jbXMtbWVudS1saXN0IConKS5lbnR3aW5lKHtcblx0XHRnZXRNZW51OiBmdW5jdGlvbigpIHtcblx0XHRcdHJldHVybiB0aGlzLnBhcmVudHMoJy5jbXMtbWVudS1saXN0OmZpcnN0Jyk7XG5cdFx0fVxuXHR9KTtcblxuXHQkKCcuY21zLW1lbnUtbGlzdCBsaSAqJykuZW50d2luZSh7XG5cdFx0Z2V0TWVudUl0ZW06IGZ1bmN0aW9uKCkge1xuXHRcdFx0cmV0dXJuIHRoaXMucGFyZW50cygnbGk6Zmlyc3QnKTtcblx0XHR9XG5cdH0pO1xuXHRcblx0LyoqXG5cdCAqIEJvdGggcHJpbWFyeSBhbmQgc2Vjb25kYXJ5IG5hdi5cblx0ICovXG5cdCQoJy5jbXMtbWVudS1saXN0IGxpIGEnKS5lbnR3aW5lKHtcblx0XHRvbmNsaWNrOiBmdW5jdGlvbihlKSB7XG5cdFx0XHQvLyBPbmx5IGNhdGNoIGxlZnQgY2xpY2tzLCBpbiBvcmRlciB0byBhbGxvdyBvcGVuaW5nIGluIHRhYnMuXG5cdFx0XHQvLyBJZ25vcmUgZXh0ZXJuYWwgbGlua3MsIGZhbGxiYWNrIHRvIHN0YW5kYXJkIGxpbmsgYmVoYXZpb3VyXG5cdFx0XHR2YXIgaXNFeHRlcm5hbCA9ICQucGF0aC5pc0V4dGVybmFsKHRoaXMuYXR0cignaHJlZicpKTtcblx0XHRcdGlmKGUud2hpY2ggPiAxIHx8IGlzRXh0ZXJuYWwpIHJldHVybjtcblxuXHRcdFx0Ly8gaWYgdGhlIGRldmVsb3BlciBoYXMgdGhpcyB0byBvcGVuIGluIGEgbmV3IHdpbmRvdywgaGFuZGxlIFxuXHRcdFx0Ly8gdGhhdFxuXHRcdFx0aWYodGhpcy5hdHRyKCd0YXJnZXQnKSA9PSBcIl9ibGFua1wiKSB7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblx0XHRcdFxuXHRcdFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXG5cdFx0XHR2YXIgaXRlbSA9IHRoaXMuZ2V0TWVudUl0ZW0oKTtcblxuXHRcdFx0dmFyIHVybCA9IHRoaXMuYXR0cignaHJlZicpO1xuXHRcdFx0aWYoIWlzRXh0ZXJuYWwpIHVybCA9ICQoJ2Jhc2UnKS5hdHRyKCdocmVmJykgKyB1cmw7XG5cdFx0XHRcblx0XHRcdHZhciBjaGlsZHJlbiA9IGl0ZW0uZmluZCgnbGknKTtcblx0XHRcdGlmKGNoaWxkcmVuLmxlbmd0aCkge1xuXHRcdFx0XHRjaGlsZHJlbi5maXJzdCgpLmZpbmQoJ2EnKS5jbGljaygpO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0Ly8gTG9hZCBVUkwsIGJ1dCBnaXZlIHRoZSBsb2FkaW5nIGxvZ2ljIGFuIG9wcG9ydHVuaXR5IHRvIHZldG8gdGhlIGFjdGlvblxuXHRcdFx0XHQvLyAoZS5nLiBiZWNhdXNlIG9mIHVuc2F2ZWQgY2hhbmdlcylcblx0XHRcdFx0aWYoISQoJy5jbXMtY29udGFpbmVyJykubG9hZFBhbmVsKHVybCkpIHJldHVybiBmYWxzZTtcdFxuXHRcdFx0fVxuXG5cdFx0XHRpdGVtLnNlbGVjdCgpO1xuXHRcdH1cblx0fSk7XG5cblx0JCgnLmNtcy1tZW51LWxpc3QgbGkgLnRvZ2dsZS1jaGlsZHJlbicpLmVudHdpbmUoe1xuXHRcdG9uY2xpY2s6IGZ1bmN0aW9uKGUpIHtcblx0XHRcdHZhciBsaSA9IHRoaXMuY2xvc2VzdCgnbGknKTtcblx0XHRcdGxpLnRvZ2dsZSgpO1xuXHRcdFx0cmV0dXJuIGZhbHNlOyAvLyBwcmV2ZW50IHdyYXBwaW5nIGxpbmsgZXZlbnQgdG8gZmlyZVxuXHRcdH1cblx0fSk7XG5cblx0JCgnLmNtcyAucHJvZmlsZS1saW5rJykuZW50d2luZSh7XG5cdFx0b25jbGljazogZnVuY3Rpb24oKSB7XG5cdFx0XHQkKCcuY21zLWNvbnRhaW5lcicpLmxvYWRQYW5lbCh0aGlzLmF0dHIoJ2hyZWYnKSk7XG5cdFx0XHQkKCcuY21zLW1lbnUtbGlzdCBsaScpLnJlbW92ZUNsYXNzKCdjdXJyZW50JykuY2xvc2UoKTsgXG5cdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0fVxuXHR9KTtcblxuXHQvKipcblx0ICogVG9nZ2xlcyB0aGUgbWFudWFsIG92ZXJyaWRlIG9mIHRoZSBsZWZ0IG1lbnUncyBhdXRvbWF0aWMgZXhwYW5kIC8gY29sbGFwc2UgYmVoYXZpb3VyLlxuXHQgKi9cblx0JCgnLmNtcy1tZW51IC5zdGlja3ktdG9nZ2xlJykuZW50d2luZSh7XG5cblx0XHRvbmFkZDogZnVuY3Rpb24gKCkge1xuXHRcdFx0dmFyIGlzU3RpY2t5ID0gJCgnLmNtcy1tZW51JykuZ2V0UGVyc2lzdGVkU3RpY2t5U3RhdGUoKSA/IHRydWUgOiBmYWxzZTtcblxuXHRcdFx0dGhpcy50b2dnbGVDU1MoaXNTdGlja3kpO1xuXHRcdFx0dGhpcy50b2dnbGVJbmRpY2F0b3IoaXNTdGlja3kpO1xuXG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBAZnVuYyB0b2dnbGVDU1Ncblx0XHQgKiBAcGFyYW0ge2Jvb2xlYW59IGlzU3RpY2t5IC0gVGhlIGN1cnJlbnQgc3RhdGUgb2YgdGhlIG1lbnUuXG5cdFx0ICogQGRlc2MgVG9nZ2xlcyB0aGUgJ2FjdGl2ZScgQ1NTIGNsYXNzIG9mIHRoZSBlbGVtZW50LlxuXHRcdCAqL1xuXHRcdHRvZ2dsZUNTUzogZnVuY3Rpb24gKGlzU3RpY2t5KSB7XG5cdFx0XHR0aGlzW2lzU3RpY2t5ID8gJ2FkZENsYXNzJyA6ICdyZW1vdmVDbGFzcyddKCdhY3RpdmUnKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogQGZ1bmMgdG9nZ2xlSW5kaWNhdG9yXG5cdFx0ICogQHBhcmFtIHtib29sZWFufSBpc1N0aWNreSAtIFRoZSBjdXJyZW50IHN0YXRlIG9mIHRoZSBtZW51LlxuXHRcdCAqIEBkZXNjIFVwZGF0ZXMgdGhlIGluZGljYXRvcidzIHRleHQgYmFzZWQgb24gdGhlIHN0aWNreSBzdGF0ZSBvZiB0aGUgbWVudS5cblx0XHQgKi9cblx0XHR0b2dnbGVJbmRpY2F0b3I6IGZ1bmN0aW9uIChpc1N0aWNreSkge1xuXHRcdFx0dGhpcy5uZXh0KCcuc3RpY2t5LXN0YXR1cy1pbmRpY2F0b3InKS50ZXh0KGlzU3RpY2t5ID8gJ2ZpeGVkJyA6ICdhdXRvJyk7XG5cdFx0fSxcblxuXHRcdG9uY2xpY2s6IGZ1bmN0aW9uICgpIHtcblx0XHRcdHZhciAkbWVudSA9IHRoaXMuY2xvc2VzdCgnLmNtcy1tZW51JyksXG5cdFx0XHRcdHBlcnNpc3RlZENvbGxhcHNlZFN0YXRlID0gJG1lbnUuZ2V0UGVyc2lzdGVkQ29sbGFwc2VkU3RhdGUoKSxcblx0XHRcdFx0cGVyc2lzdGVkU3RpY2t5U3RhdGUgPSAkbWVudS5nZXRQZXJzaXN0ZWRTdGlja3lTdGF0ZSgpLFxuXHRcdFx0XHRuZXdTdGlja3lTdGF0ZSA9IHBlcnNpc3RlZFN0aWNreVN0YXRlID09PSB2b2lkIDAgPyAhdGhpcy5oYXNDbGFzcygnYWN0aXZlJykgOiAhcGVyc2lzdGVkU3RpY2t5U3RhdGU7XG5cblx0XHRcdC8vIFVwZGF0ZSB0aGUgcGVyc2lzdGVkIGNvbGxhcHNlZCBzdGF0ZVxuXHRcdFx0aWYgKHBlcnNpc3RlZENvbGxhcHNlZFN0YXRlID09PSB2b2lkIDApIHtcblx0XHRcdFx0Ly8gSWYgdGhlcmUgaXMgbm8gcGVyc2lzdGVkIG1lbnUgc3RhdGUgY3VycmVudGx5IHNldCwgdGhlbiBzZXQgaXQgdG8gdGhlIG1lbnUncyBjdXJyZW50IHN0YXRlLlxuXHRcdFx0XHQvLyBUaGlzIHdpbGwgYmUgdGhlIGNhc2UgaWYgdGhlIHVzZXIgaGFzIG5ldmVyIG1hbnVhbGx5IGV4cGFuZGVkIG9yIGNvbGxhcHNlZCB0aGUgbWVudSxcblx0XHRcdFx0Ly8gb3IgdGhlIG1lbnUgaGFzIHByZXZpb3VzbHkgYmVlbiBtYWRlIHVuc3RpY2t5LlxuXHRcdFx0XHQkbWVudS5zZXRQZXJzaXN0ZWRDb2xsYXBzZWRTdGF0ZSgkbWVudS5oYXNDbGFzcygnY29sbGFwc2VkJykpO1xuXHRcdFx0fSBlbHNlIGlmIChwZXJzaXN0ZWRDb2xsYXBzZWRTdGF0ZSAhPT0gdm9pZCAwICYmIG5ld1N0aWNreVN0YXRlID09PSBmYWxzZSkge1xuXHRcdFx0XHQvLyBJZiB0aGVyZSBpcyBhIHBlcnNpc3RlZCBzdGF0ZSBhbmQgdGhlIG1lbnUgaGFzIGJlZW4gbWFkZSB1bnN0aWNreSwgcmVtb3ZlIHRoZSBwZXJzaXN0ZWQgc3RhdGUuXG5cdFx0XHRcdCRtZW51LmNsZWFyUGVyc2lzdGVkQ29sbGFwc2VkU3RhdGUoKTtcblx0XHRcdH1cblxuXHRcdFx0Ly8gUGVyc2lzdCB0aGUgc3RpY2t5IHN0YXRlIG9mIHRoZSBtZW51XG5cdFx0XHQkbWVudS5zZXRQZXJzaXN0ZWRTdGlja3lTdGF0ZShuZXdTdGlja3lTdGF0ZSk7XG5cblx0XHRcdHRoaXMudG9nZ2xlQ1NTKG5ld1N0aWNreVN0YXRlKTtcblx0XHRcdHRoaXMudG9nZ2xlSW5kaWNhdG9yKG5ld1N0aWNreVN0YXRlKTtcblxuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9XG5cdH0pO1xufSk7IiwiaW1wb3J0ICQgZnJvbSAnalF1ZXJ5JztcblxuJC5lbnR3aW5lKCdzcycsIGZ1bmN0aW9uKCQpIHtcblxuXHQvLyBzZXR1cCBqcXVlcnkuZW50d2luZVxuXHQkLmVudHdpbmUud2FybmluZ0xldmVsID0gJC5lbnR3aW5lLldBUk5fTEVWRUxfQkVTVFBSQUNUSVNFO1xuXG5cdC8qKlxuXHQgKiBIb3Jpem9udGFsIGNvbGxhcHNpYmxlIHBhbmVsLiBHZW5lcmljIGVub3VnaCB0byB3b3JrIHdpdGggQ01TIG1lbnUgYXMgd2VsbCBhcyB2YXJpb3VzIFwiZmlsdGVyXCIgcGFuZWxzLlxuXHQgKiBcblx0ICogQSBwYW5lbCBjb25zaXN0cyBvZiB0aGUgZm9sbG93aW5nIHBhcnRzOlxuXHQgKiAtIENvbnRhaW5lciBkaXY6IFRoZSBvdXRlciBlbGVtZW50LCB3aXRoIGNsYXNzIFwiLmNtcy1wYW5lbFwiXG5cdCAqIC0gSGVhZGVyIChvcHRpb25hbClcblx0ICogLSBDb250ZW50XG5cdCAqIC0gRXhwYW5kIGFuZCBjb2xsYXBzZSB0b2dnbGUgYW5jaG9ycyAob3B0aW9uYWwpXG5cdCAqIFxuXHQgKiBTYW1wbGUgSFRNTDpcblx0ICogPGRpdiBjbGFzcz1cImNtcy1wYW5lbFwiPlxuXHQgKiAgPGRpdiBjbGFzcz1cImNtcy1wYW5lbC1oZWFkZXJcIj55b3VyIGhlYWRlcjwvZGl2PlxuXHQgKiBcdDxkaXYgY2xhc3M9XCJjbXMtcGFuZWwtY29udGVudFwiPnlvdXIgY29udGVudCBoZXJlPC9kaXY+XG5cdCAqXHQ8ZGl2IGNsYXNzPVwiY21zLXBhbmVsLXRvZ2dsZVwiPlxuXHQgKiBcdFx0PGEgaHJlZj1cIiNcIiBjbGFzcz1cInRvZ2dsZS1leHBhbmRlXCI+eW91ciB0b2dnbGUgdGV4dDwvYT5cblx0ICogXHRcdDxhIGhyZWY9XCIjXCIgY2xhc3M9XCJ0b2dnbGUtY29sbGFwc2VcIj55b3VyIHRvZ2dsZSB0ZXh0PC9hPlxuXHQgKlx0PC9kaXY+XG5cdCAqIDwvZGl2PlxuXHQgKi9cblx0JCgnLmNtcy1wYW5lbCcpLmVudHdpbmUoe1xuXHRcdFxuXHRcdFdpZHRoRXhwYW5kZWQ6IG51bGwsXG5cdFx0XG5cdFx0V2lkdGhDb2xsYXBzZWQ6IG51bGwsXG5cblx0XHQvKipcblx0XHQgKiBAZnVuYyBjYW5TZXRDb29raWVcblx0XHQgKiBAcmV0dXJuIHtib29sZWFufVxuXHRcdCAqIEBkZXNjIEJlZm9yZSB0cnlpbmcgdG8gc2V0IGEgY29va2llLCBtYWtlIHN1cmUgJC5jb29raWUgYW5kIHRoZSBlbGVtZW50J3MgaWQgYXJlIGJvdGggZGVmaW5lZC5cblx0XHQgKi9cblx0XHRjYW5TZXRDb29raWU6IGZ1bmN0aW9uICgpIHtcblx0XHRcdHJldHVybiAkLmNvb2tpZSAhPT0gdm9pZCAwICYmIHRoaXMuYXR0cignaWQnKSAhPT0gdm9pZCAwO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBAZnVuYyBnZXRQZXJzaXN0ZWRDb2xsYXBzZWRTdGF0ZVxuXHRcdCAqIEByZXR1cm4ge2Jvb2xlYW58dW5kZWZpbmVkfSAtIFJldHVybnMgdHJ1ZSBpZiB0aGUgcGFuZWwgaXMgY29sbGFwc2VkLCBmYWxzZSBpZiBleHBhbmRlZC4gUmV0dXJucyB1bmRlZmluZWQgaWYgdGhlcmUgaXMgbm8gY29va2llIHNldC5cblx0XHQgKiBAZGVzYyBHZXQgdGhlIGNvbGxhcHNlZCBzdGF0ZSBvZiB0aGUgcGFuZWwgYWNjb3JkaW5nIHRvIHRoZSBjb29raWUuXG5cdFx0ICovXG5cdFx0Z2V0UGVyc2lzdGVkQ29sbGFwc2VkU3RhdGU6IGZ1bmN0aW9uICgpIHtcblx0XHRcdHZhciBpc0NvbGxhcHNlZCwgY29va2llVmFsdWU7XG5cblx0XHRcdGlmICh0aGlzLmNhblNldENvb2tpZSgpKSB7XG5cdFx0XHRcdGNvb2tpZVZhbHVlID0gJC5jb29raWUoJ2Ntcy1wYW5lbC1jb2xsYXBzZWQtJyArIHRoaXMuYXR0cignaWQnKSk7XG5cblx0XHRcdFx0aWYgKGNvb2tpZVZhbHVlICE9PSB2b2lkIDAgJiYgY29va2llVmFsdWUgIT09IG51bGwpIHtcblx0XHRcdFx0XHRpc0NvbGxhcHNlZCA9IGNvb2tpZVZhbHVlID09PSAndHJ1ZSc7XG5cdFx0XHRcdH1cblx0XHRcdH1cblxuXHRcdFx0cmV0dXJuIGlzQ29sbGFwc2VkO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBAZnVuYyBzZXRQZXJzaXN0ZWRDb2xsYXBzZWRTdGF0ZVxuXHRcdCAqIEBwYXJhbSB7Ym9vbGVhbn0gbmV3U3RhdGUgLSBQYXNzIHRydWUgaWYgeW91IHdhbnQgdGhlIHBhbmVsIHRvIGJlIGNvbGxhcHNlZCwgZmFsc2UgZm9yIGV4cGFuZGVkLlxuXHRcdCAqIEBkZXNjIFNldCB0aGUgY29sbGFwc2VkIHZhbHVlIG9mIHRoZSBwYW5lbCwgc3RvcmVkIGluIGNvb2tpZXMuXG5cdFx0ICovXG5cdFx0c2V0UGVyc2lzdGVkQ29sbGFwc2VkU3RhdGU6IGZ1bmN0aW9uIChuZXdTdGF0ZSkge1xuXHRcdFx0aWYgKHRoaXMuY2FuU2V0Q29va2llKCkpIHtcblx0XHRcdFx0JC5jb29raWUoJ2Ntcy1wYW5lbC1jb2xsYXBzZWQtJyArIHRoaXMuYXR0cignaWQnKSwgbmV3U3RhdGUsIHsgcGF0aDogJy8nLCBleHBpcmVzOiAzMSB9KTtcblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogQGZ1bmMgY2xlYXJQZXJzaXN0ZWRTdGF0ZVxuXHRcdCAqIEBkZXNjIFJlbW92ZSB0aGUgY29va2llIHJlc3BvbnNpYmxlIGZvciBtYWludGFpbmcgdGhlIGNvbGxhcHNlZCBzdGF0ZS5cblx0XHQgKi9cblx0XHRjbGVhclBlcnNpc3RlZENvbGxhcHNlZFN0YXRlOiBmdW5jdGlvbiAoKSB7XG5cdFx0XHRpZiAodGhpcy5jYW5TZXRDb29raWUoKSkge1xuXHRcdFx0XHQkLmNvb2tpZSgnY21zLXBhbmVsLWNvbGxhcHNlZC0nICsgdGhpcy5hdHRyKCdpZCcpLCAnJywgeyBwYXRoOiAnLycsIGV4cGlyZXM6IC0xIH0pO1xuXHRcdFx0fVxuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBAZnVuYyBnZXRJbml0aWFsQ29sbGFwc2VkU3RhdGVcblx0XHQgKiBAcmV0dXJuIHtib29sZWFufSAtIFJldHVybnMgdHJ1ZSBpZiB0aGUgdGhlIHBhbmVsIGlzIGNvbGxhcHNlZCwgZmFsc2UgaWYgZXhwYW5kZWQuXG5cdFx0ICogQGRlc2MgR2V0IHRoZSBpbml0aWFsIGNvbGxhcHNlZCBzdGF0ZSBvZiB0aGUgcGFuZWwuIENoZWNrIGlmIGEgY29va2llIHZhbHVlIGlzIHNldCB0aGVuIGZhbGwgYmFjayB0byBjaGVja2luZyBDU1MgY2xhc3Nlcy5cblx0XHQgKi9cblx0XHRnZXRJbml0aWFsQ29sbGFwc2VkU3RhdGU6IGZ1bmN0aW9uICgpIHtcblx0XHRcdHZhciBpc0NvbGxhcHNlZCA9IHRoaXMuZ2V0UGVyc2lzdGVkQ29sbGFwc2VkU3RhdGUoKTtcblxuXHRcdFx0Ly8gRmFsbGJhY2sgdG8gZ2V0dGluZyB0aGUgc3RhdGUgZnJvbSB0aGUgZGVmYXVsdCBDU1MgY2xhc3Ncblx0XHRcdGlmIChpc0NvbGxhcHNlZCA9PT0gdm9pZCAwKSB7XG5cdFx0XHRcdGlzQ29sbGFwc2VkID0gdGhpcy5oYXNDbGFzcygnY29sbGFwc2VkJyk7XG5cdFx0XHR9XG5cblx0XHRcdHJldHVybiBpc0NvbGxhcHNlZDtcblx0XHR9LFxuXG5cdFx0b25hZGQ6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dmFyIGNvbGxhcHNlZENvbnRlbnQsIGNvbnRhaW5lcjtcblxuXHRcdFx0aWYoIXRoaXMuZmluZCgnLmNtcy1wYW5lbC1jb250ZW50JykubGVuZ3RoKSB0aHJvdyBuZXcgRXhjZXB0aW9uKCdDb250ZW50IHBhbmVsIGZvciBcIi5jbXMtcGFuZWxcIiBub3QgZm91bmQnKTtcblx0XHRcdFxuXHRcdFx0Ly8gQ3JlYXRlIGRlZmF1bHQgY29udHJvbHMgdW5sZXNzIHRoZXkgYWxyZWFkeSBleGlzdC5cblx0XHRcdGlmKCF0aGlzLmZpbmQoJy5jbXMtcGFuZWwtdG9nZ2xlJykubGVuZ3RoKSB7XG5cdFx0XHRcdGNvbnRhaW5lciA9ICQoXCI8ZGl2IGNsYXNzPSdjbXMtcGFuZWwtdG9nZ2xlIHNvdXRoJz48L2Rpdj5cIilcblx0XHRcdFx0XHQuYXBwZW5kKCc8YSBjbGFzcz1cInRvZ2dsZS1leHBhbmRcIiBocmVmPVwiI1wiPjxzcGFuPiZyYXF1bzs8L3NwYW4+PC9hPicpXG5cdFx0XHRcdFx0LmFwcGVuZCgnPGEgY2xhc3M9XCJ0b2dnbGUtY29sbGFwc2VcIiBocmVmPVwiI1wiPjxzcGFuPiZsYXF1bzs8L3NwYW4+PC9hPicpO1xuXHRcdFx0XHRcdFxuXHRcdFx0XHR0aGlzLmFwcGVuZChjb250YWluZXIpO1xuXHRcdFx0fVxuXHRcdFx0XG5cdFx0XHQvLyBTZXQgcGFuZWwgd2lkdGggc2FtZSBhcyB0aGUgY29udGVudCBwYW5lbCBpdCBjb250YWlucy4gQXNzdW1lcyB0aGUgcGFuZWwgaGFzIG92ZXJmbG93OiBoaWRkZW4uXG5cdFx0XHR0aGlzLnNldFdpZHRoRXhwYW5kZWQodGhpcy5maW5kKCcuY21zLXBhbmVsLWNvbnRlbnQnKS5pbm5lcldpZHRoKCkpO1xuXHRcdFx0XG5cdFx0XHQvLyBBc3N1bWVzIHRoZSBjb2xsYXBzZWQgd2lkdGggaXMgaW5kaWNhdGVkIGJ5IHRoZSB0b2dnbGUsIG9yIGJ5IGFuIG9wdGlvbmFsbHkgY29sbGFwc2VkIHZpZXdcblx0XHRcdGNvbGxhcHNlZENvbnRlbnQgPSB0aGlzLmZpbmQoJy5jbXMtcGFuZWwtY29udGVudC1jb2xsYXBzZWQnKTtcblx0XHRcdHRoaXMuc2V0V2lkdGhDb2xsYXBzZWQoY29sbGFwc2VkQ29udGVudC5sZW5ndGggPyBjb2xsYXBzZWRDb250ZW50LmlubmVyV2lkdGgoKSA6IHRoaXMuZmluZCgnLnRvZ2dsZS1leHBhbmQnKS5pbm5lcldpZHRoKCkpO1xuXG5cdFx0XHQvLyBUb2dnbGUgdmlzaWJpbGl0eVxuXHRcdFx0dGhpcy50b2dnbGVQYW5lbCghdGhpcy5nZXRJbml0aWFsQ29sbGFwc2VkU3RhdGUoKSwgdHJ1ZSwgZmFsc2UpO1xuXHRcdFx0XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBAZnVuYyB0b2dnbGVQYW5lbFxuXHRcdCAqIEBwYXJhbSBkb0V4cGFuZCB7Ym9vbGVhbn0gLSB0cnVlIHRvIGV4cGFuZCwgZmFsc2UgdG8gY29sbGFwc2UuXG5cdFx0ICogQHBhcmFtIHNpbGVudCB7Ym9vbGVhbn0gLSB0cnVlIG1lYW5zIHRoYXQgZXZlbnRzIHdvbid0IGJlIGZpcmVkLCB3aGljaCBpcyB1c2VmdWwgZm9yIHRoZSBjb21wb25lbnQgaW5pdGlhbGl6YXRpb24gcGhhc2UuXG5cdFx0ICogQHBhcmFtIGRvU2F2ZVN0YXRlIC0gaWYgZmFsc2UsIHRoZSBwYW5lbCdzIHN0YXRlIHdpbGwgbm90IGJlIHBlcnNpc3RlZCB2aWEgY29va2llcy5cblx0XHQgKiBAZGVzYyBUb2dnbGUgdGhlIGV4cGFuZGVkIC8gY29sbGFwc2VkIHN0YXRlIG9mIHRoZSBwYW5lbC5cblx0XHQgKi9cblx0XHR0b2dnbGVQYW5lbDogZnVuY3Rpb24oZG9FeHBhbmQsIHNpbGVudCwgZG9TYXZlU3RhdGUpIHtcblx0XHRcdHZhciBuZXdXaWR0aCwgY29sbGFwc2VkQ29udGVudDtcblxuXHRcdFx0aWYoIXNpbGVudCkge1xuXHRcdFx0XHR0aGlzLnRyaWdnZXIoJ2JlZm9yZXRvZ2dsZS5zc3BhbmVsJywgZG9FeHBhbmQpO1xuXHRcdFx0XHR0aGlzLnRyaWdnZXIoZG9FeHBhbmQgPyAnYmVmb3JlZXhwYW5kJyA6ICdiZWZvcmVjb2xsYXBzZScpO1xuXHRcdFx0fVxuXG5cdFx0XHR0aGlzLnRvZ2dsZUNsYXNzKCdjb2xsYXBzZWQnLCAhZG9FeHBhbmQpO1xuXHRcdFx0bmV3V2lkdGggPSBkb0V4cGFuZCA/IHRoaXMuZ2V0V2lkdGhFeHBhbmRlZCgpIDogdGhpcy5nZXRXaWR0aENvbGxhcHNlZCgpO1xuXHRcdFx0XG5cdFx0XHR0aGlzLndpZHRoKG5ld1dpZHRoKTsgLy8gdGhlIGNvbnRlbnQgcGFuZWwgd2lkdGggYWx3YXlzIHN0YXlzIGluIFwiZXhwYW5kZWQgc3RhdGVcIiB0byBhdm9pZCBmbG9hdGluZyBlbGVtZW50c1xuXHRcdFx0XG5cdFx0XHQvLyBJZiBhbiBhbHRlcm5hdGl2ZSBjb2xsYXBzZWQgdmlldyBleGlzdHMsIHRvZ2dsZSBpdCBhcyB3ZWxsXG5cdFx0XHRjb2xsYXBzZWRDb250ZW50ID0gdGhpcy5maW5kKCcuY21zLXBhbmVsLWNvbnRlbnQtY29sbGFwc2VkJyk7XG5cdFx0XHRpZihjb2xsYXBzZWRDb250ZW50Lmxlbmd0aCkge1xuXHRcdFx0XHR0aGlzLmZpbmQoJy5jbXMtcGFuZWwtY29udGVudCcpW2RvRXhwYW5kID8gJ3Nob3cnIDogJ2hpZGUnXSgpO1xuXHRcdFx0XHR0aGlzLmZpbmQoJy5jbXMtcGFuZWwtY29udGVudC1jb2xsYXBzZWQnKVtkb0V4cGFuZCA/ICdoaWRlJyA6ICdzaG93J10oKTtcblx0XHRcdH1cblxuXHRcdFx0aWYgKGRvU2F2ZVN0YXRlICE9PSBmYWxzZSkge1xuXHRcdFx0XHR0aGlzLnNldFBlcnNpc3RlZENvbGxhcHNlZFN0YXRlKCFkb0V4cGFuZCk7XG5cdFx0XHR9XG5cblx0XHRcdC8vIFRPRE8gRml4IHJlZHJhdyBvcmRlciAoaW5uZXIgdG8gb3V0ZXIpLCBhbmQgcmUtZW5hYmxlIHNpbGVudCBmbGFnXG5cdFx0XHQvLyB0byBhdm9pZCBtdWx0aXBsZSBleHBlbnNpdmUgcmVkcmF3cyBvbiBhIHNpbmdsZSBsb2FkLlxuXHRcdFx0Ly8gaWYoIXNpbGVudCkge1xuXHRcdFx0XHR0aGlzLnRyaWdnZXIoJ3RvZ2dsZScsIGRvRXhwYW5kKTtcblx0XHRcdFx0dGhpcy50cmlnZ2VyKGRvRXhwYW5kID8gJ2V4cGFuZCcgOiAnY29sbGFwc2UnKTtcblx0XHRcdC8vIH1cblx0XHR9LFxuXHRcdFxuXHRcdGV4cGFuZFBhbmVsOiBmdW5jdGlvbihmb3JjZSkge1xuXHRcdFx0aWYoIWZvcmNlICYmICF0aGlzLmhhc0NsYXNzKCdjb2xsYXBzZWQnKSkgcmV0dXJuO1xuXG5cdFx0XHR0aGlzLnRvZ2dsZVBhbmVsKHRydWUpO1xuXHRcdH0sXG5cdFx0XG5cdFx0Y29sbGFwc2VQYW5lbDogZnVuY3Rpb24oZm9yY2UpIHtcblx0XHRcdGlmKCFmb3JjZSAmJiB0aGlzLmhhc0NsYXNzKCdjb2xsYXBzZWQnKSkgcmV0dXJuO1xuXG5cdFx0XHR0aGlzLnRvZ2dsZVBhbmVsKGZhbHNlKTtcblx0XHR9XG5cdH0pO1xuXG5cdCQoJy5jbXMtcGFuZWwuY29sbGFwc2VkIC5jbXMtcGFuZWwtdG9nZ2xlJykuZW50d2luZSh7XG5cdFx0b25jbGljazogZnVuY3Rpb24oZSkge1xuXHRcdFx0dGhpcy5leHBhbmRQYW5lbCgpO1xuXHRcdFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXHRcdH1cblx0fSk7XG5cdFxuXHQkKCcuY21zLXBhbmVsIConKS5lbnR3aW5lKHtcblx0XHRnZXRQYW5lbDogZnVuY3Rpb24oKSB7XG5cdFx0XHRyZXR1cm4gdGhpcy5wYXJlbnRzKCcuY21zLXBhbmVsOmZpcnN0Jyk7XG5cdFx0fVxuXHR9KTtcblx0XHRcdFxuXHQkKCcuY21zLXBhbmVsIC50b2dnbGUtZXhwYW5kJykuZW50d2luZSh7XG5cdFx0b25jbGljazogZnVuY3Rpb24oZSkge1xuXHRcdFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXHRcdFx0ZS5zdG9wUHJvcGFnYXRpb24oKTtcblxuXHRcdFx0dGhpcy5nZXRQYW5lbCgpLmV4cGFuZFBhbmVsKCk7XG5cblx0XHRcdHRoaXMuX3N1cGVyKGUpO1xuXHRcdH1cblx0fSk7XG5cdFxuXHQkKCcuY21zLXBhbmVsIC50b2dnbGUtY29sbGFwc2UnKS5lbnR3aW5lKHtcblx0XHRvbmNsaWNrOiBmdW5jdGlvbihlKSB7XG5cdFx0XHRlLnByZXZlbnREZWZhdWx0KCk7XG5cdFx0XHRlLnN0b3BQcm9wYWdhdGlvbigpO1xuXG5cdFx0XHR0aGlzLmdldFBhbmVsKCkuY29sbGFwc2VQYW5lbCgpO1xuXG5cdFx0XHR0aGlzLl9zdXBlcihlKTtcblx0XHR9XG5cdH0pO1xuXG5cdCQoJy5jbXMtY29udGVudC10b29scy5jb2xsYXBzZWQnKS5lbnR3aW5lKHtcblx0XHQvLyBFeHBhbmQgQ01TJyBjZW50cmUgcGFuZSwgd2hlbiB0aGUgcGFuZSBpdHNlbGYgaXMgY2xpY2tlZCBzb21ld2hlcmVcblx0XHRvbmNsaWNrOiBmdW5jdGlvbihlKSB7XG5cdFx0XHR0aGlzLmV4cGFuZFBhbmVsKCk7XG5cdFx0XHR0aGlzLl9zdXBlcihlKTtcblx0XHR9XG5cdH0pO1xufSk7XG4iLCJpbXBvcnQgJCBmcm9tICdqUXVlcnknO1xuaW1wb3J0IGkxOG4gZnJvbSAnaTE4bic7XG5cbiQuZW50d2luZSgnc3MucHJldmlldycsIGZ1bmN0aW9uKCQpe1xuXG5cdC8qKlxuXHQgKiBTaG93cyBhIHByZXZpZXdhYmxlIHdlYnNpdGUgc3RhdGUgYWxvbmdzaWRlIGl0cyBlZGl0YWJsZSB2ZXJzaW9uIGluIGJhY2tlbmQgVUkuXG5cdCAqXG5cdCAqIFJlbGllcyBvbiB0aGUgc2VydmVyIHJlc3BvbnNlcyB0byBpbmRpY2F0ZSBpZiBhIHByZXZpZXcgaXMgYXZhaWxhYmxlIGZvciB0aGUgXG5cdCAqIGN1cnJlbnRseSBsb2FkZWQgYWRtaW4gaW50ZXJmYWNlIC0gc2lnbmlmaWVkIGJ5IGNsYXNzIFwiLmNtcy1wcmV2aWV3YWJsZVwiIGJlaW5nIHByZXNlbnQuXG5cdCAqXG5cdCAqIFRoZSBwcmV2aWV3IG9wdGlvbnMgYXQgdGhlIGJvdHRvbSBhcmUgY29uc3RydWN0dXJlZCBieSBncmFiYmluZyBhIFNpbHZlclN0cmlwZU5hdmlnYXRvciBcblx0ICogc3RydWN0dXJlIGFsc28gcHJvdmlkZWQgYnkgdGhlIGJhY2tlbmQuXG5cdCAqL1xuXHQkKCcuY21zLXByZXZpZXcnKS5lbnR3aW5lKHtcblxuXHRcdC8qKlxuXHRcdCAqIExpc3Qgb2YgU2lsdmVyU3RyaXBlTmF2aWdhdG9yIHN0YXRlcyAoU2lsdmVyU3RyaXBlTmF2aWdhdG9ySXRlbSBjbGFzc2VzKSB0byBzZWFyY2ggZm9yLlxuXHRcdCAqIFRoZSBvcmRlciBpcyBzaWduaWZpY2FudCAtIGlmIHRoZSBzdGF0ZSBpcyBub3QgYXZhaWxhYmxlLCBwcmV2aWV3IHdpbGwgc3RhcnQgc2VhcmNoaW5nIHRoZSBsaXN0XG5cdFx0ICogZnJvbSB0aGUgYmVnaW5uaW5nLlxuXHRcdCAqL1xuXHRcdEFsbG93ZWRTdGF0ZXM6IFsnU3RhZ2VMaW5rJywgJ0xpdmVMaW5rJywnQXJjaGl2ZUxpbmsnXSxcblxuXHRcdC8qKlxuXHRcdCAqIEFQSVxuXHRcdCAqIE5hbWUgb2YgdGhlIGN1cnJlbnQgcHJldmlldyBzdGF0ZSAtIG9uZSBvZiB0aGUgXCJBbGxvd2VkU3RhdGVzXCIuXG5cdFx0ICovXG5cdFx0Q3VycmVudFN0YXRlTmFtZTogbnVsbCxcblxuXHRcdC8qKlxuXHRcdCAqIEFQSVxuXHRcdCAqIEN1cnJlbnQgc2l6ZSBzZWxlY3Rpb24uXG5cdFx0ICovXG5cdFx0Q3VycmVudFNpemVOYW1lOiAnYXV0bycsXG5cblx0XHQvKipcblx0XHQgKiBGbGFncyB3aGV0aGVyIHRoZSBwcmV2aWV3IGlzIGF2YWlsYWJsZSBvbiB0aGlzIENNUyBzZWN0aW9uLlxuXHRcdCAqL1xuXHRcdElzUHJldmlld0VuYWJsZWQ6IGZhbHNlLFxuXG5cdFx0LyoqXG5cdFx0ICogTW9kZSBpbiB3aGljaCB0aGUgcHJldmlldyB3aWxsIGJlIGVuYWJsZWQuXG5cdFx0ICovXG5cdFx0RGVmYXVsdE1vZGU6ICdzcGxpdCcsXG5cblx0XHRTaXplczoge1xuXHRcdFx0YXV0bzoge1xuXHRcdFx0XHR3aWR0aDogJzEwMCUnLFxuXHRcdFx0XHRoZWlnaHQ6ICcxMDAlJ1xuXHRcdFx0fSxcblx0XHRcdG1vYmlsZToge1xuXHRcdFx0XHR3aWR0aDogJzMzNXB4JywgLy8gYWRkIDE1cHggZm9yIGFwcHJveCBkZXNrdG9wIHNjcm9sbGJhciBcblx0XHRcdFx0aGVpZ2h0OiAnNTY4cHgnIFxuXHRcdFx0fSxcblx0XHRcdG1vYmlsZUxhbmRzY2FwZToge1xuXHRcdFx0XHR3aWR0aDogJzU4M3B4JywgLy8gYWRkIDE1cHggZm9yIGFwcHJveCBkZXNrdG9wIHNjcm9sbGJhclxuXHRcdFx0XHRoZWlnaHQ6ICczMjBweCdcblx0XHRcdH0sXG5cdFx0XHR0YWJsZXQ6IHtcblx0XHRcdFx0d2lkdGg6ICc3ODNweCcsIC8vIGFkZCAxNXB4IGZvciBhcHByb3ggZGVza3RvcCBzY3JvbGxiYXJcblx0XHRcdFx0aGVpZ2h0OiAnMTAyNHB4J1xuXHRcdFx0fSxcblx0XHRcdHRhYmxldExhbmRzY2FwZToge1xuXHRcdFx0XHR3aWR0aDogJzEwMzlweCcsIC8vIGFkZCAxNXB4IGZvciBhcHByb3ggZGVza3RvcCBzY3JvbGxiYXJcblx0XHRcdFx0aGVpZ2h0OiAnNzY4cHgnXG5cdFx0XHR9LFxuXHRcdFx0ZGVza3RvcDoge1xuXHRcdFx0XHR3aWR0aDogJzEwMjRweCcsXG5cdFx0XHRcdGhlaWdodDogJzgwMHB4J1xuXHRcdFx0fVxuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBBUElcblx0XHQgKiBTd2l0Y2ggdGhlIHByZXZpZXcgdG8gZGlmZmVyZW50IHN0YXRlLlxuXHRcdCAqIHN0YXRlTmFtZSBjYW4gYmUgb25lIG9mIHRoZSBcIkFsbG93ZWRTdGF0ZXNcIi5cblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7U3RyaW5nfVxuXHRcdCAqIEBwYXJhbSB7Qm9vbGVhbn0gU2V0IHRvIEZBTFNFIHRvIGF2b2lkIHBlcnNpc3RpbmcgdGhlIHN0YXRlXG5cdFx0ICovXG5cdFx0Y2hhbmdlU3RhdGU6IGZ1bmN0aW9uKHN0YXRlTmFtZSwgc2F2ZSkge1x0XHRcdFx0XG5cdFx0XHR2YXIgc2VsZiA9IHRoaXMsIHN0YXRlcyA9IHRoaXMuX2dldE5hdmlnYXRvclN0YXRlcygpO1xuXHRcdFx0aWYoc2F2ZSAhPT0gZmFsc2UpIHtcblx0XHRcdFx0JC5lYWNoKHN0YXRlcywgZnVuY3Rpb24oaW5kZXgsIHN0YXRlKSB7XG5cdFx0XHRcdFx0c2VsZi5zYXZlU3RhdGUoJ3N0YXRlJywgc3RhdGVOYW1lKTtcblx0XHRcdFx0fSk7XG5cdFx0XHR9XG5cblx0XHRcdHRoaXMuc2V0Q3VycmVudFN0YXRlTmFtZShzdGF0ZU5hbWUpO1xuXHRcdFx0dGhpcy5fbG9hZEN1cnJlbnRTdGF0ZSgpO1xuXHRcdFx0dGhpcy5yZWRyYXcoKTtcblxuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEFQSVxuXHRcdCAqIENoYW5nZSB0aGUgcHJldmlldyBtb2RlLlxuXHRcdCAqIG1vZGVOYW1lIGNhbiBiZTogc3BsaXQsIGNvbnRlbnQsIHByZXZpZXcuXG5cdFx0ICovXG5cdFx0Y2hhbmdlTW9kZTogZnVuY3Rpb24obW9kZU5hbWUsIHNhdmUpIHtcdFx0XHRcdFxuXHRcdFx0dmFyIGNvbnRhaW5lciA9ICQoJy5jbXMtY29udGFpbmVyJyk7XG5cblx0XHRcdGlmIChtb2RlTmFtZSA9PSAnc3BsaXQnKSB7XG5cdFx0XHRcdGNvbnRhaW5lci5lbnR3aW5lKCcuc3MnKS5zcGxpdFZpZXdNb2RlKCk7XG5cdFx0XHRcdHRoaXMuc2V0SXNQcmV2aWV3RW5hYmxlZCh0cnVlKTtcblx0XHRcdFx0dGhpcy5fbG9hZEN1cnJlbnRTdGF0ZSgpO1xuXHRcdFx0fSBlbHNlIGlmIChtb2RlTmFtZSA9PSAnY29udGVudCcpIHtcblx0XHRcdFx0Y29udGFpbmVyLmVudHdpbmUoJy5zcycpLmNvbnRlbnRWaWV3TW9kZSgpO1xuXHRcdFx0XHR0aGlzLnNldElzUHJldmlld0VuYWJsZWQoZmFsc2UpO1xuXHRcdFx0XHQvLyBEbyBub3QgbG9hZCBjb250ZW50IGFzIHRoZSBwcmV2aWV3IGlzIG5vdCB2aXNpYmxlLlxuXHRcdFx0fSBlbHNlIGlmIChtb2RlTmFtZSA9PSAncHJldmlldycpIHtcblx0XHRcdFx0Y29udGFpbmVyLmVudHdpbmUoJy5zcycpLnByZXZpZXdNb2RlKCk7XG5cdFx0XHRcdHRoaXMuc2V0SXNQcmV2aWV3RW5hYmxlZCh0cnVlKTtcblx0XHRcdFx0dGhpcy5fbG9hZEN1cnJlbnRTdGF0ZSgpO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0dGhyb3cgJ0ludmFsaWQgbW9kZTogJyArIG1vZGVOYW1lO1xuXHRcdFx0fVxuXG5cdFx0XHRpZihzYXZlICE9PSBmYWxzZSkgdGhpcy5zYXZlU3RhdGUoJ21vZGUnLCBtb2RlTmFtZSk7XG5cblx0XHRcdHRoaXMucmVkcmF3KCk7XG5cblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBBUElcblx0XHQgKiBDaGFuZ2UgdGhlIHByZXZpZXcgc2l6ZS5cblx0XHQgKiBzaXplTmFtZSBjYW4gYmU6IGF1dG8sIGRlc2t0b3AsIHRhYmxldCwgbW9iaWxlLlxuXHRcdCAqL1xuXHRcdGNoYW5nZVNpemU6IGZ1bmN0aW9uKHNpemVOYW1lKSB7XG5cdFx0XHR2YXIgc2l6ZXMgPSB0aGlzLmdldFNpemVzKCk7XG5cblx0XHRcdHRoaXMuc2V0Q3VycmVudFNpemVOYW1lKHNpemVOYW1lKTtcblx0XHRcdHRoaXMucmVtb3ZlQ2xhc3MoJ2F1dG8gZGVza3RvcCB0YWJsZXQgbW9iaWxlJykuYWRkQ2xhc3Moc2l6ZU5hbWUpO1xuXHRcdFx0dGhpcy5maW5kKCcucHJldmlldy1kZXZpY2Utb3V0ZXInKVxuXHRcdFx0XHQud2lkdGgoc2l6ZXNbc2l6ZU5hbWVdLndpZHRoKVxuXHRcdFx0XHQuaGVpZ2h0KHNpemVzW3NpemVOYW1lXS5oZWlnaHQpO1xuXHRcdFx0dGhpcy5maW5kKCcucHJldmlldy1kZXZpY2UtaW5uZXInKVxuXHRcdFx0XHQud2lkdGgoc2l6ZXNbc2l6ZU5hbWVdLndpZHRoKTtcblxuXHRcdFx0dGhpcy5zYXZlU3RhdGUoJ3NpemUnLCBzaXplTmFtZSk7XG5cblx0XHRcdHRoaXMucmVkcmF3KCk7XG5cblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBBUElcblx0XHQgKiBVcGRhdGUgdGhlIHZpc3VhbCBhcHBlYXJhbmNlIHRvIG1hdGNoIHRoZSBpbnRlcm5hbCBwcmV2aWV3IHN0YXRlLlxuXHRcdCAqL1xuXHRcdHJlZHJhdzogZnVuY3Rpb24oKSB7XHRcdFx0XG5cblx0XHRcdGlmKHdpbmRvdy5kZWJ1ZykgY29uc29sZS5sb2coJ3JlZHJhdycsIHRoaXMuYXR0cignY2xhc3MnKSwgdGhpcy5nZXQoMCkpO1xuXG5cdFx0XHQvLyBVcGRhdGUgcHJldmlldyBzdGF0ZSBzZWxlY3Rvci5cblx0XHRcdHZhciBjdXJyZW50U3RhdGVOYW1lID0gdGhpcy5nZXRDdXJyZW50U3RhdGVOYW1lKCk7XG5cdFx0XHRpZiAoY3VycmVudFN0YXRlTmFtZSkge1xuXHRcdFx0XHR0aGlzLmZpbmQoJy5jbXMtcHJldmlldy1zdGF0ZXMnKS5jaGFuZ2VWaXNpYmxlU3RhdGUoY3VycmVudFN0YXRlTmFtZSk7XG5cdFx0XHR9XG5cblx0XHRcdC8vIFVwZGF0ZSBwcmV2aWV3IG1vZGUgc2VsZWN0b3JzLlxuXHRcdFx0dmFyIGxheW91dE9wdGlvbnMgPSAkKCcuY21zLWNvbnRhaW5lcicpLmVudHdpbmUoJy5zcycpLmdldExheW91dE9wdGlvbnMoKTtcblx0XHRcdGlmIChsYXlvdXRPcHRpb25zKSB7XG5cdFx0XHRcdC8vIFRoZXJlIGFyZSB0d28gbW9kZSBzZWxlY3RvcnMgdGhhdCB3ZSBuZWVkIHRvIGtlZXAgaW4gc3luYy4gUmVkcmF3IGJvdGguXG5cdFx0XHRcdCQoJy5wcmV2aWV3LW1vZGUtc2VsZWN0b3InKS5jaGFuZ2VWaXNpYmxlTW9kZShsYXlvdXRPcHRpb25zLm1vZGUpO1xuXHRcdFx0fVxuXG5cdFx0XHQvLyBVcGRhdGUgcHJldmlldyBzaXplIHNlbGVjdG9yLlxuXHRcdFx0dmFyIGN1cnJlbnRTaXplTmFtZSA9IHRoaXMuZ2V0Q3VycmVudFNpemVOYW1lKCk7XG5cdFx0XHRpZiAoY3VycmVudFNpemVOYW1lKSB7XG5cdFx0XHRcdHRoaXMuZmluZCgnLnByZXZpZXctc2l6ZS1zZWxlY3RvcicpLmNoYW5nZVZpc2libGVTaXplKHRoaXMuZ2V0Q3VycmVudFNpemVOYW1lKCkpO1xuXHRcdFx0fVxuXG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogU3RvcmUgdGhlIHByZXZpZXcgb3B0aW9ucyBmb3IgdGhpcyBwYWdlLlxuXHRcdCAqL1xuXHRcdHNhdmVTdGF0ZSA6IGZ1bmN0aW9uKG5hbWUsIHZhbHVlKSB7XG5cdFx0XHRpZih0aGlzLl9zdXBwb3J0c0xvY2FsU3RvcmFnZSgpKSB3aW5kb3cubG9jYWxTdG9yYWdlLnNldEl0ZW0oJ2Ntcy1wcmV2aWV3LXN0YXRlLScgKyBuYW1lLCB2YWx1ZSk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIExvYWQgcHJldmlvdXNseSBzdG9yZWQgcHJlZmVyZW5jZXNcblx0XHQgKi9cblx0XHRsb2FkU3RhdGUgOiBmdW5jdGlvbihuYW1lKSB7XG5cdFx0XHRpZih0aGlzLl9zdXBwb3J0c0xvY2FsU3RvcmFnZSgpKSByZXR1cm4gd2luZG93LmxvY2FsU3RvcmFnZS5nZXRJdGVtKCdjbXMtcHJldmlldy1zdGF0ZS0nICsgbmFtZSk7XG5cdFx0fSwgXG5cblx0XHQvKipcblx0XHQgKiBEaXNhYmxlIHRoZSBhcmVhIC0gaXQgd2lsbCBub3QgYXBwZWFyIGluIHRoZSBHVUkuXG5cdFx0ICogQ2F2ZWF0OiB0aGUgcHJldmlldyB3aWxsIGJlIGF1dG9tYXRpY2FsbHkgZW5hYmxlZCB3aGVuIFwiLmNtcy1wcmV2aWV3YWJsZVwiIGNsYXNzIGlzIGRldGVjdGVkLlxuXHRcdCAqL1xuXHRcdGRpc2FibGVQcmV2aWV3OiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMuc2V0UGVuZGluZ1VSTChudWxsKTtcblx0XHRcdHRoaXMuX2xvYWRVcmwoJ2Fib3V0OmJsYW5rJyk7XG5cdFx0XHR0aGlzLl9ibG9jaygpO1xuXHRcdFx0dGhpcy5jaGFuZ2VNb2RlKCdjb250ZW50JywgZmFsc2UpO1xuXHRcdFx0dGhpcy5zZXRJc1ByZXZpZXdFbmFibGVkKGZhbHNlKTtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBFbmFibGUgdGhlIGFyZWEgYW5kIHN0YXJ0IHVwZGF0aW5nIHRvIHJlZmxlY3QgdGhlIGNvbnRlbnQgZWRpdGluZy5cblx0XHQgKi9cblx0XHRlbmFibGVQcmV2aWV3OiBmdW5jdGlvbigpIHtcblx0XHRcdGlmICghdGhpcy5nZXRJc1ByZXZpZXdFbmFibGVkKCkpIHtcblx0XHRcdFx0dGhpcy5zZXRJc1ByZXZpZXdFbmFibGVkKHRydWUpO1xuXG5cdFx0XHRcdC8vIEluaXRpYWxpc2UgbW9kZS5cblx0XHRcdFx0aWYgKCQuYnJvd3Nlci5tc2llICYmICQuYnJvd3Nlci52ZXJzaW9uLnNsaWNlKDAsMyk8PTcpIHtcblx0XHRcdFx0XHQvLyBXZSBkbyBub3Qgc3VwcG9ydCB0aGUgc3BsaXQgbW9kZSBpbiBJRSA8IDguXG5cdFx0XHRcdFx0dGhpcy5jaGFuZ2VNb2RlKCdjb250ZW50Jyk7XG5cdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0dGhpcy5jaGFuZ2VNb2RlKHRoaXMuZ2V0RGVmYXVsdE1vZGUoKSwgZmFsc2UpO1xuXHRcdFx0XHR9XG5cdFx0XHR9XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogUmV0dXJuIGEgc3R5bGUgZWxlbWVudCB3ZSBjYW4gdXNlIGluIElFOCB0byBmaXggZm9udHMgKHNlZSByZWFkeXN0YXRlY2hhbmdlIGJpbmRpbmcgaW4gb25hZGQgYmVsb3cpXG5cdFx0ICovXG5cdFx0Z2V0T3JBcHBlbmRGb250Rml4U3R5bGVFbGVtZW50OiBmdW5jdGlvbigpIHtcblx0XHRcdHZhciBzdHlsZSA9ICQoJyNGb250Rml4U3R5bGVFbGVtZW50Jyk7XG5cdFx0XHRpZiAoIXN0eWxlLmxlbmd0aCkge1xuXHRcdFx0XHRzdHlsZSA9ICQoXG5cdFx0XHRcdFx0JzxzdHlsZSB0eXBlPVwidGV4dC9jc3NcIiBpZD1cIkZvbnRGaXhTdHlsZUVsZW1lbnRcIiBkaXNhYmxlZD1cImRpc2FibGVkXCI+Jytcblx0XHRcdFx0XHRcdCc6YmVmb3JlLDphZnRlcntjb250ZW50Om5vbmUgIWltcG9ydGFudH0nK1xuXHRcdFx0XHRcdCc8L3N0eWxlPidcblx0XHRcdFx0KS5hcHBlbmRUbygnaGVhZCcpO1xuXHRcdFx0fVxuXG5cdFx0XHRyZXR1cm4gc3R5bGU7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEluaXRpYWxpc2UgdGhlIHByZXZpZXcgZWxlbWVudC5cblx0XHQgKi9cblx0XHRvbmFkZDogZnVuY3Rpb24oKSB7XG5cdFx0XHR2YXIgc2VsZiA9IHRoaXMsIGxheW91dENvbnRhaW5lciA9IHRoaXMucGFyZW50KCksIGlmcmFtZSA9IHRoaXMuZmluZCgnaWZyYW1lJyk7XG5cblx0XHRcdC8vIENyZWF0ZSBsYXlvdXQgYW5kIGNvbnRyb2xzXG5cdFx0XHRpZnJhbWUuYWRkQ2xhc3MoJ2NlbnRlcicpO1xuXHRcdFx0aWZyYW1lLmJpbmQoJ2xvYWQnLCBmdW5jdGlvbigpIHtcblx0XHRcdFx0c2VsZi5fYWRqdXN0SWZyYW1lRm9yUHJldmlldygpO1xuXG5cdFx0XHRcdC8vIExvYWQgZWRpdCB2aWV3IGZvciBuZXcgcGFnZSwgYnV0IG9ubHkgaWYgdGhlIHByZXZpZXcgaXMgYWN0aXZhdGVkIGF0IHRoZSBtb21lbnQuXG5cdFx0XHRcdC8vIFRoaXMgYXZvaWRzIGUuZy4gZm9yY2UtcmVkaXJlY3Rpb25zIG9mIHRoZSBlZGl0IHZpZXcgb24gUmVkaXJlY3RvclBhZ2UgaW5zdGFuY2VzLlxuXHRcdFx0XHRzZWxmLl9sb2FkQ3VycmVudFBhZ2UoKTtcblx0XHRcdFx0XG5cdFx0XHRcdCQodGhpcykucmVtb3ZlQ2xhc3MoJ2xvYWRpbmcnKTtcblx0XHRcdH0pO1xuXG5cdFx0XHQvLyBJZiB0aGVyZSdzIGFueSB3ZWJmb250cyBpbiB0aGUgcHJldmlldywgSUU4IHdpbGwgc3RhcnQgZ2xpdGNoaW5nLiBUaGlzIGZpeGVzIHRoYXQuXG5cdFx0XHRpZiAoJC5icm93c2VyLm1zaWUgJiYgOCA9PT0gcGFyc2VJbnQoJC5icm93c2VyLnZlcnNpb24sIDEwKSkge1xuXHRcdFx0XHRpZnJhbWUuYmluZCgncmVhZHlzdGF0ZWNoYW5nZScsIGZ1bmN0aW9uKGUpIHtcblx0XHRcdFx0XHRpZihpZnJhbWVbMF0ucmVhZHlTdGF0ZSA9PSAnaW50ZXJhY3RpdmUnKSB7XG5cdFx0XHRcdFx0XHRzZWxmLmdldE9yQXBwZW5kRm9udEZpeFN0eWxlRWxlbWVudCgpLnJlbW92ZUF0dHIoJ2Rpc2FibGVkJyk7XG5cdFx0XHRcdFx0XHRzZXRUaW1lb3V0KGZ1bmN0aW9uKCl7IHNlbGYuZ2V0T3JBcHBlbmRGb250Rml4U3R5bGVFbGVtZW50KCkuYXR0cignZGlzYWJsZWQnLCAnZGlzYWJsZWQnKTsgfSwgMCk7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9KTtcblx0XHRcdH1cblxuXHRcdFx0Ly8gUHJldmlldyBtaWdodCBub3QgYmUgYXZhaWxhYmxlIGluIGFsbCBhZG1pbiBpbnRlcmZhY2VzIC0gYmxvY2svZGlzYWJsZSB3aGVuIG5lY2Vzc2FyeVxuXHRcdFx0dGhpcy5hcHBlbmQoJzxkaXYgY2xhc3M9XCJjbXMtcHJldmlldy1vdmVybGF5IHVpLXdpZGdldC1vdmVybGF5LWxpZ2h0XCI+PC9kaXY+Jyk7XG5cdFx0XHR0aGlzLmZpbmQoJy5jbXMtcHJldmlldy1vdmVybGF5JykuaGlkZSgpO1x0XHRcdFxuXG5cdFx0XHR0aGlzLmRpc2FibGVQcmV2aWV3KCk7XG5cblx0XHRcdHRoaXMuX3N1cGVyKCk7XG5cdFx0fSxcblx0XHRcblx0XHQvKipcblx0XHQqIERldGVjdCBhbmQgdXNlIGxvY2FsU3RvcmFnZSBpZiBhdmFpbGFibGUuIEluIElFMTEgd2luZG93cyA4LjEgY2FsbCB0byB3aW5kb3cubG9jYWxTdG9yYWdlIHdhcyB0aHJvd2luZyBvdXQgYW4gYWNjZXNzIGRlbmllZCBlcnJvciBpbiBzb21lIGNhc2VzIHdoaWNoIHdhcyBjYXVzaW5nIHRoZSBwcmV2aWV3IHdpbmRvdyBub3QgdG8gZGlzcGxheSBjb3JyZWN0bHkgaW4gdGhlIENNUyBhZG1pbiBhcmVhLlxuXHRcdCovXG5cdFx0X3N1cHBvcnRzTG9jYWxTdG9yYWdlOiBmdW5jdGlvbigpIHtcblx0XHRcdHZhciB1aWQgPSBuZXcgRGF0ZTtcblx0XHRcdHZhciBzdG9yYWdlO1xuXHRcdFx0dmFyIHJlc3VsdDtcblx0XHRcdHRyeSB7XG5cdFx0XHRcdChzdG9yYWdlID0gd2luZG93LmxvY2FsU3RvcmFnZSkuc2V0SXRlbSh1aWQsIHVpZCk7XG5cdFx0XHRcdHJlc3VsdCA9IHN0b3JhZ2UuZ2V0SXRlbSh1aWQpID09IHVpZDtcblx0XHRcdFx0c3RvcmFnZS5yZW1vdmVJdGVtKHVpZCk7XG5cdFx0XHRcdHJldHVybiByZXN1bHQgJiYgc3RvcmFnZTtcblx0XHRcdH0gY2F0Y2ggKGV4Y2VwdGlvbikge1xuXHRcdFx0XHRjb25zb2xlLndhcm4oJ2xvY2FsU3RvcmdlIGlzIG5vdCBhdmFpbGFibGUgZHVlIHRvIGN1cnJlbnQgYnJvd3NlciAvIHN5c3RlbSBzZXR0aW5ncy4nKTtcblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0b25lbmFibGU6IGZ1bmN0aW9uICgpIHtcblx0XHRcdHZhciAkdmlld01vZGVTZWxlY3RvciA9ICQoJy5wcmV2aWV3LW1vZGUtc2VsZWN0b3InKTtcblxuXHRcdFx0JHZpZXdNb2RlU2VsZWN0b3IucmVtb3ZlQ2xhc3MoJ3NwbGl0LWRpc2FibGVkJyk7XG5cdFx0XHQkdmlld01vZGVTZWxlY3Rvci5maW5kKCcuZGlzYWJsZWQtdG9vbHRpcCcpLmhpZGUoKTtcblx0XHR9LFxuXG5cdFx0b25kaXNhYmxlOiBmdW5jdGlvbiAoKSB7XG5cdFx0XHR2YXIgJHZpZXdNb2RlU2VsZWN0b3IgPSAkKCcucHJldmlldy1tb2RlLXNlbGVjdG9yJyk7XG5cblx0XHRcdCR2aWV3TW9kZVNlbGVjdG9yLmFkZENsYXNzKCdzcGxpdC1kaXNhYmxlZCcpO1xuXHRcdFx0JHZpZXdNb2RlU2VsZWN0b3IuZmluZCgnLmRpc2FibGVkLXRvb2x0aXAnKS5zaG93KCk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFNldCB0aGUgcHJldmlldyB0byB1bmF2YWlsYWJsZSAtIGNvdWxkIGJlIHN0aWxsIHZpc2libGUuIFRoaXMgaXMgcHVyZWx5IHZpc3VhbC5cblx0XHQgKi9cblx0XHRfYmxvY2s6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dGhpcy5hZGRDbGFzcygnYmxvY2tlZCcpO1xuXHRcdFx0dGhpcy5maW5kKCcuY21zLXByZXZpZXctb3ZlcmxheScpLnNob3coKTtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBTZXQgdGhlIHByZXZpZXcgdG8gYXZhaWxhYmxlIChyZW1vdmUgdGhlIG92ZXJsYXkpO1xuXHRcdCAqL1xuXHRcdF91bmJsb2NrOiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMucmVtb3ZlQ2xhc3MoJ2Jsb2NrZWQnKTtcblx0XHRcdHRoaXMuZmluZCgnLmNtcy1wcmV2aWV3LW92ZXJsYXknKS5oaWRlKCk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogVXBkYXRlIHRoZSBwcmV2aWV3IGFjY29yZGluZyB0byBicm93c2VyIGFuZCBDTVMgc2VjdGlvbiBjYXBhYmlsaXRpZXMuXG5cdFx0ICovXG5cdFx0X2luaXRpYWxpc2VGcm9tQ29udGVudDogZnVuY3Rpb24oKSB7XG5cdFx0XHR2YXIgbW9kZSwgc2l6ZTtcblxuXHRcdFx0aWYgKCEkKCcuY21zLXByZXZpZXdhYmxlJykubGVuZ3RoKSB7XG5cdFx0XHRcdHRoaXMuZGlzYWJsZVByZXZpZXcoKTtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdG1vZGUgPSB0aGlzLmxvYWRTdGF0ZSgnbW9kZScpO1xuXHRcdFx0XHRzaXplID0gdGhpcy5sb2FkU3RhdGUoJ3NpemUnKTtcblxuXHRcdFx0XHR0aGlzLl9tb3ZlTmF2aWdhdG9yKCk7XG5cdFx0XHRcdGlmKCFtb2RlIHx8IG1vZGUgIT0gJ2NvbnRlbnQnKSB7XG5cdFx0XHRcdFx0dGhpcy5lbmFibGVQcmV2aWV3KCk7XG5cdFx0XHRcdFx0dGhpcy5fbG9hZEN1cnJlbnRTdGF0ZSgpO1xuXHRcdFx0XHR9XG5cdFx0XHRcdHRoaXMucmVkcmF3KCk7XG5cblx0XHRcdFx0Ly8gbm93IGNoZWNrIHRoZSBjb29raWUgdG8gc2VlIGlmIHdlIGhhdmUgYW55IHByZXZpZXcgc2V0dGluZ3MgdGhhdCBoYXZlIGJlZW5cblx0XHRcdFx0Ly8gcmV0YWluZWQgZm9yIHRoaXMgcGFnZSBmcm9tIHRoZSBsYXN0IHZpc2l0XG5cdFx0XHRcdGlmKG1vZGUpIHRoaXMuY2hhbmdlTW9kZShtb2RlKTtcblx0XHRcdFx0aWYoc2l6ZSkgdGhpcy5jaGFuZ2VTaXplKHNpemUpO1xuXHRcdFx0fVxuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFVwZGF0ZSBwcmV2aWV3IHdoZW5ldmVyIGFueSBwYW5lbHMgYXJlIHJlbG9hZGVkLlxuXHRcdCAqL1xuXHRcdCdmcm9tIC5jbXMtY29udGFpbmVyJzoge1xuXHRcdFx0b25hZnRlcnN0YXRlY2hhbmdlOiBmdW5jdGlvbihlLCBkYXRhKSB7XG5cdFx0XHRcdC8vIERvbid0IHVwZGF0ZSBwcmV2aWV3IGlmIHdlJ3JlIGRlYWxpbmcgd2l0aCBhIGN1c3RvbSByZWRpcmVjdFxuXHRcdFx0XHRpZihkYXRhLnhoci5nZXRSZXNwb25zZUhlYWRlcignWC1Db250cm9sbGVyVVJMJykpIHJldHVybjtcblxuXHRcdFx0XHR0aGlzLl9pbml0aWFsaXNlRnJvbUNvbnRlbnQoKTtcblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0LyoqIEB2YXIgc3RyaW5nIEEgVVJMIHRoYXQgc2hvdWxkIGJlIGRpc3BsYXllZCBpbiB0aGlzIHByZXZpZXcgcGFuZWwgb25jZSBpdCBiZWNvbWVzIHZpc2libGUgKi9cblx0XHRQZW5kaW5nVVJMOiBudWxsLFxuXG5cdFx0b25jb2x1bW52aXNpYmlsaXR5Y2hhbmdlZDogZnVuY3Rpb24oKSB7XG5cdFx0XHR2YXIgdXJsID0gdGhpcy5nZXRQZW5kaW5nVVJMKCk7XG5cdFx0XHRpZiAodXJsICYmICF0aGlzLmlzKCcuY29sdW1uLWhpZGRlbicpKSB7XG5cdFx0XHRcdHRoaXMuc2V0UGVuZGluZ1VSTChudWxsKTtcblx0XHRcdFx0dGhpcy5fbG9hZFVybCh1cmwpO1xuXHRcdFx0XHR0aGlzLl91bmJsb2NrKCk7XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFVwZGF0ZSBwcmV2aWV3IHdoZW5ldmVyIGEgZm9ybSBpcyBzdWJtaXR0ZWQuXG5cdFx0ICogVGhpcyBpcyBhbiBhbHRlcm5hdGl2ZSB0byB0aGUgTGVmdEFuZG1NYWluOjpsb2FkUGFuZWwgZnVuY3Rpb25hbGl0eSB3aGljaCB3ZSBhbHJlYWR5XG5cdFx0ICogY292ZXIgaW4gdGhlIG9uYWZ0ZXJzdGF0ZWNoYW5nZSBoYW5kbGVyLlxuXHRcdCAqL1xuXHRcdCdmcm9tIC5jbXMtY29udGFpbmVyIC5jbXMtZWRpdC1mb3JtJzoge1xuXHRcdFx0b25hZnRlcnN1Ym1pdGZvcm06IGZ1bmN0aW9uKCl7XG5cdFx0XHRcdHRoaXMuX2luaXRpYWxpc2VGcm9tQ29udGVudCgpO1xuXHRcdFx0fVxuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBDaGFuZ2UgdGhlIFVSTCBvZiB0aGUgcHJldmlldyBpZnJhbWUgKGlmIGl0cyBub3QgYWxyZWFkeSBkaXNwbGF5ZWQpLlxuXHRcdCAqL1xuXHRcdF9sb2FkVXJsOiBmdW5jdGlvbih1cmwpIHtcblx0XHRcdHRoaXMuZmluZCgnaWZyYW1lJykuYWRkQ2xhc3MoJ2xvYWRpbmcnKS5hdHRyKCdzcmMnLCB1cmwpO1xuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEZldGNoIGF2YWlsYWJsZSBzdGF0ZXMgZnJvbSB0aGUgY3VycmVudCBTaWx2ZXJTdHJpcGVOYXZpZ2F0b3IgKFNpbHZlclN0cmlwZU5hdmlnYXRvckl0ZW1zKS5cblx0XHQgKiBOYXZpZ2F0b3IgaXMgc3VwcGxpZWQgYnkgdGhlIGJhY2tlbmQgYW5kIGNvbnRhaW5zIGFsbCBzdGF0ZSBvcHRpb25zIGZvciB0aGUgY3VycmVudCBvYmplY3QuXG5cdFx0ICovXG5cdFx0X2dldE5hdmlnYXRvclN0YXRlczogZnVuY3Rpb24oKSB7XG5cdFx0XHQvLyBXYWxrIHRocm91Z2ggYXZhaWxhYmxlIHN0YXRlcyBhbmQgZ2V0IHRoZSBVUkxzLlxuXHRcdFx0dmFyIHVybE1hcCA9ICQubWFwKHRoaXMuZ2V0QWxsb3dlZFN0YXRlcygpLCBmdW5jdGlvbihuYW1lKSB7XG5cdFx0XHRcdHZhciBzdGF0ZUxpbmsgPSAkKCcuY21zLXByZXZpZXctc3RhdGVzIC5zdGF0ZS1uYW1lW2RhdGEtbmFtZT0nICsgbmFtZSArICddJyk7XG5cdFx0XHRcdGlmKHN0YXRlTGluay5sZW5ndGgpIHtcblx0XHRcdFx0XHRyZXR1cm4ge1xuXHRcdFx0XHRcdFx0bmFtZTogbmFtZSwgXG5cdFx0XHRcdFx0XHR1cmw6IHN0YXRlTGluay5hdHRyKCdkYXRhLWxpbmsnKSxcblx0XHRcdFx0XHRcdGFjdGl2ZTogc3RhdGVMaW5rLmlzKCc6cmFkaW8nKSA/IHN0YXRlTGluay5pcygnOmNoZWNrZWQnKSA6IHN0YXRlTGluay5pcygnOnNlbGVjdGVkJylcblx0XHRcdFx0XHR9O1xuXHRcdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRcdHJldHVybiBudWxsO1xuXHRcdFx0XHR9XG5cdFx0XHR9KTtcblxuXHRcdFx0cmV0dXJuIHVybE1hcDtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogTG9hZCBjdXJyZW50IHN0YXRlIGludG8gdGhlIHByZXZpZXcgKGUuZy4gU3RhZ2VMaW5rIG9yIExpdmVMaW5rKS5cblx0XHQgKiBXZSB0cnkgdG8gcmV1c2UgdGhlIHN0YXRlIHdlIGhhdmUgYmVlbiBwcmV2aW91c2x5IGluLiBPdGhlcndpc2Ugd2UgZmFsbCBiYWNrXG5cdFx0ICogdG8gdGhlIGZpcnN0IHN0YXRlIGF2YWlsYWJsZSBvbiB0aGUgXCJBbGxvd2VkU3RhdGVzXCIgbGlzdC5cblx0XHQgKlxuXHRcdCAqIEByZXR1cm5zIE5ldyBzdGF0ZSBuYW1lLlxuXHRcdCAqL1xuXHRcdF9sb2FkQ3VycmVudFN0YXRlOiBmdW5jdGlvbigpIHtcblx0XHRcdGlmICghdGhpcy5nZXRJc1ByZXZpZXdFbmFibGVkKCkpIHJldHVybiB0aGlzO1xuXG5cdFx0XHR2YXIgc3RhdGVzID0gdGhpcy5fZ2V0TmF2aWdhdG9yU3RhdGVzKCk7XG5cdFx0XHR2YXIgY3VycmVudFN0YXRlTmFtZSA9IHRoaXMuZ2V0Q3VycmVudFN0YXRlTmFtZSgpO1xuXHRcdFx0dmFyIGN1cnJlbnRTdGF0ZSA9IG51bGw7XG5cblx0XHRcdC8vIEZpbmQgY3VycmVudCBzdGF0ZSB3aXRoaW4gY3VycmVudGx5IGF2YWlsYWJsZSBzdGF0ZXMuXG5cdFx0XHRpZiAoc3RhdGVzKSB7XG5cdFx0XHRcdGN1cnJlbnRTdGF0ZSA9ICQuZ3JlcChzdGF0ZXMsIGZ1bmN0aW9uKHN0YXRlLCBpbmRleCkge1xuXHRcdFx0XHRcdHJldHVybiAoXG5cdFx0XHRcdFx0XHRjdXJyZW50U3RhdGVOYW1lID09PSBzdGF0ZS5uYW1lIHx8XG5cdFx0XHRcdFx0XHQoIWN1cnJlbnRTdGF0ZU5hbWUgJiYgc3RhdGUuYWN0aXZlKVxuXHRcdFx0XHRcdCk7XG5cdFx0XHRcdH0pO1xuXHRcdFx0fVxuXG5cdFx0XHR2YXIgdXJsID0gbnVsbDtcblxuXHRcdFx0aWYgKGN1cnJlbnRTdGF0ZVswXSkge1xuXHRcdFx0XHQvLyBTdGF0ZSBpcyBhdmFpbGFibGUgb24gdGhlIG5ld2x5IGxvYWRlZCBjb250ZW50LiBHZXQgaXQuXG5cdFx0XHRcdHVybCA9IGN1cnJlbnRTdGF0ZVswXS51cmw7XG5cdFx0XHR9IGVsc2UgaWYgKHN0YXRlcy5sZW5ndGgpIHtcblx0XHRcdFx0Ly8gRmFsbCBiYWNrIHRvIHRoZSBmaXJzdCBhdmFpbGFibGUgY29udGVudCBzdGF0ZS5cblx0XHRcdFx0dGhpcy5zZXRDdXJyZW50U3RhdGVOYW1lKHN0YXRlc1swXS5uYW1lKTtcblx0XHRcdFx0dXJsID0gc3RhdGVzWzBdLnVybDtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdC8vIE5vIHN0YXRlIGF2YWlsYWJsZSBhdCBhbGwuXG5cdFx0XHRcdHRoaXMuc2V0Q3VycmVudFN0YXRlTmFtZShudWxsKTtcblx0XHRcdH1cblxuXHRcdFx0Ly8gTWFyayB1cmwgYXMgYSBwcmV2aWV3IHVybCBzbyBpdCBjYW4gZ2V0IHNwZWNpYWwgdHJlYXRtZW50XG4gXHRcdFx0dXJsICs9ICgodXJsLmluZGV4T2YoJz8nKSA9PT0gLTEpID8gJz8nIDogJyYnKSArICdDTVNQcmV2aWV3PTEnO1xuXG5cdFx0XHQvLyBJZiB0aGlzIHByZXZpZXcgcGFuZWwgaXNuJ3QgdmlzaWJsZSBhdCB0aGUgbW9tZW50LCBkZWxheSBsb2FkaW5nIHRoZSBVUkwgdW50aWwgaXQgKG1heWJlKSBpcyBsYXRlclxuXHRcdFx0aWYgKHRoaXMuaXMoJy5jb2x1bW4taGlkZGVuJykpIHtcblx0XHRcdFx0dGhpcy5zZXRQZW5kaW5nVVJMKHVybCk7XG5cdFx0XHRcdHRoaXMuX2xvYWRVcmwoJ2Fib3V0OmJsYW5rJyk7XG5cdFx0XHRcdHRoaXMuX2Jsb2NrKCk7XG5cdFx0XHR9XG5cdFx0XHRlbHNlIHtcblx0XHRcdFx0dGhpcy5zZXRQZW5kaW5nVVJMKG51bGwpO1xuXG5cdFx0XHRcdGlmICh1cmwpIHtcblx0XHRcdFx0XHR0aGlzLl9sb2FkVXJsKHVybCk7XG5cdFx0XHRcdFx0dGhpcy5fdW5ibG9jaygpO1xuXHRcdFx0XHR9XG5cdFx0XHRcdGVsc2Uge1xuXHRcdFx0XHRcdHRoaXMuX2Jsb2NrKCk7XG5cdFx0XHRcdH1cblx0XHRcdH1cblxuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIE1vdmUgdGhlIG5hdmlnYXRvciBmcm9tIHRoZSBjb250ZW50IHRvIHRoZSBwcmV2aWV3IGJhci5cblx0XHQgKi9cblx0XHRfbW92ZU5hdmlnYXRvcjogZnVuY3Rpb24oKSB7XG5cdFx0XHR2YXIgcHJldmlld0VsID0gJCgnLmNtcy1wcmV2aWV3IC5jbXMtcHJldmlldy1jb250cm9scycpO1xuXHRcdFx0dmFyIG5hdmlnYXRvckVsID0gJCgnLmNtcy1lZGl0LWZvcm0gLmNtcy1uYXZpZ2F0b3InKTtcblxuXHRcdFx0aWYgKG5hdmlnYXRvckVsLmxlbmd0aCAmJiBwcmV2aWV3RWwubGVuZ3RoKSB7XG5cdFx0XHRcdC8vIE5hdmlnYXRvciBpcyBhdmFpbGFibGUgLSBpbnN0YWxsIHRoZSBuYXZpZ2F0b3IuXG5cdFx0XHRcdHByZXZpZXdFbC5odG1sKCQoJy5jbXMtZWRpdC1mb3JtIC5jbXMtbmF2aWdhdG9yJykuZGV0YWNoKCkpO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0Ly8gTmF2aWdhdG9yIG5vdCBhdmFpbGFibGUuXG5cdFx0XHRcdHRoaXMuX2Jsb2NrKCk7XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIExvYWRzIHRoZSBtYXRjaGluZyBlZGl0IGZvcm0gZm9yIGEgcGFnZSB2aWV3ZWQgaW4gdGhlIHByZXZpZXcgaWZyYW1lLFxuXHRcdCAqIGJhc2VkIG9uIG1ldGFkYXRhIHNlbnQgYWxvbmcgd2l0aCB0aGlzIGRvY3VtZW50LlxuXHRcdCAqL1xuXHRcdF9sb2FkQ3VycmVudFBhZ2U6IGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYgKCF0aGlzLmdldElzUHJldmlld0VuYWJsZWQoKSkgcmV0dXJuO1xuXG4gICAgICAgICAgICB2YXIgZG9jLFxuICAgICAgICAgICAgICAgIGNvbnRhaW5lckVsID0gJCgnLmNtcy1jb250YWluZXInKTtcbiAgICAgICAgICAgIHRyeSB7XG4gICAgICAgICAgICAgICAgZG9jID0gdGhpcy5maW5kKCdpZnJhbWUnKVswXS5jb250ZW50RG9jdW1lbnQ7XG4gICAgICAgICAgICB9IGNhdGNoIChlKSB7XG4gICAgICAgICAgICAgICAgLy8gaWZyYW1lIGNhbid0IGJlIGFjY2Vzc2VkIC0gbWlnaHQgYmUgc2VjdXJlP1xuICAgICAgICAgICAgICAgIGNvbnNvbGUud2FybignVW5hYmxlIHRvIGFjY2VzcyBpZnJhbWUsIHBvc3NpYmxlIGh0dHBzIG1pcy1tYXRjaCcpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKCFkb2MpIHtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG5cblx0XHRcdC8vIExvYWQgdGhpcyBwYWdlIGluIHRoZSBhZG1pbiBpbnRlcmZhY2UgaWYgYXBwcm9wcmlhdGVcblx0XHRcdHZhciBpZCA9ICQoZG9jKS5maW5kKCdtZXRhW25hbWU9eC1wYWdlLWlkXScpLmF0dHIoJ2NvbnRlbnQnKTsgXG5cdFx0XHR2YXIgZWRpdExpbmsgPSAkKGRvYykuZmluZCgnbWV0YVtuYW1lPXgtY21zLWVkaXQtbGlua10nKS5hdHRyKCdjb250ZW50Jyk7XG5cdFx0XHR2YXIgY29udGVudFBhbmVsID0gJCgnLmNtcy1jb250ZW50Jyk7XG5cdFx0XHRcblx0XHRcdGlmKGlkICYmIGNvbnRlbnRQYW5lbC5maW5kKCc6aW5wdXRbbmFtZT1JRF0nKS52YWwoKSAhPSBpZCkge1xuXHRcdFx0XHQvLyBJZ25vcmUgYmVoYXZpb3VyIHdpdGhvdXQgaGlzdG9yeSBzdXBwb3J0IChhcyB3ZSBuZWVkIGFqYXggbG9hZGluZyBcblx0XHRcdFx0Ly8gZm9yIHRoZSBuZXcgZm9ybSB0byBsb2FkIGluIHRoZSBiYWNrZ3JvdW5kKVxuXHRcdFx0XHQkKCcuY21zLWNvbnRhaW5lcicpLmVudHdpbmUoJy5zcycpLmxvYWRQYW5lbChlZGl0TGluayk7XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFByZXBhcmUgdGhlIGlmcmFtZSBjb250ZW50IGZvciBwcmV2aWV3LlxuXHRcdCAqL1xuXHRcdF9hZGp1c3RJZnJhbWVGb3JQcmV2aWV3OiBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgIHZhciBpZnJhbWUgPSB0aGlzLmZpbmQoJ2lmcmFtZScpWzBdLFxuICAgICAgICAgICAgICAgIGRvYztcbiAgICAgICAgICAgIGlmKCFpZnJhbWUpe1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgICAgICBkb2MgPSBpZnJhbWUuY29udGVudERvY3VtZW50O1xuICAgICAgICAgICAgfSBjYXRjaCAoZSkge1xuICAgICAgICAgICAgICAgIC8vIGlmcmFtZSBjYW4ndCBiZSBhY2Nlc3NlZCAtIG1pZ2h0IGJlIHNlY3VyZT9cbiAgICAgICAgICAgICAgICBjb25zb2xlLndhcm4oJ1VuYWJsZSB0byBhY2Nlc3MgaWZyYW1lLCBwb3NzaWJsZSBodHRwcyBtaXMtbWF0Y2gnKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmKCFkb2MpIHtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG5cblx0XHRcdC8vIE9wZW4gZXh0ZXJuYWwgbGlua3MgaW4gbmV3IHdpbmRvdyB0byBhdm9pZCBcImVzY2FwaW5nXCIgdGhlIGludGVybmFsIHBhZ2UgY29udGV4dCBpbiB0aGUgcHJldmlld1xuXHRcdFx0Ly8gaWZyYW1lLCB3aGljaCBpcyBpbXBvcnRhbnQgdG8gc3RheSBpbiBmb3IgdGhlIENNUyBsb2dpYy5cblx0XHRcdHZhciBsaW5rcyA9IGRvYy5nZXRFbGVtZW50c0J5VGFnTmFtZSgnQScpO1xuXHRcdFx0Zm9yICh2YXIgaSA9IDA7IGkgPCBsaW5rcy5sZW5ndGg7IGkrKykge1xuXHRcdFx0XHR2YXIgaHJlZiA9IGxpbmtzW2ldLmdldEF0dHJpYnV0ZSgnaHJlZicpO1xuXHRcdFx0XHRpZighaHJlZikgY29udGludWU7XG5cdFx0XHRcdFxuXHRcdFx0XHRpZiAoaHJlZi5tYXRjaCgvXmh0dHA6XFwvXFwvLykpIGxpbmtzW2ldLnNldEF0dHJpYnV0ZSgndGFyZ2V0JywgJ19ibGFuaycpO1xuXHRcdFx0fVxuXG5cdFx0XHQvLyBIaWRlIHRoZSBuYXZpZ2F0b3IgZnJvbSB0aGUgcHJldmlldyBpZnJhbWUgYW5kIHVzZSBvbmx5IHRoZSBDTVMgb25lLlxuXHRcdFx0dmFyIG5hdmkgPSBkb2MuZ2V0RWxlbWVudEJ5SWQoJ1NpbHZlclN0cmlwZU5hdmlnYXRvcicpO1xuXHRcdFx0aWYobmF2aSkgbmF2aS5zdHlsZS5kaXNwbGF5ID0gJ25vbmUnO1xuXHRcdFx0dmFyIG5hdmlNc2cgPSBkb2MuZ2V0RWxlbWVudEJ5SWQoJ1NpbHZlclN0cmlwZU5hdmlnYXRvck1lc3NhZ2UnKTtcblx0XHRcdGlmKG5hdmlNc2cpIG5hdmlNc2cuc3R5bGUuZGlzcGxheSA9ICdub25lJztcblxuXHRcdFx0Ly8gVHJpZ2dlciBleHRlbnNpb25zLlxuXHRcdFx0dGhpcy50cmlnZ2VyKCdhZnRlcklmcmFtZUFkanVzdGVkRm9yUHJldmlldycsIFsgZG9jIF0pO1xuXHRcdH1cblx0fSk7XG5cblx0JCgnLmNtcy1lZGl0LWZvcm0nKS5lbnR3aW5lKHtcblx0XHRvbmFkZDogZnVuY3Rpb24oKSB7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdFx0JCgnLmNtcy1wcmV2aWV3JykuX2luaXRpYWxpc2VGcm9tQ29udGVudCgpO1xuXHRcdH1cblx0fSk7XG5cdFxuXHQvKipcblx0ICogXCJQcmV2aWV3IHN0YXRlXCIgZnVuY3Rpb25zLlxuXHQgKiAtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tXG5cdCAqL1xuXHQkKCcuY21zLXByZXZpZXctc3RhdGVzJykuZW50d2luZSh7XG5cdFx0LyoqXG5cdFx0ICogQ2hhbmdlIHRoZSBhcHBlYXJhbmNlIG9mIHRoZSBzdGF0ZSBzZWxlY3Rvci5cblx0XHQgKi9cblx0XHRjaGFuZ2VWaXNpYmxlU3RhdGU6IGZ1bmN0aW9uKHN0YXRlKSB7XG5cdFx0XHR0aGlzLmZpbmQoJ2lucHV0W2RhdGEtbmFtZT1cIicrc3RhdGUrJ1wiXScpLnByb3AoJ2NoZWNrZWQnLCB0cnVlKTtcblx0XHR9XG5cdH0pO1xuXG5cdCQoJy5jbXMtcHJldmlldy1zdGF0ZXMgLnN0YXRlLW5hbWUnKS5lbnR3aW5lKHtcblx0XHQvKipcblx0XHQgKiBSZWFjdHMgdG8gdGhlIHVzZXIgY2hhbmdpbmcgdGhlIHN0YXRlIG9mIHRoZSBwcmV2aWV3LlxuXHRcdCAqL1xuXHRcdG9uY2xpY2s6IGZ1bmN0aW9uKGUpIHtcdFxuXHRcdFx0Ly9BZGQgYW5kIHJlbW92ZSBjbGFzc2VzIHRvIG1ha2Ugc3dpdGNoIHdvcmsgb2sgaW4gb2xkIElFXG5cdFx0XHR0aGlzLnBhcmVudCgpLmZpbmQoJy5hY3RpdmUnKS5yZW1vdmVDbGFzcygnYWN0aXZlJyk7XG5cdFx0XHR0aGlzLm5leHQoJ2xhYmVsJykuYWRkQ2xhc3MoJ2FjdGl2ZScpO1xuXG5cdFx0XHR2YXIgdGFyZ2V0U3RhdGVOYW1lID0gJCh0aGlzKS5hdHRyKCdkYXRhLW5hbWUnKTtcblx0XHRcdC8vIFJlbG9hZCBwcmV2aWV3IHdpdGggdGhlIHNlbGVjdGVkIHN0YXRlLlxuXHRcdFx0JCgnLmNtcy1wcmV2aWV3JykuY2hhbmdlU3RhdGUodGFyZ2V0U3RhdGVOYW1lKTtcdFx0XHRcdFxuXHRcdH1cblx0fSk7XHRcblx0XG5cdC8qKlxuXHQgKiBcIlByZXZpZXcgbW9kZVwiIGZ1bmN0aW9uc1xuXHQgKiAtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tXG5cdCAqL1xuXHQkKCcucHJldmlldy1tb2RlLXNlbGVjdG9yJykuZW50d2luZSh7XG5cdFx0LyoqXG5cdFx0ICogQ2hhbmdlIHRoZSBhcHBlYXJhbmNlIG9mIHRoZSBtb2RlIHNlbGVjdG9yLlxuXHRcdCAqL1xuXHRcdGNoYW5nZVZpc2libGVNb2RlOiBmdW5jdGlvbihtb2RlKSB7XG5cdFx0XHR0aGlzLmZpbmQoJ3NlbGVjdCcpXG5cdFx0XHRcdC52YWwobW9kZSlcblx0XHRcdFx0LnRyaWdnZXIoJ2xpc3p0OnVwZGF0ZWQnKVxuXHRcdFx0XHQuX2FkZEljb24oKTtcblx0XHR9XG5cdH0pO1xuXG5cdCQoJy5wcmV2aWV3LW1vZGUtc2VsZWN0b3Igc2VsZWN0JykuZW50d2luZSh7XG5cdFx0LyoqXG5cdFx0ICogUmVhY3RzIHRvIHRoZSB1c2VyIGNoYW5naW5nIHRoZSBwcmV2aWV3IG1vZGUuXG5cdFx0ICovXG5cdFx0b25jaGFuZ2U6IGZ1bmN0aW9uKGUpIHtcdFx0XHRcdFxuXHRcdFx0dGhpcy5fc3VwZXIoZSk7XG5cdFx0XHRlLnByZXZlbnREZWZhdWx0KCk7XG5cblx0XHRcdHZhciB0YXJnZXRTdGF0ZU5hbWUgPSAkKHRoaXMpLnZhbCgpO1xuXHRcdFx0JCgnLmNtcy1wcmV2aWV3JykuY2hhbmdlTW9kZSh0YXJnZXRTdGF0ZU5hbWUpO1xuXHRcdH1cblx0fSk7XG5cblx0XG5cdCQoJy5wcmV2aWV3LW1vZGUtc2VsZWN0b3IgLmNoem4tcmVzdWx0cyBsaScpLmVudHdpbmUoe1xuXHRcdC8qKlxuXHRcdCAqICBJRTggZG9lc24ndCBzdXBwb3J0IHByb2dyYW1hdGljIGFjY2VzcyB0byBvbmNoYW5nZSBldmVudCBcblx0XHQgKlx0c28gcmVhY3Qgb24gY2xpY2tcblx0XHQgKi9cblx0XHRvbmNsaWNrOmZ1bmN0aW9uKGUpe1xuXHRcdFx0aWYgKCQuYnJvd3Nlci5tc2llKSB7XG5cdFx0XHRcdGUucHJldmVudERlZmF1bHQoKTtcdFx0XHRcdFx0XG5cdFx0XHRcdHZhciBpbmRleCA9IHRoaXMuaW5kZXgoKTtcblx0XHRcdFx0dmFyIHRhcmdldFN0YXRlTmFtZSA9IHRoaXMuY2xvc2VzdCgnLnByZXZpZXctbW9kZS1zZWxlY3RvcicpLmZpbmQoJ3NlbGVjdCBvcHRpb246ZXEoJytpbmRleCsnKScpLnZhbCgpO1x0XHRcdFx0XHRcblx0XHRcdFx0XHRcdFx0XHRcdFx0XHRcdFxuXHRcdFx0XHQvL3ZhciB0YXJnZXRTdGF0ZU5hbWUgPSAkKHRoaXMpLnZhbCgpO1xuXHRcdFx0XHQkKCcuY21zLXByZXZpZXcnKS5jaGFuZ2VNb2RlKHRhcmdldFN0YXRlTmFtZSk7XG5cdFx0XHR9XG5cdFx0fVxuXHR9KTtcblx0XG5cdC8qKlxuXHQgKiBBZGp1c3QgdGhlIHZpc2liaWxpdHkgb2YgdGhlIHByZXZpZXctbW9kZSBzZWxlY3RvciBpbiB0aGUgQ01TIHBhcnQgKGhpZGRlbiBpZiBwcmV2aWV3IGlzIHZpc2libGUpLlxuXHQgKi9cblx0JCgnLmNtcy1wcmV2aWV3LmNvbHVtbi1oaWRkZW4nKS5lbnR3aW5lKHtcblx0XHRvbm1hdGNoOiBmdW5jdGlvbigpIHtcblx0XHRcdCQoJyNwcmV2aWV3LW1vZGUtZHJvcGRvd24taW4tY29udGVudCcpLnNob3coKTtcblx0XHRcdC8vIEFsZXJ0IHRoZSB1c2VyIGFzIHRvIHdoeSB0aGUgcHJldmlldyBpcyBoaWRkZW5cblx0XHRcdGlmICgkKCcuY21zLXByZXZpZXcgLnJlc3VsdC1zZWxlY3RlZCcpLmhhc0NsYXNzKCdmb250LWljb24tY29sdW1ucycpKSB7XG5cdFx0XHRcdHN0YXR1c01lc3NhZ2UoaTE4bi5fdChcblx0XHRcdFx0XHQnTGVmdEFuZE1haW4uRElTQUJMRVNQTElUVklFVycsXG5cdFx0XHRcdFx0XCJTY3JlZW4gdG9vIHNtYWxsIHRvIHNob3cgc2l0ZSBwcmV2aWV3IGluIHNwbGl0IG1vZGVcIiksXG5cdFx0XHRcdFwiZXJyb3JcIik7XG5cdFx0XHR9XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdH0sXG5cblx0XHRvbnVubWF0Y2g6IGZ1bmN0aW9uKCkge1xuXHRcdFx0JCgnI3ByZXZpZXctbW9kZS1kcm9wZG93bi1pbi1jb250ZW50JykuaGlkZSgpO1xuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9XG5cdH0pO1xuXG5cdC8qKlxuXHQgKiBJbml0aWFsaXNlIHRoZSBwcmV2aWV3LW1vZGUgc2VsZWN0b3IgaW4gdGhlIENNUyBwYXJ0IChjb3VsZCBiZSBoaWRkZW4gaWYgcHJldmlldyBpcyB2aXNpYmxlKS5cblx0ICovXG5cdCQoJyNwcmV2aWV3LW1vZGUtZHJvcGRvd24taW4tY29udGVudCcpLmVudHdpbmUoe1xuXHRcdG9ubWF0Y2g6IGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYgKCQoJy5jbXMtcHJldmlldycpLmlzKCcuY29sdW1uLWhpZGRlbicpKSB7XG5cdFx0XHRcdHRoaXMuc2hvdygpO1xuXHRcdFx0fVxuXHRcdFx0ZWxzZSB7XG5cdFx0XHRcdHRoaXMuaGlkZSgpO1xuXHRcdFx0fVxuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9LFxuXHRcdG9udW5tYXRjaDogZnVuY3Rpb24oKSB7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdH1cblx0fSk7XG5cblx0LyoqXG5cdCAqIFwiUHJldmlldyBzaXplXCIgZnVuY3Rpb25zXG5cdCAqIC0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS1cblx0ICovXG5cdCQoJy5wcmV2aWV3LXNpemUtc2VsZWN0b3InKS5lbnR3aW5lKHtcblx0XHQvKipcblx0XHQgKiBDaGFuZ2UgdGhlIGFwcGVhcmFuY2Ugb2YgdGhlIHNpemUgc2VsZWN0b3IuXG5cdFx0ICovXG5cdFx0Y2hhbmdlVmlzaWJsZVNpemU6IGZ1bmN0aW9uKHNpemUpIHtcdFx0XHRcdFxuXHRcdFx0dGhpcy5maW5kKCdzZWxlY3QnKVxuXHRcdFx0XHQudmFsKHNpemUpXG5cdFx0XHRcdC50cmlnZ2VyKCdsaXN6dDp1cGRhdGVkJylcblx0XHRcdFx0Ll9hZGRJY29uKCk7XG5cdFx0fVxuXHR9KTtcblxuXHQkKCcucHJldmlldy1zaXplLXNlbGVjdG9yIHNlbGVjdCcpLmVudHdpbmUoe1xuXHRcdC8qKlxuXHRcdCAqIFRyaWdnZXIgY2hhbmdlIGluIHRoZSBwcmV2aWV3IHNpemUuXG5cdFx0ICovXG5cdFx0b25jaGFuZ2U6IGZ1bmN0aW9uKGUpIHtcblx0XHRcdGUucHJldmVudERlZmF1bHQoKTtcblxuXHRcdFx0dmFyIHRhcmdldFNpemVOYW1lID0gJCh0aGlzKS52YWwoKTtcblx0XHRcdCQoJy5jbXMtcHJldmlldycpLmNoYW5nZVNpemUodGFyZ2V0U2l6ZU5hbWUpO1xuXHRcdH1cblx0fSk7XG5cblx0XG5cdC8qKlxuXHQgKiBcIkNob3NlblwiIHBsdW1iaW5nLlxuXHQgKiAtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tXG5cdCAqL1xuXG5cdC8qXG5cdCpcdEFkZCBhIGNsYXNzIHRvIHRoZSBjaHpuIHNlbGVjdCB0cmlnZ2VyIGJhc2VkIG9uIHRoZSBjdXJyZW50bHkgXG5cdCpcdHNlbGVjdGVkIG9wdGlvbi4gVXBkYXRlIGFzIHRoaXMgY2hhbmdlc1xuXHQqL1xuXHQkKCcucHJldmlldy1zZWxlY3RvciBzZWxlY3QucHJldmlldy1kcm9wZG93bicpLmVudHdpbmUoe1xuXHRcdCdvbmxpc3p0OnNob3dpbmdfZHJvcGRvd24nOiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMuc2libGluZ3MoKS5maW5kKCcuY2h6bi1kcm9wJykuYWRkQ2xhc3MoJ29wZW4nKS5fYWxpZ25SaWdodCgpO1xuXHRcdH0sXG5cblx0XHQnb25saXN6dDpoaWRpbmdfZHJvcGRvd24nOiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMuc2libGluZ3MoKS5maW5kKCcuY2h6bi1kcm9wJykucmVtb3ZlQ2xhc3MoJ29wZW4nKS5fcmVtb3ZlUmlnaHRBbGlnbigpO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBUcmlnZ2VyIGFkZGl0aW9uYWwgaW5pdGlhbCBpY29uIHVwZGF0ZSB3aGVuIHRoZSBjb250cm9sIGlzIGZ1bGx5IGxvYWRlZC5cblx0XHQgKiBTb2x2ZXMgYW4gSUU4IHRpbWluZyBpc3N1ZS5cblx0XHQgKi9cblx0XHQnb25saXN6dDpyZWFkeSc6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHRcdHRoaXMuX2FkZEljb24oKTtcblx0XHR9LFxuXG5cdFx0X2FkZEljb246IGZ1bmN0aW9uKCl7XG5cdFx0XHR2YXIgc2VsZWN0ZWQgPSB0aGlzLmZpbmQoJzpzZWxlY3RlZCcpO1x0XHRcdFx0XG5cdFx0XHR2YXIgaWNvbkNsYXNzID0gc2VsZWN0ZWQuYXR0cignZGF0YS1pY29uJyk7XHRcblx0XHRcdFx0XHRcdFx0XG5cdFx0XHR2YXIgdGFyZ2V0ID0gdGhpcy5wYXJlbnQoKS5maW5kKCcuY2h6bi1jb250YWluZXIgYS5jaHpuLXNpbmdsZScpO1xuXHRcdFx0dmFyIG9sZEljb24gPSB0YXJnZXQuYXR0cignZGF0YS1pY29uJyk7XG5cdFx0XHRpZih0eXBlb2Ygb2xkSWNvbiAhPT0gJ3VuZGVmaW5lZCcpe1xuXHRcdFx0XHR0YXJnZXQucmVtb3ZlQ2xhc3Mob2xkSWNvbik7XG5cdFx0XHR9XG5cdFx0XHR0YXJnZXQuYWRkQ2xhc3MoaWNvbkNsYXNzKTtcblx0XHRcdHRhcmdldC5hdHRyKCdkYXRhLWljb24nLCBpY29uQ2xhc3MpO1x0XHRcdFx0XG5cblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH1cblx0fSk7XG5cblx0JCgnLnByZXZpZXctc2VsZWN0b3IgLmNoem4tZHJvcCcpLmVudHdpbmUoe1xuXHRcdF9hbGlnblJpZ2h0OiBmdW5jdGlvbigpe1xuXHRcdFx0dmFyIHRoYXQgPSB0aGlzO1xuXHRcdFx0JCh0aGlzKS5oaWRlKCk7XG5cdFx0XHQvKiBEZWxheSBzbyBzdHlsZXMgYXBwbGllZCBhZnRlciBjaG9zZW4gYXBwbGllcyBjc3NcdFxuXHRcdFx0ICAgKHRoZSBsaW5lIGFmdGVyIHdlIGZpbmQgb3V0IHRoZSBkcm9wZG93biBpcyBvcGVuKVxuXHRcdFx0Ki9cblx0XHRcdHNldFRpbWVvdXQoZnVuY3Rpb24oKXsgXG5cdFx0XHRcdCQodGhhdCkuY3NzKHtsZWZ0OidhdXRvJywgcmlnaHQ6MH0pO1xuXHRcdFx0XHQkKHRoYXQpLnNob3coKTtcdFxuXHRcdFx0fSwgMTAwKTtcdFx0XHRcdFx0XHRcdFxuXHRcdH0sXG5cdFx0X3JlbW92ZVJpZ2h0QWxpZ246ZnVuY3Rpb24oKXtcblx0XHRcdCQodGhpcykuY3NzKHtyaWdodDonYXV0byd9KTtcblx0XHR9XG5cblx0fSk7XG5cblx0LyogXG5cdCogTWVhbnMgb2YgaGF2aW5nIGV4dHJhIHN0eWxlZCBkYXRhIGluIGNoem4gJ3ByZXZpZXctc2VsZWN0b3InIHNlbGVjdHMgXG5cdCogV2hlbiBjaHpuIHVsIGlzIHJlYWR5LCBncmFiIGRhdGEtZGVzY3JpcHRpb24gZnJvbSBvcmlnaW5hbCBzZWxlY3QuIFxuXHQqIElmIGl0IGV4aXN0cywgYXBwZW5kIHRvIG9wdGlvbiBhbmQgYWRkIGRlc2NyaXB0aW9uIGNsYXNzIHRvIGxpc3QgaXRlbVxuXHQqL1xuXHQvKlxuXG5cdEN1cnJlbnRseSBidWdneSAoYWRkcyBkZXhjcmlwdGlvbiwgdGhlbiByZS1yZW5kZXJzKS4gVGhpcyBtYXkgbmVlZCB0byBcblx0YmUgZG9uZSBpbnNpZGUgY2hvc2VuLiBDaG9zZW4gcmVjb21tZW5kcyB0byBkbyB0aGlzIHN0dWZmIGluIHRoZSBjc3MsIFxuXHRidXQgdGhhdCBvcHRpb24gaXMgaW5hY2Nlc3NpYmxlIGFuZCB1bnRyYW5zbGF0YWJsZSBcblx0KGh0dHBzOi8vZ2l0aHViLmNvbS9oYXJ2ZXN0aHEvY2hvc2VuL2lzc3Vlcy8zOTkpXG5cblx0JCgnLnByZXZpZXctc2VsZWN0b3IgLmNoem4tZHJvcCB1bCcpLmVudHdpbmUoe1xuXHRcdG9ubWF0Y2g6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dGhpcy5leHRyYURhdGEoKTtcblx0XHRcdHRoaXMuX3N1cGVyKCk7XG5cdFx0fSxcblx0XHRvbnVubWF0Y2g6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9LFxuXHRcdGV4dHJhRGF0YTogZnVuY3Rpb24oKXtcblx0XHRcdHZhciB0aGF0ID0gdGhpcztcblx0XHRcdHZhciBvcHRpb25zID0gdGhpcy5jbG9zZXN0KCcucHJldmlldy1zZWxlY3RvcicpLmZpbmQoJ3NlbGVjdCBvcHRpb24nKTtcdFxuXHRcdFx0XHRcblx0XHRcdCQuZWFjaChvcHRpb25zLCBmdW5jdGlvbihpbmRleCwgb3B0aW9uKXtcblx0XHRcdFx0dmFyIHRhcmdldCA9ICQodGhhdCkuZmluZChcImxpOmVxKFwiICsgaW5kZXggKyBcIilcIik7XG5cdFx0XHRcdHZhciBkZXNjcmlwdGlvbiA9ICQob3B0aW9uKS5hdHRyKCdkYXRhLWRlc2NyaXB0aW9uJyk7XG5cdFx0XHRcdGlmKGRlc2NyaXB0aW9uICE9IHVuZGVmaW5lZCAmJiAhJCh0YXJnZXQpLmhhc0NsYXNzKCdkZXNjcmlwdGlvbicpKXtcblx0XHRcdFx0XHQkKHRhcmdldCkuYXBwZW5kKCc8c3Bhbj4nICsgZGVzY3JpcHRpb24gKyAnPC9zcGFuPicpO1xuXHRcdFx0XHRcdCQodGFyZ2V0KS5hZGRDbGFzcygnZGVzY3JpcHRpb24nKTtcdFx0XHRcdFx0XHRcblx0XHRcdFx0fVxuXHRcdFx0fSk7XG5cdFx0fVxuXHR9KTsgKi9cblxuXHQkKCcucHJldmlldy1tb2RlLXNlbGVjdG9yIC5jaHpuLWRyb3AgbGk6bGFzdC1jaGlsZCcpLmVudHdpbmUoe1xuXHRcdG9ubWF0Y2g6IGZ1bmN0aW9uICgpIHtcblx0XHRcdGlmICgkKCcucHJldmlldy1tb2RlLXNlbGVjdG9yJykuaGFzQ2xhc3MoJ3NwbGl0LWRpc2FibGVkJykpIHtcblx0XHRcdFx0dGhpcy5wYXJlbnQoKS5hcHBlbmQoJzxkaXYgY2xhc3M9XCJkaXNhYmxlZC10b29sdGlwXCI+PC9kaXY+Jyk7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHR0aGlzLnBhcmVudCgpLmFwcGVuZCgnPGRpdiBjbGFzcz1cImRpc2FibGVkLXRvb2x0aXBcIiBzdHlsZT1cImRpc3BsYXk6IG5vbmU7XCI+PC9kaXY+Jyk7XG5cdFx0XHR9XG5cdFx0fVxuXHR9KTtcblxuXHQvKipcblx0ICogUmVjYWxjdWxhdGUgdGhlIHByZXZpZXcgc3BhY2UgdG8gYWxsb3cgZm9yIGhvcml6b250YWwgc2Nyb2xsYmFyIGFuZCB0aGUgcHJldmlldyBhY3Rpb25zIHBhbmVsXG5cdCAqL1xuXHQkKCcucHJldmlldy1zY3JvbGwnKS5lbnR3aW5lKHtcblx0XHQvKipcblx0XHQgKiBIZWlnaHQgb2YgdGhlIHByZXZpZXcgYWN0aW9ucyBwYW5lbFxuXHRcdCAqL1xuXHRcdFRvb2xiYXJTaXplOiA1MyxcblxuXHRcdF9yZWRyYXc6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dmFyIHRvb2xiYXJTaXplID0gdGhpcy5nZXRUb29sYmFyU2l6ZSgpO1xuXG5cdFx0XHRpZih3aW5kb3cuZGVidWcpIGNvbnNvbGUubG9nKCdyZWRyYXcnLCB0aGlzLmF0dHIoJ2NsYXNzJyksIHRoaXMuZ2V0KDApKTtcblx0XHRcdHZhciBwcmV2aWV3SGVpZ2h0ID0gKHRoaXMuaGVpZ2h0KCkgLSB0b29sYmFyU2l6ZSk7XG5cdFx0XHR0aGlzLmhlaWdodChwcmV2aWV3SGVpZ2h0KTtcblx0XHR9LCBcblxuXHRcdG9ubWF0Y2g6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dGhpcy5fcmVkcmF3KCk7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdH0sXG5cblx0XHRvbnVubWF0Y2g6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9XG5cdFx0Ly8gVE9ETzogTmVlZCB0byByZWNhbGN1bGF0ZSBvbiByZXNpemUgb2YgYnJvd3NlclxuXG5cdH0pO1xuXG5cdC8qKlxuXHQgKiBSb3RhdGUgcHJldmlldyB0byBsYW5kc2NhcGVcblx0ICovXG5cdCQoJy5wcmV2aWV3LWRldmljZS1vdXRlcicpLmVudHdpbmUoe1xuXHRcdG9uY2xpY2s6IGZ1bmN0aW9uICgpIHtcblx0XHRcdHRoaXMudG9nZ2xlQ2xhc3MoJ3JvdGF0ZScpO1xuXHRcdH1cblx0fSk7XG59KTtcbiIsIi8qKlxuICogRmlsZTogTGVmdEFuZE1haW4uVHJlZS5qc1xuICovXG5cbmltcG9ydCAkIGZyb20gJ2pRdWVyeSc7XG5cbiQuZW50d2luZSgnc3MudHJlZScsIGZ1bmN0aW9uKCQpe1xuXG5cdCQoJy5jbXMtdHJlZScpLmVudHdpbmUoe1xuXHRcdFxuXHRcdEhpbnRzOiBudWxsLFxuXG5cdFx0SXNVcGRhdGluZ1RyZWU6IGZhbHNlLFxuXG5cdFx0SXNMb2FkZWQ6IGZhbHNlLFxuXG5cdFx0b25hZGQ6IGZ1bmN0aW9uKCl7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXG5cdFx0XHQvLyBEb24ndCByZWFwcGx5IChleHBlbnNpdmUpIHRyZWUgYmVoYXZpb3VyIGlmIGFscmVhZHkgcHJlc2VudFxuXHRcdFx0aWYoJC5pc051bWVyaWModGhpcy5kYXRhKCdqc3RyZWVfaW5zdGFuY2VfaWQnKSkpIHJldHVybjtcblx0XHRcdFxuXHRcdFx0dmFyIGhpbnRzID0gdGhpcy5hdHRyKCdkYXRhLWhpbnRzJyk7XG5cdFx0XHRpZihoaW50cykgdGhpcy5zZXRIaW50cygkLnBhcnNlSlNPTihoaW50cykpO1xuXHRcdFx0XG5cdFx0XHQvKipcblx0XHRcdCAqIEB0b2RvIEljb24gYW5kIHBhZ2UgdHlwZSBob3ZlciBzdXBwb3J0XG5cdFx0XHQgKiBAdG9kbyBTb3J0aW5nIG9mIHN1YiBub2RlcyAob3JpZ2luYWxseSBwbGFjZWQgaW4gY29udGV4dCBtZW51KVxuXHRcdFx0ICogQHRvZG8gQXV0b21hdGljIGxvYWQgb2YgZnVsbCBzdWJ0cmVlIHZpYSBhamF4IG9uIG5vZGUgY2hlY2tib3ggc2VsZWN0aW9uIChtaW5Ob2RlQ291bnQgPSAwKVxuXHRcdFx0ICogIHRvIGF2b2lkIGRvaW5nIHBhcnRpYWwgc2VsZWN0aW9uIHdpdGggXCJoaWRkZW4gbm9kZXNcIiAodW5sb2FkZWQgbWFya3VwKVxuXHRcdFx0ICogQHRvZG8gRGlzYWxsb3cgZHJhZyduJ2Ryb3Agd2hlbiBub2RlIGhhcyBcIm5vQ2hpbGRyZW5cIiBzZXQgKHNlZSBzaXRlVHJlZUhpbnRzKVxuXHRcdFx0ICogQHRvZG8gRGlzYWxsb3cgbW92aW5nIG9mIHBhZ2VzIG1hcmtlZCBhcyBkZWxldGVkIFxuXHRcdFx0ICogIG1vc3QgbGlrZWx5IGJ5IHNlcnZlciByZXNwb25zZSBjb2RlcyByYXRoZXIgdGhhbiBjbGllbnRzaWRlXG5cdFx0XHQgKiBAdG9kbyBcImRlZmF1bHRDaGlsZFwiIHdoZW4gY3JlYXRpbmcgYSBwYWdlIChzaXRldHJlZUhpbnRzKVxuXHRcdFx0ICogQHRvZG8gRHVwbGljYXRlIHBhZ2UgKG9yaWdpbmFsbHkgbG9jYXRlZCBpbiBjb250ZXh0IG1lbnUpXG5cdFx0XHQgKiBAdG9kbyBVcGRhdGUgdHJlZSBub2RlIHRpdGxlIGluZm9ybWF0aW9uIGFuZCBtb2RpZmllZCBzdGF0ZSBhZnRlciByZW9yZGVyaW5nIChyZXNwb25zZSBpcyBhIEpTT04gYXJyYXkpXG5cdFx0XHQgKiBcblx0XHRcdCAqIFRhc2tzIG1vc3QgbGlrZWx5IG5vdCByZXF1aXJlZCBhZnRlciBtb3ZpbmcgdG8gYSBzdGFuZGFsb25lIHRyZWU6XG5cdFx0XHQgKiBcblx0XHRcdCAqIEB0b2RvIENvbnRleHQgbWVudSAtIHRvIGJlIHJlcGxhY2VkIGJ5IGEgYmV6ZWwgVUlcblx0XHRcdCAqIEB0b2RvIFJlZnJlc2ggZm9ybSBmb3Igc2VsZWN0ZWQgdHJlZSBub2RlIGlmIGFmZmVjdGVkIGJ5IHJlb3JkZXJpbmcgKG5ldyBwYXJlbnQgcmVsYXRpb25zaGlwKVxuXHRcdFx0ICogQHRvZG8gQ2FuY2VsIGN1cnJlbnQgZm9ybSBsb2FkIHZpYSBhamF4IHdoZW4gbmV3IGxvYWQgaXMgcmVxdWVzdGVkIChzeW5jaHJvbm91cyBsb2FkaW5nKVxuXHRcdFx0ICovXG5cdFx0XHR2YXIgc2VsZiA9IHRoaXM7XG5cdFx0XHRcdHRoaXNcblx0XHRcdFx0XHQuanN0cmVlKHRoaXMuZ2V0VHJlZUNvbmZpZygpKVxuXHRcdFx0XHRcdC5iaW5kKCdsb2FkZWQuanN0cmVlJywgZnVuY3Rpb24oZSwgZGF0YSkge1xuXHRcdFx0XHRcdFx0c2VsZi5zZXRJc0xvYWRlZCh0cnVlKTtcblxuXHRcdFx0XHRcdFx0Ly8gQWRkIGFqYXggc2V0dGluZ3MgYWZ0ZXIgaW5pdCBwZXJpb2QgdG8gYXZvaWQgdW5uZWNlc3NhcnkgaW5pdGlhbCBhamF4IGxvYWRcblx0XHRcdFx0XHRcdC8vIG9mIGV4aXN0aW5nIHRyZWUgaW4gRE9NIC0gc2VlIGxvYWRfbm9kZV9odG1sKClcblx0XHRcdFx0XHRcdGRhdGEuaW5zdC5fc2V0X3NldHRpbmdzKHsnaHRtbF9kYXRhJzogeydhamF4Jzoge1xuXHRcdFx0XHRcdFx0XHQndXJsJzogc2VsZi5kYXRhKCd1cmxUcmVlJyksXG5cdFx0XHRcdFx0XHRcdCdkYXRhJzogZnVuY3Rpb24obm9kZSkge1xuXHRcdFx0XHRcdFx0XHRcdHZhciBwYXJhbXMgPSBzZWxmLmRhdGEoJ3NlYXJjaHBhcmFtcycpIHx8IFtdO1xuXHRcdFx0XHRcdFx0XHRcdC8vIEF2b2lkIGR1cGxpY2F0aW9uIG9mIHBhcmFtZXRlcnNcblx0XHRcdFx0XHRcdFx0XHRwYXJhbXMgPSAkLmdyZXAocGFyYW1zLCBmdW5jdGlvbihuLCBpKSB7cmV0dXJuIChuLm5hbWUgIT0gJ0lEJyAmJiBuLm5hbWUgIT0gJ3ZhbHVlJyk7fSk7XG5cdFx0XHRcdFx0XHRcdFx0cGFyYW1zLnB1c2goe25hbWU6ICdJRCcsIHZhbHVlOiAkKG5vZGUpLmRhdGEoXCJpZFwiKSA/ICQobm9kZSkuZGF0YShcImlkXCIpIDogMH0pO1xuXHRcdFx0XHRcdFx0XHRcdHBhcmFtcy5wdXNoKHtuYW1lOiAnYWpheCcsIHZhbHVlOiAxfSk7XG5cdFx0XHRcdFx0XHRcdFx0cmV0dXJuIHBhcmFtcztcblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0fX19KTtcblxuXHRcdFx0XHRcdFx0c2VsZi51cGRhdGVGcm9tRWRpdEZvcm0oKTtcblx0XHRcdFx0XHRcdHNlbGYuY3NzKCd2aXNpYmlsaXR5JywgJ3Zpc2libGUnKTtcblx0XHRcdFx0XHRcdFxuXHRcdFx0XHRcdFx0Ly8gT25seSBzaG93IGNoZWNrYm94ZXMgd2l0aCAubXVsdGlwbGUgY2xhc3Ncblx0XHRcdFx0XHRcdGRhdGEuaW5zdC5oaWRlX2NoZWNrYm94ZXMoKTtcblx0XHRcdFx0XHR9KVxuXHRcdFx0XHRcdC5iaW5kKCdiZWZvcmUuanN0cmVlJywgZnVuY3Rpb24oZSwgZGF0YSkge1xuXHRcdFx0XHRcdFx0aWYoZGF0YS5mdW5jID09ICdzdGFydF9kcmFnJykge1xuXHRcdFx0XHRcdFx0XHQvLyBEb24ndCBhbGxvdyBkcmFnJ24nZHJvcCBpZiBtdWx0aS1zZWxlY3QgaXMgZW5hYmxlZCdcblx0XHRcdFx0XHRcdFx0aWYoIXNlbGYuaGFzQ2xhc3MoJ2RyYWdnYWJsZScpIHx8IHNlbGYuaGFzQ2xhc3MoJ211bHRpc2VsZWN0JykpIHtcblx0XHRcdFx0XHRcdFx0XHRlLnN0b3BJbW1lZGlhdGVQcm9wYWdhdGlvbigpO1xuXHRcdFx0XHRcdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XG5cdFx0XHRcdFx0XHRpZigkLmluQXJyYXkoZGF0YS5mdW5jLCBbJ2NoZWNrX25vZGUnLCAndW5jaGVja19ub2RlJ10pKSB7XG5cdFx0XHRcdFx0XHRcdC8vIGRvbid0IGFsbG93IGNoZWNrIGFuZCB1bmNoZWNrIGlmIHBhcmVudCBpcyBkaXNhYmxlZFxuXHRcdFx0XHRcdFx0XHR2YXIgbm9kZSA9ICQoZGF0YS5hcmdzWzBdKS5wYXJlbnRzKCdsaTpmaXJzdCcpO1xuXHRcdFx0XHRcdFx0XHR2YXIgYWxsb3dlZENoaWxkcmVuID0gbm9kZS5maW5kKCdsaTpub3QoLmRpc2FibGVkKScpO1xuXG5cdFx0XHRcdFx0XHRcdC8vIGlmIHRoZXJlIGFyZSBjaGlsZCBub2RlcyB0aGF0IGFyZW4ndCBkaXNhYmxlZCwgYWxsb3cgZXhwYW5kaW5nIHRoZSB0cmVlXG5cdFx0XHRcdFx0XHRcdGlmKG5vZGUuaGFzQ2xhc3MoJ2Rpc2FibGVkJykgJiYgYWxsb3dlZENoaWxkcmVuID09IDApIHtcblx0XHRcdFx0XHRcdFx0XHRlLnN0b3BJbW1lZGlhdGVQcm9wYWdhdGlvbigpO1xuXHRcdFx0XHRcdFx0XHRcdHJldHVybiBmYWxzZTtcblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdH0pXG5cdFx0XHRcdFx0LmJpbmQoJ21vdmVfbm9kZS5qc3RyZWUnLCBmdW5jdGlvbihlLCBkYXRhKSB7XG5cdFx0XHRcdFx0XHRpZihzZWxmLmdldElzVXBkYXRpbmdUcmVlKCkpIHJldHVybjtcblxuXHRcdFx0XHRcdFx0dmFyIG1vdmVkTm9kZSA9IGRhdGEucnNsdC5vLCBuZXdQYXJlbnROb2RlID0gZGF0YS5yc2x0Lm5wLCBvbGRQYXJlbnROb2RlID0gZGF0YS5pbnN0Ll9nZXRfcGFyZW50KG1vdmVkTm9kZSksIG5ld1BhcmVudElEID0gJChuZXdQYXJlbnROb2RlKS5kYXRhKCdpZCcpIHx8IDAsIG5vZGVJRCA9ICQobW92ZWROb2RlKS5kYXRhKCdpZCcpO1xuXHRcdFx0XHRcdFx0dmFyIHNpYmxpbmdJRHMgPSAkLm1hcCgkKG1vdmVkTm9kZSkuc2libGluZ3MoKS5hbmRTZWxmKCksIGZ1bmN0aW9uKGVsKSB7XG5cdFx0XHRcdFx0XHRcdHJldHVybiAkKGVsKS5kYXRhKCdpZCcpO1xuXHRcdFx0XHRcdFx0fSk7XG5cblx0XHRcdFx0XHRcdCQuYWpheCh7XG5cdFx0XHRcdFx0XHRcdCd1cmwnOiBzZWxmLmRhdGEoJ3VybFNhdmV0cmVlbm9kZScpLFxuXHRcdFx0XHRcdFx0XHQndHlwZSc6ICdQT1NUJyxcblx0XHRcdFx0XHRcdFx0J2RhdGEnOiB7XG5cdFx0XHRcdFx0XHRcdFx0SUQ6IG5vZGVJRCwgXG5cdFx0XHRcdFx0XHRcdFx0UGFyZW50SUQ6IG5ld1BhcmVudElELFxuXHRcdFx0XHRcdFx0XHRcdFNpYmxpbmdJRHM6IHNpYmxpbmdJRHNcblx0XHRcdFx0XHRcdFx0fSxcblx0XHRcdFx0XHRcdFx0c3VjY2VzczogZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0XHRcdFx0Ly8gV2Ugb25seSBuZWVkIHRvIHVwZGF0ZSB0aGUgUGFyZW50SUQgaWYgdGhlIGN1cnJlbnQgcGFnZSB3ZSdyZSBvbiBpcyB0aGUgcGFnZSBiZWluZyBtb3ZlZFxuXHRcdFx0XHRcdFx0XHRcdGlmICgkKCcuY21zLWVkaXQtZm9ybSA6aW5wdXRbbmFtZT1JRF0nKS52YWwoKSA9PSBub2RlSUQpIHtcblx0XHRcdFx0XHRcdFx0XHRcdCQoJy5jbXMtZWRpdC1mb3JtIDppbnB1dFtuYW1lPVBhcmVudElEXScpLnZhbChuZXdQYXJlbnRJRCk7XG5cdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdHNlbGYudXBkYXRlTm9kZXNGcm9tU2VydmVyKFtub2RlSURdKTtcblx0XHRcdFx0XHRcdFx0fSxcblx0XHRcdFx0XHRcdFx0c3RhdHVzQ29kZToge1xuXHRcdFx0XHRcdFx0XHRcdDQwMzogZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0XHRcdFx0XHQkLmpzdHJlZS5yb2xsYmFjayhkYXRhLnJsYmspO1xuXHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0fSk7XG5cdFx0XHRcdFx0fSlcblx0XHRcdFx0XHQvLyBNYWtlIHNvbWUganN0cmVlIGV2ZW50cyBkZWxlZ2F0YWJsZVxuXHRcdFx0XHRcdC5iaW5kKCdzZWxlY3Rfbm9kZS5qc3RyZWUgY2hlY2tfbm9kZS5qc3RyZWUgdW5jaGVja19ub2RlLmpzdHJlZScsIGZ1bmN0aW9uKGUsIGRhdGEpIHtcblx0XHRcdFx0XHRcdCQoZG9jdW1lbnQpLnRyaWdnZXJIYW5kbGVyKGUsIGRhdGEpO1xuXHRcdFx0XHRcdH0pO1xuXHRcdH0sXG5cdFx0b25yZW1vdmU6IGZ1bmN0aW9uKCl7XG5cdFx0XHR0aGlzLmpzdHJlZSgnZGVzdHJveScpO1xuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9LFxuXG5cdFx0J2Zyb20gLmNtcy1jb250YWluZXInOiB7XG5cdFx0XHRvbmFmdGVyc3RhdGVjaGFuZ2U6IGZ1bmN0aW9uKGUpe1xuXHRcdFx0XHR0aGlzLnVwZGF0ZUZyb21FZGl0Rm9ybSgpO1xuXHRcdFx0XHQvLyBObyBuZWVkIHRvIHJlZnJlc2ggdHJlZSBub2Rlcywgd2UgYXNzdW1lIG9ubHkgZm9ybSBzdWJtaXRzIGNhdXNlIHN0YXRlIGNoYW5nZXNcblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0J2Zyb20gLmNtcy1jb250YWluZXIgZm9ybSc6IHtcblx0XHRcdG9uYWZ0ZXJzdWJtaXRmb3JtOiBmdW5jdGlvbihlKXtcblx0XHRcdFx0dmFyIGlkID0gJCgnLmNtcy1lZGl0LWZvcm0gOmlucHV0W25hbWU9SURdJykudmFsKCk7XG5cdFx0XHRcdC8vIFRPRE8gVHJpZ2dlciBieSBpbXBsZW1lbnRpbmcgYW5kIGluc3BlY3RpbmcgXCJjaGFuZ2VkIHJlY29yZHNcIiBtZXRhZGF0YSBcblx0XHRcdFx0Ly8gc2VudCBieSBmb3JtIHN1Ym1pc3Npb24gcmVzcG9uc2UgKGFzIEhUVFAgcmVzcG9uc2UgaGVhZGVycylcblx0XHRcdFx0dGhpcy51cGRhdGVOb2Rlc0Zyb21TZXJ2ZXIoW2lkXSk7XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdGdldFRyZWVDb25maWc6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dmFyIHNlbGYgPSB0aGlzO1xuXHRcdFx0cmV0dXJuIHtcblx0XHRcdFx0J2NvcmUnOiB7XG5cdFx0XHRcdFx0J2luaXRpYWxseV9vcGVuJzogWydyZWNvcmQtMCddLFxuXHRcdFx0XHRcdCdhbmltYXRpb24nOiAwLFxuXHRcdFx0XHRcdCdodG1sX3RpdGxlcyc6IHRydWVcblx0XHRcdFx0fSxcblx0XHRcdFx0J2h0bWxfZGF0YSc6IHtcblx0XHRcdFx0XHQvLyAnYWpheCcgd2lsbCBiZSBzZXQgb24gJ2xvYWRlZC5qc3RyZWUnIGV2ZW50XG5cdFx0XHRcdH0sXG5cdFx0XHRcdCd1aSc6IHtcblx0XHRcdFx0XHRcInNlbGVjdF9saW1pdFwiIDogMSxcblx0XHRcdFx0XHQnaW5pdGlhbGx5X3NlbGVjdCc6IFt0aGlzLmZpbmQoJy5jdXJyZW50JykuYXR0cignaWQnKV1cblx0XHRcdFx0fSxcblx0XHRcdFx0XCJjcnJtXCI6IHtcblx0XHRcdFx0XHQnbW92ZSc6IHtcblx0XHRcdFx0XHRcdC8vIENoZWNrIGlmIGEgbm9kZSBpcyBhbGxvd2VkIHRvIGJlIG1vdmVkLlxuXHRcdFx0XHRcdFx0Ly8gQ2F1dGlvbjogUnVucyBvbiBldmVyeSBkcmFnIG92ZXIgYSBuZXcgbm9kZVxuXHRcdFx0XHRcdFx0J2NoZWNrX21vdmUnOiBmdW5jdGlvbihkYXRhKSB7XG5cdFx0XHRcdFx0XHRcdHZhciBtb3ZlZE5vZGUgPSAkKGRhdGEubyksIG5ld1BhcmVudCA9ICQoZGF0YS5ucCksIFxuXHRcdFx0XHRcdFx0XHRcdGlzTW92ZWRPbnRvQ29udGFpbmVyID0gZGF0YS5vdC5nZXRfY29udGFpbmVyKClbMF0gPT0gZGF0YS5ucFswXSxcblx0XHRcdFx0XHRcdFx0XHRtb3ZlZE5vZGVDbGFzcyA9IG1vdmVkTm9kZS5nZXRDbGFzc25hbWUoKSwgXG5cdFx0XHRcdFx0XHRcdFx0bmV3UGFyZW50Q2xhc3MgPSBuZXdQYXJlbnQuZ2V0Q2xhc3NuYW1lKCksXG5cdFx0XHRcdFx0XHRcdFx0Ly8gQ2hlY2sgYWxsb3dlZENoaWxkcmVuIG9mIG5ld1BhcmVudCBvciBhZ2FpbnN0IHJvb3Qgbm9kZSBydWxlc1xuXHRcdFx0XHRcdFx0XHRcdGhpbnRzID0gc2VsZi5nZXRIaW50cygpLFxuXHRcdFx0XHRcdFx0XHRcdGRpc2FsbG93ZWRDaGlsZHJlbiA9IFtdLFxuXHRcdFx0XHRcdFx0XHRcdGhpbnRLZXkgPSBuZXdQYXJlbnRDbGFzcyA/IG5ld1BhcmVudENsYXNzIDogJ1Jvb3QnLFxuXHRcdFx0XHRcdFx0XHRcdGhpbnQgPSAoaGludHMgJiYgdHlwZW9mIGhpbnRzW2hpbnRLZXldICE9ICd1bmRlZmluZWQnKSA/IGhpbnRzW2hpbnRLZXldIDogbnVsbDtcblxuXHRcdFx0XHRcdFx0XHQvLyBTcGVjaWFsIGNhc2UgZm9yIFZpcnR1YWxQYWdlOiBDaGVjayB0aGF0IG9yaWdpbmFsIHBhZ2UgdHlwZSBpcyBhbiBhbGxvd2VkIGNoaWxkXG5cdFx0XHRcdFx0XHRcdGlmKGhpbnQgJiYgbW92ZWROb2RlLmF0dHIoJ2NsYXNzJykubWF0Y2goL1ZpcnR1YWxQYWdlLShbXlxcc10qKS8pKSBtb3ZlZE5vZGVDbGFzcyA9IFJlZ0V4cC4kMTtcblx0XHRcdFx0XHRcdFx0XG5cdFx0XHRcdFx0XHRcdGlmKGhpbnQpIGRpc2FsbG93ZWRDaGlsZHJlbiA9ICh0eXBlb2YgaGludC5kaXNhbGxvd2VkQ2hpbGRyZW4gIT0gJ3VuZGVmaW5lZCcpID8gaGludC5kaXNhbGxvd2VkQ2hpbGRyZW4gOiBbXTtcblx0XHRcdFx0XHRcdFx0dmFyIGlzQWxsb3dlZCA9IChcblx0XHRcdFx0XHRcdFx0XHQvLyBEb24ndCBhbGxvdyBtb3ZpbmcgdGhlIHJvb3Qgbm9kZVxuXHRcdFx0XHRcdFx0XHRcdG1vdmVkTm9kZS5kYXRhKCdpZCcpICE9PSAwIFxuXHRcdFx0XHRcdFx0XHRcdC8vIEFyY2hpdmVkIHBhZ2VzIGNhbid0IGJlIG1vdmVkXG5cdFx0XHRcdFx0XHRcdFx0JiYgIW1vdmVkTm9kZS5oYXNDbGFzcygnc3RhdHVzLWFyY2hpdmVkJylcblx0XHRcdFx0XHRcdFx0XHQvLyBPbmx5IGFsbG93IG1vdmluZyBub2RlIGluc2lkZSB0aGUgcm9vdCBjb250YWluZXIsIG5vdCBiZWZvcmUvYWZ0ZXIgaXRcblx0XHRcdFx0XHRcdFx0XHQmJiAoIWlzTW92ZWRPbnRvQ29udGFpbmVyIHx8IGRhdGEucCA9PSAnaW5zaWRlJylcblx0XHRcdFx0XHRcdFx0XHQvLyBDaGlsZHJlbiBhcmUgZ2VuZXJhbGx5IGFsbG93ZWQgb24gcGFyZW50XG5cdFx0XHRcdFx0XHRcdFx0JiYgIW5ld1BhcmVudC5oYXNDbGFzcygnbm9jaGlsZHJlbicpXG5cdFx0XHRcdFx0XHRcdFx0Ly8gbW92ZWROb2RlIGlzIGFsbG93ZWQgYXMgYSBjaGlsZFxuXHRcdFx0XHRcdFx0XHRcdCYmICghZGlzYWxsb3dlZENoaWxkcmVuLmxlbmd0aCB8fCAkLmluQXJyYXkobW92ZWROb2RlQ2xhc3MsIGRpc2FsbG93ZWRDaGlsZHJlbikgPT0gLTEpXG5cdFx0XHRcdFx0XHRcdCk7XG5cdFx0XHRcdFx0XHRcdFxuXHRcdFx0XHRcdFx0XHRyZXR1cm4gaXNBbGxvd2VkO1xuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSxcblx0XHRcdFx0J2RuZCc6IHtcblx0XHRcdFx0XHRcImRyb3BfdGFyZ2V0XCIgOiBmYWxzZSxcblx0XHRcdFx0XHRcImRyYWdfdGFyZ2V0XCIgOiBmYWxzZVxuXHRcdFx0XHR9LFxuXHRcdFx0XHQnY2hlY2tib3gnOiB7XG5cdFx0XHRcdFx0J3R3b19zdGF0ZSc6IHRydWVcblx0XHRcdFx0fSxcblx0XHRcdFx0J3RoZW1lcyc6IHtcblx0XHRcdFx0XHQndGhlbWUnOiAnYXBwbGUnLFxuXHRcdFx0XHRcdCd1cmwnOiAkKCdib2R5JykuZGF0YSgnZnJhbWV3b3JrcGF0aCcpICsgJy90aGlyZHBhcnR5L2pzdHJlZS90aGVtZXMvYXBwbGUvc3R5bGUuY3NzJ1xuXHRcdFx0XHR9LFxuXHRcdFx0XHQvLyBDYXV0aW9uOiBTaWx2ZXJTdHJpcGUgaGFzIGRpc2FibGVkICQudmFrYXRhLmNzcy5hZGRfc2hlZXQoKSBmb3IgcGVyZm9ybWFuY2UgcmVhc29ucyxcblx0XHRcdFx0Ly8gd2hpY2ggbWVhbnMgeW91IG5lZWQgdG8gYWRkIGFueSBDU1MgbWFudWFsbHkgdG8gZnJhbWV3b3JrL2FkbWluL3Njc3MvX3RyZWUuY3NzXG5cdFx0XHRcdCdwbHVnaW5zJzogW1xuXHRcdFx0XHRcdCdodG1sX2RhdGEnLCAndWknLCAnZG5kJywgJ2Nycm0nLCAndGhlbWVzJywgXG5cdFx0XHRcdFx0J2NoZWNrYm94JyAvLyBjaGVja2JveGVzIGFyZSBoaWRkZW4gdW5sZXNzIC5tdWx0aXBsZSBpcyBzZXRcblx0XHRcdFx0XVxuXHRcdFx0fTtcblx0XHR9LFxuXHRcdFxuXHRcdC8qKlxuXHRcdCAqIEZ1bmN0aW9uOlxuXHRcdCAqICBzZWFyY2hcblx0XHQgKiBcblx0XHQgKiBQYXJhbWV0ZXJzOlxuXHRcdCAqICAoT2JqZWN0KSBkYXRhIFBhc3MgZW1wdHkgZGF0YSB0byBjYW5jZWwgc2VhcmNoXG5cdFx0ICogIChGdW5jdGlvbikgY2FsbGJhY2sgU3VjY2VzcyBjYWxsYmFja1xuXHRcdCAqL1xuXHRcdHNlYXJjaDogZnVuY3Rpb24ocGFyYW1zLCBjYWxsYmFjaykge1xuXHRcdFx0aWYocGFyYW1zKSB0aGlzLmRhdGEoJ3NlYXJjaHBhcmFtcycsIHBhcmFtcyk7XG5cdFx0XHRlbHNlIHRoaXMucmVtb3ZlRGF0YSgnc2VhcmNocGFyYW1zJyk7XG5cdFx0XHR0aGlzLmpzdHJlZSgncmVmcmVzaCcsIC0xLCBjYWxsYmFjayk7XG5cdFx0fSxcblx0XHRcblx0XHQvKipcblx0XHQgKiBGdW5jdGlvbjogZ2V0Tm9kZUJ5SURcblx0XHQgKiBcblx0XHQgKiBQYXJhbWV0ZXJzOlxuXHRcdCAqICAoSW50KSBpZCBcblx0XHQgKiBcblx0XHQgKiBSZXR1cm5zXG5cdFx0ICogIERPTUVsZW1lbnRcblx0XHQgKi9cblx0XHRnZXROb2RlQnlJRDogZnVuY3Rpb24oaWQpIHtcblx0XHRcdHJldHVybiB0aGlzLmZpbmQoJypbZGF0YS1pZD0nK2lkKyddJyk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIENyZWF0ZXMgYSBuZXcgbm9kZSBmcm9tIHRoZSBnaXZlbiBIVE1MLlxuXHRcdCAqIFdyYXBwaW5nIGFyb3VuZCBqc3RyZWUgQVBJIGJlY2F1c2Ugd2Ugd2FudCB0aGUgZmxleGliaWxpdHkgdG8gZGVmaW5lXG5cdFx0ICogdGhlIG5vZGUncyA8bGk+IG91cnNlbHZlcy4gUGxhY2VzIHRoZSBub2RlIGluIHRoZSB0cmVlXG5cdFx0ICogYWNjb3JkaW5nIHRvIGRhdGEuUGFyZW50SUQuXG5cdFx0ICpcblx0XHQgKiBQYXJhbWV0ZXJzOlxuXHRcdCAqICAoU3RyaW5nKSBIVE1MIE5ldyBub2RlIGNvbnRlbnQgKDxsaT4pXG5cdFx0ICogIChPYmplY3QpIE1hcCBvZiBhZGRpdGlvbmFsIGRhdGEsIGUuZy4gUGFyZW50SURcblx0XHQgKiAgKEZ1bmN0aW9uKSBTdWNjZXNzIGNhbGxiYWNrXG5cdFx0ICovXG5cdFx0Y3JlYXRlTm9kZTogZnVuY3Rpb24oaHRtbCwgZGF0YSwgY2FsbGJhY2spIHtcblx0XHRcdHZhciBzZWxmID0gdGhpcywgXG5cdFx0XHRcdHBhcmVudE5vZGUgPSBkYXRhLlBhcmVudElEICE9PSB2b2lkIDAgPyBzZWxmLmdldE5vZGVCeUlEKGRhdGEuUGFyZW50SUQpIDogZmFsc2UsIC8vIEV4cGxpY2l0bHkgY2hlY2sgZm9yIHVuZGVmaW5lZCBhcyAwIGlzIGEgdmFsaWQgUGFyZW50SURcblx0XHRcdFx0bmV3Tm9kZSA9ICQoaHRtbCk7XG5cdFx0XHRcblx0XHRcdC8vIEV4dHJhY3QgdGhlIHN0YXRlIGZvciB0aGUgbmV3IG5vZGUgZnJvbSB0aGUgcHJvcGVydGllcyB0YWtlbiBmcm9tIHRoZSBwcm92aWRlZCBIVE1MIHRlbXBsYXRlLlxuXHRcdFx0Ly8gVGhpcyB3aWxsIGNvcnJlY3RseSBpbml0aWFsaXNlIHRoZSBiZWhhdmlvdXIgb2YgdGhlIG5vZGUgZm9yIGFqYXggbG9hZGluZyBvZiBjaGlsZHJlbi5cblx0XHRcdHZhciBwcm9wZXJ0aWVzID0ge2RhdGE6ICcnfTtcblx0XHRcdGlmKG5ld05vZGUuaGFzQ2xhc3MoJ2pzdHJlZS1vcGVuJykpIHtcblx0XHRcdFx0cHJvcGVydGllcy5zdGF0ZSA9ICdvcGVuJztcblx0XHRcdH0gZWxzZSBpZihuZXdOb2RlLmhhc0NsYXNzKCdqc3RyZWUtY2xvc2VkJykpIHtcblx0XHRcdFx0cHJvcGVydGllcy5zdGF0ZSA9ICdjbG9zZWQnO1xuXHRcdFx0fVxuXHRcdFx0dGhpcy5qc3RyZWUoXG5cdFx0XHRcdCdjcmVhdGVfbm9kZScsIFxuXHRcdFx0XHRwYXJlbnROb2RlLmxlbmd0aCA/IHBhcmVudE5vZGUgOiAtMSwgXG5cdFx0XHRcdCdsYXN0JywgXG5cdFx0XHRcdHByb3BlcnRpZXMsXG5cdFx0XHRcdGZ1bmN0aW9uKG5vZGUpIHtcblx0XHRcdFx0XHR2YXIgb3JpZ0NsYXNzZXMgPSBub2RlLmF0dHIoJ2NsYXNzJyk7XG5cdFx0XHRcdFx0Ly8gQ29weSBhdHRyaWJ1dGVzXG5cdFx0XHRcdFx0Zm9yKHZhciBpPTA7IGk8bmV3Tm9kZVswXS5hdHRyaWJ1dGVzLmxlbmd0aDsgaSsrKXtcblx0XHRcdFx0XHRcdHZhciBhdHRyID0gbmV3Tm9kZVswXS5hdHRyaWJ1dGVzW2ldO1xuXHRcdFx0XHRcdFx0bm9kZS5hdHRyKGF0dHIubmFtZSwgYXR0ci52YWx1ZSk7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHRcdC8vIFN1YnN0aXR1dGUgaHRtbCBmcm9tIHJlcXVlc3QgZm9yIHRoYXQgZ2VuZXJhdGVkIGJ5IGpzdHJlZVxuXHRcdFx0XHRcdG5vZGUuYWRkQ2xhc3Mob3JpZ0NsYXNzZXMpLmh0bWwobmV3Tm9kZS5odG1sKCkpO1xuXHRcdFx0XHRcdGNhbGxiYWNrKG5vZGUpO1xuXHRcdFx0XHR9XG5cdFx0XHQpO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBVcGRhdGVzIGEgbm9kZSdzIHN0YXRlIGluIHRoZSB0cmVlLFxuXHRcdCAqIGluY2x1ZGluZyBhbGwgb2YgaXRzIEhUTUwsIGFzIHdlbGwgYXMgaXRzIHBvc2l0aW9uLlxuXHRcdCAqIFxuXHRcdCAqIFBhcmFtZXRlcnM6XG5cdFx0ICogIChET01FbGVtZW50KSBFeGlzdGluZyBub2RlXG5cdFx0ICogIChTdHJpbmcpIEhUTUwgTmV3IG5vZGUgY29udGVudCAoPGxpPilcblx0XHQgKiAgKE9iamVjdCkgTWFwIG9mIGFkZGl0aW9uYWwgZGF0YSwgZS5nLiBQYXJlbnRJRFxuXHRcdCAqL1xuXHRcdHVwZGF0ZU5vZGU6IGZ1bmN0aW9uKG5vZGUsIGh0bWwsIGRhdGEpIHtcblx0XHRcdHZhciBzZWxmID0gdGhpcywgbmV3Tm9kZSA9ICQoaHRtbCksIG9yaWdDbGFzc2VzID0gbm9kZS5hdHRyKCdjbGFzcycpO1xuXG5cdFx0XHR2YXIgbmV4dE5vZGUgPSBkYXRhLk5leHRJRCA/IHRoaXMuZ2V0Tm9kZUJ5SUQoZGF0YS5OZXh0SUQpIDogZmFsc2U7XG5cdFx0XHR2YXIgcHJldk5vZGUgPSBkYXRhLlByZXZJRCA/IHRoaXMuZ2V0Tm9kZUJ5SUQoZGF0YS5QcmV2SUQpIDogZmFsc2U7XG5cdFx0XHR2YXIgcGFyZW50Tm9kZSA9IGRhdGEuUGFyZW50SUQgPyB0aGlzLmdldE5vZGVCeUlEKGRhdGEuUGFyZW50SUQpIDogZmFsc2U7XG5cblx0XHRcdC8vIENvcHkgYXR0cmlidXRlcy4gV2UgY2FuJ3QgcmVwbGFjZSB0aGUgbm9kZSBjb21wbGV0ZWx5XG5cdFx0XHQvLyB3aXRob3V0IHJlbW92aW5nIG9yIGRldGFjaGluZyBpdHMgY2hpbGRyZW4gbm9kZXMuXG5cdFx0XHQkLmVhY2goWydpZCcsICdzdHlsZScsICdjbGFzcycsICdkYXRhLXBhZ2V0eXBlJ10sIGZ1bmN0aW9uKGksIGF0dHJOYW1lKSB7XG5cdFx0XHRcdG5vZGUuYXR0cihhdHRyTmFtZSwgbmV3Tm9kZS5hdHRyKGF0dHJOYW1lKSk7XG5cdFx0XHR9KTtcblxuXHRcdFx0Ly8gVG8gYXZvaWQgY29uZmxpY3RpbmcgY2xhc3NlcyB3aGVuIHRoZSBub2RlIGdldHMgaXRzIGNvbnRlbnQgcmVwbGFjZWQgKHNlZSBiZWxvdylcblx0XHRcdC8vIEZpbHRlciBvdXQgYWxsIHByZXZpb3VzIHN0YXR1cyBmbGFncyBpZiB0aGV5IGFyZSBub3QgaW4gdGhlIGNsYXNzIHByb3BlcnR5IG9mIHRoZSBuZXcgbm9kZVxuXHRcdFx0b3JpZ0NsYXNzZXMgPSBvcmlnQ2xhc3Nlcy5yZXBsYWNlKC9zdGF0dXMtW15cXHNdKi8sICcnKTtcblxuXHRcdFx0Ly8gUmVwbGFjZSBpbm5lciBjb250ZW50XG5cdFx0XHR2YXIgb3JpZ0NoaWxkcmVuID0gbm9kZS5jaGlsZHJlbigndWwnKS5kZXRhY2goKTtcblx0XHRcdG5vZGUuYWRkQ2xhc3Mob3JpZ0NsYXNzZXMpLmh0bWwobmV3Tm9kZS5odG1sKCkpLmFwcGVuZChvcmlnQ2hpbGRyZW4pO1xuXG5cdFx0XHRpZiAobmV4dE5vZGUgJiYgbmV4dE5vZGUubGVuZ3RoKSB7XG5cdFx0XHRcdHRoaXMuanN0cmVlKCdtb3ZlX25vZGUnLCBub2RlLCBuZXh0Tm9kZSwgJ2JlZm9yZScpO1xuXHRcdFx0fVxuXHRcdFx0ZWxzZSBpZiAocHJldk5vZGUgJiYgcHJldk5vZGUubGVuZ3RoKSB7XG5cdFx0XHRcdHRoaXMuanN0cmVlKCdtb3ZlX25vZGUnLCBub2RlLCBwcmV2Tm9kZSwgJ2FmdGVyJyk7XG5cdFx0XHR9XG5cdFx0XHRlbHNlIHtcblx0XHRcdFx0dGhpcy5qc3RyZWUoJ21vdmVfbm9kZScsIG5vZGUsIHBhcmVudE5vZGUubGVuZ3RoID8gcGFyZW50Tm9kZSA6IC0xKTtcblx0XHRcdH1cblx0XHR9LFxuXHRcdFxuXHRcdC8qKlxuXHRcdCAqIFNldHMgdGhlIGN1cnJlbnQgc3RhdGUgYmFzZWQgb24gdGhlIGZvcm0gdGhlIHRyZWUgaXMgbWFuYWdpbmcuXG5cdFx0ICovXG5cdFx0dXBkYXRlRnJvbUVkaXRGb3JtOiBmdW5jdGlvbigpIHtcblx0XHRcdHZhciBub2RlLCBpZCA9ICQoJy5jbXMtZWRpdC1mb3JtIDppbnB1dFtuYW1lPUlEXScpLnZhbCgpO1xuXHRcdFx0aWYoaWQpIHtcblx0XHRcdFx0bm9kZSA9IHRoaXMuZ2V0Tm9kZUJ5SUQoaWQpO1xuXHRcdFx0XHRpZihub2RlLmxlbmd0aCkge1xuXHRcdFx0XHRcdHRoaXMuanN0cmVlKCdkZXNlbGVjdF9hbGwnKTtcblx0XHRcdFx0XHR0aGlzLmpzdHJlZSgnc2VsZWN0X25vZGUnLCBub2RlKTtcblx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHQvLyBJZiBmb3JtIGlzIHNob3dpbmcgYW4gSUQgdGhhdCBkb2Vzbid0IGV4aXN0IGluIHRoZSB0cmVlLFxuXHRcdFx0XHRcdC8vIGdldCBpdCBmcm9tIHRoZSBzZXJ2ZXJcblx0XHRcdFx0XHR0aGlzLnVwZGF0ZU5vZGVzRnJvbVNlcnZlcihbaWRdKTtcblx0XHRcdFx0fVxuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0Ly8gSWYgbm8gSUQgZXhpc3RzIGluIGEgZm9ybSB2aWV3LCB3ZSdyZSBkaXNwbGF5aW5nIHRoZSB0cmVlIG9uIGl0cyBvd24sXG5cdFx0XHRcdC8vIGhlbmNlIHRvIHBhZ2Ugc2hvdWxkIHNob3cgYXMgYWN0aXZlXG5cdFx0XHRcdHRoaXMuanN0cmVlKCdkZXNlbGVjdF9hbGwnKTtcblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogUmVsb2FkcyB0aGUgdmlldyBvZiBvbmUgb3IgbW9yZSB0cmVlIG5vZGVzXG5cdFx0ICogZnJvbSB0aGUgc2VydmVyLCBlbnN1cmluZyB0aGF0IHRoZWlyIHN0YXRlIGlzIHVwIHRvIGRhdGVcblx0XHQgKiAoaWNvbiwgdGl0bGUsIGhpZXJhcmNoeSwgYmFkZ2VzLCBldGMpLlxuXHRcdCAqIFRoaXMgaXMgZWFzaWVyLCBtb3JlIGNvbnNpc3RlbnQgYW5kIG1vcmUgZXh0ZW5zaWJsZSBcblx0XHQgKiB0aGFuIHRyeWluZyB0byBjb3JyZWN0IGFsbCBhc3BlY3RzIHZpYSBET00gbW9kaWZpY2F0aW9ucywgXG5cdFx0ICogYmFzZWQgb24gdGhlIHNwYXJzZSBkYXRhIGF2YWlsYWJsZSBpbiB0aGUgY3VycmVudCBlZGl0IGZvcm0uXG5cdFx0ICpcblx0XHQgKiBQYXJhbWV0ZXJzOlxuXHRcdCAqICAoQXJyYXkpIExpc3Qgb2YgSURzIHRvIHJldHJpZXZlXG5cdFx0ICovXG5cdFx0dXBkYXRlTm9kZXNGcm9tU2VydmVyOiBmdW5jdGlvbihpZHMpIHtcblx0XHRcdGlmKHRoaXMuZ2V0SXNVcGRhdGluZ1RyZWUoKSB8fCAhdGhpcy5nZXRJc0xvYWRlZCgpKSByZXR1cm47XG5cblx0XHRcdHZhciBzZWxmID0gdGhpcywgaSwgaW5jbHVkZXNOZXdOb2RlID0gZmFsc2U7XG5cdFx0XHR0aGlzLnNldElzVXBkYXRpbmdUcmVlKHRydWUpO1xuXHRcdFx0c2VsZi5qc3RyZWUoJ3NhdmVfc2VsZWN0ZWQnKTtcblxuXHRcdFx0dmFyIGNvcnJlY3RTdGF0ZUZuID0gZnVuY3Rpb24obm9kZSkge1xuXHRcdFx0XHQvLyBEdXBsaWNhdGVzIGNhbiBiZSBjYXVzZWQgYnkgdGhlIHN1YnRyZWUgcmVsb2FkaW5nIHRocm91Z2hcblx0XHRcdFx0Ly8gYSB0cmVlIFwib3BlblwiL1wic2VsZWN0XCIgZXZlbnQsIHdoaWxlIGF0IHRoZSBzYW1lIHRpbWUgY3JlYXRpbmcgYSBuZXcgbm9kZVxuXHRcdFx0XHRzZWxmLmdldE5vZGVCeUlEKG5vZGUuZGF0YSgnaWQnKSkubm90KG5vZGUpLnJlbW92ZSgpO1xuXHRcdFx0XHRcblx0XHRcdFx0Ly8gU2VsZWN0IHRoaXMgbm9kZVxuXHRcdFx0XHRzZWxmLmpzdHJlZSgnZGVzZWxlY3RfYWxsJyk7XG5cdFx0XHRcdHNlbGYuanN0cmVlKCdzZWxlY3Rfbm9kZScsIG5vZGUpO1xuXHRcdFx0fTtcblxuXHRcdFx0Ly8gVE9ETyAnaW5pdGlhbGx5X29wZW5lZCcgY29uZmlnIGRvZXNuJ3QgYXBwbHkgaGVyZVxuXHRcdFx0c2VsZi5qc3RyZWUoJ29wZW5fbm9kZScsIHRoaXMuZ2V0Tm9kZUJ5SUQoMCkpO1xuXHRcdFx0c2VsZi5qc3RyZWUoJ3NhdmVfb3BlbmVkJyk7XG5cdFx0XHRzZWxmLmpzdHJlZSgnc2F2ZV9zZWxlY3RlZCcpO1xuXG5cdFx0XHQkLmFqYXgoe1xuXHRcdFx0XHR1cmw6ICQucGF0aC5hZGRTZWFyY2hQYXJhbXModGhpcy5kYXRhKCd1cmxVcGRhdGV0cmVlbm9kZXMnKSwgJ2lkcz0nICsgaWRzLmpvaW4oJywnKSksXG5cdFx0XHRcdGRhdGFUeXBlOiAnanNvbicsXG5cdFx0XHRcdHN1Y2Nlc3M6IGZ1bmN0aW9uKGRhdGEsIHhocikge1xuXHRcdFx0XHRcdCQuZWFjaChkYXRhLCBmdW5jdGlvbihub2RlSWQsIG5vZGVEYXRhKSB7XG5cdFx0XHRcdFx0XHR2YXIgbm9kZSA9IHNlbGYuZ2V0Tm9kZUJ5SUQobm9kZUlkKTtcblxuXHRcdFx0XHRcdFx0Ly8gSWYgbm8gbm9kZSBkYXRhIGlzIGdpdmVuLCBhc3N1bWUgdGhlIG5vZGUgaGFzIGJlZW4gcmVtb3ZlZFxuXHRcdFx0XHRcdFx0aWYoIW5vZGVEYXRhKSB7XG5cdFx0XHRcdFx0XHRcdHNlbGYuanN0cmVlKCdkZWxldGVfbm9kZScsIG5vZGUpO1xuXHRcdFx0XHRcdFx0XHRyZXR1cm47XG5cdFx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHRcdC8vIENoZWNrIGlmIG5vZGUgZXhpc3RzLCBjcmVhdGUgaWYgbmVjZXNzYXJ5XG5cdFx0XHRcdFx0XHRpZihub2RlLmxlbmd0aCkge1xuXHRcdFx0XHRcdFx0XHRzZWxmLnVwZGF0ZU5vZGUobm9kZSwgbm9kZURhdGEuaHRtbCwgbm9kZURhdGEpO1xuXHRcdFx0XHRcdFx0XHRzZXRUaW1lb3V0KGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdFx0XHRcdGNvcnJlY3RTdGF0ZUZuKG5vZGUpO1xuXHRcdFx0XHRcdFx0XHR9LCA1MDApO1xuXHRcdFx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRcdFx0aW5jbHVkZXNOZXdOb2RlID0gdHJ1ZTtcblxuXHRcdFx0XHRcdFx0XHQvLyBJZiB0aGUgcGFyZW50IG5vZGUgY2FuJ3QgYmUgZm91bmQsIGl0IG1pZ2h0IGhhdmUgbm90IGJlZW4gbG9hZGVkIHlldC5cblx0XHRcdFx0XHRcdFx0Ly8gVGhpcyBjYW4gaGFwcGVuIGZvciBkZWVwIHRyZWVzIHdoaWNoIHJlcXVpcmUgYWpheCBsb2FkaW5nLlxuXHRcdFx0XHRcdFx0XHQvLyBBc3N1bWVzIHRoYXQgdGhlIG5ldyBub2RlIGhhcyBiZWVuIHN1Ym1pdHRlZCB0byB0aGUgc2VydmVyIGFscmVhZHkuXG5cdFx0XHRcdFx0XHRcdGlmKG5vZGVEYXRhLlBhcmVudElEICYmICFzZWxmLmZpbmQoJ2xpW2RhdGEtaWQ9Jytub2RlRGF0YS5QYXJlbnRJRCsnXScpLmxlbmd0aCkge1xuXHRcdFx0XHRcdFx0XHRcdHNlbGYuanN0cmVlKCdsb2FkX25vZGUnLCAtMSwgZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRuZXdOb2RlID0gc2VsZi5maW5kKCdsaVtkYXRhLWlkPScrbm9kZUlkKyddJyk7XG5cdFx0XHRcdFx0XHRcdFx0XHRjb3JyZWN0U3RhdGVGbihuZXdOb2RlKTtcblx0XHRcdFx0XHRcdFx0XHR9KTtcblx0XHRcdFx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRcdFx0XHRzZWxmLmNyZWF0ZU5vZGUobm9kZURhdGEuaHRtbCwgbm9kZURhdGEsIGZ1bmN0aW9uKG5ld05vZGUpIHtcblx0XHRcdFx0XHRcdFx0XHRcdGNvcnJlY3RTdGF0ZUZuKG5ld05vZGUpO1xuXHRcdFx0XHRcdFx0XHRcdH0pO1xuXHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0fSk7XG5cblx0XHRcdFx0XHRpZighaW5jbHVkZXNOZXdOb2RlKSB7XG5cdFx0XHRcdFx0XHRzZWxmLmpzdHJlZSgnZGVzZWxlY3RfYWxsJyk7XG5cdFx0XHRcdFx0XHRzZWxmLmpzdHJlZSgncmVzZWxlY3QnKTtcblx0XHRcdFx0XHRcdHNlbGYuanN0cmVlKCdyZW9wZW4nKTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH0sXG5cdFx0XHRcdGNvbXBsZXRlOiBmdW5jdGlvbigpIHtcblx0XHRcdFx0XHRzZWxmLnNldElzVXBkYXRpbmdUcmVlKGZhbHNlKTtcblx0XHRcdFx0fVxuXHRcdFx0fSk7XG5cdFx0fVxuXG5cdH0pO1xuXHRcblx0JCgnLmNtcy10cmVlLm11bHRpcGxlJykuZW50d2luZSh7XG5cdFx0b25tYXRjaDogZnVuY3Rpb24oKSB7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdFx0dGhpcy5qc3RyZWUoJ3Nob3dfY2hlY2tib3hlcycpO1xuXHRcdH0sXG5cdFx0b251bm1hdGNoOiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMuX3N1cGVyKCk7XG5cdFx0XHR0aGlzLmpzdHJlZSgndW5jaGVja19hbGwnKTtcblx0XHRcdHRoaXMuanN0cmVlKCdoaWRlX2NoZWNrYm94ZXMnKTtcblx0XHR9LFxuXHRcdC8qKlxuXHRcdCAqIEZ1bmN0aW9uOiBnZXRTZWxlY3RlZElEc1xuXHRcdCAqIFxuXHRcdCAqIFJldHVybnM6XG5cdFx0ICogXHQoQXJyYXkpXG5cdFx0ICovXG5cdFx0Z2V0U2VsZWN0ZWRJRHM6IGZ1bmN0aW9uKCkge1xuXHRcdFx0cmV0dXJuICQodGhpcylcblx0XHRcdFx0LmpzdHJlZSgnZ2V0X2NoZWNrZWQnKVxuXHRcdFx0XHQubm90KCcuZGlzYWJsZWQnKVxuXHRcdFx0XHQubWFwKGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdHJldHVybiAkKHRoaXMpLmRhdGEoJ2lkJyk7XG5cdFx0XHRcdH0pXG5cdFx0XHRcdC5nZXQoKTtcblx0XHR9XG5cdH0pO1xuXHRcblx0JCgnLmNtcy10cmVlIGxpJykuZW50d2luZSh7XG5cdFx0XG5cdFx0LyoqXG5cdFx0ICogRnVuY3Rpb246IHNldEVuYWJsZWRcblx0XHQgKiBcblx0XHQgKiBQYXJhbWV0ZXJzOlxuXHRcdCAqIFx0KGJvb2wpXG5cdFx0ICovXG5cdFx0c2V0RW5hYmxlZDogZnVuY3Rpb24oYm9vbCkge1xuXHRcdFx0dGhpcy50b2dnbGVDbGFzcygnZGlzYWJsZWQnLCAhKGJvb2wpKTtcblx0XHR9LFxuXHRcdFxuXHRcdC8qKlxuXHRcdCAqIEZ1bmN0aW9uOiBnZXRDbGFzc25hbWVcblx0XHQgKiBcblx0XHQgKiBSZXR1cm5zIFBIUCBjbGFzcyBmb3IgdGhpcyBlbGVtZW50LiBVc2VmdWwgdG8gY2hlY2sgYnVzaW5lc3MgcnVsZXMgbGlrZSB2YWxpZCBkcmFnJ24nZHJvcCB0YXJnZXRzLlxuXHRcdCAqL1xuXHRcdGdldENsYXNzbmFtZTogZnVuY3Rpb24oKSB7XG5cdFx0XHR2YXIgbWF0Y2hlcyA9IHRoaXMuYXR0cignY2xhc3MnKS5tYXRjaCgvY2xhc3MtKFteXFxzXSopL2kpO1xuXHRcdFx0cmV0dXJuIG1hdGNoZXMgPyBtYXRjaGVzWzFdIDogJyc7XG5cdFx0fSxcblx0XHRcblx0XHQvKipcblx0XHQgKiBGdW5jdGlvbjogZ2V0SURcblx0XHQgKiBcblx0XHQgKiBSZXR1cm5zOlxuXHRcdCAqIFx0KE51bWJlcilcblx0XHQgKi9cblx0XHRnZXRJRDogZnVuY3Rpb24oKSB7XG5cdFx0XHRyZXR1cm4gdGhpcy5kYXRhKCdpZCcpO1xuXHRcdH1cblx0fSk7XG59KTtcbiIsImltcG9ydCAkIGZyb20gJ2pRdWVyeSc7XG5cbiQuZW50d2luZSgnc3MnLCBmdW5jdGlvbigkKXtcblxuXHQvLyBBbnkgVHJlZURvd25kb3duRmllbGQgbmVlZHMgdG8gcmVmcmVzaCBpdCdzIGNvbnRlbnRzIGFmdGVyIGEgZm9ybSBzdWJtaXNzaW9uLFxuXHQvLyBiZWNhdXNlIHRoZSB0cmVlIG9uIHRoZSBiYWNrZW5kIG1pZ2h0IGhhdmUgY2hhbmdlZFxuXHQkKCcuVHJlZURyb3Bkb3duRmllbGQnKS5lbnR3aW5lKHtcblx0XHQnZnJvbSAuY21zLWNvbnRhaW5lciBmb3JtJzoge1xuXHRcdFx0b25hZnRlcnN1Ym1pdGZvcm06IGZ1bmN0aW9uKGUpe1xuXHRcdFx0XHR0aGlzLmZpbmQoJy50cmVlLWhvbGRlcicpLmVtcHR5KCk7XG5cdFx0XHRcdHRoaXMuX3N1cGVyKCk7XG5cdFx0XHR9XG5cdFx0fVxuXHR9KTtcblxufSk7XG4iLCIvKipcbiAqIEZpbGU6IExlZnRBbmRNYWluLmpzXG4gKi9cbmltcG9ydCAkIGZyb20gJ2pRdWVyeSc7XG5pbXBvcnQgcm91dGVyIGZyb20gJ3JvdXRlcic7XG5pbXBvcnQgQ29uZmlnIGZyb20gJ2NvbmZpZyc7XG5cbnZhciB3aW5kb3dXaWR0aCwgd2luZG93SGVpZ2h0O1xuXG4kLm5vQ29uZmxpY3QoKTtcblxud2luZG93LnNzID0gd2luZG93LnNzIHx8IHt9O1xud2luZG93LnNzLnJvdXRlciA9IHJvdXRlcjtcblxuLyoqXG4gKiBAZnVuYyBkZWJvdW5jZVxuICogQHBhcmFtIGZ1bmMge2Z1bmN0aW9ufSAtIFRoZSBjYWxsYmFjayB0byBpbnZva2UgYWZ0ZXIgYHdhaXRgIG1pbGxpc2Vjb25kcy5cbiAqIEBwYXJhbSB3YWl0IHtudW1iZXJ9IC0gTWlsbGlzZWNvbmRzIHRvIHdhaXQuXG4gKiBAcGFyYW0gaW1tZWRpYXRlIHtib29sZWFufSAtIElmIHRydWUgdGhlIGNhbGxiYWNrIHdpbGwgYmUgaW52b2tlZCBhdCB0aGUgc3RhcnQgcmF0aGVyIHRoYW4gdGhlIGVuZC5cbiAqIEByZXR1cm4ge2Z1bmN0aW9ufVxuICogQGRlc2MgUmV0dXJucyBhIGZ1bmN0aW9uIHRoYXQgd2lsbCBub3QgYmUgY2FsbGVkIHVudGlsIGl0IGhhc24ndCBiZWVuIGludm9rZWQgZm9yIGB3YWl0YCBzZWNvbmRzLlxuICovXG53aW5kb3cuc3MuZGVib3VuY2UgPSBmdW5jdGlvbiAoZnVuYywgd2FpdCwgaW1tZWRpYXRlKSB7XG5cdHZhciB0aW1lb3V0LCBjb250ZXh0LCBhcmdzO1xuXG5cdHZhciBsYXRlciA9IGZ1bmN0aW9uKCkge1xuXHRcdHRpbWVvdXQgPSBudWxsO1xuXHRcdGlmICghaW1tZWRpYXRlKSBmdW5jLmFwcGx5KGNvbnRleHQsIGFyZ3MpO1xuXHR9O1xuXG5cdHJldHVybiBmdW5jdGlvbigpIHtcblx0XHR2YXIgY2FsbE5vdyA9IGltbWVkaWF0ZSAmJiAhdGltZW91dDtcblxuXHRcdGNvbnRleHQgPSB0aGlzO1xuXHRcdGFyZ3MgPSBhcmd1bWVudHM7XG5cblx0XHRjbGVhclRpbWVvdXQodGltZW91dCk7XG5cdFx0dGltZW91dCA9IHNldFRpbWVvdXQobGF0ZXIsIHdhaXQpO1xuXG5cdFx0aWYgKGNhbGxOb3cpIHtcblx0XHRcdGZ1bmMuYXBwbHkoY29udGV4dCwgYXJncyk7XG5cdFx0fVxuXHR9O1xufTtcblxuLyoqXG4gKiBFeHRyYWN0cyB0aGUgcGF0aG5hbWUgZnJvbSBhIFVSTC5cbiAqXG4gKiBAcGFyYW0gc3RyaW5nIHVybFxuICogQHJldHVybiBzdHJpbmdcbiAqL1xuZnVuY3Rpb24gZ2V0VXJsUGF0aCh1cmwpIHtcblx0dmFyIGFuY2hvciA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2EnKTtcblx0YW5jaG9yLmhyZWYgPSB1cmw7XG5cblx0cmV0dXJuIGFuY2hvci5wYXRobmFtZVxufVxuXG4kKHdpbmRvdykuYmluZCgncmVzaXplLmxlZnRhbmRtYWluJywgZnVuY3Rpb24oZSkge1xuXHQvLyBFbnR3aW5lJ3MgJ2Zyb21XaW5kb3c6Om9ucmVzaXplJyBkb2VzIG5vdCB0cmlnZ2VyIG9uIElFOC4gVXNlIHN5bnRoZXRpYyBldmVudC5cblx0dmFyIGNiID0gZnVuY3Rpb24oKSB7JCgnLmNtcy1jb250YWluZXInKS50cmlnZ2VyKCd3aW5kb3dyZXNpemUnKTt9O1xuXG5cdC8vIFdvcmthcm91bmQgdG8gYXZvaWQgSUU4IGluZmluaXRlIGxvb3BzIHdoZW4gZWxlbWVudHMgYXJlIHJlc2l6ZWQgYXMgYSByZXN1bHQgb2YgdGhpcyBldmVudFxuXHRpZigkLmJyb3dzZXIubXNpZSAmJiBwYXJzZUludCgkLmJyb3dzZXIudmVyc2lvbiwgMTApIDwgOSkge1xuXHRcdHZhciBuZXdXaW5kb3dXaWR0aCA9ICQod2luZG93KS53aWR0aCgpLCBuZXdXaW5kb3dIZWlnaHQgPSAkKHdpbmRvdykuaGVpZ2h0KCk7XG5cdFx0aWYobmV3V2luZG93V2lkdGggIT0gd2luZG93V2lkdGggfHwgbmV3V2luZG93SGVpZ2h0ICE9IHdpbmRvd0hlaWdodCkge1xuXHRcdFx0d2luZG93V2lkdGggPSBuZXdXaW5kb3dXaWR0aDtcblx0XHRcdHdpbmRvd0hlaWdodCA9IG5ld1dpbmRvd0hlaWdodDtcblx0XHRcdGNiKCk7XG5cdFx0fVxuXHR9IGVsc2Uge1xuXHRcdGNiKCk7XG5cdH1cbn0pO1xuXG4vLyBzZXR1cCBqcXVlcnkuZW50d2luZVxuJC5lbnR3aW5lLndhcm5pbmdMZXZlbCA9ICQuZW50d2luZS5XQVJOX0xFVkVMX0JFU1RQUkFDVElTRTtcblxuJC5lbnR3aW5lKCdzcycsIGZ1bmN0aW9uKCQpIHtcblxuXHQvKlxuXHQgKiBIYW5kbGUgbWVzc2FnZXMgc2VudCB2aWEgbmVzdGVkIGlmcmFtZXNcblx0ICogTWVzc2FnZXMgc2hvdWxkIGJlIHJhaXNlZCB2aWEgcG9zdE1lc3NhZ2Ugd2l0aCBhbiBvYmplY3Qgd2l0aCB0aGUgJ3R5cGUnIHBhcmFtZXRlciBnaXZlbi5cblx0ICogQW4gb3B0aW9uYWwgJ3RhcmdldCcgYW5kICdkYXRhJyBwYXJhbWV0ZXIgY2FuIGFsc28gYmUgc3BlY2lmaWVkLiBJZiBubyB0YXJnZXQgaXMgc3BlY2lmaWVkXG5cdCAqIGV2ZW50cyB3aWxsIGJlIHNlbnQgdG8gdGhlIHdpbmRvdyBpbnN0ZWFkLlxuXHQgKiB0eXBlIHNob3VsZCBiZSBvbmUgb2Y6XG5cdCAqICAtICdldmVudCcgLSBXaWxsIHRyaWdnZXIgdGhlIGdpdmVuIGV2ZW50IChzcGVjaWZpZWQgYnkgJ2V2ZW50Jykgb24gdGhlIHRhcmdldFxuXHQgKiAgLSAnY2FsbGJhY2snIC0gV2lsbCBjYWxsIHRoZSBnaXZlbiBtZXRob2QgKHNwZWNpZmllZCBieSAnY2FsbGJhY2snKSBvbiB0aGUgdGFyZ2V0XG5cdCAqL1xuXHQkKHdpbmRvdykub24oXCJtZXNzYWdlXCIsIGZ1bmN0aW9uKGUpIHtcblx0XHR2YXIgdGFyZ2V0LFxuXHRcdFx0ZXZlbnQgPSBlLm9yaWdpbmFsRXZlbnQsXG5cdFx0XHRkYXRhID0gdHlwZW9mIGV2ZW50LmRhdGEgPT09ICdvYmplY3QnID8gZXZlbnQuZGF0YSA6IEpTT04ucGFyc2UoZXZlbnQuZGF0YSk7XG5cblx0XHQvLyBSZWplY3QgbWVzc2FnZXMgb3V0c2lkZSBvZiB0aGUgc2FtZSBvcmlnaW5cblx0XHRpZigkLnBhdGgucGFyc2VVcmwod2luZG93LmxvY2F0aW9uLmhyZWYpLmRvbWFpbiAhPT0gJC5wYXRoLnBhcnNlVXJsKGV2ZW50Lm9yaWdpbikuZG9tYWluKSByZXR1cm47XG5cblx0XHQvLyBHZXQgdGFyZ2V0IG9mIHRoaXMgYWN0aW9uXG5cdFx0dGFyZ2V0ID0gdHlwZW9mKGRhdGEudGFyZ2V0KSA9PT0gJ3VuZGVmaW5lZCdcblx0XHRcdD8gJCh3aW5kb3cpXG5cdFx0XHQ6ICQoZGF0YS50YXJnZXQpO1xuXG5cdFx0Ly8gRGV0ZXJtaW5lIGFjdGlvblxuXHRcdHN3aXRjaChkYXRhLnR5cGUpIHtcblx0XHRcdGNhc2UgJ2V2ZW50Jzpcblx0XHRcdFx0dGFyZ2V0LnRyaWdnZXIoZGF0YS5ldmVudCwgZGF0YS5kYXRhKTtcblx0XHRcdFx0YnJlYWs7XG5cdFx0XHRjYXNlICdjYWxsYmFjayc6XG5cdFx0XHRcdHRhcmdldFtkYXRhLmNhbGxiYWNrXS5jYWxsKHRhcmdldCwgZGF0YS5kYXRhKTtcblx0XHRcdFx0YnJlYWs7XG5cdFx0fVxuXHR9KTtcblxuXHQvKipcblx0ICogUG9zaXRpb24gdGhlIGxvYWRpbmcgc3Bpbm5lciBhbmltYXRpb24gYmVsb3cgdGhlIHNzIGxvZ29cblx0ICovXG5cdHZhciBwb3NpdGlvbkxvYWRpbmdTcGlubmVyID0gZnVuY3Rpb24oKSB7XG5cdFx0dmFyIG9mZnNldCA9IDEyMDsgLy8gb2Zmc2V0IGZyb20gdGhlIHNzIGxvZ29cblx0XHR2YXIgc3Bpbm5lciA9ICQoJy5zcy1sb2FkaW5nLXNjcmVlbiAubG9hZGluZy1hbmltYXRpb24nKTtcblx0XHR2YXIgdG9wID0gKCQod2luZG93KS5oZWlnaHQoKSAtIHNwaW5uZXIuaGVpZ2h0KCkpIC8gMjtcblx0XHRzcGlubmVyLmNzcygndG9wJywgdG9wICsgb2Zmc2V0KTtcblx0XHRzcGlubmVyLnNob3coKTtcblx0fTtcblxuXHQvLyBhcHBseSBhbiBzZWxlY3QgZWxlbWVudCBvbmx5IHdoZW4gaXQgaXMgcmVhZHksIGllLiB3aGVuIGl0IGlzIHJlbmRlcmVkIGludG8gYSB0ZW1wbGF0ZVxuXHQvLyB3aXRoIGNzcyBhcHBsaWVkIGFuZCBnb3QgYSB3aWR0aCB2YWx1ZS5cblx0dmFyIGFwcGx5Q2hvc2VuID0gZnVuY3Rpb24oZWwpIHtcblx0XHRpZihlbC5pcygnOnZpc2libGUnKSkge1xuXHRcdFx0ZWwuYWRkQ2xhc3MoJ2hhcy1jaHpuJykuY2hvc2VuKHtcblx0XHRcdFx0YWxsb3dfc2luZ2xlX2Rlc2VsZWN0OiB0cnVlLFxuXHRcdFx0XHRkaXNhYmxlX3NlYXJjaF90aHJlc2hvbGQ6IDIwXG5cdFx0XHR9KTtcblxuXHRcdFx0dmFyIHRpdGxlID0gZWwucHJvcCgndGl0bGUnKTtcblxuXHRcdFx0aWYodGl0bGUpIHtcblx0XHRcdFx0ZWwuc2libGluZ3MoJy5jaHpuLWNvbnRhaW5lcicpLnByb3AoJ3RpdGxlJywgdGl0bGUpO1xuXHRcdFx0fVxuXHRcdH0gZWxzZSB7XG5cdFx0XHRzZXRUaW1lb3V0KGZ1bmN0aW9uKCkge1xuXHRcdFx0XHQvLyBNYWtlIHN1cmUgaXQncyB2aXNpYmxlIGJlZm9yZSBhcHBseWluZyB0aGUgdWlcblx0XHRcdFx0ZWwuc2hvdygpO1xuXHRcdFx0XHRhcHBseUNob3NlbihlbCk7IH0sXG5cdFx0XHQ1MDApO1xuXHRcdH1cblx0fTtcblxuXHQvKipcblx0ICogQ29tcGFyZSBVUkxzLCBidXQgbm9ybWFsaXplIHRyYWlsaW5nIHNsYXNoZXMgaW5cblx0ICogVVJMIHRvIHdvcmsgYXJvdW5kIHJvdXRpbmcgd2VpcmRuZXNzZXMgaW4gU1NfSFRUUFJlcXVlc3QuXG5cdCAqIEFsc28gbm9ybWFsaXplcyByZWxhdGl2ZSBVUkxzIGJ5IHByZWZpeGluZyB0aGVtIHdpdGggdGhlIDxiYXNlPi5cblx0ICovXG5cdHZhciBpc1NhbWVVcmwgPSBmdW5jdGlvbih1cmwxLCB1cmwyKSB7XG5cdFx0dmFyIGJhc2VVcmwgPSAkKCdiYXNlJykuYXR0cignaHJlZicpO1xuXHRcdHVybDEgPSAkLnBhdGguaXNBYnNvbHV0ZVVybCh1cmwxKSA/IHVybDEgOiAkLnBhdGgubWFrZVVybEFic29sdXRlKHVybDEsIGJhc2VVcmwpLFxuXHRcdHVybDIgPSAkLnBhdGguaXNBYnNvbHV0ZVVybCh1cmwyKSA/IHVybDIgOiAkLnBhdGgubWFrZVVybEFic29sdXRlKHVybDIsIGJhc2VVcmwpO1xuXHRcdHZhciB1cmwxcGFydHMgPSAkLnBhdGgucGFyc2VVcmwodXJsMSksIHVybDJwYXJ0cyA9ICQucGF0aC5wYXJzZVVybCh1cmwyKTtcblx0XHRyZXR1cm4gKFxuXHRcdFx0dXJsMXBhcnRzLnBhdGhuYW1lLnJlcGxhY2UoL1xcLyokLywgJycpID09IHVybDJwYXJ0cy5wYXRobmFtZS5yZXBsYWNlKC9cXC8qJC8sICcnKSAmJlxuXHRcdFx0dXJsMXBhcnRzLnNlYXJjaCA9PSB1cmwycGFydHMuc2VhcmNoXG5cdFx0KTtcblx0fTtcblxuXHR2YXIgYWpheENvbXBsZXRlRXZlbnQgPSB3aW5kb3cuc3MuZGVib3VuY2UoZnVuY3Rpb24gKCkge1xuXHRcdCQod2luZG93KS50cmlnZ2VyKCdhamF4Q29tcGxldGUnKTtcblx0fSwgMTAwMCwgdHJ1ZSk7XG5cblx0JCh3aW5kb3cpLmJpbmQoJ3Jlc2l6ZScsIHBvc2l0aW9uTG9hZGluZ1NwaW5uZXIpLnRyaWdnZXIoJ3Jlc2l6ZScpO1xuXG5cdC8vIGdsb2JhbCBhamF4IGhhbmRsZXJzXG5cdCQoZG9jdW1lbnQpLmFqYXhDb21wbGV0ZShmdW5jdGlvbihlLCB4aHIsIHNldHRpbmdzKSB7XG5cdFx0Ly8gU2ltdWxhdGVzIGEgcmVkaXJlY3Qgb24gYW4gYWpheCByZXNwb25zZS5cblx0XHR2YXIgb3JpZ1VybCxcblx0XHRcdHVybCA9IHhoci5nZXRSZXNwb25zZUhlYWRlcignWC1Db250cm9sbGVyVVJMJyksXG5cdFx0XHRkZXN0VXJsID0gc2V0dGluZ3MudXJsLFxuXHRcdFx0bXNnID0geGhyLmdldFJlc3BvbnNlSGVhZGVyKCdYLVN0YXR1cycpICE9PSBudWxsID8geGhyLmdldFJlc3BvbnNlSGVhZGVyKCdYLVN0YXR1cycpIDogeGhyLnN0YXR1c1RleHQsIC8vIEhhbmRsZSBjdXN0b20gc3RhdHVzIG1lc3NhZ2UgaGVhZGVyc1xuXHRcdFx0bXNnVHlwZSA9ICh4aHIuc3RhdHVzIDwgMjAwIHx8IHhoci5zdGF0dXMgPiAzOTkpID8gJ2JhZCcgOiAnZ29vZCcsXG5cdFx0XHRpZ25vcmVkTWVzc2FnZXMgPSBbJ09LJ107XG5cdFx0aWYod2luZG93Lmhpc3Rvcnkuc3RhdGUpIHtcblx0XHRcdG9yaWdVcmwgPSB3aW5kb3cuaGlzdG9yeS5zdGF0ZS5wYXRoO1xuXHRcdH0gZWxzZSB7XG5cdFx0XHRvcmlnVXJsID0gZG9jdW1lbnQuVVJMO1xuXHRcdH1cblxuXHRcdC8vIE9ubHkgcmVkaXJlY3QgaWYgY29udHJvbGxlciB1cmwgZGlmZmVycyB0byB0aGUgcmVxdWVzdGVkIG9yIGN1cnJlbnQgb25lXG5cdFx0aWYgKHVybCAhPT0gbnVsbCAmJiAoIWlzU2FtZVVybChvcmlnVXJsLCB1cmwpIHx8ICFpc1NhbWVVcmwoZGVzdFVybCwgdXJsKSkpIHtcblx0XHRcdHJvdXRlci5zaG93KHVybCwge1xuXHRcdFx0XHRpZDogKG5ldyBEYXRlKCkpLmdldFRpbWUoKSArIFN0cmluZyhNYXRoLnJhbmRvbSgpKS5yZXBsYWNlKC9cXEQvZywnJyksIC8vIEVuc3VyZSB0aGF0IHJlZGlyZWN0aW9ucyBhcmUgZm9sbG93ZWQgdGhyb3VnaCBieSBoaXN0b3J5IEFQSSBieSBoYW5kaW5nIGl0IGEgdW5pcXVlIElEXG5cdFx0XHRcdHBqYXg6IHhoci5nZXRSZXNwb25zZUhlYWRlcignWC1QamF4JykgPyB4aHIuZ2V0UmVzcG9uc2VIZWFkZXIoJ1gtUGpheCcpIDogc2V0dGluZ3MuaGVhZGVyc1snWC1QamF4J11cblx0XHRcdH0pO1xuXHRcdH1cblxuXHRcdC8vIEVuYWJsZSByZWF1dGhlbnRpY2F0ZSBkaWFsb2cgaWYgcmVxdWVzdGVkXG5cdFx0aWYgKHhoci5nZXRSZXNwb25zZUhlYWRlcignWC1SZWF1dGhlbnRpY2F0ZScpKSB7XG5cdFx0XHQkKCcuY21zLWNvbnRhaW5lcicpLnNob3dMb2dpbkRpYWxvZygpO1xuXHRcdFx0cmV0dXJuO1xuXHRcdH1cblxuXHRcdC8vIFNob3cgbWVzc2FnZSAoYnV0IGlnbm9yZSBhYm9ydGVkIHJlcXVlc3RzKVxuXHRcdGlmICh4aHIuc3RhdHVzICE9PSAwICYmIG1zZyAmJiAkLmluQXJyYXkobXNnLCBpZ25vcmVkTWVzc2FnZXMpKSB7XG5cdFx0XHQvLyBEZWNvZGUgaW50byBVVEYtOCwgSFRUUCBoZWFkZXJzIGRvbid0IGFsbG93IG11bHRpYnl0ZVxuXHRcdFx0c3RhdHVzTWVzc2FnZShkZWNvZGVVUklDb21wb25lbnQobXNnKSwgbXNnVHlwZSk7XG5cdFx0fVxuXG5cdFx0YWpheENvbXBsZXRlRXZlbnQodGhpcyk7XG5cdH0pO1xuXG5cdC8qKlxuXHQgKiBNYWluIExlZnRBbmRNYWluIGludGVyZmFjZSB3aXRoIHNvbWUgY29udHJvbCBwYW5lbCBhbmQgYW4gZWRpdCBmb3JtLlxuXHQgKlxuXHQgKiBFdmVudHM6XG5cdCAqICBhamF4c3VibWl0IC0gLi4uXG5cdCAqICB2YWxpZGF0ZSAtIC4uLlxuXHQgKiAgYWZ0ZXJzdWJtaXRmb3JtIC0gLi4uXG5cdCAqL1xuXHQkKCcuY21zLWNvbnRhaW5lcicpLmVudHdpbmUoe1xuXG5cdFx0LyoqXG5cdFx0ICogVHJhY2tzIGN1cnJlbnQgcGFuZWwgcmVxdWVzdC5cblx0XHQgKi9cblx0XHRTdGF0ZUNoYW5nZVhIUjogbnVsbCxcblxuXHRcdC8qKlxuXHRcdCAqIFRyYWNrcyBjdXJyZW50IGZyYWdtZW50LW9ubHkgcGFyYWxsZWwgUEpBWCByZXF1ZXN0cy5cblx0XHQgKi9cblx0XHRGcmFnbWVudFhIUjoge30sXG5cblx0XHRTdGF0ZUNoYW5nZUNvdW50OiAwLFxuXG5cdFx0LyoqXG5cdFx0ICogT3B0aW9ucyBmb3IgdGhlIHRocmVlQ29sdW1uQ29tcHJlc3NvciBsYXlvdXQgYWxnb3JpdGhtLlxuXHRcdCAqXG5cdFx0ICogU2VlIExlZnRBbmRNYWluLkxheW91dC5qcyBmb3IgZGVzY3JpcHRpb24gb2YgdGhlc2Ugb3B0aW9ucy5cblx0XHQgKi9cblx0XHRMYXlvdXRPcHRpb25zOiB7XG5cdFx0XHRtaW5Db250ZW50V2lkdGg6IDk0MCxcblx0XHRcdG1pblByZXZpZXdXaWR0aDogNDAwLFxuXHRcdFx0bW9kZTogJ2NvbnRlbnQnXG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIENvbnN0cnVjdG9yOiBvbm1hdGNoXG5cdFx0ICovXG5cdFx0b25hZGQ6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dmFyIHNlbGYgPSB0aGlzLFxuXHRcdFx0XHRiYXNlUGF0aCA9IGdldFVybFBhdGgoJCgnYmFzZScpWzBdLmhyZWYpO1xuXG5cdFx0XHQvLyBBdm9pZCBhZGRpbmcgYSBkb3VibGUgc2xhc2ggaWYgdGhlIGJhc2UgcGF0aCBpcyAnLydcblx0XHRcdGlmIChiYXNlUGF0aFtiYXNlUGF0aC5sZW5ndGggLSAxXSA9PT0gJy8nKSB7XG5cdFx0XHRcdGJhc2VQYXRoICs9ICdhZG1pbic7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRiYXNlUGF0aCA9ICcvYWRtaW4nO1xuXHRcdFx0fVxuXG5cdFx0XHRyb3V0ZXIuYmFzZShiYXNlUGF0aCk7XG5cblx0XHRcdC8vIFJlZ2lzdGVyIGFsbCB0b3AgbGV2ZWwgcm91dGVzLlxuXHRcdFx0Q29uZmlnLmdldFRvcExldmVsUm91dGVzKCkuZm9yRWFjaCgocm91dGUpID0+IHtcblx0XHRcdFx0cm91dGVyKGAvJHtyb3V0ZX0vKmAsIChjdHgsIG5leHQpID0+IHtcblx0XHRcdFx0XHQvLyBJZiB0aGUgcGFnZSBpc24ndCByZWFkeSBvciB0aGUgcmVxdWVzdCBoYXNuJ3QgY29tZSBmcm9tICdsb2FkUGFuZWwnXG5cdFx0XHRcdFx0Ly8gdGhlbiBkb24ndCBQSkFYIGxvYWQgdGhlIHBhbmVsLiBOb3RlOiBfX2ZvcmNlUmVmZXJlciBpcyBzZXQgYnkgJ2xvYWRQYW5lbCcgb25seS5cblx0XHRcdFx0XHRpZiAoZG9jdW1lbnQucmVhZHlTdGF0ZSAhPT0gJ2NvbXBsZXRlJyB8fCB0eXBlb2YgY3R4LnN0YXRlLl9fZm9yY2VSZWZlcmVyID09PSAndW5kZWZpbmVkJykge1xuXHRcdFx0XHRcdFx0cmV0dXJuIG5leHQoKTtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHQvLyBMb2FkIHRoZSBwYW5lbCB0aGVuIGNhbGwgdGhlIG5leHQgcm91dGUuXG5cdFx0XHRcdFx0c2VsZi5oYW5kbGVTdGF0ZUNoYW5nZShudWxsLCBjdHguc3RhdGUpXG5cdFx0XHRcdFx0XHQuZG9uZShuZXh0KTtcblx0XHRcdFx0fSk7XG5cdFx0XHR9KTtcblxuXHRcdFx0cm91dGVyLnN0YXJ0KCk7XG5cblx0XHRcdC8vIEJyb3dzZXIgZGV0ZWN0aW9uXG5cdFx0XHRpZigkLmJyb3dzZXIubXNpZSAmJiBwYXJzZUludCgkLmJyb3dzZXIudmVyc2lvbiwgMTApIDwgOCkge1xuXHRcdFx0XHQkKCcuc3MtbG9hZGluZy1zY3JlZW4nKS5hcHBlbmQoXG5cdFx0XHRcdFx0JzxwIGNsYXNzPVwic3MtbG9hZGluZy1pbmNvbXBhdC13YXJuaW5nXCI+PHNwYW4gY2xhc3M9XCJub3RpY2VcIj4nICtcblx0XHRcdFx0XHQnWW91ciBicm93c2VyIGlzIG5vdCBjb21wYXRpYmxlIHdpdGggdGhlIENNUyBpbnRlcmZhY2UuIFBsZWFzZSB1c2UgSW50ZXJuZXQgRXhwbG9yZXIgOCssIEdvb2dsZSBDaHJvbWUgb3IgTW96aWxsYSBGaXJlZm94LicgK1xuXHRcdFx0XHRcdCc8L3NwYW4+PC9wPidcblx0XHRcdFx0KS5jc3MoJ3otaW5kZXgnLCAkKCcuc3MtbG9hZGluZy1zY3JlZW4nKS5jc3MoJ3otaW5kZXgnKSsxKTtcblx0XHRcdFx0JCgnLmxvYWRpbmctYW5pbWF0aW9uJykucmVtb3ZlKCk7XG5cblx0XHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHQvLyBJbml0aWFsaXplIGxheW91dHNcblx0XHRcdHRoaXMucmVkcmF3KCk7XG5cblx0XHRcdC8vIFJlbW92ZSBsb2FkaW5nIHNjcmVlblxuXHRcdFx0JCgnLnNzLWxvYWRpbmctc2NyZWVuJykuaGlkZSgpO1xuXHRcdFx0JCgnYm9keScpLnJlbW92ZUNsYXNzKCdsb2FkaW5nJyk7XG5cdFx0XHQkKHdpbmRvdykudW5iaW5kKCdyZXNpemUnLCBwb3NpdGlvbkxvYWRpbmdTcGlubmVyKTtcblx0XHRcdHRoaXMucmVzdG9yZVRhYlN0YXRlKCk7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdH0sXG5cblx0XHRmcm9tV2luZG93OiB7XG5cdFx0XHRvbnN0YXRlY2hhbmdlOiBmdW5jdGlvbihldmVudCwgaGlzdG9yeVN0YXRlKXtcblx0XHRcdFx0dGhpcy5oYW5kbGVTdGF0ZUNoYW5nZShldmVudCwgaGlzdG9yeVN0YXRlKTtcblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0J29ud2luZG93cmVzaXplJzogZnVuY3Rpb24oKSB7XG5cdFx0XHR0aGlzLnJlZHJhdygpO1xuXHRcdH0sXG5cblx0XHQnZnJvbSAuY21zLXBhbmVsJzoge1xuXHRcdFx0b250b2dnbGU6IGZ1bmN0aW9uKCl7IHRoaXMucmVkcmF3KCk7IH1cblx0XHR9LFxuXG5cdFx0J2Zyb20gLmNtcy1jb250YWluZXInOiB7XG5cdFx0XHRvbmFmdGVyc3VibWl0Zm9ybTogZnVuY3Rpb24oKXsgdGhpcy5yZWRyYXcoKTsgfVxuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBFbnN1cmUgdGhlIHVzZXIgY2FuIHNlZSB0aGUgcmVxdWVzdGVkIHNlY3Rpb24gLSByZXN0b3JlIHRoZSBkZWZhdWx0IHZpZXcuXG5cdFx0ICovXG5cdFx0J2Zyb20gLmNtcy1tZW51LWxpc3QgbGkgYSc6IHtcblx0XHRcdG9uY2xpY2s6IGZ1bmN0aW9uKGUpIHtcblx0XHRcdFx0dmFyIGhyZWYgPSAkKGUudGFyZ2V0KS5hdHRyKCdocmVmJyk7XG5cdFx0XHRcdGlmKGUud2hpY2ggPiAxIHx8IGhyZWYgPT0gdGhpcy5fdGFiU3RhdGVVcmwoKSkgcmV0dXJuO1xuXHRcdFx0XHR0aGlzLnNwbGl0Vmlld01vZGUoKTtcblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogQ2hhbmdlIHRoZSBvcHRpb25zIG9mIHRoZSB0aHJlZUNvbHVtbkNvbXByZXNzb3IgbGF5b3V0LCBhbmQgdHJpZ2dlciBsYXlvdXRpbmcgaWYgbmVlZGVkLlxuXHRcdCAqIFlvdSBjYW4gcHJvdmlkZSBhbnkgb3IgYWxsIG9wdGlvbnMuIFRoZSByZW1haW5pbmcgb3B0aW9ucyB3aWxsIG5vdCBiZSBjaGFuZ2VkLlxuXHRcdCAqL1xuXHRcdHVwZGF0ZUxheW91dE9wdGlvbnM6IGZ1bmN0aW9uKG5ld1NwZWMpIHtcblx0XHRcdHZhciBzcGVjID0gdGhpcy5nZXRMYXlvdXRPcHRpb25zKCk7XG5cblx0XHRcdHZhciBkaXJ0eSA9IGZhbHNlO1xuXG5cdFx0XHRmb3IgKHZhciBrIGluIG5ld1NwZWMpIHtcblx0XHRcdFx0aWYgKHNwZWNba10gIT09IG5ld1NwZWNba10pIHtcblx0XHRcdFx0XHRzcGVjW2tdID0gbmV3U3BlY1trXTtcblx0XHRcdFx0XHRkaXJ0eSA9IHRydWU7XG5cdFx0XHRcdH1cblx0XHRcdH1cblxuXHRcdFx0aWYgKGRpcnR5KSB0aGlzLnJlZHJhdygpO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBFbmFibGUgdGhlIHNwbGl0IHZpZXcgLSB3aXRoIGNvbnRlbnQgb24gdGhlIGxlZnQgYW5kIHByZXZpZXcgb24gdGhlIHJpZ2h0LlxuXHRcdCAqL1xuXHRcdHNwbGl0Vmlld01vZGU6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dGhpcy51cGRhdGVMYXlvdXRPcHRpb25zKHtcblx0XHRcdFx0bW9kZTogJ3NwbGl0J1xuXHRcdFx0fSk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIENvbnRlbnQgb25seS5cblx0XHQgKi9cblx0XHRjb250ZW50Vmlld01vZGU6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dGhpcy51cGRhdGVMYXlvdXRPcHRpb25zKHtcblx0XHRcdFx0bW9kZTogJ2NvbnRlbnQnXG5cdFx0XHR9KTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogUHJldmlldyBvbmx5LlxuXHRcdCAqL1xuXHRcdHByZXZpZXdNb2RlOiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMudXBkYXRlTGF5b3V0T3B0aW9ucyh7XG5cdFx0XHRcdG1vZGU6ICdwcmV2aWV3J1xuXHRcdFx0fSk7XG5cdFx0fSxcblxuXHRcdFJlZHJhd1N1cHByZXNzaW9uOiBmYWxzZSxcblxuXHRcdHJlZHJhdzogZnVuY3Rpb24oKSB7XG5cdFx0XHRpZiAodGhpcy5nZXRSZWRyYXdTdXBwcmVzc2lvbigpKSByZXR1cm47XG5cblx0XHRcdGlmKHdpbmRvdy5kZWJ1ZykgY29uc29sZS5sb2coJ3JlZHJhdycsIHRoaXMuYXR0cignY2xhc3MnKSwgdGhpcy5nZXQoMCkpO1xuXG5cdFx0XHQvLyBSZXNldCB0aGUgYWxnb3JpdGhtLlxuXHRcdFx0dGhpcy5kYXRhKCdqbGF5b3V0JywgakxheW91dC50aHJlZUNvbHVtbkNvbXByZXNzb3IoXG5cdFx0XHRcdHtcblx0XHRcdFx0XHRtZW51OiB0aGlzLmNoaWxkcmVuKCcuY21zLW1lbnUnKSxcblx0XHRcdFx0XHRjb250ZW50OiB0aGlzLmNoaWxkcmVuKCcuY21zLWNvbnRlbnQnKSxcblx0XHRcdFx0XHRwcmV2aWV3OiB0aGlzLmNoaWxkcmVuKCcuY21zLXByZXZpZXcnKVxuXHRcdFx0XHR9LFxuXHRcdFx0XHR0aGlzLmdldExheW91dE9wdGlvbnMoKVxuXHRcdFx0KSk7XG5cblx0XHRcdC8vIFRyaWdnZXIgbGF5b3V0IGFsZ29yaXRobSBvbmNlIGF0IHRoZSB0b3AuIFRoaXMgYWxzbyBsYXlzIG91dCBjaGlsZHJlbiAtIHdlIG1vdmUgZnJvbSBvdXRzaWRlIHRvXG5cdFx0XHQvLyBpbnNpZGUsIHJlc2l6aW5nIHRvIGZpdCB0aGUgcGFyZW50LlxuXHRcdFx0dGhpcy5sYXlvdXQoKTtcblxuXHRcdFx0Ly8gUmVkcmF3IG9uIGFsbCB0aGUgY2hpbGRyZW4gdGhhdCBuZWVkIGl0XG5cdFx0XHR0aGlzLmZpbmQoJy5jbXMtcGFuZWwtbGF5b3V0JykucmVkcmF3KCk7XG5cdFx0XHR0aGlzLmZpbmQoJy5jbXMtY29udGVudC1maWVsZHNbZGF0YS1sYXlvdXQtdHlwZV0nKS5yZWRyYXcoKTtcblx0XHRcdHRoaXMuZmluZCgnLmNtcy1lZGl0LWZvcm1bZGF0YS1sYXlvdXQtdHlwZV0nKS5yZWRyYXcoKTtcblx0XHRcdHRoaXMuZmluZCgnLmNtcy1wcmV2aWV3JykucmVkcmF3KCk7XG5cdFx0XHR0aGlzLmZpbmQoJy5jbXMtY29udGVudCcpLnJlZHJhdygpO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBDb25maXJtIHdoZXRoZXIgdGhlIGN1cnJlbnQgdXNlciBjYW4gbmF2aWdhdGUgYXdheSBmcm9tIHRoaXMgcGFnZVxuXHRcdCAqXG5cdFx0ICogQHBhcmFtIHthcnJheX0gc2VsZWN0b3JzIE9wdGlvbmFsIGxpc3Qgb2Ygc2VsZWN0b3JzXG5cdFx0ICogQHJldHVybnMge2Jvb2xlYW59IFRydWUgaWYgdGhlIG5hdmlnYXRpb24gY2FuIHByb2NlZWRcblx0XHQgKi9cblx0XHRjaGVja0Nhbk5hdmlnYXRlOiBmdW5jdGlvbihzZWxlY3RvcnMpIHtcblx0XHRcdC8vIENoZWNrIGNoYW5nZSB0cmFja2luZyAoY2FuJ3QgdXNlIGV2ZW50cyBhcyB3ZSBuZWVkIGEgd2F5IHRvIGNhbmNlbCB0aGUgY3VycmVudCBzdGF0ZSBjaGFuZ2UpXG5cdFx0XHR2YXIgY29udGVudEVscyA9IHRoaXMuX2ZpbmRGcmFnbWVudHMoc2VsZWN0b3JzIHx8IFsnQ29udGVudCddKSxcblx0XHRcdFx0dHJhY2tlZEVscyA9IGNvbnRlbnRFbHNcblx0XHRcdFx0XHQuZmluZCgnOmRhdGEoY2hhbmdldHJhY2tlciknKVxuXHRcdFx0XHRcdC5hZGQoY29udGVudEVscy5maWx0ZXIoJzpkYXRhKGNoYW5nZXRyYWNrZXIpJykpLFxuXHRcdFx0XHRzYWZlID0gdHJ1ZTtcblxuXHRcdFx0aWYoIXRyYWNrZWRFbHMubGVuZ3RoKSB7XG5cdFx0XHRcdHJldHVybiB0cnVlO1xuXHRcdFx0fVxuXG5cdFx0XHR0cmFja2VkRWxzLmVhY2goZnVuY3Rpb24oKSB7XG5cdFx0XHRcdC8vIFNlZSBMZWZ0QW5kTWFpbi5FZGl0Rm9ybS5qc1xuXHRcdFx0XHRpZighJCh0aGlzKS5jb25maXJtVW5zYXZlZENoYW5nZXMoKSkge1xuXHRcdFx0XHRcdHNhZmUgPSBmYWxzZTtcblx0XHRcdFx0fVxuXHRcdFx0fSk7XG5cblx0XHRcdHJldHVybiBzYWZlO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBAcGFyYW0gc3RyaW5nIHVybFxuXHRcdCAqIEBwYXJhbSBzdHJpbmcgdGl0bGUgLSBOZXcgd2luZG93IHRpdGxlLlxuXHRcdCAqIEBwYXJhbSBvYmplY3QgZGF0YSAtIEFueSBhZGRpdGlvbmFsIGRhdGEgcGFzc2VkIHRocm91Z2ggdG8gYHdpbmRvdy5oaXN0b3J5LnN0YXRlYC5cblx0XHQgKiBAcGFyYW0gYm9vbGVhbiBmb3JjZVJlbG9hZCAtIEZvcmNlcyB0aGUgcmVwbGFjZW1lbnQgb2YgdGhlIGN1cnJlbnQgaGlzdG9yeSBzdGF0ZSwgZXZlbiBpZiB0aGUgVVJMIGlzIHRoZSBzYW1lLCBpLmUuIGFsbG93cyByZWxvYWRpbmcuXG5cdFx0ICovXG5cdFx0bG9hZFBhbmVsOiBmdW5jdGlvbiAodXJsLCB0aXRsZSA9ICcnLCBkYXRhID0ge30sIGZvcmNlUmVsb2FkLCBmb3JjZVJlZmVyZXIgPSB3aW5kb3cuaGlzdG9yeS5zdGF0ZS5wYXRoKSB7XG5cdFx0XHQvLyBDaGVjayBmb3IgdW5zYXZlZCBjaGFuZ2VzXG5cdFx0XHRpZiAoIXRoaXMuY2hlY2tDYW5OYXZpZ2F0ZShkYXRhLnBqYXggPyBkYXRhLnBqYXguc3BsaXQoJywnKSA6IFsnQ29udGVudCddKSkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdHRoaXMuc2F2ZVRhYlN0YXRlKCk7XG5cblx0XHRcdGRhdGEuX19mb3JjZVJlZmVyZXIgPSBmb3JjZVJlZmVyZXI7XG5cblx0XHRcdGlmIChmb3JjZVJlbG9hZCkge1xuXHRcdFx0XHRkYXRhLl9fZm9yY2VSZWxvYWQgPSBNYXRoLnJhbmRvbSgpOyAvLyBNYWtlIHN1cmUgdGhlIHBhZ2UgcmVsb2FkcyBldmVuIGlmIHRoZSBVUkwgaXMgdGhlIHNhbWUuXG5cdFx0XHR9XG5cblx0XHRcdHJvdXRlci5zaG93KHVybCwgZGF0YSk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIE5pY2Ugd3JhcHBlciBmb3IgcmVsb2FkaW5nIGN1cnJlbnQgaGlzdG9yeSBzdGF0ZS5cblx0XHQgKi9cblx0XHRyZWxvYWRDdXJyZW50UGFuZWw6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dGhpcy5sb2FkUGFuZWwod2luZG93Lmhpc3Rvcnkuc3RhdGUucGF0aCwgbnVsbCwgbnVsbCwgdHJ1ZSk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEZ1bmN0aW9uOiBzdWJtaXRGb3JtXG5cdFx0ICpcblx0XHQgKiBQYXJhbWV0ZXJzOlxuXHRcdCAqICB7RE9NRWxlbWVudH0gZm9ybSAtIFRoZSBmb3JtIHRvIGJlIHN1Ym1pdHRlZC4gTmVlZHMgdG8gYmUgcGFzc2VkXG5cdFx0ICogICBpbiB0byBhdm9pZCBlbnR3aW5lIG1ldGhvZHMvY29udGV4dCBiZWluZyByZW1vdmVkIHRocm91Z2ggcmVwbGFjaW5nIHRoZSBub2RlIGl0c2VsZi5cblx0XHQgKiAge0RPTUVsZW1lbnR9IGJ1dHRvbiAtIFRoZSBwcmVzc2VkIGJ1dHRvbiAob3B0aW9uYWwpXG5cdFx0ICogIHtGdW5jdGlvbn0gY2FsbGJhY2sgLSBDYWxsZWQgaW4gY29tcGxldGUoKSBoYW5kbGVyIG9mIGpRdWVyeS5hamF4KClcblx0XHQgKiAge09iamVjdH0gYWpheE9wdGlvbnMgLSBPYmplY3QgbGl0ZXJhbCB0byBtZXJnZSBpbnRvICQuYWpheCgpIGNhbGxcblx0XHQgKlxuXHRcdCAqIFJldHVybnM6XG5cdFx0ICogIChib29sZWFuKVxuXHRcdCAqL1xuXHRcdHN1Ym1pdEZvcm06IGZ1bmN0aW9uKGZvcm0sIGJ1dHRvbiwgY2FsbGJhY2ssIGFqYXhPcHRpb25zKSB7XG5cdFx0XHR2YXIgc2VsZiA9IHRoaXM7XG5cblx0XHRcdC8vIGxvb2sgZm9yIHNhdmUgYnV0dG9uXG5cdFx0XHRpZighYnV0dG9uKSBidXR0b24gPSB0aGlzLmZpbmQoJy5BY3Rpb25zIDpzdWJtaXRbbmFtZT1hY3Rpb25fc2F2ZV0nKTtcblx0XHRcdC8vIGRlZmF1bHQgdG8gZmlyc3QgYnV0dG9uIGlmIG5vbmUgZ2l2ZW4gLSBzaW11bGF0ZXMgYnJvd3NlciBiZWhhdmlvdXJcblx0XHRcdGlmKCFidXR0b24pIGJ1dHRvbiA9IHRoaXMuZmluZCgnLkFjdGlvbnMgOnN1Ym1pdDpmaXJzdCcpO1xuXG5cdFx0XHRmb3JtLnRyaWdnZXIoJ2JlZm9yZXN1Ym1pdGZvcm0nKTtcblx0XHRcdHRoaXMudHJpZ2dlcignc3VibWl0Zm9ybScsIHtmb3JtOiBmb3JtLCBidXR0b246IGJ1dHRvbn0pO1xuXG5cdFx0XHQvLyBzZXQgYnV0dG9uIHRvIFwic3VibWl0dGluZ1wiIHN0YXRlXG5cdFx0XHQkKGJ1dHRvbikuYWRkQ2xhc3MoJ2xvYWRpbmcnKTtcblxuXHRcdFx0Ly8gdmFsaWRhdGUgaWYgcmVxdWlyZWRcblx0XHRcdHZhciB2YWxpZGF0aW9uUmVzdWx0ID0gZm9ybS52YWxpZGF0ZSgpO1xuXHRcdFx0aWYodHlwZW9mIHZhbGlkYXRpb25SZXN1bHQhPT0ndW5kZWZpbmVkJyAmJiAhdmFsaWRhdGlvblJlc3VsdCkge1xuXHRcdFx0XHQvLyBUT0RPIEF1dG9tYXRpY2FsbHkgc3dpdGNoIHRvIHRoZSB0YWIvcG9zaXRpb24gb2YgdGhlIGZpcnN0IGVycm9yXG5cdFx0XHRcdHN0YXR1c01lc3NhZ2UoXCJWYWxpZGF0aW9uIGZhaWxlZC5cIiwgXCJiYWRcIik7XG5cblx0XHRcdFx0JChidXR0b24pLnJlbW92ZUNsYXNzKCdsb2FkaW5nJyk7XG5cblx0XHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdFx0fVxuXG5cdFx0XHQvLyBnZXQgYWxsIGRhdGEgZnJvbSB0aGUgZm9ybVxuXHRcdFx0dmFyIGZvcm1EYXRhID0gZm9ybS5zZXJpYWxpemVBcnJheSgpO1xuXHRcdFx0Ly8gYWRkIGJ1dHRvbiBhY3Rpb25cblx0XHRcdGZvcm1EYXRhLnB1c2goe25hbWU6ICQoYnV0dG9uKS5hdHRyKCduYW1lJyksIHZhbHVlOicxJ30pO1xuXHRcdFx0Ly8gQXJ0aWZpY2lhbCBIVFRQIHJlZmVyZXIsIElFIGRvZXNuJ3Qgc3VibWl0IHRoZW0gdmlhIGFqYXguXG5cdFx0XHQvLyBBbHNvIHJld3JpdGVzIGFuY2hvcnMgdG8gdGhlaXIgcGFnZSBjb3VudGVycGFydHMsIHdoaWNoIGlzIGltcG9ydGFudFxuXHRcdFx0Ly8gYXMgYXV0b21hdGljIGJyb3dzZXIgYWpheCByZXNwb25zZSByZWRpcmVjdHMgc2VlbSB0byBkaXNjYXJkIHRoZSBoYXNoL2ZyYWdtZW50LlxuXHRcdFx0Ly8gVE9ETyBSZXBsYWNlcyB0cmFpbGluZyBzbGFzaGVzIGFkZGVkIGJ5IEhpc3RvcnkgYWZ0ZXIgbG9jYWxlIChlLmcuIGFkbWluLz9sb2NhbGU9ZW4vKVxuXHRcdFx0Zm9ybURhdGEucHVzaCh7IG5hbWU6ICdCYWNrVVJMJywgdmFsdWU6IHdpbmRvdy5oaXN0b3J5LnN0YXRlLnBhdGgucmVwbGFjZSgvXFwvJC8sICcnKSB9KTtcblxuXHRcdFx0Ly8gU2F2ZSB0YWIgc2VsZWN0aW9ucyBzbyB3ZSBjYW4gcmVzdG9yZSB0aGVtIGxhdGVyXG5cdFx0XHR0aGlzLnNhdmVUYWJTdGF0ZSgpO1xuXG5cdFx0XHQvLyBTdGFuZGFyZCBQamF4IGJlaGF2aW91ciBpcyB0byByZXBsYWNlIHRoZSBzdWJtaXR0ZWQgZm9ybSB3aXRoIG5ldyBjb250ZW50LlxuXHRcdFx0Ly8gVGhlIHJldHVybmVkIHZpZXcgaXNuJ3QgYWx3YXlzIGRlY2lkZWQgdXBvbiB3aGVuIHRoZSByZXF1ZXN0XG5cdFx0XHQvLyBpcyBmaXJlZCwgc28gdGhlIHNlcnZlciBtaWdodCBkZWNpZGUgdG8gY2hhbmdlIGl0IGJhc2VkIG9uIGl0cyBvd24gbG9naWMsXG5cdFx0XHQvLyBzZW5kaW5nIGJhY2sgZGlmZmVyZW50IGBYLVBqYXhgIGhlYWRlcnMgYW5kIGNvbnRlbnRcblx0XHRcdGpRdWVyeS5hamF4KGpRdWVyeS5leHRlbmQoe1xuXHRcdFx0XHRoZWFkZXJzOiB7XCJYLVBqYXhcIiA6IFwiQ3VycmVudEZvcm0sQnJlYWRjcnVtYnNcIn0sXG5cdFx0XHRcdHVybDogZm9ybS5hdHRyKCdhY3Rpb24nKSxcblx0XHRcdFx0ZGF0YTogZm9ybURhdGEsXG5cdFx0XHRcdHR5cGU6ICdQT1NUJyxcblx0XHRcdFx0Y29tcGxldGU6IGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdCQoYnV0dG9uKS5yZW1vdmVDbGFzcygnbG9hZGluZycpO1xuXHRcdFx0XHR9LFxuXHRcdFx0XHRzdWNjZXNzOiBmdW5jdGlvbihkYXRhLCBzdGF0dXMsIHhocikge1xuXHRcdFx0XHRcdGZvcm0ucmVtb3ZlQ2xhc3MoJ2NoYW5nZWQnKTsgLy8gVE9ETyBUaGlzIHNob3VsZCBiZSB1c2luZyB0aGUgcGx1Z2luIEFQSVxuXHRcdFx0XHRcdGlmKGNhbGxiYWNrKSBjYWxsYmFjayhkYXRhLCBzdGF0dXMsIHhocik7XG5cblx0XHRcdFx0XHR2YXIgbmV3Q29udGVudEVscyA9IHNlbGYuaGFuZGxlQWpheFJlc3BvbnNlKGRhdGEsIHN0YXR1cywgeGhyKTtcblx0XHRcdFx0XHRpZighbmV3Q29udGVudEVscykgcmV0dXJuO1xuXG5cdFx0XHRcdFx0bmV3Q29udGVudEVscy5maWx0ZXIoJ2Zvcm0nKS50cmlnZ2VyKCdhZnRlcnN1Ym1pdGZvcm0nLCB7c3RhdHVzOiBzdGF0dXMsIHhocjogeGhyLCBmb3JtRGF0YTogZm9ybURhdGF9KTtcblx0XHRcdFx0fVxuXHRcdFx0fSwgYWpheE9wdGlvbnMpKTtcblxuXHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBMYXN0IGh0bWw1IGhpc3Rvcnkgc3RhdGVcblx0XHQgKi9cblx0XHRMYXN0U3RhdGU6IG51bGwsXG5cblx0XHQvKipcblx0XHQgKiBGbGFnIHRvIHBhdXNlIGhhbmRsZVN0YXRlQ2hhbmdlXG5cdFx0ICovXG5cdFx0UGF1c2VTdGF0ZTogZmFsc2UsXG5cblx0XHQvKipcblx0XHQgKiBIYW5kbGVzIGFqYXggbG9hZGluZyBvZiBuZXcgcGFuZWxzIHRocm91Z2ggdGhlIHdpbmRvdy5oaXN0b3J5IG9iamVjdC5cblx0XHQgKiBUbyB0cmlnZ2VyIGxvYWRpbmcsIHBhc3MgYSBuZXcgVVJMIHRvIHJvdXRlci5zaG93KCkuXG5cdFx0ICogVXNlIGxvYWRQYW5lbCgpIGFzIGEgcm91dGVyLnNob3coKSB3cmFwcGVyIGFzIGl0IHByb3ZpZGVzIHNvbWUgYWRkaXRpb25hbCBmdW5jdGlvbmFsaXR5XG5cdFx0ICogbGlrZSBnbG9iYWwgY2hhbmdldHJhY2tpbmcgYW5kIHVzZXIgYWJvcnRzLlxuXHRcdCAqXG5cdFx0ICogRHVlIHRvIHRoZSBuYXR1cmUgb2YgaGlzdG9yeSBtYW5hZ2VtZW50LCBubyBjYWxsYmFja3MgYXJlIGFsbG93ZWQuXG5cdFx0ICogVXNlIHRoZSAnYmVmb3Jlc3RhdGVjaGFuZ2UnIGFuZCAnYWZ0ZXJzdGF0ZWNoYW5nZScgZXZlbnRzIGluc3RlYWQsXG5cdFx0ICogb3Igb3ZlcndyaXRlIHRoZSBiZWZvcmVMb2FkKCkgYW5kIGFmdGVyTG9hZCgpIG1ldGhvZHMgb24gdGhlXG5cdFx0ICogRE9NIGVsZW1lbnQgeW91J3JlIGxvYWRpbmcgdGhlIG5ldyBjb250ZW50IGludG8uXG5cdFx0ICogQWx0aG91Z2ggeW91IGNhbiBwYXNzIGRhdGEgaW50byByb3V0ZXIuc2hvdyh1cmwsIGRhdGEpLCBpdCBzaG91bGRuJ3QgY29udGFpblxuXHRcdCAqIERPTSBlbGVtZW50cyBvciBjYWxsYmFjayBjbG9zdXJlcy5cblx0XHQgKlxuXHRcdCAqIFRoZSBwYXNzZWQgVVJMIHNob3VsZCBhbGxvdyByZWNvbnN0cnVjdGluZyBpbXBvcnRhbnQgaW50ZXJmYWNlIHN0YXRlXG5cdFx0ICogd2l0aG91dCBhZGRpdGlvbmFsIHBhcmFtZXRlcnMsIGluIHRoZSBmb2xsb3dpbmcgdXNlIGNhc2VzOlxuXHRcdCAqIC0gRXhwbGljaXQgbG9hZGluZyB0aHJvdWdoIHJvdXRlci5zaG93KClcblx0XHQgKiAtIEltcGxpY2l0IGxvYWRpbmcgdGhyb3VnaCBicm93c2VyIG5hdmlnYXRpb24gZXZlbnQgdHJpZ2dlcmVkIGJ5IHRoZSB1c2VyIChmb3J3YXJkIG9yIGJhY2spXG5cdFx0ICogLSBGdWxsIHdpbmRvdyByZWZyZXNoIHdpdGhvdXQgYWpheFxuXHRcdCAqIEZvciBleGFtcGxlLCBhIE1vZGVsQWRtaW4gc2VhcmNoIGV2ZW50IHNob3VsZCBjb250YWluIHRoZSBzZWFyY2ggdGVybXNcblx0XHQgKiBhcyBVUkwgcGFyYW1ldGVycywgYW5kIHRoZSByZXN1bHQgZGlzcGxheSBzaG91bGQgYXV0b21hdGljYWxseSBhcHBlYXJcblx0XHQgKiBpZiB0aGUgVVJMIGlzIGxvYWRlZCB3aXRob3V0IGFqYXguXG5cdFx0ICovXG5cdFx0aGFuZGxlU3RhdGVDaGFuZ2U6IGZ1bmN0aW9uIChldmVudCwgaGlzdG9yeVN0YXRlID0gd2luZG93Lmhpc3Rvcnkuc3RhdGUpIHtcblx0XHRcdGlmICh0aGlzLmdldFBhdXNlU3RhdGUoKSkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdC8vIERvbid0IGFsbG93IHBhcmFsbGVsIGxvYWRpbmcgdG8gYXZvaWQgZWRnZSBjYXNlc1xuXHRcdFx0aWYgKHRoaXMuZ2V0U3RhdGVDaGFuZ2VYSFIoKSkge1xuXHRcdFx0XHR0aGlzLmdldFN0YXRlQ2hhbmdlWEhSKCkuYWJvcnQoKTtcblx0XHRcdH1cblxuXHRcdFx0dmFyIHNlbGYgPSB0aGlzLFxuXHRcdFx0XHRmcmFnbWVudHMgPSBoaXN0b3J5U3RhdGUucGpheCB8fCAnQ29udGVudCcsXG5cdFx0XHRcdGhlYWRlcnMgPSB7fSxcblx0XHRcdFx0ZnJhZ21lbnRzQXJyID0gZnJhZ21lbnRzLnNwbGl0KCcsJyksXG5cdFx0XHRcdGNvbnRlbnRFbHMgPSB0aGlzLl9maW5kRnJhZ21lbnRzKGZyYWdtZW50c0Fycik7XG5cblx0XHRcdHRoaXMuc2V0U3RhdGVDaGFuZ2VDb3VudCh0aGlzLmdldFN0YXRlQ2hhbmdlQ291bnQoKSArIDEpO1xuXG5cdFx0XHRpZiAoIXRoaXMuY2hlY2tDYW5OYXZpZ2F0ZSgpKSB7XG5cdFx0XHRcdHZhciBsYXN0U3RhdGUgPSB0aGlzLmdldExhc3RTdGF0ZSgpO1xuXG5cdFx0XHRcdC8vIFN1cHByZXNzIHBhbmVsIGxvYWRpbmcgd2hpbGUgcmVzZXR0aW5nIHN0YXRlXG5cdFx0XHRcdHRoaXMuc2V0UGF1c2VTdGF0ZSh0cnVlKTtcblxuXHRcdFx0XHQvLyBSZXN0b3JlIGJlc3QgbGFzdCBzdGF0ZVxuXHRcdFx0XHRpZiAobGFzdFN0YXRlICE9PSBudWxsKSB7XG5cdFx0XHRcdFx0cm91dGVyLnNob3cobGFzdFN0YXRlLnVybCk7XG5cdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0cm91dGVyLmJhY2soKTtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdHRoaXMuc2V0UGF1c2VTdGF0ZShmYWxzZSk7XG5cblx0XHRcdFx0Ly8gQWJvcnQgbG9hZGluZyBvZiB0aGlzIHBhbmVsXG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0dGhpcy5zZXRMYXN0U3RhdGUoaGlzdG9yeVN0YXRlKTtcblxuXHRcdFx0Ly8gSWYgYW55IG9mIHRoZSByZXF1ZXN0ZWQgUGpheCBmcmFnbWVudHMgZG9uJ3QgZXhpc3QgaW4gdGhlIGN1cnJlbnQgdmlldyxcblx0XHRcdC8vIGZldGNoIHRoZSBcIkNvbnRlbnRcIiB2aWV3IGluc3RlYWQsIHdoaWNoIGlzIHRoZSBcIm91dGVybW9zdFwiIGZyYWdtZW50XG5cdFx0XHQvLyB0aGF0IGNhbiBiZSByZWxvYWRlZCB3aXRob3V0IHJlbG9hZGluZyB0aGUgd2hvbGUgd2luZG93LlxuXHRcdFx0aWYgKGNvbnRlbnRFbHMubGVuZ3RoIDwgZnJhZ21lbnRzQXJyLmxlbmd0aCkge1xuXHRcdFx0XHRmcmFnbWVudHMgPSAnQ29udGVudCcsIGZyYWdtZW50c0FyciA9IFsnQ29udGVudCddO1xuXHRcdFx0XHRjb250ZW50RWxzID0gdGhpcy5fZmluZEZyYWdtZW50cyhmcmFnbWVudHNBcnIpO1xuXHRcdFx0fVxuXG5cdFx0XHR0aGlzLnRyaWdnZXIoJ2JlZm9yZXN0YXRlY2hhbmdlJywgeyBzdGF0ZTogaGlzdG9yeVN0YXRlLCBlbGVtZW50OiBjb250ZW50RWxzIH0pO1xuXG5cdFx0XHQvLyBTZXQgUGpheCBoZWFkZXJzLCB3aGljaCBjYW4gZGVjbGFyZSBhIHByZWZlcmVuY2UgZm9yIHRoZSByZXR1cm5lZCB2aWV3LlxuXHRcdFx0Ly8gVGhlIGFjdHVhbGx5IHJldHVybmVkIHZpZXcgaXNuJ3QgYWx3YXlzIGRlY2lkZWQgdXBvbiB3aGVuIHRoZSByZXF1ZXN0XG5cdFx0XHQvLyBpcyBmaXJlZCwgc28gdGhlIHNlcnZlciBtaWdodCBkZWNpZGUgdG8gY2hhbmdlIGl0IGJhc2VkIG9uIGl0cyBvd24gbG9naWMuXG5cdFx0XHRoZWFkZXJzWydYLVBqYXgnXSA9IGZyYWdtZW50cztcblxuXHRcdFx0aWYgKHR5cGVvZiBoaXN0b3J5U3RhdGUuX19mb3JjZVJlZmVyZXIgIT09ICd1bmRlZmluZWQnKSB7XG5cdFx0XHRcdC8vIEVuc3VyZSBxdWVyeSBzdHJpbmcgaXMgcHJvcGVybHkgZW5jb2RlZCBpZiBwcmVzZW50XG5cdFx0XHRcdGxldCB1cmwgPSBoaXN0b3J5U3RhdGUuX19mb3JjZVJlZmVyZXI7XG5cblx0XHRcdFx0dHJ5IHtcblx0XHRcdFx0XHQvLyBQcmV2ZW50IGRvdWJsZS1lbmNvZGluZyBieSBhdHRlbXB0aW5nIHRvIGRlY29kZVxuXHRcdFx0XHRcdHVybCA9IGRlY29kZVVSSSh1cmwpO1xuXHRcdFx0XHR9IGNhdGNoKGUpIHtcblx0XHRcdFx0XHQvLyBVUkwgbm90IGVuY29kZWQsIG9yIHdhcyBlbmNvZGVkIGluY29ycmVjdGx5LCBzbyBkbyBub3RoaW5nXG5cdFx0XHRcdH0gZmluYWxseSB7XG5cdFx0XHRcdFx0Ly8gU2V0IG91ciByZWZlcmVyIGhlYWRlciB0byB0aGUgZW5jb2RlZCBVUkxcblx0XHRcdFx0XHRoZWFkZXJzWydYLUJhY2t1cmwnXSA9IGVuY29kZVVSSSh1cmwpO1xuXHRcdFx0XHR9XG5cdFx0XHR9XG5cblx0XHRcdGNvbnRlbnRFbHMuYWRkQ2xhc3MoJ2xvYWRpbmcnKTtcblxuXHRcdFx0bGV0IHByb21pc2UgPSAkLmFqYXgoe1xuXHRcdFx0XHRoZWFkZXJzOiBoZWFkZXJzLFxuXHRcdFx0XHR1cmw6IGhpc3RvcnlTdGF0ZS5wYXRoXG5cdFx0XHR9KVxuXHRcdFx0LmRvbmUoKGRhdGEsIHN0YXR1cywgeGhyKSA9PiB7XG5cdFx0XHRcdHZhciBlbHMgPSBzZWxmLmhhbmRsZUFqYXhSZXNwb25zZShkYXRhLCBzdGF0dXMsIHhociwgaGlzdG9yeVN0YXRlKTtcblx0XHRcdFx0c2VsZi50cmlnZ2VyKCdhZnRlcnN0YXRlY2hhbmdlJywge2RhdGE6IGRhdGEsIHN0YXR1czogc3RhdHVzLCB4aHI6IHhociwgZWxlbWVudDogZWxzLCBzdGF0ZTogaGlzdG9yeVN0YXRlfSk7XG5cdFx0XHR9KVxuXHRcdFx0LmFsd2F5cygoKSA9PiB7XG5cdFx0XHRcdHNlbGYuc2V0U3RhdGVDaGFuZ2VYSFIobnVsbCk7XG5cdFx0XHRcdC8vIFJlbW92ZSBsb2FkaW5nIGluZGljYXRpb24gZnJvbSBvbGQgY29udGVudCBlbHMgKHJlZ2FyZGxlc3Mgb2Ygd2hpY2ggYXJlIHJlcGxhY2VkKVxuXHRcdFx0XHRjb250ZW50RWxzLnJlbW92ZUNsYXNzKCdsb2FkaW5nJyk7XG5cdFx0XHR9KTtcblxuXHRcdFx0dGhpcy5zZXRTdGF0ZUNoYW5nZVhIUihwcm9taXNlKTtcblxuXHRcdFx0cmV0dXJuIHByb21pc2U7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEFMdGVybmF0aXZlIHRvIGxvYWRQYW5lbC9zdWJtaXRGb3JtLlxuXHRcdCAqXG5cdFx0ICogVHJpZ2dlcnMgYSBwYXJhbGxlbC1mZXRjaCBvZiBhIFBKQVggZnJhZ21lbnQsIHdoaWNoIGlzIGEgc2VwYXJhdGUgcmVxdWVzdCB0byB0aGVcblx0XHQgKiBzdGF0ZSBjaGFuZ2UgcmVxdWVzdHMuIFRoZXJlIGNvdWxkIGJlIGFueSBhbW91bnQgb2YgdGhlc2UgZmV0Y2hlcyBnb2luZyBvbiBpbiB0aGUgYmFja2dyb3VuZCxcblx0XHQgKiBhbmQgdGhleSBkb24ndCByZWdpc3RlciBhcyBhIEhUTUw1IGhpc3Rvcnkgc3RhdGVzLlxuXHRcdCAqXG5cdFx0ICogVGhpcyBpcyBtZWFudCBmb3IgdXBkYXRpbmcgYSBQSkFYIGFyZWFzIHRoYXQgYXJlIG5vdCBjb21wbGV0ZSBwYW5lbC9mb3JtIHJlbG9hZHMuIFRoZXNlIHlvdSdkXG5cdFx0ICogbm9ybWFsbHkgZG8gdmlhIHN1Ym1pdEZvcm0gb3IgbG9hZFBhbmVsIHdoaWNoIGhhdmUgYSBsb3Qgb2YgYXV0b21hdGlvbiBidWlsdCBpbi5cblx0XHQgKlxuXHRcdCAqIE9uIHJlY2VpdmluZyBzdWNjZXNzZnVsIHJlc3BvbnNlLCB0aGUgZnJhbWV3b3JrIHdpbGwgdXBkYXRlIHRoZSBlbGVtZW50IHRhZ2dlZCB3aXRoIGFwcHJvcHJpYXRlXG5cdFx0ICogZGF0YS1wamF4LWZyYWdtZW50IGF0dHJpYnV0ZSAoZS5nLiBkYXRhLXBqYXgtZnJhZ21lbnQ9XCI8cGpheC1mcmFnbWVudC1uYW1lPlwiKS4gTWFrZSBzdXJlIHRoaXMgZWxlbWVudFxuXHRcdCAqIGlzIGF2YWlsYWJsZS5cblx0XHQgKlxuXHRcdCAqIEV4YW1wbGUgdXNhZ2U6XG5cdFx0ICogJCgnLmNtcy1jb250YWluZXInKS5sb2FkRnJhZ21lbnQoJ2FkbWluL2Zvb2Jhci8nLCAnRnJhZ21lbnROYW1lJyk7XG5cdFx0ICpcblx0XHQgKiBAcGFyYW0gdXJsIHN0cmluZyBSZWxhdGl2ZSBvciBhYnNvbHV0ZSB1cmwgb2YgdGhlIGNvbnRyb2xsZXIuXG5cdFx0ICogQHBhcmFtIHBqYXhGcmFnbWVudHMgc3RyaW5nIFBKQVggZnJhZ21lbnQocyksIGNvbW1hIHNlcGFyYXRlZC5cblx0XHQgKi9cblx0XHRsb2FkRnJhZ21lbnQ6IGZ1bmN0aW9uKHVybCwgcGpheEZyYWdtZW50cykge1xuXG5cdFx0XHR2YXIgc2VsZiA9IHRoaXMsXG5cdFx0XHRcdHhocixcblx0XHRcdFx0aGVhZGVycyA9IHt9LFxuXHRcdFx0XHRiYXNlVXJsID0gJCgnYmFzZScpLmF0dHIoJ2hyZWYnKSxcblx0XHRcdFx0ZnJhZ21lbnRYSFIgPSB0aGlzLmdldEZyYWdtZW50WEhSKCk7XG5cblx0XHRcdC8vIE1ha2Ugc3VyZSBvbmx5IG9uZSBYSFIgZm9yIGEgc3BlY2lmaWMgZnJhZ21lbnQgaXMgY3VycmVudGx5IGluIHByb2dyZXNzLlxuXHRcdFx0aWYoXG5cdFx0XHRcdHR5cGVvZiBmcmFnbWVudFhIUltwamF4RnJhZ21lbnRzXSE9PSd1bmRlZmluZWQnICYmXG5cdFx0XHRcdGZyYWdtZW50WEhSW3BqYXhGcmFnbWVudHNdIT09bnVsbFxuXHRcdFx0KSB7XG5cdFx0XHRcdGZyYWdtZW50WEhSW3BqYXhGcmFnbWVudHNdLmFib3J0KCk7XG5cdFx0XHRcdGZyYWdtZW50WEhSW3BqYXhGcmFnbWVudHNdID0gbnVsbDtcblx0XHRcdH1cblxuXHRcdFx0dXJsID0gJC5wYXRoLmlzQWJzb2x1dGVVcmwodXJsKSA/IHVybCA6ICQucGF0aC5tYWtlVXJsQWJzb2x1dGUodXJsLCBiYXNlVXJsKTtcblx0XHRcdGhlYWRlcnNbJ1gtUGpheCddID0gcGpheEZyYWdtZW50cztcblxuXHRcdFx0eGhyID0gJC5hamF4KHtcblx0XHRcdFx0aGVhZGVyczogaGVhZGVycyxcblx0XHRcdFx0dXJsOiB1cmwsXG5cdFx0XHRcdHN1Y2Nlc3M6IGZ1bmN0aW9uKGRhdGEsIHN0YXR1cywgeGhyKSB7XG5cdFx0XHRcdFx0dmFyIGVsZW1lbnRzID0gc2VsZi5oYW5kbGVBamF4UmVzcG9uc2UoZGF0YSwgc3RhdHVzLCB4aHIsIG51bGwpO1xuXG5cdFx0XHRcdFx0Ly8gV2UgYXJlIGZ1bGx5IGRvbmUgbm93LCBtYWtlIGl0IHBvc3NpYmxlIGZvciBvdGhlcnMgdG8gaG9vayBpbiBoZXJlLlxuXHRcdFx0XHRcdHNlbGYudHJpZ2dlcignYWZ0ZXJsb2FkZnJhZ21lbnQnLCB7IGRhdGE6IGRhdGEsIHN0YXR1czogc3RhdHVzLCB4aHI6IHhociwgZWxlbWVudHM6IGVsZW1lbnRzIH0pO1xuXHRcdFx0XHR9LFxuXHRcdFx0XHRlcnJvcjogZnVuY3Rpb24oeGhyLCBzdGF0dXMsIGVycm9yKSB7XG5cdFx0XHRcdFx0c2VsZi50cmlnZ2VyKCdsb2FkZnJhZ21lbnRlcnJvcicsIHsgeGhyOiB4aHIsIHN0YXR1czogc3RhdHVzLCBlcnJvcjogZXJyb3IgfSk7XG5cdFx0XHRcdH0sXG5cdFx0XHRcdGNvbXBsZXRlOiBmdW5jdGlvbigpIHtcblx0XHRcdFx0XHQvLyBSZXNldCB0aGUgY3VycmVudCBYSFIgaW4gdHJhY2tpbmcgb2JqZWN0LlxuXHRcdFx0XHRcdHZhciBmcmFnbWVudFhIUiA9IHNlbGYuZ2V0RnJhZ21lbnRYSFIoKTtcblx0XHRcdFx0XHRpZihcblx0XHRcdFx0XHRcdHR5cGVvZiBmcmFnbWVudFhIUltwamF4RnJhZ21lbnRzXSE9PSd1bmRlZmluZWQnICYmXG5cdFx0XHRcdFx0XHRmcmFnbWVudFhIUltwamF4RnJhZ21lbnRzXSE9PW51bGxcblx0XHRcdFx0XHQpIHtcblx0XHRcdFx0XHRcdGZyYWdtZW50WEhSW3BqYXhGcmFnbWVudHNdID0gbnVsbDtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH1cblx0XHRcdH0pO1xuXG5cdFx0XHQvLyBTdG9yZSB0aGUgZnJhZ21lbnQgcmVxdWVzdCBzbyB3ZSBjYW4gYWJvcnQgbGF0ZXIsIHNob3VsZCB3ZSBnZXQgYSBkdXBsaWNhdGUgcmVxdWVzdC5cblx0XHRcdGZyYWdtZW50WEhSW3BqYXhGcmFnbWVudHNdID0geGhyO1xuXG5cdFx0XHRyZXR1cm4geGhyO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBIYW5kbGVzIGFqYXggcmVzcG9uc2VzIGNvbnRhaW5pbmcgcGxhaW4gSFRNTCwgb3IgbXVsaXRwbGVcblx0XHQgKiBQSkFYIGZyYWdtZW50cyB3cmFwcGVkIGluIEpTT04gKHNlZSBQamF4UmVzcG9uc2VOZWdvdGlhdG9yIFBIUCBjbGFzcykuXG5cdFx0ICogQ2FuIGJlIGhvb2tlZCBpbnRvIGFuIGFqYXggJ3N1Y2Nlc3MnIGNhbGxiYWNrLlxuXHRcdCAqXG5cdFx0ICogUGFyYW1ldGVyczpcblx0XHQgKiBcdChPYmplY3QpIGRhdGFcblx0XHQgKiBcdChTdHJpbmcpIHN0YXR1c1xuXHRcdCAqIFx0KFhNTEhUVFBSZXF1ZXN0KSB4aHJcblx0XHQgKiBcdChPYmplY3QpIHN0YXRlIFRoZSBvcmlnaW5hbCBoaXN0b3J5IHN0YXRlIHdoaWNoIHRoZSByZXF1ZXN0IHdhcyBpbml0aWF0ZWQgd2l0aFxuXHRcdCAqL1xuXHRcdGhhbmRsZUFqYXhSZXNwb25zZTogZnVuY3Rpb24oZGF0YSwgc3RhdHVzLCB4aHIsIHN0YXRlKSB7XG5cdFx0XHR2YXIgc2VsZiA9IHRoaXMsIHVybCwgc2VsZWN0ZWRUYWJzLCBndWVzc0ZyYWdtZW50LCBmcmFnbWVudCwgJGRhdGE7XG5cblx0XHRcdC8vIFN1cHBvcnQgYSBmdWxsIHJlbG9hZFxuXHRcdFx0aWYoeGhyLmdldFJlc3BvbnNlSGVhZGVyKCdYLVJlbG9hZCcpICYmIHhoci5nZXRSZXNwb25zZUhlYWRlcignWC1Db250cm9sbGVyVVJMJykpIHtcblx0XHRcdFx0dmFyIGJhc2VVcmwgPSAkKCdiYXNlJykuYXR0cignaHJlZicpLFxuXHRcdFx0XHRcdHJhd1VSTCA9IHhoci5nZXRSZXNwb25zZUhlYWRlcignWC1Db250cm9sbGVyVVJMJyksXG5cdFx0XHRcdFx0dXJsID0gJC5wYXRoLmlzQWJzb2x1dGVVcmwocmF3VVJMKSA/IHJhd1VSTCA6ICQucGF0aC5tYWtlVXJsQWJzb2x1dGUocmF3VVJMLCBiYXNlVXJsKTtcblxuXHRcdFx0XHRkb2N1bWVudC5sb2NhdGlvbi5ocmVmID0gdXJsO1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdC8vIFBzZXVkby1yZWRpcmVjdHMgdmlhIFgtQ29udHJvbGxlclVSTCBtaWdodCByZXR1cm4gZW1wdHkgZGF0YSwgaW4gd2hpY2hcblx0XHRcdC8vIGNhc2Ugd2UnbGwgaWdub3JlIHRoZSByZXNwb25zZVxuXHRcdFx0aWYoIWRhdGEpIHJldHVybjtcblxuXHRcdFx0Ly8gVXBkYXRlIHRpdGxlXG5cdFx0XHR2YXIgdGl0bGUgPSB4aHIuZ2V0UmVzcG9uc2VIZWFkZXIoJ1gtVGl0bGUnKTtcblx0XHRcdGlmKHRpdGxlKSBkb2N1bWVudC50aXRsZSA9IGRlY29kZVVSSUNvbXBvbmVudCh0aXRsZS5yZXBsYWNlKC9cXCsvZywgJyAnKSk7XG5cblx0XHRcdHZhciBuZXdGcmFnbWVudHMgPSB7fSwgbmV3Q29udGVudEVscztcblx0XHRcdC8vIElmIGNvbnRlbnQgdHlwZSBpcyB0ZXh0L2pzb24gKGlnbm9yaW5nIGNoYXJzZXQgYW5kIG90aGVyIHBhcmFtZXRlcnMpXG5cdFx0XHRpZih4aHIuZ2V0UmVzcG9uc2VIZWFkZXIoJ0NvbnRlbnQtVHlwZScpLm1hdGNoKC9eKCh0ZXh0KXwoYXBwbGljYXRpb24pKVxcL2pzb25bIFxcdF0qOz8vaSkpIHtcblx0XHRcdFx0bmV3RnJhZ21lbnRzID0gZGF0YTtcblx0XHRcdH0gZWxzZSB7XG5cblx0XHRcdFx0Ly8gRmFsbCBiYWNrIHRvIHJlcGxhY2luZyB0aGUgY29udGVudCBmcmFnbWVudCBpZiBIVE1MIGlzIHJldHVybmVkXG5cdFx0XHRcdGZyYWdtZW50ID0gZG9jdW1lbnQuY3JlYXRlRG9jdW1lbnRGcmFnbWVudCgpO1xuXG5cdFx0XHRcdGpRdWVyeS5jbGVhbiggWyBkYXRhIF0sIGRvY3VtZW50LCBmcmFnbWVudCwgW10gKTtcblx0XHRcdFx0JGRhdGEgPSAkKGpRdWVyeS5tZXJnZSggW10sIGZyYWdtZW50LmNoaWxkTm9kZXMgKSk7XG5cblx0XHRcdFx0Ly8gVHJ5IGFuZCBndWVzcyB0aGUgZnJhZ21lbnQgaWYgbm9uZSBpcyBwcm92aWRlZFxuXHRcdFx0XHQvLyBUT0RPOiBkYXRhLXBqYXgtZnJhZ21lbnQgbWlnaHQgYWN0dWFsbHkgZ2l2ZSB1cyB0aGUgZnJhZ21lbnQuIEZvciBub3cgd2UganVzdCBjaGVjayBtb3N0IGNvbW1vbiBjYXNlXG5cdFx0XHRcdGd1ZXNzRnJhZ21lbnQgPSAnQ29udGVudCc7XG5cdFx0XHRcdGlmICgkZGF0YS5pcygnZm9ybScpICYmICEkZGF0YS5pcygnW2RhdGEtcGpheC1mcmFnbWVudH49Q29udGVudF0nKSkgZ3Vlc3NGcmFnbWVudCA9ICdDdXJyZW50Rm9ybSc7XG5cblx0XHRcdFx0bmV3RnJhZ21lbnRzW2d1ZXNzRnJhZ21lbnRdID0gJGRhdGE7XG5cdFx0XHR9XG5cblx0XHRcdHRoaXMuc2V0UmVkcmF3U3VwcHJlc3Npb24odHJ1ZSk7XG5cdFx0XHR0cnkge1xuXHRcdFx0XHQvLyBSZXBsYWNlIGVhY2ggZnJhZ21lbnQgaW5kaXZpZHVhbGx5XG5cdFx0XHRcdCQuZWFjaChuZXdGcmFnbWVudHMsIGZ1bmN0aW9uKG5ld0ZyYWdtZW50LCBodG1sKSB7XG5cdFx0XHRcdFx0dmFyIGNvbnRlbnRFbCA9ICQoJ1tkYXRhLXBqYXgtZnJhZ21lbnRdJykuZmlsdGVyKGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdFx0cmV0dXJuICQuaW5BcnJheShuZXdGcmFnbWVudCwgJCh0aGlzKS5kYXRhKCdwamF4RnJhZ21lbnQnKS5zcGxpdCgnICcpKSAhPSAtMTtcblx0XHRcdFx0XHR9KSwgbmV3Q29udGVudEVsID0gJChodG1sKTtcblxuXHRcdFx0XHRcdC8vIEFkZCB0byByZXN1bHQgY29sbGVjdGlvblxuXHRcdFx0XHRcdGlmKG5ld0NvbnRlbnRFbHMpIG5ld0NvbnRlbnRFbHMuYWRkKG5ld0NvbnRlbnRFbCk7XG5cdFx0XHRcdFx0ZWxzZSBuZXdDb250ZW50RWxzID0gbmV3Q29udGVudEVsO1xuXG5cdFx0XHRcdFx0Ly8gVXBkYXRlIHBhbmVsc1xuXHRcdFx0XHRcdGlmKG5ld0NvbnRlbnRFbC5maW5kKCcuY21zLWNvbnRhaW5lcicpLmxlbmd0aCkge1xuXHRcdFx0XHRcdFx0dGhyb3cgJ0NvbnRlbnQgbG9hZGVkIHZpYSBhamF4IGlzIG5vdCBhbGxvd2VkIHRvIGNvbnRhaW4gdGFncyBtYXRjaGluZyB0aGUgXCIuY21zLWNvbnRhaW5lclwiIHNlbGVjdG9yIHRvIGF2b2lkIGluZmluaXRlIGxvb3BzJztcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHQvLyBTZXQgbG9hZGluZyBzdGF0ZSBhbmQgc3RvcmUgZWxlbWVudCBzdGF0ZVxuXHRcdFx0XHRcdHZhciBvcmlnU3R5bGUgPSBjb250ZW50RWwuYXR0cignc3R5bGUnKTtcblx0XHRcdFx0XHR2YXIgb3JpZ1BhcmVudCA9IGNvbnRlbnRFbC5wYXJlbnQoKTtcblx0XHRcdFx0XHR2YXIgb3JpZ1BhcmVudExheW91dEFwcGxpZWQgPSAodHlwZW9mIG9yaWdQYXJlbnQuZGF0YSgnamxheW91dCcpIT09J3VuZGVmaW5lZCcpO1xuXHRcdFx0XHRcdHZhciBsYXlvdXRDbGFzc2VzID0gWydlYXN0JywgJ3dlc3QnLCAnY2VudGVyJywgJ25vcnRoJywgJ3NvdXRoJywgJ2NvbHVtbi1oaWRkZW4nXTtcblx0XHRcdFx0XHR2YXIgZWxlbUNsYXNzZXMgPSBjb250ZW50RWwuYXR0cignY2xhc3MnKTtcblx0XHRcdFx0XHR2YXIgb3JpZ0xheW91dENsYXNzZXMgPSBbXTtcblx0XHRcdFx0XHRpZihlbGVtQ2xhc3Nlcykge1xuXHRcdFx0XHRcdFx0b3JpZ0xheW91dENsYXNzZXMgPSAkLmdyZXAoXG5cdFx0XHRcdFx0XHRcdGVsZW1DbGFzc2VzLnNwbGl0KCcgJyksXG5cdFx0XHRcdFx0XHRcdGZ1bmN0aW9uKHZhbCkgeyByZXR1cm4gKCQuaW5BcnJheSh2YWwsIGxheW91dENsYXNzZXMpID49IDApO31cblx0XHRcdFx0XHRcdCk7XG5cdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0bmV3Q29udGVudEVsXG5cdFx0XHRcdFx0XHQucmVtb3ZlQ2xhc3MobGF5b3V0Q2xhc3Nlcy5qb2luKCcgJykpXG5cdFx0XHRcdFx0XHQuYWRkQ2xhc3Mob3JpZ0xheW91dENsYXNzZXMuam9pbignICcpKTtcblx0XHRcdFx0XHRpZihvcmlnU3R5bGUpIG5ld0NvbnRlbnRFbC5hdHRyKCdzdHlsZScsIG9yaWdTdHlsZSk7XG5cblx0XHRcdFx0XHQvLyBBbGxvdyBpbmplY3Rpb24gb2YgaW5saW5lIHN0eWxlcywgYXMgdGhleSdyZSBub3QgYWxsb3dlZCBpbiB0aGUgZG9jdW1lbnQgYm9keS5cblx0XHRcdFx0XHQvLyBOb3QgaGFuZGxpbmcgdGhpcyB0aHJvdWdoIGpRdWVyeS5vbmRlbWFuZCB0byBhdm9pZCBwYXJzaW5nIHRoZSBET00gdHdpY2UuXG5cdFx0XHRcdFx0dmFyIHN0eWxlcyA9IG5ld0NvbnRlbnRFbC5maW5kKCdzdHlsZScpLmRldGFjaCgpO1xuXHRcdFx0XHRcdGlmKHN0eWxlcy5sZW5ndGgpICQoZG9jdW1lbnQpLmZpbmQoJ2hlYWQnKS5hcHBlbmQoc3R5bGVzKTtcblxuXHRcdFx0XHRcdC8vIFJlcGxhY2UgcGFuZWwgY29tcGxldGVseSAod2UgbmVlZCB0byBvdmVycmlkZSB0aGUgXCJsYXlvdXRcIiBhdHRyaWJ1dGUsIHNvIGNhbid0IHJlcGxhY2UgdGhlIGNoaWxkIGluc3RlYWQpXG5cdFx0XHRcdFx0Y29udGVudEVsLnJlcGxhY2VXaXRoKG5ld0NvbnRlbnRFbCk7XG5cblx0XHRcdFx0XHQvLyBGb3JjZSBqbGF5b3V0IHRvIHJlYnVpbGQgaW50ZXJuYWwgaGllcmFyY2h5IHRvIHBvaW50IHRvIHRoZSBuZXcgZWxlbWVudHMuXG5cdFx0XHRcdFx0Ly8gVGhpcyBpcyBvbmx5IG5lY2Vzc2FyeSBmb3IgZWxlbWVudHMgdGhhdCBhcmUgYXQgbGVhc3QgMyBsZXZlbHMgZGVlcC4gMm5kIGxldmVsIGVsZW1lbnRzIHdpbGxcblx0XHRcdFx0XHQvLyBiZSB0YWtlbiBjYXJlIG9mIHdoZW4gd2UgbGF5IG91dCB0aGUgdG9wIGxldmVsIGVsZW1lbnQgKC5jbXMtY29udGFpbmVyKS5cblx0XHRcdFx0XHRpZiAoIW9yaWdQYXJlbnQuaXMoJy5jbXMtY29udGFpbmVyJykgJiYgb3JpZ1BhcmVudExheW91dEFwcGxpZWQpIHtcblx0XHRcdFx0XHRcdG9yaWdQYXJlbnQubGF5b3V0KCk7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9KTtcblxuXHRcdFx0XHQvLyBSZS1pbml0IHRhYnMgKGluIGNhc2UgdGhlIGZvcm0gdGFnIGl0c2VsZiBpcyBhIHRhYnNldClcblx0XHRcdFx0dmFyIG5ld0Zvcm0gPSBuZXdDb250ZW50RWxzLmZpbHRlcignZm9ybScpO1xuXHRcdFx0XHRpZihuZXdGb3JtLmhhc0NsYXNzKCdjbXMtdGFic2V0JykpIG5ld0Zvcm0ucmVtb3ZlQ2xhc3MoJ2Ntcy10YWJzZXQnKS5hZGRDbGFzcygnY21zLXRhYnNldCcpO1xuXHRcdFx0fVxuXHRcdFx0ZmluYWxseSB7XG5cdFx0XHRcdHRoaXMuc2V0UmVkcmF3U3VwcHJlc3Npb24oZmFsc2UpO1xuXHRcdFx0fVxuXG5cdFx0XHR0aGlzLnJlZHJhdygpO1xuXHRcdFx0dGhpcy5yZXN0b3JlVGFiU3RhdGUoKHN0YXRlICYmIHR5cGVvZiBzdGF0ZS50YWJTdGF0ZSAhPT0gJ3VuZGVmaW5lZCcpID8gc3RhdGUudGFiU3RhdGUgOiBudWxsKTtcblxuXHRcdFx0cmV0dXJuIG5ld0NvbnRlbnRFbHM7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqXG5cdFx0ICpcblx0XHQgKiBQYXJhbWV0ZXJzOlxuXHRcdCAqIC0gZnJhZ21lbnRzIHtBcnJheX1cblx0XHQgKiBSZXR1cm5zOiBqUXVlcnkgY29sbGVjdGlvblxuXHRcdCAqL1xuXHRcdF9maW5kRnJhZ21lbnRzOiBmdW5jdGlvbihmcmFnbWVudHMpIHtcblx0XHRcdHJldHVybiAkKCdbZGF0YS1wamF4LWZyYWdtZW50XScpLmZpbHRlcihmdW5jdGlvbigpIHtcblx0XHRcdFx0Ly8gQWxsb3dzIGZvciBtb3JlIHRoYW4gb25lIGZyYWdtZW50IHBlciBub2RlXG5cdFx0XHRcdHZhciBpLCBub2RlRnJhZ21lbnRzID0gJCh0aGlzKS5kYXRhKCdwamF4RnJhZ21lbnQnKS5zcGxpdCgnICcpO1xuXHRcdFx0XHRmb3IoaSBpbiBmcmFnbWVudHMpIHtcblx0XHRcdFx0XHRpZigkLmluQXJyYXkoZnJhZ21lbnRzW2ldLCBub2RlRnJhZ21lbnRzKSAhPSAtMSkgcmV0dXJuIHRydWU7XG5cdFx0XHRcdH1cblx0XHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdFx0fSk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEZ1bmN0aW9uOiByZWZyZXNoXG5cdFx0ICpcblx0XHQgKiBVcGRhdGVzIHRoZSBjb250YWluZXIgYmFzZWQgb24gdGhlIGN1cnJlbnQgdXJsXG5cdFx0ICpcblx0XHQgKiBSZXR1cm5zOiB2b2lkXG5cdFx0ICovXG5cdFx0cmVmcmVzaDogZnVuY3Rpb24oKSB7XG5cdFx0XHQkKHdpbmRvdykudHJpZ2dlcignc3RhdGVjaGFuZ2UnKTtcblxuXHRcdFx0JCh0aGlzKS5yZWRyYXcoKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogU2F2ZSB0YWIgc2VsZWN0aW9ucyBpbiBvcmRlciB0byByZWNvbnN0cnVjdCB0aGVtIGxhdGVyLlxuXHRcdCAqIFJlcXVpcmVzIEhUTUw1IHNlc3Npb25TdG9yYWdlIHN1cHBvcnQuXG5cdFx0ICovXG5cdFx0c2F2ZVRhYlN0YXRlOiBmdW5jdGlvbigpIHtcblx0XHRcdGlmKHR5cGVvZih3aW5kb3cuc2Vzc2lvblN0b3JhZ2UpPT1cInVuZGVmaW5lZFwiIHx8IHdpbmRvdy5zZXNzaW9uU3RvcmFnZSA9PT0gbnVsbCkgcmV0dXJuO1xuXG5cdFx0XHR2YXIgc2VsZWN0ZWRUYWJzID0gW10sIHVybCA9IHRoaXMuX3RhYlN0YXRlVXJsKCk7XG5cdFx0XHR0aGlzLmZpbmQoJy5jbXMtdGFic2V0LC5zcy10YWJzZXQnKS5lYWNoKGZ1bmN0aW9uKGksIGVsKSB7XG5cdFx0XHRcdHZhciBpZCA9ICQoZWwpLmF0dHIoJ2lkJyk7XG5cdFx0XHRcdGlmKCFpZCkgcmV0dXJuOyAvLyB3ZSBuZWVkIGEgdW5pcXVlIHJlZmVyZW5jZVxuXHRcdFx0XHRpZighJChlbCkuZGF0YSgndGFicycpKSByZXR1cm47IC8vIGRvbid0IGFjdCBvbiB1bmluaXQnZWQgY29udHJvbHNcblxuXHRcdFx0XHQvLyBBbGxvdyBvcHQtb3V0IHZpYSBkYXRhIGVsZW1lbnQgb3IgZW50d2luZSBwcm9wZXJ0eS5cblx0XHRcdFx0aWYoJChlbCkuZGF0YSgnaWdub3JlVGFiU3RhdGUnKSB8fCAkKGVsKS5nZXRJZ25vcmVUYWJTdGF0ZSgpKSByZXR1cm47XG5cblx0XHRcdFx0c2VsZWN0ZWRUYWJzLnB1c2goe2lkOmlkLCBzZWxlY3RlZDokKGVsKS50YWJzKCdvcHRpb24nLCAnc2VsZWN0ZWQnKX0pO1xuXHRcdFx0fSk7XG5cblx0XHRcdGlmKHNlbGVjdGVkVGFicykge1xuXHRcdFx0XHR2YXIgdGFic1VybCA9ICd0YWJzLScgKyB1cmw7XG5cdFx0XHRcdHRyeSB7XG5cdFx0XHRcdFx0d2luZG93LnNlc3Npb25TdG9yYWdlLnNldEl0ZW0odGFic1VybCwgSlNPTi5zdHJpbmdpZnkoc2VsZWN0ZWRUYWJzKSk7XG5cdFx0XHRcdH0gY2F0Y2goZXJyKSB7XG5cdFx0XHRcdFx0aWYgKGVyci5jb2RlID09PSBET01FeGNlcHRpb24uUVVPVEFfRVhDRUVERURfRVJSICYmIHdpbmRvdy5zZXNzaW9uU3RvcmFnZS5sZW5ndGggPT09IDApIHtcblx0XHRcdFx0XHRcdC8vIElmIHRoaXMgZmFpbHMgd2UgaWdub3JlIHRoZSBlcnJvciBhcyB0aGUgb25seSBpc3N1ZSBpcyB0aGF0IGl0XG5cdFx0XHRcdFx0XHQvLyBkb2VzIG5vdCByZW1lbWJlciB0aGUgdGFiIHN0YXRlLlxuXHRcdFx0XHRcdFx0Ly8gVGhpcyBpcyBhIFNhZmFyaSBidWcgd2hpY2ggaGFwcGVucyB3aGVuIHByaXZhdGUgYnJvd3NpbmcgaXMgZW5hYmxlZC5cblx0XHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRcdFx0dGhyb3cgZXJyO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fVxuXHRcdFx0fVxuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBSZS1zZWxlY3QgcHJldmlvdXNseSBzYXZlZCB0YWJzLlxuXHRcdCAqIFJlcXVpcmVzIEhUTUw1IHNlc3Npb25TdG9yYWdlIHN1cHBvcnQuXG5cdFx0ICpcblx0XHQgKiBQYXJhbWV0ZXJzOlxuXHRcdCAqIFx0KE9iamVjdCkgTWFwIG9mIHRhYiBjb250YWluZXIgc2VsZWN0b3JzIHRvIHRhYiBzZWxlY3RvcnMuXG5cdFx0ICogXHRVc2VkIHRvIG1hcmsgYSBzcGVjaWZpYyB0YWIgYXMgYWN0aXZlIHJlZ2FyZGxlc3Mgb2YgdGhlIHByZXZpb3VzbHkgc2F2ZWQgb3B0aW9ucy5cblx0XHQgKi9cblx0XHRyZXN0b3JlVGFiU3RhdGU6IGZ1bmN0aW9uKG92ZXJyaWRlU3RhdGVzKSB7XG5cdFx0XHR2YXIgc2VsZiA9IHRoaXMsIHVybCA9IHRoaXMuX3RhYlN0YXRlVXJsKCksXG5cdFx0XHRcdGhhc1Nlc3Npb25TdG9yYWdlID0gKHR5cGVvZih3aW5kb3cuc2Vzc2lvblN0b3JhZ2UpIT09XCJ1bmRlZmluZWRcIiAmJiB3aW5kb3cuc2Vzc2lvblN0b3JhZ2UpLFxuXHRcdFx0XHRzZXNzaW9uRGF0YSA9IGhhc1Nlc3Npb25TdG9yYWdlID8gd2luZG93LnNlc3Npb25TdG9yYWdlLmdldEl0ZW0oJ3RhYnMtJyArIHVybCkgOiBudWxsLFxuXHRcdFx0XHRzZXNzaW9uU3RhdGVzID0gc2Vzc2lvbkRhdGEgPyBKU09OLnBhcnNlKHNlc3Npb25EYXRhKSA6IGZhbHNlO1xuXG5cdFx0XHR0aGlzLmZpbmQoJy5jbXMtdGFic2V0LCAuc3MtdGFic2V0JykuZWFjaChmdW5jdGlvbigpIHtcblx0XHRcdFx0dmFyIGluZGV4LCB0YWJzZXQgPSAkKHRoaXMpLCB0YWJzZXRJZCA9IHRhYnNldC5hdHRyKCdpZCcpLCB0YWIsXG5cdFx0XHRcdFx0Zm9yY2VkVGFiID0gdGFic2V0LmZpbmQoJy5zcy10YWJzLWZvcmNlLWFjdGl2ZScpO1xuXG5cdFx0XHRcdGlmKCF0YWJzZXQuZGF0YSgndGFicycpKXtcblx0XHRcdFx0XHRyZXR1cm47IC8vIGRvbid0IGFjdCBvbiB1bmluaXQnZWQgY29udHJvbHNcblx0XHRcdFx0fVxuXG5cdFx0XHRcdC8vIFRoZSB0YWJzIG1heSBoYXZlIGNoYW5nZWQsIG5vdGlmeSB0aGUgd2lkZ2V0IHRoYXQgaXQgc2hvdWxkIHVwZGF0ZSBpdHMgaW50ZXJuYWwgc3RhdGUuXG5cdFx0XHRcdHRhYnNldC50YWJzKCdyZWZyZXNoJyk7XG5cblx0XHRcdFx0Ly8gTWFrZSBzdXJlIHRoZSBpbnRlbmRlZCB0YWIgaXMgc2VsZWN0ZWQuXG5cdFx0XHRcdGlmKGZvcmNlZFRhYi5sZW5ndGgpIHtcblx0XHRcdFx0XHRpbmRleCA9IGZvcmNlZFRhYi5pbmRleCgpO1xuXHRcdFx0XHR9IGVsc2UgaWYob3ZlcnJpZGVTdGF0ZXMgJiYgb3ZlcnJpZGVTdGF0ZXNbdGFic2V0SWRdKSB7XG5cdFx0XHRcdFx0dGFiID0gdGFic2V0LmZpbmQob3ZlcnJpZGVTdGF0ZXNbdGFic2V0SWRdLnRhYlNlbGVjdG9yKTtcblx0XHRcdFx0XHRpZih0YWIubGVuZ3RoKXtcblx0XHRcdFx0XHRcdGluZGV4ID0gdGFiLmluZGV4KCk7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9IGVsc2UgaWYoc2Vzc2lvblN0YXRlcykge1xuXHRcdFx0XHRcdCQuZWFjaChzZXNzaW9uU3RhdGVzLCBmdW5jdGlvbihpLCBzZXNzaW9uU3RhdGUpIHtcblx0XHRcdFx0XHRcdGlmKHRhYnNldC5pcygnIycgKyBzZXNzaW9uU3RhdGUuaWQpKXtcblx0XHRcdFx0XHRcdFx0aW5kZXggPSBzZXNzaW9uU3RhdGUuc2VsZWN0ZWQ7XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0fSk7XG5cdFx0XHRcdH1cblx0XHRcdFx0aWYoaW5kZXggIT09IG51bGwpe1xuXHRcdFx0XHRcdHRhYnNldC50YWJzKCdvcHRpb24nLCAnYWN0aXZlJywgaW5kZXgpO1xuXHRcdFx0XHRcdHNlbGYudHJpZ2dlcigndGFic3RhdGVyZXN0b3JlZCcpO1xuXHRcdFx0XHR9XG5cdFx0XHR9KTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogUmVtb3ZlIGFueSBwcmV2aW91c2x5IHNhdmVkIHN0YXRlLlxuXHRcdCAqXG5cdFx0ICogUGFyYW1ldGVyczpcblx0XHQgKiAgKFN0cmluZykgdXJsIE9wdGlvbmFsIChzYW5pdGl6ZWQpIFVSTCB0byBjbGVhciBhIHNwZWNpZmljIHN0YXRlLlxuXHRcdCAqL1xuXHRcdGNsZWFyVGFiU3RhdGU6IGZ1bmN0aW9uKHVybCkge1xuXHRcdFx0aWYodHlwZW9mKHdpbmRvdy5zZXNzaW9uU3RvcmFnZSk9PVwidW5kZWZpbmVkXCIpIHJldHVybjtcblxuXHRcdFx0dmFyIHMgPSB3aW5kb3cuc2Vzc2lvblN0b3JhZ2U7XG5cdFx0XHRpZih1cmwpIHtcblx0XHRcdFx0cy5yZW1vdmVJdGVtKCd0YWJzLScgKyB1cmwpO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0Zm9yKHZhciBpPTA7aTxzLmxlbmd0aDtpKyspIHtcblx0XHRcdFx0XHRpZihzLmtleShpKS5tYXRjaCgvXnRhYnMtLykpIHMucmVtb3ZlSXRlbShzLmtleShpKSk7XG5cdFx0XHR9XG5cdFx0XHR9XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFJlbW92ZSB0YWIgc3RhdGUgZm9yIHRoZSBjdXJyZW50IFVSTC5cblx0XHQgKi9cblx0XHRjbGVhckN1cnJlbnRUYWJTdGF0ZTogZnVuY3Rpb24oKSB7XG5cdFx0XHR0aGlzLmNsZWFyVGFiU3RhdGUodGhpcy5fdGFiU3RhdGVVcmwoKSk7XG5cdFx0fSxcblxuXHRcdF90YWJTdGF0ZVVybDogZnVuY3Rpb24oKSB7XG5cdFx0XHRyZXR1cm4gd2luZG93Lmhpc3Rvcnkuc3RhdGUucGF0aFxuXHRcdFx0XHQucmVwbGFjZSgvXFw/LiovLCAnJylcblx0XHRcdFx0LnJlcGxhY2UoLyMuKi8sICcnKVxuXHRcdFx0XHQucmVwbGFjZSgkKCdiYXNlJykuYXR0cignaHJlZicpLCAnJyk7XG5cdFx0fSxcblxuXHRcdHNob3dMb2dpbkRpYWxvZzogZnVuY3Rpb24oKSB7XG5cdFx0XHR2YXIgdGVtcGlkID0gJCgnYm9keScpLmRhdGEoJ21lbWJlci10ZW1waWQnKSxcblx0XHRcdFx0ZGlhbG9nID0gJCgnLmxlZnRhbmRtYWluLWxvZ2luZGlhbG9nJyksXG5cdFx0XHRcdHVybCA9ICdDTVNTZWN1cml0eS9sb2dpbic7XG5cblx0XHRcdC8vIEZvcmNlIHJlZ2VuZXJhdGlvbiBvZiBhbnkgZXhpc3RpbmcgZGlhbG9nXG5cdFx0XHRpZihkaWFsb2cubGVuZ3RoKSBkaWFsb2cucmVtb3ZlKCk7XG5cblx0XHRcdC8vIEpvaW4gdXJsIHBhcmFtc1xuXHRcdFx0dXJsID0gJC5wYXRoLmFkZFNlYXJjaFBhcmFtcyh1cmwsIHtcblx0XHRcdFx0J3RlbXBpZCc6IHRlbXBpZCxcblx0XHRcdFx0J0JhY2tVUkwnOiB3aW5kb3cubG9jYXRpb24uaHJlZlxuXHRcdFx0fSk7XG5cblx0XHRcdC8vIFNob3cgYSBwbGFjZWhvbGRlciBmb3IgaW5zdGFudCBmZWVkYmFjay4gV2lsbCBiZSByZXBsYWNlZCB3aXRoIGFjdHVhbFxuXHRcdFx0Ly8gZm9ybSBkaWFsb2cgb25jZSBpdHMgbG9hZGVkLlxuXHRcdFx0ZGlhbG9nID0gJCgnPGRpdiBjbGFzcz1cImxlZnRhbmRtYWluLWxvZ2luZGlhbG9nXCI+PC9kaXY+Jyk7XG5cdFx0XHRkaWFsb2cuYXR0cignaWQnLCBuZXcgRGF0ZSgpLmdldFRpbWUoKSk7XG5cdFx0XHRkaWFsb2cuZGF0YSgndXJsJywgdXJsKTtcblx0XHRcdCQoJ2JvZHknKS5hcHBlbmQoZGlhbG9nKTtcblx0XHR9XG5cdH0pO1xuXG5cdC8vIExvZ2luIGRpYWxvZyBwYWdlXG5cdCQoJy5sZWZ0YW5kbWFpbi1sb2dpbmRpYWxvZycpLmVudHdpbmUoe1xuXHRcdG9ubWF0Y2g6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblxuXHRcdFx0Ly8gQ3JlYXRlIGpRdWVyeSBkaWFsb2dcblx0XHRcdHRoaXMuc3NkaWFsb2coe1xuXHRcdFx0XHRpZnJhbWVVcmw6IHRoaXMuZGF0YSgndXJsJyksXG5cdFx0XHRcdGRpYWxvZ0NsYXNzOiBcImxlZnRhbmRtYWluLWxvZ2luZGlhbG9nLWRpYWxvZ1wiLFxuXHRcdFx0XHRhdXRvT3BlbjogdHJ1ZSxcblx0XHRcdFx0bWluV2lkdGg6IDUwMCxcblx0XHRcdFx0bWF4V2lkdGg6IDUwMCxcblx0XHRcdFx0bWluSGVpZ2h0OiAzNzAsXG5cdFx0XHRcdG1heEhlaWdodDogNDAwLFxuXHRcdFx0XHRjbG9zZU9uRXNjYXBlOiBmYWxzZSxcblx0XHRcdFx0b3BlbjogZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0JCgnLnVpLXdpZGdldC1vdmVybGF5JykuYWRkQ2xhc3MoJ2xlZnRhbmRtYWluLWxvZ2luZGlhbG9nLW92ZXJsYXknKTtcblx0XHRcdFx0fSxcblx0XHRcdFx0Y2xvc2U6IGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdCQoJy51aS13aWRnZXQtb3ZlcmxheScpLnJlbW92ZUNsYXNzKCdsZWZ0YW5kbWFpbi1sb2dpbmRpYWxvZy1vdmVybGF5Jyk7XG5cdFx0XHRcdH1cblx0XHRcdH0pO1xuXHRcdH0sXG5cdFx0b251bm1hdGNoOiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMuX3N1cGVyKCk7XG5cdFx0fSxcblx0XHRvcGVuOiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMuc3NkaWFsb2coJ29wZW4nKTtcblx0XHR9LFxuXHRcdGNsb3NlOiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMuc3NkaWFsb2coJ2Nsb3NlJyk7XG5cdFx0fSxcblx0XHR0b2dnbGU6IGZ1bmN0aW9uKGJvb2wpIHtcblx0XHRcdGlmKHRoaXMuaXMoJzp2aXNpYmxlJykpIHRoaXMuY2xvc2UoKTtcblx0XHRcdGVsc2UgdGhpcy5vcGVuKCk7XG5cdFx0fSxcblx0XHQvKipcblx0XHQgKiBDYWxsYmFjayBhY3RpdmF0ZWQgYnkgQ01TU2VjdXJpdHlfc3VjY2Vzcy5zc1xuXHRcdCAqL1xuXHRcdHJlYXV0aGVudGljYXRlOiBmdW5jdGlvbihkYXRhKSB7XG5cdFx0XHQvLyBSZXBsYWNlIGFsbCBTZWN1cml0eUlEIGZpZWxkcyB3aXRoIHRoZSBnaXZlbiB2YWx1ZVxuXHRcdFx0aWYodHlwZW9mKGRhdGEuU2VjdXJpdHlJRCkgIT09ICd1bmRlZmluZWQnKSB7XG5cdFx0XHRcdCQoJzppbnB1dFtuYW1lPVNlY3VyaXR5SURdJykudmFsKGRhdGEuU2VjdXJpdHlJRCk7XG5cdFx0XHR9XG5cdFx0XHQvLyBVcGRhdGUgVGVtcElEIGZvciBjdXJyZW50IHVzZXJcblx0XHRcdGlmKHR5cGVvZihkYXRhLlRlbXBJRCkgIT09ICd1bmRlZmluZWQnKSB7XG5cdFx0XHRcdCQoJ2JvZHknKS5kYXRhKCdtZW1iZXItdGVtcGlkJywgZGF0YS5UZW1wSUQpO1xuXHRcdFx0fVxuXHRcdFx0dGhpcy5jbG9zZSgpO1xuXHRcdH1cblx0fSk7XG5cblx0LyoqXG5cdCAqIEFkZCBsb2FkaW5nIG92ZXJsYXkgdG8gc2VsZWN0ZWQgcmVnaW9ucyBpbiB0aGUgQ01TIGF1dG9tYXRpY2FsbHkuXG5cdCAqIE5vdCBhcHBsaWVkIHRvIGFsbCBcIioubG9hZGluZ1wiIGVsZW1lbnRzIHRvIGF2b2lkIHNlY29uZGFyeSByZWdpb25zXG5cdCAqIGxpa2UgdGhlIGJyZWFkY3J1bWJzIHNob3dpbmcgdW5uZWNlc3NhcnkgbG9hZGluZyBzdGF0dXMuXG5cdCAqL1xuXHQkKCdmb3JtLmxvYWRpbmcsLmNtcy1jb250ZW50LmxvYWRpbmcsLmNtcy1jb250ZW50LWZpZWxkcy5sb2FkaW5nLC5jbXMtY29udGVudC12aWV3LmxvYWRpbmcnKS5lbnR3aW5lKHtcblx0XHRvbm1hdGNoOiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMuYXBwZW5kKCc8ZGl2IGNsYXNzPVwiY21zLWNvbnRlbnQtbG9hZGluZy1vdmVybGF5IHVpLXdpZGdldC1vdmVybGF5LWxpZ2h0XCI+PC9kaXY+PGRpdiBjbGFzcz1cImNtcy1jb250ZW50LWxvYWRpbmctc3Bpbm5lclwiPjwvZGl2PicpO1xuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9LFxuXHRcdG9udW5tYXRjaDogZnVuY3Rpb24oKSB7XG5cdFx0XHR0aGlzLmZpbmQoJy5jbXMtY29udGVudC1sb2FkaW5nLW92ZXJsYXksLmNtcy1jb250ZW50LWxvYWRpbmctc3Bpbm5lcicpLnJlbW92ZSgpO1xuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9XG5cdH0pO1xuXG5cdC8qKiBNYWtlIGFsbCBidXR0b25zIFwiaG92ZXJhYmxlXCIgd2l0aCBqUXVlcnkgdGhlbWluZy4gKi9cblx0JCgnLmNtcyBpbnB1dFt0eXBlPVwic3VibWl0XCJdLCAuY21zIGJ1dHRvbiwgLmNtcyBpbnB1dFt0eXBlPVwicmVzZXRcIl0sIC5jbXMgLnNzLXVpLWJ1dHRvbicpLmVudHdpbmUoe1xuXHRcdG9uYWRkOiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMuYWRkQ2xhc3MoJ3NzLXVpLWJ1dHRvbicpO1xuXHRcdFx0aWYoIXRoaXMuZGF0YSgnYnV0dG9uJykpIHRoaXMuYnV0dG9uKCk7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdH0sXG5cdFx0b25yZW1vdmU6IGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYodGhpcy5kYXRhKCdidXR0b24nKSkgdGhpcy5idXR0b24oJ2Rlc3Ryb3knKTtcblx0XHRcdHRoaXMuX3N1cGVyKCk7XG5cdFx0fVxuXHR9KTtcblxuXHQvKipcblx0ICogTG9hZHMgdGhlIGxpbmsncyAnaHJlZicgYXR0cmlidXRlIGludG8gYSBwYW5lbCB2aWEgYWpheCxcblx0ICogYXMgb3Bwb3NlZCB0byB0cmlnZ2VyaW5nIGEgZnVsbCBwYWdlIHJlbG9hZC5cblx0ICogTGl0dGxlIGhlbHBlciB0byBhdm9pZCByZXBldGl0aW9uLCBhbmQgbWFrZSBpdCBlYXN5IHRvXG5cdCAqIFwib3B0IGluXCIgdG8gcGFuZWwgbG9hZGluZywgd2hpbGUgYnkgZGVmYXVsdCBsaW5rcyBzdGlsbCBleGhpYml0IHRoZWlyIGRlZmF1bHQgYmVoYXZpb3VyLlxuXHQgKiBUaGUgUEpBWCB0YXJnZXQgY2FuIGJlIHNwZWNpZmllZCB2aWEgYSAnZGF0YS1wamF4LXRhcmdldCcgYXR0cmlidXRlLlxuXHQgKi9cblx0JCgnLmNtcyAuY21zLXBhbmVsLWxpbmsnKS5lbnR3aW5lKHtcblx0XHRvbmNsaWNrOiBmdW5jdGlvbihlKSB7XG5cdFx0XHRpZigkKHRoaXMpLmhhc0NsYXNzKCdleHRlcm5hbC1saW5rJykpIHtcblx0XHRcdFx0ZS5zdG9wUHJvcGFnYXRpb24oKTtcblxuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdHZhciBocmVmID0gdGhpcy5hdHRyKCdocmVmJyksXG5cdFx0XHRcdHVybCA9IChocmVmICYmICFocmVmLm1hdGNoKC9eIy8pKSA/IGhyZWYgOiB0aGlzLmRhdGEoJ2hyZWYnKSxcblx0XHRcdFx0ZGF0YSA9IHtwamF4OiB0aGlzLmRhdGEoJ3BqYXhUYXJnZXQnKX07XG5cblx0XHRcdCQoJy5jbXMtY29udGFpbmVyJykubG9hZFBhbmVsKHVybCwgbnVsbCwgZGF0YSk7XG5cdFx0XHRlLnByZXZlbnREZWZhdWx0KCk7XG5cdFx0fVxuXHR9KTtcblxuXHQvKipcblx0ICogRG9lcyBhbiBhamF4IGxvYWRzIG9mIHRoZSBsaW5rJ3MgJ2hyZWYnIGF0dHJpYnV0ZSB2aWEgYWpheCBhbmQgZGlzcGxheXMgYW55IEZvcm1SZXNwb25zZSBtZXNzYWdlcyBmcm9tIHRoZSBDTVMuXG5cdCAqIExpdHRsZSBoZWxwZXIgdG8gYXZvaWQgcmVwZXRpdGlvbiwgYW5kIG1ha2UgaXQgZWFzeSB0byB0cmlnZ2VyIGFjdGlvbnMgdmlhIGEgbGluayxcblx0ICogd2l0aG91dCByZWxvYWRpbmcgdGhlIHBhZ2UsIGNoYW5naW5nIHRoZSBVUkwsIG9yIGxvYWRpbmcgaW4gYW55IG5ldyBwYW5lbCBjb250ZW50LlxuXHQgKi9cblx0JCgnLmNtcyAuc3MtdWktYnV0dG9uLWFqYXgnKS5lbnR3aW5lKHtcblx0XHRvbmNsaWNrOiBmdW5jdGlvbihlKSB7XG5cdFx0XHQkKHRoaXMpLnJlbW92ZUNsYXNzKCd1aS1idXR0b24tdGV4dC1vbmx5Jyk7XG5cdFx0XHQkKHRoaXMpLmFkZENsYXNzKCdzcy11aS1idXR0b24tbG9hZGluZyB1aS1idXR0b24tdGV4dC1pY29ucycpO1xuXG5cdFx0XHR2YXIgbG9hZGluZyA9ICQodGhpcykuZmluZChcIi5zcy11aS1sb2FkaW5nLWljb25cIik7XG5cblx0XHRcdGlmKGxvYWRpbmcubGVuZ3RoIDwgMSkge1xuXHRcdFx0XHRsb2FkaW5nID0gJChcIjxzcGFuPjwvc3Bhbj5cIikuYWRkQ2xhc3MoJ3NzLXVpLWxvYWRpbmctaWNvbiB1aS1idXR0b24taWNvbi1wcmltYXJ5IHVpLWljb24nKTtcblxuXHRcdFx0XHQkKHRoaXMpLnByZXBlbmQobG9hZGluZyk7XG5cdFx0XHR9XG5cblx0XHRcdGxvYWRpbmcuc2hvdygpO1xuXG5cdFx0XHR2YXIgaHJlZiA9IHRoaXMuYXR0cignaHJlZicpLCB1cmwgPSBocmVmID8gaHJlZiA6IHRoaXMuZGF0YSgnaHJlZicpO1xuXG5cdFx0XHRqUXVlcnkuYWpheCh7XG5cdFx0XHRcdHVybDogdXJsLFxuXHRcdFx0XHQvLyBFbnN1cmUgdGhhdCBmb3JtIHZpZXcgaXMgbG9hZGVkIChyYXRoZXIgdGhhbiB3aG9sZSBcIkNvbnRlbnRcIiB0ZW1wbGF0ZSlcblx0XHRcdFx0Y29tcGxldGU6IGZ1bmN0aW9uKHhtbGh0dHAsIHN0YXR1cykge1xuXHRcdFx0XHRcdHZhciBtc2cgPSAoeG1saHR0cC5nZXRSZXNwb25zZUhlYWRlcignWC1TdGF0dXMnKSkgPyB4bWxodHRwLmdldFJlc3BvbnNlSGVhZGVyKCdYLVN0YXR1cycpIDogeG1saHR0cC5yZXNwb25zZVRleHQ7XG5cblx0XHRcdFx0XHR0cnkge1xuXHRcdFx0XHRcdFx0aWYgKHR5cGVvZiBtc2cgIT0gXCJ1bmRlZmluZWRcIiAmJiBtc2cgIT09IG51bGwpIGV2YWwobXNnKTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0Y2F0Y2goZSkge31cblxuXHRcdFx0XHRcdGxvYWRpbmcuaGlkZSgpO1xuXG5cdFx0XHRcdFx0JChcIi5jbXMtY29udGFpbmVyXCIpLnJlZnJlc2goKTtcblxuXHRcdFx0XHRcdCQodGhpcykucmVtb3ZlQ2xhc3MoJ3NzLXVpLWJ1dHRvbi1sb2FkaW5nIHVpLWJ1dHRvbi10ZXh0LWljb25zJyk7XG5cdFx0XHRcdFx0JCh0aGlzKS5hZGRDbGFzcygndWktYnV0dG9uLXRleHQtb25seScpO1xuXHRcdFx0XHR9LFxuXHRcdFx0XHRkYXRhVHlwZTogJ2h0bWwnXG5cdFx0XHR9KTtcblx0XHRcdGUucHJldmVudERlZmF1bHQoKTtcblx0XHR9XG5cdH0pO1xuXG5cdC8qKlxuXHQgKiBUcmlnZ2VyIGRpYWxvZ3Mgd2l0aCBpZnJhbWUgYmFzZWQgb24gdGhlIGxpbmtzIGhyZWYgYXR0cmlidXRlIChzZWUgc3N1aS1jb3JlLmpzKS5cblx0ICovXG5cdCQoJy5jbXMgLnNzLXVpLWRpYWxvZy1saW5rJykuZW50d2luZSh7XG5cdFx0VVVJRDogbnVsbCxcblx0XHRvbm1hdGNoOiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMuX3N1cGVyKCk7XG5cdFx0XHR0aGlzLnNldFVVSUQobmV3IERhdGUoKS5nZXRUaW1lKCkpO1xuXHRcdH0sXG5cdFx0b251bm1hdGNoOiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMuX3N1cGVyKCk7XG5cdFx0fSxcblx0XHRvbmNsaWNrOiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMuX3N1cGVyKCk7XG5cblx0XHRcdHZhciBzZWxmID0gdGhpcywgaWQgPSAnc3MtdWktZGlhbG9nLScgKyB0aGlzLmdldFVVSUQoKTtcblx0XHRcdHZhciBkaWFsb2cgPSAkKCcjJyArIGlkKTtcblx0XHRcdGlmKCFkaWFsb2cubGVuZ3RoKSB7XG5cdFx0XHRcdGRpYWxvZyA9ICQoJzxkaXYgY2xhc3M9XCJzcy11aS1kaWFsb2dcIiBpZD1cIicgKyBpZCArICdcIiAvPicpO1xuXHRcdFx0XHQkKCdib2R5JykuYXBwZW5kKGRpYWxvZyk7XG5cdFx0XHR9XG5cblx0XHRcdHZhciBleHRyYUNsYXNzID0gdGhpcy5kYXRhKCdwb3B1cGNsYXNzJyk/dGhpcy5kYXRhKCdwb3B1cGNsYXNzJyk6Jyc7XG5cblx0XHRcdGRpYWxvZy5zc2RpYWxvZyh7aWZyYW1lVXJsOiB0aGlzLmF0dHIoJ2hyZWYnKSwgYXV0b09wZW46IHRydWUsIGRpYWxvZ0V4dHJhQ2xhc3M6IGV4dHJhQ2xhc3N9KTtcblx0XHRcdHJldHVybiBmYWxzZTtcblx0XHR9XG5cdH0pO1xuXG5cdC8qKlxuXHQgKiBBZGQgc3R5bGluZyB0byBhbGwgY29udGFpbmVkIGJ1dHRvbnMsIGFuZCBjcmVhdGUgYnV0dG9uc2V0cyBpZiByZXF1aXJlZC5cblx0ICovXG5cdCQoJy5jbXMtY29udGVudCAuQWN0aW9ucycpLmVudHdpbmUoe1xuXHRcdG9ubWF0Y2g6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dGhpcy5maW5kKCcuc3MtdWktYnV0dG9uJykuY2xpY2soZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0dmFyIGZvcm0gPSB0aGlzLmZvcm07XG5cblx0XHRcdFx0XHQvLyBmb3JtcyBkb24ndCBuYXRpdmVseSBzdG9yZSB0aGUgYnV0dG9uIHRoZXkndmUgYmVlbiB0cmlnZ2VyZWQgd2l0aFxuXHRcdFx0XHRcdGlmKGZvcm0pIHtcblx0XHRcdFx0XHRcdGZvcm0uY2xpY2tlZEJ1dHRvbiA9IHRoaXM7XG5cdFx0XHRcdFx0XHQvLyBSZXNldCB0aGUgY2xpY2tlZCBidXR0b24gc2hvcnRseSBhZnRlciB0aGUgb25zdWJtaXQgaGFuZGxlcnNcblx0XHRcdFx0XHRcdC8vIGhhdmUgZmlyZWQgb24gdGhlIGZvcm1cblx0XHRcdFx0XHRzZXRUaW1lb3V0KGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdFx0Zm9ybS5jbGlja2VkQnV0dG9uID0gbnVsbDtcblx0XHRcdFx0XHR9LCAxMCk7XG5cdFx0XHRcdH1cblx0XHRcdH0pO1xuXG5cdFx0XHR0aGlzLnJlZHJhdygpO1xuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9LFxuXHRcdG9udW5tYXRjaDogZnVuY3Rpb24oKSB7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdH0sXG5cdFx0cmVkcmF3OiBmdW5jdGlvbigpIHtcblx0XHRcdGlmKHdpbmRvdy5kZWJ1ZykgY29uc29sZS5sb2coJ3JlZHJhdycsIHRoaXMuYXR0cignY2xhc3MnKSwgdGhpcy5nZXQoMCkpO1xuXG5cdFx0XHQvLyBSZW1vdmUgd2hpdGVzcGFjZSB0byBhdm9pZCBnYXBzIHdpdGggaW5saW5lIGVsZW1lbnRzXG5cdFx0XHR0aGlzLmNvbnRlbnRzKCkuZmlsdGVyKGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRyZXR1cm4gKHRoaXMubm9kZVR5cGUgPT0gMyAmJiAhL1xcUy8udGVzdCh0aGlzLm5vZGVWYWx1ZSkpO1xuXHRcdFx0fSkucmVtb3ZlKCk7XG5cblx0XHRcdC8vIEluaXQgYnV0dG9ucyBpZiByZXF1aXJlZFxuXHRcdFx0dGhpcy5maW5kKCcuc3MtdWktYnV0dG9uJykuZWFjaChmdW5jdGlvbigpIHtcblx0XHRcdFx0aWYoISQodGhpcykuZGF0YSgnYnV0dG9uJykpICQodGhpcykuYnV0dG9uKCk7XG5cdFx0XHR9KTtcblxuXHRcdFx0Ly8gTWFyayB1cCBidXR0b25zZXRzXG5cdFx0XHR0aGlzLmZpbmQoJy5zcy11aS1idXR0b25zZXQnKS5idXR0b25zZXQoKTtcblx0XHR9XG5cdH0pO1xuXG5cdC8qKlxuXHQgKiBEdXBsaWNhdGVzIGZ1bmN0aW9uYWxpdHkgaW4gRGF0ZUZpZWxkLmpzLCBidXQgZHVlIHRvIHVzaW5nIGVudHdpbmUgd2UgY2FuIG1hdGNoXG5cdCAqIHRoZSBET00gZWxlbWVudCBvbiBjcmVhdGlvbiwgcmF0aGVyIHRoYW4gb25jbGljayAtIHdoaWNoIGFsbG93cyB1cyB0byBkZWNvcmF0ZVxuXHQgKiB0aGUgZmllbGQgd2l0aCBhIGNhbGVuZGFyIGljb25cblx0ICovXG5cdCQoJy5jbXMgLmZpZWxkLmRhdGUgaW5wdXQudGV4dCcpLmVudHdpbmUoe1xuXHRcdG9ubWF0Y2g6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dmFyIGhvbGRlciA9ICQodGhpcykucGFyZW50cygnLmZpZWxkLmRhdGU6Zmlyc3QnKSwgY29uZmlnID0gaG9sZGVyLmRhdGEoKTtcblx0XHRcdGlmKCFjb25maWcuc2hvd2NhbGVuZGFyKSB7XG5cdFx0XHRcdHRoaXMuX3N1cGVyKCk7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0Y29uZmlnLnNob3dPbiA9ICdidXR0b24nO1xuXHRcdFx0aWYoY29uZmlnLmxvY2FsZSAmJiAkLmRhdGVwaWNrZXIucmVnaW9uYWxbY29uZmlnLmxvY2FsZV0pIHtcblx0XHRcdFx0Y29uZmlnID0gJC5leHRlbmQoY29uZmlnLCAkLmRhdGVwaWNrZXIucmVnaW9uYWxbY29uZmlnLmxvY2FsZV0sIHt9KTtcblx0XHRcdH1cblxuXHRcdFx0JCh0aGlzKS5kYXRlcGlja2VyKGNvbmZpZyk7XG5cdFx0XHQvLyAvLyBVbmZvcnR1bmF0ZWx5IGpRdWVyeSBVSSBvbmx5IGFsbG93cyBjb25maWd1cmF0aW9uIG9mIGljb24gaW1hZ2VzLCBub3Qgc3ByaXRlc1xuXHRcdFx0Ly8gdGhpcy5uZXh0KCdidXR0b24nKS5idXR0b24oJ29wdGlvbicsICdpY29ucycsIHtwcmltYXJ5IDogJ3VpLWljb24tY2FsZW5kYXInfSk7XG5cblx0XHRcdHRoaXMuX3N1cGVyKCk7XG5cdFx0fSxcblx0XHRvbnVubWF0Y2g6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9XG5cdH0pO1xuXG5cdC8qKlxuXHQgKiBTdHlsZWQgZHJvcGRvd24gc2VsZWN0IGZpZWxkcyB2aWEgY2hvc2VuLiBBbGxvd3MgdGhpbmdzIGxpa2Ugc2VhcmNoIGFuZCBvcHRncm91cFxuXHQgKiBzZWxlY3Rpb24gc3VwcG9ydC4gUmF0aGVyIHRoYW4gbWFudWFsbHkgYWRkaW5nIGNsYXNzZXMgdG8gc2VsZWN0cyB3ZSB3YW50XG5cdCAqIHN0eWxlZCwgd2Ugc3R5bGUgZXZlcnl0aGluZyBidXQgdGhlIG9uZXMgd2UgdGVsbCBpdCBub3QgdG8uXG5cdCAqXG5cdCAqIEZvciB0aGUgQ01TIHdlIGFsc28gbmVlZCB0byB0ZWxsIHRoZSBwYXJlbnQgZGl2IHRoYXQgaXQgaGFzIGEgc2VsZWN0IHNvXG5cdCAqIHdlIGNhbiBmaXggdGhlIGhlaWdodCBjcm9wcGluZy5cblx0ICovXG5cblx0JCgnLmNtcyAuZmllbGQuZHJvcGRvd24gc2VsZWN0LCAuY21zIC5maWVsZCBzZWxlY3RbbXVsdGlwbGVdLCAuZmllbGRob2xkZXItc21hbGwgc2VsZWN0LmRyb3Bkb3duJykuZW50d2luZSh7XG5cdFx0b25tYXRjaDogZnVuY3Rpb24oKSB7XG5cdFx0XHRpZih0aGlzLmlzKCcubm8tY2h6bicpKSB7XG5cdFx0XHRcdHRoaXMuX3N1cGVyKCk7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0Ly8gRXhwbGljaXRseSBkaXNhYmxlIGRlZmF1bHQgcGxhY2Vob2xkZXIgaWYgbm8gY3VzdG9tIG9uZSBpcyBkZWZpbmVkXG5cdFx0XHRpZighdGhpcy5kYXRhKCdwbGFjZWhvbGRlcicpKSB0aGlzLmRhdGEoJ3BsYWNlaG9sZGVyJywgJyAnKTtcblxuXHRcdFx0Ly8gV2UgY291bGQndmUgZ290dGVuIHN0YWxlIGNsYXNzZXMgYW5kIERPTSBlbGVtZW50cyBmcm9tIGRlZmVycmVkIGNhY2hlLlxuXHRcdFx0dGhpcy5yZW1vdmVDbGFzcygnaGFzLWNoem4gY2h6bi1kb25lJyk7XG5cdFx0XHR0aGlzLnNpYmxpbmdzKCcuY2h6bi1jb250YWluZXInKS5yZW1vdmUoKTtcblxuXHRcdFx0Ly8gQXBwbHkgQ2hvc2VuXG5cdFx0XHRhcHBseUNob3Nlbih0aGlzKTtcblxuXHRcdFx0dGhpcy5fc3VwZXIoKTtcblx0XHR9LFxuXHRcdG9udW5tYXRjaDogZnVuY3Rpb24oKSB7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdH1cblx0fSk7XG5cblx0JChcIi5jbXMtcGFuZWwtbGF5b3V0XCIpLmVudHdpbmUoe1xuXHRcdHJlZHJhdzogZnVuY3Rpb24oKSB7XG5cdFx0XHRpZih3aW5kb3cuZGVidWcpIGNvbnNvbGUubG9nKCdyZWRyYXcnLCB0aGlzLmF0dHIoJ2NsYXNzJyksIHRoaXMuZ2V0KDApKTtcblx0XHR9XG5cdH0pO1xuXG5cdC8qKlxuXHQgKiBPdmVybG9hZCB0aGUgZGVmYXVsdCBHcmlkRmllbGQgYmVoYXZpb3VyIChvcGVuIGEgbmV3IFVSTCBpbiB0aGUgYnJvd3Nlcilcblx0ICogd2l0aCB0aGUgQ01TLXNwZWNpZmljIGFqYXggbG9hZGluZy5cblx0ICovXG5cdCQoJy5jbXMgLnNzLWdyaWRmaWVsZCcpLmVudHdpbmUoe1xuXHRcdHNob3dEZXRhaWxWaWV3OiBmdW5jdGlvbih1cmwpIHtcblx0XHRcdC8vIEluY2x1ZGUgYW55IEdFVCBwYXJhbWV0ZXJzIGZyb20gdGhlIGN1cnJlbnQgVVJMLCBhcyB0aGUgdmlldyBzdGF0ZSBtaWdodCBkZXBlbmQgb24gaXQuXG5cdFx0XHQvLyBGb3IgZXhhbXBsZSwgYSBsaXN0IHByZWZpbHRlcmVkIHRocm91Z2ggZXh0ZXJuYWwgc2VhcmNoIGNyaXRlcmlhIG1pZ2h0IGJlIHBhc3NlZCB0byBHcmlkRmllbGQuXG5cdFx0XHR2YXIgcGFyYW1zID0gd2luZG93LmxvY2F0aW9uLnNlYXJjaC5yZXBsYWNlKC9eXFw/LywgJycpO1xuXHRcdFx0aWYocGFyYW1zKSB1cmwgPSAkLnBhdGguYWRkU2VhcmNoUGFyYW1zKHVybCwgcGFyYW1zKTtcblx0XHRcdCQoJy5jbXMtY29udGFpbmVyJykubG9hZFBhbmVsKHVybCk7XG5cdFx0fVxuXHR9KTtcblxuXG5cdC8qKlxuXHQgKiBHZW5lcmljIHNlYXJjaCBmb3JtIGluIHRoZSBDTVMsIG9mdGVuIGhvb2tlZCB1cCB0byBhIEdyaWRGaWVsZCByZXN1bHRzIGRpc3BsYXkuXG5cdCAqL1xuXHQkKCcuY21zLXNlYXJjaC1mb3JtJykuZW50d2luZSh7XG5cdFx0b25zdWJtaXQ6IGZ1bmN0aW9uKGUpIHtcblx0XHRcdC8vIFJlbW92ZSBlbXB0eSBlbGVtZW50cyBhbmQgbWFrZSB0aGUgVVJMIHByZXR0aWVyXG5cdFx0XHR2YXIgbm9uRW1wdHlJbnB1dHMsXG5cdFx0XHRcdHVybDtcblxuXHRcdFx0bm9uRW1wdHlJbnB1dHMgPSB0aGlzLmZpbmQoJzppbnB1dDpub3QoOnN1Ym1pdCknKS5maWx0ZXIoZnVuY3Rpb24oKSB7XG5cdFx0XHRcdC8vIFVzZSBmaWVsZFZhbHVlKCkgZnJvbSBqUXVlcnkuZm9ybSBwbHVnaW4gcmF0aGVyIHRoYW4galF1ZXJ5LnZhbCgpLFxuXHRcdFx0XHQvLyBhcyBpdCBoYW5kbGVzIGNoZWNrYm94IHZhbHVlcyBtb3JlIGNvbnNpc3RlbnRseVxuXHRcdFx0XHR2YXIgdmFscyA9ICQuZ3JlcCgkKHRoaXMpLmZpZWxkVmFsdWUoKSwgZnVuY3Rpb24odmFsKSB7IHJldHVybiAodmFsKTt9KTtcblx0XHRcdFx0cmV0dXJuICh2YWxzLmxlbmd0aCk7XG5cdFx0XHR9KTtcblxuXHRcdFx0dXJsID0gdGhpcy5hdHRyKCdhY3Rpb24nKTtcblxuXHRcdFx0aWYobm9uRW1wdHlJbnB1dHMubGVuZ3RoKSB7XG5cdFx0XHRcdHVybCA9ICQucGF0aC5hZGRTZWFyY2hQYXJhbXModXJsLCBub25FbXB0eUlucHV0cy5zZXJpYWxpemUoKSk7XG5cdFx0XHR9XG5cblx0XHRcdHZhciBjb250YWluZXIgPSB0aGlzLmNsb3Nlc3QoJy5jbXMtY29udGFpbmVyJyk7XG5cdFx0XHRjb250YWluZXIuZmluZCgnLmNtcy1lZGl0LWZvcm0nKS50YWJzKCdzZWxlY3QnLDApOyAgLy9hbHdheXMgc3dpdGNoIHRvIHRoZSBmaXJzdCB0YWIgKGxpc3Qgdmlldykgd2hlbiBzZWFyY2hpbmdcblx0XHRcdGNvbnRhaW5lci5sb2FkUGFuZWwodXJsLCBcIlwiLCB7fSwgdHJ1ZSk7XG5cblx0XHRcdHJldHVybiBmYWxzZTtcblx0XHR9XG5cdH0pO1xuXG5cdC8qKlxuXHQgKiBSZXNldCBidXR0b24gaGFuZGxlci4gSUU4IGRvZXMgbm90IGJ1YmJsZSByZXNldCBldmVudHMgdG9cblx0ICovXG5cdCQoXCIuY21zLXNlYXJjaC1mb3JtIGJ1dHRvblt0eXBlPXJlc2V0XSwgLmNtcy1zZWFyY2gtZm9ybSBpbnB1dFt0eXBlPXJlc2V0XVwiKS5lbnR3aW5lKHtcblx0XHRvbmNsaWNrOiBmdW5jdGlvbihlKSB7XG5cdFx0XHRlLnByZXZlbnREZWZhdWx0KCk7XG5cblx0XHRcdHZhciBmb3JtID0gJCh0aGlzKS5wYXJlbnRzKCdmb3JtJyk7XG5cblx0XHRcdGZvcm0uY2xlYXJGb3JtKCk7XG5cdFx0XHRmb3JtLmZpbmQoXCIuZHJvcGRvd24gc2VsZWN0XCIpLnByb3AoJ3NlbGVjdGVkSW5kZXgnLCAwKS50cmlnZ2VyKFwibGlzenQ6dXBkYXRlZFwiKTsgLy8gUmVzZXQgY2hvc2VuLmpzXG5cdFx0XHRmb3JtLnN1Ym1pdCgpO1xuXHRcdFx0fVxuXHR9KTtcblxuXHQvKipcblx0ICogQWxsb3dzIHRvIGxhenkgbG9hZCBhIHBhbmVsLCBieSBsZWF2aW5nIGl0IGVtcHR5XG5cdCAqIGFuZCBkZWNsYXJpbmcgYSBVUkwgdG8gbG9hZCBpdHMgY29udGVudCB2aWEgYSAndXJsJyBIVE1MNSBkYXRhIGF0dHJpYnV0ZS5cblx0ICogVGhlIGxvYWRlZCBIVE1MIGlzIGNhY2hlZCwgd2l0aCBjYWNoZSBrZXkgYmVpbmcgdGhlICd1cmwnIGF0dHJpYnV0ZS5cblx0ICogSW4gb3JkZXIgZm9yIHRoaXMgdG8gd29yayBjb25zaXN0ZW50bHksIHdlIGFzc3VtZSB0aGF0IHRoZSByZXNwb25zZXMgYXJlIHN0YXRlbGVzcy5cblx0ICogVG8gYXZvaWQgY2FjaGluZywgYWRkIGEgJ2RlZmVycmVkLW5vLWNhY2hlJyB0byB0aGUgbm9kZS5cblx0ICovXG5cdHdpbmRvdy5fcGFuZWxEZWZlcnJlZENhY2hlID0ge307XG5cdCQoJy5jbXMtcGFuZWwtZGVmZXJyZWQnKS5lbnR3aW5lKHtcblx0XHRvbmFkZDogZnVuY3Rpb24oKSB7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdFx0dGhpcy5yZWRyYXcoKTtcblx0XHR9LFxuXHRcdG9ucmVtb3ZlOiBmdW5jdGlvbigpIHtcblx0XHRcdGlmKHdpbmRvdy5kZWJ1ZykgY29uc29sZS5sb2coJ3NhdmluZycsIHRoaXMuZGF0YSgndXJsJyksIHRoaXMpO1xuXG5cdFx0XHQvLyBTYXZlIHRoZSBIVE1MIHN0YXRlIGF0IHRoZSBsYXN0IHBvc3NpYmxlIG1vbWVudC5cblx0XHRcdC8vIERvbid0IHN0b3JlIHRoZSBET00gdG8gYXZvaWQgbWVtb3J5IGxlYWtzLlxuXHRcdFx0aWYoIXRoaXMuZGF0YSgnZGVmZXJyZWROb0NhY2hlJykpIHdpbmRvdy5fcGFuZWxEZWZlcnJlZENhY2hlW3RoaXMuZGF0YSgndXJsJyldID0gdGhpcy5odG1sKCk7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdH0sXG5cdFx0cmVkcmF3OiBmdW5jdGlvbigpIHtcblx0XHRcdGlmKHdpbmRvdy5kZWJ1ZykgY29uc29sZS5sb2coJ3JlZHJhdycsIHRoaXMuYXR0cignY2xhc3MnKSwgdGhpcy5nZXQoMCkpO1xuXG5cdFx0XHR2YXIgc2VsZiA9IHRoaXMsIHVybCA9IHRoaXMuZGF0YSgndXJsJyk7XG5cdFx0XHRpZighdXJsKSB0aHJvdyAnRWxlbWVudHMgb2YgY2xhc3MgLmNtcy1wYW5lbC1kZWZlcnJlZCBuZWVkIGEgXCJkYXRhLXVybFwiIGF0dHJpYnV0ZSc7XG5cblx0XHRcdHRoaXMuX3N1cGVyKCk7XG5cblx0XHRcdC8vIElmIHRoZSBub2RlIGlzIGVtcHR5LCB0cnkgdG8gZWl0aGVyIGxvYWQgaXQgZnJvbSBjYWNoZSBvciB2aWEgYWpheC5cblx0XHRcdGlmKCF0aGlzLmNoaWxkcmVuKCkubGVuZ3RoKSB7XG5cdFx0XHRcdGlmKCF0aGlzLmRhdGEoJ2RlZmVycmVkTm9DYWNoZScpICYmIHR5cGVvZiB3aW5kb3cuX3BhbmVsRGVmZXJyZWRDYWNoZVt1cmxdICE9PSAndW5kZWZpbmVkJykge1xuXHRcdFx0XHRcdHRoaXMuaHRtbCh3aW5kb3cuX3BhbmVsRGVmZXJyZWRDYWNoZVt1cmxdKTtcblx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHR0aGlzLmFkZENsYXNzKCdsb2FkaW5nJyk7XG5cdFx0XHRcdFx0JC5hamF4KHtcblx0XHRcdFx0XHRcdHVybDogdXJsLFxuXHRcdFx0XHRcdFx0Y29tcGxldGU6IGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdFx0XHRzZWxmLnJlbW92ZUNsYXNzKCdsb2FkaW5nJyk7XG5cdFx0XHRcdFx0XHR9LFxuXHRcdFx0XHRcdFx0c3VjY2VzczogZnVuY3Rpb24oZGF0YSwgc3RhdHVzLCB4aHIpIHtcblx0XHRcdFx0XHRcdFx0c2VsZi5odG1sKGRhdGEpO1xuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdH0pO1xuXHRcdFx0XHR9XG5cdFx0XHR9XG5cdFx0fVxuXHR9KTtcblxuXHQvKipcblx0ICogTGlnaHR3ZWlnaHQgd3JhcHBlciBhcm91bmQgalF1ZXJ5IFVJIHRhYnMuXG5cdCAqIEVuc3VyZXMgdGhhdCBhbmNob3IgbGlua3MgYXJlIHNldCBwcm9wZXJseSxcblx0ICogYW5kIGFueSBuZXN0ZWQgdGFicyBhcmUgc2Nyb2xsZWQgaWYgdGhleSBoYXZlXG5cdCAqIHRoZWlyIGhlaWdodCBleHBsaWNpdGx5IHNldC4gVGhpcyBpcyBpbXBvcnRhbnRcblx0ICogZm9yIGZvcm1zIGluc2lkZSB0aGUgQ01TIGxheW91dC5cblx0ICovXG5cdCQoJy5jbXMtdGFic2V0JykuZW50d2luZSh7XG5cdFx0b25hZGQ6IGZ1bmN0aW9uKCkge1xuXHRcdFx0Ly8gQ2FuJ3QgbmFtZSByZWRyYXcoKSBhcyBpdCBjbGFzaGVzIHdpdGggb3RoZXIgQ01TIGVudHdpbmUgY2xhc3Nlc1xuXHRcdFx0dGhpcy5yZWRyYXdUYWJzKCk7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdH0sXG5cdFx0b25yZW1vdmU6IGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYgKHRoaXMuZGF0YSgndGFicycpKSB0aGlzLnRhYnMoJ2Rlc3Ryb3knKTtcblx0XHRcdHRoaXMuX3N1cGVyKCk7XG5cdFx0fSxcblx0XHRyZWRyYXdUYWJzOiBmdW5jdGlvbigpIHtcblx0XHRcdHRoaXMucmV3cml0ZUhhc2hsaW5rcygpO1xuXG5cdFx0XHR2YXIgaWQgPSB0aGlzLmF0dHIoJ2lkJyksIGFjdGl2ZVRhYiA9IHRoaXMuZmluZCgndWw6Zmlyc3QgLnVpLXRhYnMtYWN0aXZlJyk7XG5cblx0XHRcdGlmKCF0aGlzLmRhdGEoJ3VpVGFicycpKSB0aGlzLnRhYnMoe1xuXHRcdFx0XHRhY3RpdmU6IChhY3RpdmVUYWIuaW5kZXgoKSAhPSAtMSkgPyBhY3RpdmVUYWIuaW5kZXgoKSA6IDAsXG5cdFx0XHRcdGJlZm9yZUxvYWQ6IGZ1bmN0aW9uKGUsIHVpKSB7XG5cdFx0XHRcdFx0Ly8gRGlzYWJsZSBhdXRvbWF0aWMgYWpheCBsb2FkaW5nIG9mIHRhYnMgd2l0aG91dCBtYXRjaGluZyBET00gZWxlbWVudHMsXG5cdFx0XHRcdFx0Ly8gZGV0ZXJtaW5pbmcgaWYgdGhlIGN1cnJlbnQgVVJMIGRpZmZlcnMgZnJvbSB0aGUgdGFiIFVSTCBpcyB0b28gZXJyb3IgcHJvbmUuXG5cdFx0XHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdFx0XHR9LFxuXHRcdFx0XHRhY3RpdmF0ZTogZnVuY3Rpb24oZSwgdWkpIHtcblx0XHRcdFx0XHQvLyBVc2FiaWxpdHk6IEhpZGUgYWN0aW9ucyBmb3IgXCJyZWFkb25seVwiIHRhYnMgKHdoaWNoIGRvbid0IGNvbnRhaW4gYW55IGVkaXRhYmxlIGZpZWxkcylcblx0XHRcdFx0XHR2YXIgYWN0aW9ucyA9ICQodGhpcykuY2xvc2VzdCgnZm9ybScpLmZpbmQoJy5BY3Rpb25zJyk7XG5cdFx0XHRcdFx0aWYoJCh1aS5uZXdUYWIpLmNsb3Nlc3QoJ2xpJykuaGFzQ2xhc3MoJ3JlYWRvbmx5JykpIHtcblx0XHRcdFx0XHRcdGFjdGlvbnMuZmFkZU91dCgpO1xuXHRcdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0XHRhY3Rpb25zLnNob3coKTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH1cblx0XHRcdH0pO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBFbnN1cmUgaGFzaCBsaW5rcyBhcmUgcHJlZml4ZWQgd2l0aCB0aGUgY3VycmVudCBwYWdlIFVSTCxcblx0XHQgKiBvdGhlcndpc2UgalF1ZXJ5IGludGVycHJldHMgdGhlbSBhcyBiZWluZyBleHRlcm5hbC5cblx0XHQgKi9cblx0XHRyZXdyaXRlSGFzaGxpbmtzOiBmdW5jdGlvbigpIHtcblx0XHRcdCQodGhpcykuZmluZCgndWwgYScpLmVhY2goZnVuY3Rpb24oKSB7XG5cdFx0XHRcdGlmICghJCh0aGlzKS5hdHRyKCdocmVmJykpIHJldHVybjtcblx0XHRcdFx0dmFyIG1hdGNoZXMgPSAkKHRoaXMpLmF0dHIoJ2hyZWYnKS5tYXRjaCgvIy4qLyk7XG5cdFx0XHRcdGlmKCFtYXRjaGVzKSByZXR1cm47XG5cdFx0XHRcdCQodGhpcykuYXR0cignaHJlZicsIGRvY3VtZW50LmxvY2F0aW9uLmhyZWYucmVwbGFjZSgvIy4qLywgJycpICsgbWF0Y2hlc1swXSk7XG5cdFx0XHR9KTtcblx0XHR9XG5cdH0pO1xuXG5cdC8qKlxuXHQgKiBDTVMgY29udGVudCBmaWx0ZXJzXG5cdCAqL1xuXHQkKCcjZmlsdGVycy1idXR0b24nKS5lbnR3aW5lKHtcblx0XHRvbm1hdGNoOiBmdW5jdGlvbiAoKSB7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXG5cdFx0XHR0aGlzLmRhdGEoJ2NvbGxhcHNlZCcsIHRydWUpOyAvLyBUaGUgY3VycmVudCBjb2xsYXBzZWQgc3RhdGUgb2YgdGhlIGVsZW1lbnQuXG5cdFx0XHR0aGlzLmRhdGEoJ2FuaW1hdGluZycsIGZhbHNlKTsgLy8gVHJ1ZSBpZiB0aGUgZWxlbWVudCBpcyBjdXJyZW50bHkgYW5pbWF0aW5nLlxuXHRcdH0sXG5cdFx0b251bm1hdGNoOiBmdW5jdGlvbiAoKSB7XG5cdFx0XHR0aGlzLl9zdXBlcigpO1xuXHRcdH0sXG5cdFx0c2hvd0hpZGU6IGZ1bmN0aW9uICgpIHtcblx0XHRcdHZhciBzZWxmID0gdGhpcyxcblx0XHRcdFx0JGZpbHRlcnMgPSAkKCcuY21zLWNvbnRlbnQtZmlsdGVycycpLmZpcnN0KCksXG5cdFx0XHRcdGNvbGxhcHNlZCA9IHRoaXMuZGF0YSgnY29sbGFwc2VkJyk7XG5cblx0XHRcdC8vIFByZXZlbnQgdGhlIHVzZXIgZnJvbSBzcGFtbWluZyB0aGUgVUkgd2l0aCBhbmltYXRpb24gcmVxdWVzdHMuXG5cdFx0XHRpZiAodGhpcy5kYXRhKCdhbmltYXRpbmcnKSkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdHRoaXMudG9nZ2xlQ2xhc3MoJ2FjdGl2ZScpO1xuXHRcdFx0dGhpcy5kYXRhKCdhbmltYXRpbmcnLCB0cnVlKTtcblxuXHRcdFx0Ly8gU2xpZGUgdGhlIGVsZW1lbnQgZG93biAvIHVwIGJhc2VkIG9uIGl0J3MgY3VycmVudCBjb2xsYXBzZWQgc3RhdGUuXG5cdFx0XHQkZmlsdGVyc1tjb2xsYXBzZWQgPyAnc2xpZGVEb3duJyA6ICdzbGlkZVVwJ10oe1xuXHRcdFx0XHRjb21wbGV0ZTogZnVuY3Rpb24gKCkge1xuXHRcdFx0XHRcdC8vIFVwZGF0ZSB0aGUgZWxlbWVudCdzIHN0YXRlLlxuXHRcdFx0XHRcdHNlbGYuZGF0YSgnY29sbGFwc2VkJywgIWNvbGxhcHNlZCk7XG5cdFx0XHRcdFx0c2VsZi5kYXRhKCdhbmltYXRpbmcnLCBmYWxzZSk7XG5cdFx0XHRcdH1cblx0XHRcdH0pO1xuXHRcdH0sXG5cdFx0b25jbGljazogZnVuY3Rpb24gKCkge1xuXHRcdFx0dGhpcy5zaG93SGlkZSgpO1xuXHRcdH1cblx0fSk7XG59KTtcblxudmFyIHN0YXR1c01lc3NhZ2UgPSBmdW5jdGlvbih0ZXh0LCB0eXBlKSB7XG5cdHRleHQgPSBqUXVlcnkoJzxkaXYvPicpLnRleHQodGV4dCkuaHRtbCgpOyAvLyBFc2NhcGUgSFRNTCBlbnRpdGllcyBpbiB0ZXh0XG5cdGpRdWVyeS5ub3RpY2VBZGQoe3RleHQ6IHRleHQsIHR5cGU6IHR5cGUsIHN0YXlUaW1lOiA1MDAwLCBpbkVmZmVjdDoge2xlZnQ6ICcwJywgb3BhY2l0eTogJ3Nob3cnfX0pO1xufTtcblxudmFyIGVycm9yTWVzc2FnZSA9IGZ1bmN0aW9uKHRleHQpIHtcblx0alF1ZXJ5Lm5vdGljZUFkZCh7dGV4dDogdGV4dCwgdHlwZTogJ2Vycm9yJywgc3RheVRpbWU6IDUwMDAsIGluRWZmZWN0OiB7bGVmdDogJzAnLCBvcGFjaXR5OiAnc2hvdyd9fSk7XG59O1xuIiwicmVxdWlyZSgnLi4vLi4vc3JjL0xlZnRBbmRNYWluLkxheW91dC5qcycpO1xucmVxdWlyZSgnLi4vLi4vc3JjL0xlZnRBbmRNYWluLmpzJyk7XG5yZXF1aXJlKCcuLi8uLi9zcmMvTGVmdEFuZE1haW4uQWN0aW9uVGFiU2V0LmpzJyk7XG5yZXF1aXJlKCcuLi8uLi9zcmMvTGVmdEFuZE1haW4uUGFuZWwuanMnKTtcbnJlcXVpcmUoJy4uLy4uL3NyYy9MZWZ0QW5kTWFpbi5UcmVlLmpzJyk7XG5yZXF1aXJlKCcuLi8uLi9zcmMvTGVmdEFuZE1haW4uQ29udGVudC5qcycpO1xucmVxdWlyZSgnLi4vLi4vc3JjL0xlZnRBbmRNYWluLkVkaXRGb3JtLmpzJyk7XG5yZXF1aXJlKCcuLi8uLi9zcmMvTGVmdEFuZE1haW4uTWVudS5qcycpO1xucmVxdWlyZSgnLi4vLi4vc3JjL0xlZnRBbmRNYWluLlByZXZpZXcuanMnKTtcbnJlcXVpcmUoJy4uLy4uL3NyYy9MZWZ0QW5kTWFpbi5CYXRjaEFjdGlvbnMuanMnKTtcbnJlcXVpcmUoJy4uLy4uL3NyYy9MZWZ0QW5kTWFpbi5GaWVsZEhlbHAuanMnKTtcbnJlcXVpcmUoJy4uLy4uL3NyYy9MZWZ0QW5kTWFpbi5GaWVsZERlc2NyaXB0aW9uVG9nZ2xlLmpzJyk7XG5yZXF1aXJlKCcuLi8uLi9zcmMvTGVmdEFuZE1haW4uVHJlZURyb3Bkb3duRmllbGQuanMnKTtcbiJdfQ==
