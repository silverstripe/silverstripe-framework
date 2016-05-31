(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.LeftAndMain.ActionTabSet', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssLeftAndMainActionTabSet = mod.exports;
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
						containerSouth = activePanel.parents('.toolbar--south');
						if (containerSouth) {
							padding = activeTab.offset().top - containerSouth.offset().top;
							topPosition = topPosition - padding;
						}
						$(activePanel).css('top', topPosition + "px");
					}
				} else {
					this.removeClass('rise-up');
					if (activeTab.position() !== null) {
						$(activePanel).css('bottom', '100%');
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
});