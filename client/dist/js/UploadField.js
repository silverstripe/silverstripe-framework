(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.UploadField', ['./jQuery', './i18n'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('./jQuery'), require('./i18n'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery, global.i18n);
		global.ssUploadField = mod.exports;
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

	_jQuery2.default.widget('blueimpUIX.fileupload', _jQuery2.default.blueimpUI.fileupload, {
		_initTemplates: function _initTemplates() {
			this.options.templateContainer = document.createElement(this._files.prop('nodeName'));
			this.options.uploadTemplate = window.tmpl(this.options.uploadTemplateName);
			this.options.downloadTemplate = window.tmpl(this.options.downloadTemplateName);
		},
		_enableFileInputButton: function _enableFileInputButton() {
			_jQuery2.default.blueimpUI.fileupload.prototype._enableFileInputButton.call(this);
			this.element.find('.ss-uploadfield-addfile').show();
		},
		_disableFileInputButton: function _disableFileInputButton() {
			_jQuery2.default.blueimpUI.fileupload.prototype._disableFileInputButton.call(this);
			this.element.find('.ss-uploadfield-addfile').hide();
		},
		_onAdd: function _onAdd(e, data) {
			var result = _jQuery2.default.blueimpUI.fileupload.prototype._onAdd.call(this, e, data);
			var firstNewFile = this._files.find('.ss-uploadfield-item').slice(data.files.length * -1).first();
			var top = '+=' + (firstNewFile.position().top - parseInt(firstNewFile.css('marginTop'), 10) || 0 - parseInt(firstNewFile.css('borderTopWidth'), 10) || 0);
			firstNewFile.offsetParent().animate({ scrollTop: top }, 1000);

			var fSize = 0;
			for (var i = 0; i < data.files.length; i++) {
				if (typeof data.files[i].size === 'number') {
					fSize = fSize + data.files[i].size;
				}
			}

			(0, _jQuery2.default)('.fileOverview .uploadStatus .details .total').text(data.files.length);
			if (typeof fSize === 'number' && fSize > 0) {
				fSize = this._formatFileSize(fSize);
				(0, _jQuery2.default)('.fileOverview .uploadStatus .details .fileSize').text(fSize);
			}

			if (data.files.length == 1 && data.files[0].error !== null) {
				(0, _jQuery2.default)('.fileOverview .uploadStatus .state').text(_i18n2.default._t('AssetUploadField.UploadField.UPLOADFAIL', 'Sorry your upload failed'));
				(0, _jQuery2.default)('.fileOverview .uploadStatus').addClass("bad").removeClass("good").removeClass("notice");
			} else {
				(0, _jQuery2.default)('.fileOverview .uploadStatus .state').text(_i18n2.default._t('AssetUploadField.UPLOADINPROGRESS', 'Please wait… upload in progress'));
				(0, _jQuery2.default)('.ss-uploadfield-item-edit-all').hide();
				(0, _jQuery2.default)('.fileOverview .uploadStatus').addClass("notice").removeClass("good").removeClass("bad");
			}

			return result;
		},
		_onDone: function _onDone(result, textStatus, jqXHR, options) {
			if (this.options.changeDetection) {
				this.element.closest('form').trigger('dirty');
			}

			_jQuery2.default.blueimpUI.fileupload.prototype._onDone.call(this, result, textStatus, jqXHR, options);
		},
		_onSend: function _onSend(e, data) {
			var that = this;
			var config = this.options;
			if (config.overwriteWarning && config.replaceFile) {
				_jQuery2.default.get(config['urlFileExists'], { 'filename': data.files[0].name }, function (response, status, xhr) {
					if (response.exists) {
						data.context.find('.ss-uploadfield-item-status').text(config.errorMessages.overwriteWarning).addClass('ui-state-warning-text');
						data.context.find('.ss-uploadfield-item-progress').hide();
						data.context.find('.ss-uploadfield-item-overwrite').show();
						data.context.find('.ss-uploadfield-item-overwrite-warning').on('click', function (e) {
							data.context.find('.ss-uploadfield-item-progress').show();
							data.context.find('.ss-uploadfield-item-overwrite').hide();
							data.context.find('.ss-uploadfield-item-status').removeClass('ui-state-warning-text');

							_jQuery2.default.blueimpUI.fileupload.prototype._onSend.call(that, e, data);

							e.preventDefault();
							return false;
						});
					} else {
						return _jQuery2.default.blueimpUI.fileupload.prototype._onSend.call(that, e, data);
					}
				});
			} else {
				return _jQuery2.default.blueimpUI.fileupload.prototype._onSend.call(that, e, data);
			}
		},
		_onAlways: function _onAlways(jqXHRorResult, textStatus, jqXHRorError, options) {
			_jQuery2.default.blueimpUI.fileupload.prototype._onAlways.call(this, jqXHRorResult, textStatus, jqXHRorError, options);

			if (typeof jqXHRorError === 'string') {
				(0, _jQuery2.default)('.fileOverview .uploadStatus .state').text(_i18n2.default._t('AssetUploadField.UploadField.UPLOADFAIL', 'Sorry your upload failed'));
				(0, _jQuery2.default)('.fileOverview .uploadStatus').addClass("bad").removeClass("good").removeClass("notice");
			} else if (jqXHRorError.status === 200) {
				(0, _jQuery2.default)('.fileOverview .uploadStatus .state').text(_i18n2.default._t('AssetUploadField.FILEUPLOADCOMPLETED', 'File upload completed!'));
				(0, _jQuery2.default)('.ss-uploadfield-item-edit-all').show();
				(0, _jQuery2.default)('.fileOverview .uploadStatus').addClass("good").removeClass("notice").removeClass("bad");
			}
		},
		_create: function _create() {
			_jQuery2.default.blueimpUI.fileupload.prototype._create.call(this);

			this._adjustMaxNumberOfFiles(0);
		},
		attach: function attach(data) {
			if (this.options.changeDetection) {
				this.element.closest('form').trigger('dirty');
			}

			var self = this,
			    files = data.files,
			    replaceFileID = data.replaceFileID,
			    valid = true;

			var replacedElement = null;
			if (replaceFileID) {
				replacedElement = (0, _jQuery2.default)(".ss-uploadfield-item[data-fileid='" + replaceFileID + "']");
				if (replacedElement.length === 0) {
					replacedElement = null;
				} else {
					self._adjustMaxNumberOfFiles(1);
				}
			}

			_jQuery2.default.each(files, function (index, file) {
				self._adjustMaxNumberOfFiles(-1);
				valid = self._validate([file]) && valid;
			});
			data.isAdjusted = true;
			data.files.valid = data.isValidated = valid;

			data.context = this._renderDownload(files);
			if (replacedElement) {
				replacedElement.replaceWith(data.context);
			} else {
				data.context.appendTo(this._files);
			}
			data.context.data('data', data);

			this._reflow = this._transition && data.context[0].offsetWidth;
			data.context.addClass('in');
		}
	});

	_jQuery2.default.entwine('ss', function ($) {

		$('div.ss-upload').entwine({

			Config: null,

			onmatch: function onmatch() {

				if (this.is('.readonly,.disabled')) {
					return;
				}

				var $fileInput = this.find('.ss-uploadfield-fromcomputer-fileinput'),
				    $dropZone = $('.ss-uploadfield-dropzone'),
				    config = $fileInput.data('config');

				$dropZone.on('dragover', function (e) {
					e.preventDefault();
				});

				$dropZone.on('dragenter', function (e) {
					$dropZone.addClass('hover active');
				});

				$dropZone.on('dragleave', function (e) {
					if (e.target === $dropZone[0]) {
						$dropZone.removeClass('hover active');
					}
				});

				$dropZone.on('drop', function (e) {
					$dropZone.removeClass('hover active');

					if (e.target !== $dropZone[0]) {
						return false;
					}
				});

				this.setConfig(config);
				this.fileupload($.extend(true, {
					formData: function formData(form) {
						var idVal = $(form).find(':input[name=ID]').val();
						var data = [{ name: 'SecurityID', value: $(form).find(':input[name=SecurityID]').val() }];
						if (idVal) data.push({ name: 'ID', value: idVal });

						return data;
					},
					errorMessages: {
						1: _i18n2.default._t('UploadField.PHP_MAXFILESIZE'),
						2: _i18n2.default._t('UploadField.HTML_MAXFILESIZE'),
						3: _i18n2.default._t('UploadField.ONLYPARTIALUPLOADED'),
						4: _i18n2.default._t('UploadField.NOFILEUPLOADED'),
						5: _i18n2.default._t('UploadField.NOTMPFOLDER'),
						6: _i18n2.default._t('UploadField.WRITEFAILED'),
						7: _i18n2.default._t('UploadField.STOPEDBYEXTENSION'),
						maxFileSize: _i18n2.default._t('UploadField.TOOLARGESHORT'),
						minFileSize: _i18n2.default._t('UploadField.TOOSMALL'),
						acceptFileTypes: _i18n2.default._t('UploadField.INVALIDEXTENSIONSHORT'),
						maxNumberOfFiles: _i18n2.default._t('UploadField.MAXNUMBEROFFILESSHORT'),
						uploadedBytes: _i18n2.default._t('UploadField.UPLOADEDBYTES'),
						emptyResult: _i18n2.default._t('UploadField.EMPTYRESULT')
					},
					send: function send(e, data) {
						if (data.context && data.dataType && data.dataType.substr(0, 6) === 'iframe') {
							data.total = 1;
							data.loaded = 1;
							$(this).data('fileupload').options.progress(e, data);
						}
					},
					progress: function progress(e, data) {
						if (data.context) {
							var value = parseInt(data.loaded / data.total * 100, 10) + '%';
							data.context.find('.ss-uploadfield-item-status').html(data.total == 1 ? _i18n2.default._t('UploadField.LOADING') : value);
							data.context.find('.ss-uploadfield-item-progressbarvalue').css('width', value);
						}
					}
				}, config, {
					fileInput: $fileInput,
					dropZone: $dropZone,
					form: $fileInput.closest('form'),
					previewAsCanvas: false,
					acceptFileTypes: new RegExp(config.acceptFileTypes, 'i')
				}));

				if (this.data('fileupload')._isXHRUpload({ multipart: true })) {
					$('.ss-uploadfield-item-uploador').hide().show();
				}

				this._super();
			},
			onunmatch: function onunmatch() {
				$('.ss-uploadfield-dropzone').off('dragover dragenter dragleave drop');
				this._super();
			},
			openSelectDialog: function openSelectDialog(uploadedFile) {
				var self = this,
				    config = this.getConfig(),
				    dialogId = 'ss-uploadfield-dialog-' + this.attr('id'),
				    dialog = jQuery('#' + dialogId);
				if (!dialog.length) dialog = jQuery('<div class="ss-uploadfield-dialog" id="' + dialogId + '" />');

				var iframeUrl = config['urlSelectDialog'];
				var uploadedFileId = null;
				if (uploadedFile && uploadedFile.attr('data-fileid') > 0) {
					uploadedFileId = uploadedFile.attr('data-fileid');
				}

				dialog.ssdialog({ iframeUrl: iframeUrl, height: 550 });

				dialog.find('iframe').bind('load', function (e) {
					var contents = $(this).contents(),
					    gridField = contents.find('.ss-gridfield');

					contents.find('table.ss-gridfield').css('margin-top', 0);

					contents.find('input[name=action_doAttach]').unbind('click.openSelectDialog').bind('click.openSelectDialog', function () {
						var ids = $.map(gridField.find('.ss-gridfield-item.ui-selected'), function (el) {
							return $(el).data('id');
						});
						if (ids && ids.length) self.attachFiles(ids, uploadedFileId);

						dialog.ssdialog('close');
						return false;
					});
				});
				dialog.ssdialog('open');
			},
			attachFiles: function attachFiles(ids, uploadedFileId) {
				var self = this,
				    config = this.getConfig(),
				    indicator = $('<div class="loader" />'),
				    target = uploadedFileId ? this.find(".ss-uploadfield-item[data-fileid='" + uploadedFileId + "']") : this.find('.ss-uploadfield-addfile');

				target.children().hide();
				target.append(indicator);

				$.ajax({
					type: "POST",
					url: config['urlAttach'],
					data: { 'ids': ids },
					complete: function complete(xhr, status) {
						target.children().show();
						indicator.remove();
					},
					success: function success(data, status, xhr) {
						if (!data || $.isEmptyObject(data)) return;

						self.fileupload('attach', {
							files: data,
							options: self.fileupload('option'),
							replaceFileID: uploadedFileId
						});
					}
				});
			}
		});
		$('div.ss-upload *').entwine({
			getUploadField: function getUploadField() {

				return this.parents('div.ss-upload:first');
			}
		});
		$('div.ss-upload .ss-uploadfield-files .ss-uploadfield-item').entwine({
			onadd: function onadd() {
				this._super();
				this.closest('.ss-upload').find('.ss-uploadfield-addfile').addClass('borderTop');
			},
			onremove: function onremove() {
				$('.ss-uploadfield-files:not(:has(.ss-uploadfield-item))').closest('.ss-upload').find('.ss-uploadfield-addfile').removeClass('borderTop');
				this._super();
			}
		});
		$('div.ss-upload .ss-uploadfield-startall').entwine({
			onclick: function onclick(e) {
				this.closest('.ss-upload').find('.ss-uploadfield-item-start button').click();
				e.preventDefault();
				return false;
			}
		});
		$('div.ss-upload .ss-uploadfield-item-cancelfailed').entwine({
			onclick: function onclick(e) {
				this.closest('.ss-uploadfield-item').remove();
				e.preventDefault();
				return false;
			}
		});

		$('div.ss-upload .ss-uploadfield-item-remove:not(.ui-state-disabled), .ss-uploadfield-item-delete:not(.ui-state-disabled)').entwine({
			onclick: function onclick(e) {
				var field = this.closest('div.ss-upload'),
				    config = field.getConfig('changeDetection'),
				    fileupload = field.data('fileupload'),
				    item = this.closest('.ss-uploadfield-item'),
				    msg = '';

				if (this.is('.ss-uploadfield-item-delete')) {
					if (confirm(_i18n2.default._t('UploadField.ConfirmDelete'))) {
						if (config.changeDetection) {
							this.closest('form').trigger('dirty');
						}

						if (fileupload) {
							fileupload._trigger('destroy', e, {
								context: item,
								url: this.data('href'),
								type: 'get',
								dataType: fileupload.options.dataType
							});
						}
					}
				} else {
					if (config.changeDetection) {
						this.closest('form').trigger('dirty');
					}

					if (fileupload) {
						fileupload._trigger('destroy', e, { context: item });
					}
				}

				e.preventDefault();
				return false;
			}
		});

		$('div.ss-upload .ss-uploadfield-item-edit-all').entwine({
			onclick: function onclick(e) {

				if ($(this).hasClass('opened')) {
					$('.ss-uploadfield-item .ss-uploadfield-item-edit .toggle-details-icon.opened').each(function (i) {
						$(this).closest('.ss-uploadfield-item-edit').click();
					});
					$(this).removeClass('opened').find('.toggle-details-icon').removeClass('opened');
				} else {
					$('.ss-uploadfield-item .ss-uploadfield-item-edit .toggle-details-icon').each(function (i) {
						if (!$(this).hasClass('opened')) {
							$(this).closest('.ss-uploadfield-item-edit').click();
						}
					});
					$(this).addClass('opened').find('.toggle-details-icon').addClass('opened');
				}

				e.preventDefault();
				return false;
			}
		});
		$('div.ss-upload:not(.disabled):not(.readonly) .ss-uploadfield-item-edit').entwine({
			onclick: function onclick(e) {
				var self = this,
				    editform = self.closest('.ss-uploadfield-item').find('.ss-uploadfield-item-editform'),
				    itemInfo = editform.prev('.ss-uploadfield-item-info'),
				    iframe = editform.find('iframe');

				if (iframe.parent().hasClass('loading')) {
					e.preventDefault();
					return false;
				}

				if (iframe.attr('src') == 'about:blank') {
					var disabled = this.siblings();

					iframe.attr('src', iframe.data('src'));

					iframe.parent().addClass('loading');
					disabled.addClass('ui-state-disabled');
					disabled.attr('disabled', 'disabled');

					iframe.on('load', function () {
						iframe.parent().removeClass('loading');

						if (iframe.data('src')) {
							self._prepareIframe(iframe, editform, itemInfo);
							iframe.data('src', '');
						}
					});
				} else {
					self._prepareIframe(iframe, editform, itemInfo);
				}

				e.preventDefault();
				return false;
			},
			_prepareIframe: function _prepareIframe(iframe, editform, itemInfo) {
				var disabled;

				iframe.contents().ready(function () {
					var iframe_jQuery = iframe.get(0).contentWindow.jQuery;
					iframe_jQuery(iframe_jQuery.find(':input')).bind('change', function (e) {
						editform.removeClass('edited');
						editform.addClass('edited');
					});
				});

				if (editform.hasClass('loading')) {} else {
						if (this.hasClass('ss-uploadfield-item-edit')) {
							disabled = this.siblings();
						} else {
							disabled = this.find('ss-uploadfield-item-edit').siblings();
						}
						editform.parent('.ss-uploadfield-item').removeClass('ui-state-warning');
						editform.toggleEditForm();

						if (itemInfo.find('.toggle-details-icon').hasClass('opened')) {
							disabled.addClass('ui-state-disabled');
							disabled.attr('disabled', 'disabled');
						} else {
							disabled.removeClass('ui-state-disabled');
							disabled.removeAttr('disabled');
						}
					}
			}
		});

		$('div.ss-upload .ss-uploadfield-item-editform').entwine({
			fitHeight: function fitHeight() {
				var iframe = this.find('iframe'),
				    contents = iframe.contents().find('body'),
				    bodyH = contents.find('form').outerHeight(true),
				    iframeH = bodyH + (iframe.outerHeight(true) - iframe.height()),
				    containerH = iframeH + (this.outerHeight(true) - this.height());
				if (!$.browser.msie && $.browser.version.slice(0, 3) != "8.0") {
					contents.find('body').css({ 'height': bodyH });
				}

				iframe.height(iframeH);
				this.animate({ height: containerH }, 500);
			},
			toggleEditForm: function toggleEditForm() {
				var itemInfo = this.prev('.ss-uploadfield-item-info'),
				    status = itemInfo.find('.ss-uploadfield-item-status');

				var iframe = this.find('iframe').contents(),
				    saved = iframe.find('#Form_EditForm_error');

				var text = "";

				if (this.height() === 0) {
					text = _i18n2.default._t('UploadField.Editing', "Editing ...");
					this.fitHeight();
					this.addClass('opened');
					itemInfo.find('.toggle-details-icon').addClass('opened');
					status.removeClass('ui-state-success-text').removeClass('ui-state-warning-text');
					iframe.find('#Form_EditForm_action_doEdit').click(function () {
						itemInfo.find('label .name').text(iframe.find('#Name input').val());
					});
					if ($('div.ss-upload  .ss-uploadfield-files .ss-uploadfield-item-actions .toggle-details-icon:not(.opened)').index() < 0) {
						$('div.ss-upload .ss-uploadfield-item-edit-all').addClass('opened').find('.toggle-details-icon').addClass('opened');
					}
				} else {
					this.animate({ height: 0 }, 500);
					this.removeClass('opened');
					itemInfo.find('.toggle-details-icon').removeClass('opened');
					$('div.ss-upload .ss-uploadfield-item-edit-all').removeClass('opened').find('.toggle-details-icon').removeClass('opened');
					if (!this.hasClass('edited')) {
						text = _i18n2.default._t('UploadField.NOCHANGES', 'No Changes');
						status.addClass('ui-state-success-text');
					} else {
						if (saved.hasClass('good')) {
							text = _i18n2.default._t('UploadField.CHANGESSAVED', 'Changes Saved');
							this.removeClass('edited').parent('.ss-uploadfield-item').removeClass('ui-state-warning');
							status.addClass('ui-state-success-text');
						} else {
							text = _i18n2.default._t('UploadField.UNSAVEDCHANGES', 'Unsaved Changes');
							this.parent('.ss-uploadfield-item').addClass('ui-state-warning');
							status.addClass('ui-state-warning-text');
						}
					}
					saved.removeClass('good').hide();
				}
				status.attr('title', text).text(text);
			}
		});
		$('div.ss-upload .ss-uploadfield-fromfiles').entwine({
			onclick: function onclick(e) {
				this.getUploadField().openSelectDialog(this.closest('.ss-uploadfield-item'));
				e.preventDefault();
				return false;
			}
		});
	});
});