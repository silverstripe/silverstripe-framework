(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.SecurityAdmin', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssSecurityAdmin = mod.exports;
	}
})(this, function (_jQuery) {
	'use strict';

	var _jQuery2 = _interopRequireDefault(_jQuery);

	function _interopRequireDefault(obj) {
		return obj && obj.__esModule ? obj : {
			default: obj
		};
	}

	var refreshAfterImport = function refreshAfterImport(e) {
		var existingFormMessage = (0, _jQuery2.default)((0, _jQuery2.default)(this).contents()).find('.message');
		if (existingFormMessage && existingFormMessage.html()) {
			var memberTableField = (0, _jQuery2.default)(window.parent.document).find('#Form_EditForm_Members').get(0);
			if (memberTableField) memberTableField.refresh();

			var tree = (0, _jQuery2.default)(window.parent.document).find('.cms-tree').get(0);
			if (tree) tree.reload();
		}
	};

	(0, _jQuery2.default)('#MemberImportFormIframe, #GroupImportFormIframe').entwine({
		onadd: function onadd() {
			this._super();

			(0, _jQuery2.default)(this).bind('load', refreshAfterImport);
		}
	});

	_jQuery2.default.entwine('ss', function ($) {
		$('.permissioncheckboxset .checkbox[value=ADMIN]').entwine({
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
				var self = this,
				    checkboxes = this.parents('.field:eq(0)').find('.checkbox').not(this);

				if (this.is(':checked')) {
					checkboxes.each(function () {
						$(this).data('SecurityAdmin.oldChecked', $(this).is(':checked'));
						$(this).data('SecurityAdmin.oldDisabled', $(this).is(':disabled'));
						$(this).prop('disabled', true);
						$(this).prop('checked', true);
					});
				} else {
					checkboxes.each(function () {
						$(this).prop('checked', $(this).data('SecurityAdmin.oldChecked'));
						$(this).prop('disabled', $(this).data('SecurityAdmin.oldDisabled'));
					});
				}
			}
		});
	});
});