(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.LeftAndMain.EditForm', ['jQuery', 'i18n'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'), require('i18n'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery, global.i18n);
		global.ssLeftAndMainEditForm = mod.exports;
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

			ValidationErrorShown: false,

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

				this.setValidationErrorShown(false);

				this._super();
			},
			'from .cms-tabset': {
				onafterredrawtabs: function onafterredrawtabs() {
					if (this.hasClass('validationerror')) {
						var tabError = this.find('.message.validation, .message.required').first().closest('.tab');
						$('.cms-container').clearCurrentTabState();
						var $tabSet = tabError.closest('.ss-tabset');

						if (!$tabSet.length) {
							$tabSet = tabError.closest('.cms-tabset');
						}

						if ($tabSet.length) {
							$tabSet.tabs('option', 'active', tabError.index('.tab'));
						} else if (!this.getValidationErrorShown()) {
							this.setValidationErrorShown(true);
							errorMessage(ss.i18n._t('ModelAdmin.VALIDATIONERROR', 'Validation Error'));
						}
					}
				}
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

			'from .cms-edit-form .dropdown .chosen-container a': {
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
});