(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.PermissionCheckboxSetField', ['./jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('./jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssPermissionCheckboxSetField = mod.exports;
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
		$('.permissioncheckboxset .valADMIN input').entwine({
			onmatch: function onmatch() {
				this._super();
			},
			onunmatch: function onunmatch() {
				this._super();
			},
			onclick: function onclick(e) {
				this.toggleCheckboxes();
			},
			toggleCheckboxes: function toggleCheckboxes() {
				var checkboxes = $(this).parents('.field:eq(0)').find('.checkbox').not(this);

				if ($(this).is(':checked')) {
					checkboxes.each(function () {
						$(this).data('SecurityAdmin.oldChecked', $(this).attr('checked'));
						$(this).data('SecurityAdmin.oldDisabled', $(this).attr('disabled'));
						$(this).attr('disabled', 'disabled');
						$(this).attr('checked', 'checked');
					});
				} else {
					checkboxes.each(function () {
						var oldChecked = $(this).data('SecurityAdmin.oldChecked');
						var oldDisabled = $(this).data('SecurityAdmin.oldDisabled');
						if (oldChecked !== null) $(this).attr('checked', oldChecked);
						if (oldDisabled !== null) $(this).attr('disabled', oldDisabled);
					});
				}
			}
		});

		$('.permissioncheckboxset .valCMS_ACCESS_LeftAndMain input').entwine({
			getCheckboxesExceptThisOne: function getCheckboxesExceptThisOne() {
				return $(this).parents('.field:eq(0)').find('li').filter(function (i) {
					var klass = $(this).attr('class');
					return klass ? klass.match(/CMS_ACCESS_/) : false;
				}).find('.checkbox').not(this);
			},
			onmatch: function onmatch() {
				this.toggleCheckboxes();

				this._super();
			},
			onunmatch: function onunmatch() {
				this._super();
			},
			onclick: function onclick(e) {
				this.toggleCheckboxes();
			},
			toggleCheckboxes: function toggleCheckboxes() {
				var checkboxes = this.getCheckboxesExceptThisOne();
				if ($(this).is(':checked')) {
					checkboxes.each(function () {
						$(this).data('PermissionCheckboxSetField.oldChecked', $(this).is(':checked'));
						$(this).data('PermissionCheckboxSetField.oldDisabled', $(this).is(':disabled'));
						$(this).prop('disabled', 'disabled');
						$(this).prop('checked', 'checked');
					});
				} else {
					checkboxes.each(function () {
						$(this).prop('checked', $(this).data('PermissionCheckboxSetField.oldChecked'));
						$(this).prop('disabled', $(this).data('PermissionCheckboxSetField.oldDisabled'));
					});
				}
			}
		});
	});
});