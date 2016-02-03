(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.GridField', ['./jQuery', './i18n'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('./jQuery'), require('./i18n'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery, global.i18n);
		global.ssGridField = mod.exports;
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
		$('.ss-gridfield').entwine({

			reload: function reload(ajaxOpts, successCallback) {
				var self = this,
				    form = this.closest('form'),
				    focusedElName = this.find(':input:focus').attr('name'),
				    data = form.find(':input').serializeArray();

				if (!ajaxOpts) ajaxOpts = {};
				if (!ajaxOpts.data) ajaxOpts.data = [];
				ajaxOpts.data = ajaxOpts.data.concat(data);

				if (window.location.search) {
					ajaxOpts.data = window.location.search.replace(/^\?/, '') + '&' + $.param(ajaxOpts.data);
				}

				if (!window.history || !window.history.pushState) {
					if (window.location.hash && window.location.hash.indexOf('?') != -1) {
						ajaxOpts.data = window.location.hash.substring(window.location.hash.indexOf('?') + 1) + '&' + $.param(ajaxOpts.data);
					}
				}

				form.addClass('loading');

				$.ajax($.extend({}, {
					headers: { "X-Pjax": 'CurrentField' },
					type: "POST",
					url: this.data('url'),
					dataType: 'html',
					success: function success(data) {
						self.empty().append($(data).children());

						if (focusedElName) self.find(':input[name="' + focusedElName + '"]').focus();

						if (self.find('.filter-header').length) {
							var content;
							if (ajaxOpts.data[0].filter == "show") {
								content = '<span class="non-sortable"></span>';
								self.addClass('show-filter').find('.filter-header').show();
							} else {
								content = '<button type="button" name="showFilter" class="ss-gridfield-button-filter trigger"></button>';
								self.removeClass('show-filter').find('.filter-header').hide();
							}

							self.find('.sortable-header th:last').html(content);
						}

						form.removeClass('loading');
						if (successCallback) successCallback.apply(this, arguments);
						self.trigger('reload', self);
					},
					error: function error(e) {
						alert(_i18n2.default._t('GRIDFIELD.ERRORINTRANSACTION'));
						form.removeClass('loading');
					}
				}, ajaxOpts));
			},
			showDetailView: function showDetailView(url) {
				window.location.href = url;
			},
			getItems: function getItems() {
				return this.find('.ss-gridfield-item');
			},

			setState: function setState(k, v) {
				var state = this.getState();
				state[k] = v;
				this.find(':input[name="' + this.data('name') + '[GridState]"]').val(JSON.stringify(state));
			},

			getState: function getState() {
				return JSON.parse(this.find(':input[name="' + this.data('name') + '[GridState]"]').val());
			}
		});

		$('.ss-gridfield *').entwine({
			getGridField: function getGridField() {
				return this.closest('.ss-gridfield');
			}
		});

		$('.ss-gridfield :button[name=showFilter]').entwine({
			onclick: function onclick(e) {
				$('.filter-header').show('slow').find(':input:first').focus();
				this.closest('.ss-gridfield').addClass('show-filter');
				this.parent().html('<span class="non-sortable"></span>');
				e.preventDefault();
			}
		});

		$('.ss-gridfield .ss-gridfield-item').entwine({
			onclick: function onclick(e) {
				if ($(e.target).closest('.action').length) {
					this._super(e);
					return false;
				}

				var editLink = this.find('.edit-link');
				if (editLink.length) this.getGridField().showDetailView(editLink.prop('href'));
			},
			onmouseover: function onmouseover() {
				if (this.find('.edit-link').length) this.css('cursor', 'pointer');
			},
			onmouseout: function onmouseout() {
				this.css('cursor', 'default');
			}
		});

		$('.ss-gridfield .action').entwine({
			onclick: function onclick(e) {
				var filterState = 'show';
				if (this.button('option', 'disabled')) {
					e.preventDefault();
					return;
				}

				if (this.hasClass('ss-gridfield-button-close') || !this.closest('.ss-gridfield').hasClass('show-filter')) {
					filterState = 'hidden';
				}

				this.getGridField().reload({ data: [{ name: this.attr('name'), value: this.val(), filter: filterState }] });
				e.preventDefault();
			}
		});

		$('.ss-gridfield .add-existing-autocompleter').entwine({
			onbuttoncreate: function onbuttoncreate() {
				var self = this;

				this.toggleDisabled();

				this.find('input[type="text"]').on('keyup', function () {
					self.toggleDisabled();
				});
			},
			onunmatch: function onunmatch() {
				this.find('input[type="text"]').off('keyup');
			},
			toggleDisabled: function toggleDisabled() {
				var $button = this.find('.ss-ui-button'),
				    $input = this.find('input[type="text"]'),
				    inputHasValue = $input.val() !== '',
				    buttonDisabled = $button.is(':disabled');

				if (inputHasValue && buttonDisabled || !inputHasValue && !buttonDisabled) {
					$button.button("option", "disabled", !buttonDisabled);
				}
			}
		});

		$('.ss-gridfield .col-buttons .action.gridfield-button-delete, .cms-edit-form .Actions button.action.action-delete').entwine({
			onclick: function onclick(e) {
				if (!confirm(_i18n2.default._t('TABLEFIELD.DELETECONFIRMMESSAGE'))) {
					e.preventDefault();
					return false;
				} else {
					this._super(e);
				}
			}
		});

		$('.ss-gridfield .action.gridfield-button-print').entwine({
			UUID: null,
			onmatch: function onmatch() {
				this._super();
				this.setUUID(new Date().getTime());
			},
			onunmatch: function onunmatch() {
				this._super();
			},
			onclick: function onclick(e) {
				var btn = this.closest(':button'),
				    grid = this.getGridField(),
				    form = this.closest('form'),
				    data = form.find(':input.gridstate').serialize();;

				data += "&" + encodeURIComponent(btn.attr('name')) + '=' + encodeURIComponent(btn.val());

				if (window.location.search) {
					data = window.location.search.replace(/^\?/, '') + '&' + data;
				}

				var connector = grid.data('url').indexOf('?') == -1 ? '?' : '&';

				var url = $.path.makeUrlAbsolute(grid.data('url') + connector + data, $('base').attr('href'));

				var newWindow = window.open(url);

				return false;
			}
		});

		$('.ss-gridfield-print-iframe').entwine({
			onmatch: function onmatch() {
				this._super();

				this.hide().bind('load', function () {
					this.focus();
					var ifWin = this.contentWindow || this;
					ifWin.print();
				});
			},
			onunmatch: function onunmatch() {
				this._super();
			}
		});

		$('.ss-gridfield .action.no-ajax').entwine({
			onclick: function onclick(e) {
				var self = this,
				    btn = this.closest(':button'),
				    grid = this.getGridField(),
				    form = this.closest('form'),
				    data = form.find(':input.gridstate').serialize();

				data += "&" + encodeURIComponent(btn.attr('name')) + '=' + encodeURIComponent(btn.val());

				if (window.location.search) {
					data = window.location.search.replace(/^\?/, '') + '&' + data;
				}

				var connector = grid.data('url').indexOf('?') == -1 ? '?' : '&';

				window.location.href = $.path.makeUrlAbsolute(grid.data('url') + connector + data, $('base').attr('href'));

				return false;
			}
		});

		$('.ss-gridfield .action-detail').entwine({
			onclick: function onclick() {
				this.getGridField().showDetailView($(this).prop('href'));
				return false;
			}
		});

		$('.ss-gridfield[data-selectable]').entwine({
			getSelectedItems: function getSelectedItems() {
				return this.find('.ss-gridfield-item.ui-selected');
			},

			getSelectedIDs: function getSelectedIDs() {
				return $.map(this.getSelectedItems(), function (el) {
					return $(el).data('id');
				});
			}
		});
		$('.ss-gridfield[data-selectable] .ss-gridfield-items').entwine({
			onadd: function onadd() {
				this._super();

				this.selectable();
			},
			onremove: function onremove() {
				this._super();
				if (this.data('selectable')) this.selectable('destroy');
			}
		});

		$('.ss-gridfield .filter-header :input').entwine({
			onmatch: function onmatch() {
				var filterbtn = this.closest('.fieldgroup').find('.ss-gridfield-button-filter'),
				    resetbtn = this.closest('.fieldgroup').find('.ss-gridfield-button-reset');

				if (this.val()) {
					filterbtn.addClass('filtered');
					resetbtn.addClass('filtered');
				}
				this._super();
			},
			onunmatch: function onunmatch() {
				this._super();
			},
			onkeydown: function onkeydown(e) {
				if (this.closest('.ss-gridfield-button-reset').length) return;

				var filterbtn = this.closest('.fieldgroup').find('.ss-gridfield-button-filter'),
				    resetbtn = this.closest('.fieldgroup').find('.ss-gridfield-button-reset');

				if (e.keyCode == '13') {
					var btns = this.closest('.filter-header').find('.ss-gridfield-button-filter');
					var filterState = 'show';
					if (this.hasClass('ss-gridfield-button-close') || !this.closest('.ss-gridfield').hasClass('show-filter')) {
						filterState = 'hidden';
					}

					this.getGridField().reload({ data: [{ name: btns.attr('name'), value: btns.val(), filter: filterState }] });
					return false;
				} else {
					filterbtn.addClass('hover-alike');
					resetbtn.addClass('hover-alike');
				}
			}
		});

		$(".ss-gridfield .relation-search").entwine({
			onfocusin: function onfocusin(event) {
				this.autocomplete({
					source: function source(request, response) {
						var searchField = $(this.element);
						var form = $(this.element).closest("form");
						$.ajax({
							headers: {
								"X-Pjax": 'Partial'
							},
							type: "GET",
							url: $(searchField).data('searchUrl'),
							data: encodeURIComponent(searchField.attr('name')) + '=' + encodeURIComponent(searchField.val()),
							success: function success(data) {
								response(JSON.parse(data));
							},
							error: function error(e) {
								alert(_i18n2.default._t('GRIDFIELD.ERRORINTRANSACTION', 'An error occured while fetching data from the server\n Please try again later.'));
							}
						});
					},
					select: function select(event, ui) {
						$(this).closest(".ss-gridfield").find("#action_gridfield_relationfind").replaceWith('<input type="hidden" name="relationID" value="' + ui.item.id + '" id="relationID"/>');
						var addbutton = $(this).closest(".ss-gridfield").find("#action_gridfield_relationadd");
						if (addbutton.data('button')) {
							addbutton.button('enable');
						} else {
							addbutton.removeAttr('disabled');
						}
					}
				});
			}
		});

		$(".ss-gridfield .pagination-page-number input").entwine({
			onkeydown: function onkeydown(event) {
				if (event.keyCode == 13) {
					var newpage = parseInt($(this).val(), 10);

					var gridfield = $(this).getGridField();
					gridfield.setState('GridFieldPaginator', { currentPage: newpage });
					gridfield.reload();

					return false;
				}
			}
		});
	});
});