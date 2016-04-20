(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.LeftAndMain.Menu', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssLeftAndMainMenu = mod.exports;
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
});