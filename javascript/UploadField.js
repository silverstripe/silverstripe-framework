(function($) {
	$.widget('blueimpUIX.fileupload', $.blueimpUI.fileupload, {
		_initTemplates: function() {
			this.options.templateContainer = document.createElement(
					this._files.prop('nodeName')
			);
			this.options.uploadTemplate = window.tmpl(this.options.uploadTemplateName);
			this.options.downloadTemplate = window.tmpl(this.options.downloadTemplateName);
		},
		_enableFileInputButton: function() {
			$.blueimpUI.fileupload.prototype._enableFileInputButton.call(this);
			this.element.find('.ss-uploadfield-addfile').show();
		},
		_disableFileInputButton: function() {
			$.blueimpUI.fileupload.prototype._disableFileInputButton.call(this);
			this.element.find('.ss-uploadfield-addfile').hide();
		},
		_onAdd: function(e, data) {
			// use _onAdd instead of add since we only want it called once for a file set, not for each file
			var result = $.blueimpUI.fileupload.prototype._onAdd.call(this, e, data);
			var firstNewFile = this._files.find('.ss-uploadfield-item').slice(data.files.length*-1).first();
			var top = '+=' + (firstNewFile.position().top - parseInt(firstNewFile.css('marginTop'), 10) || 0 - parseInt(firstNewFile.css('borderTopWidth'), 10) || 0);
			firstNewFile.offsetParent().animate({scrollTop: top}, 1000);

			/* Compute total size of files */
			var fSize = 0;
			for(var i = 0; i < data.files.length; i++){
				if(typeof data.files[i].size === 'number'){
					fSize = fSize + data.files[i].size;
				}
			}

			$('.fileOverview .uploadStatus .details .total').text(data.files.length);
			if(typeof fSize === 'number' && fSize > 0){
				fSize = this._formatFileSize(fSize);
				$('.fileOverview .uploadStatus .details .fileSize').text(fSize);
			}

			//Fixes case where someone uploads a single erroring file
			if(data.files.length == 1 && data.files[0].error !== null){
				$('.fileOverview .uploadStatus .state').text(ss.i18n._t('AssetUploadField.UploadField.UPLOADFAIL', 'Sorry your upload failed'));
				$('.fileOverview .uploadStatus').addClass("bad").removeClass("good").removeClass("notice");
			}else{
				$('.fileOverview .uploadStatus .state').text(ss.i18n._t('AssetUploadField.UPLOADINPROGRESS', 'Please wait… upload in progress'));//.show();
				$('.ss-uploadfield-item-edit-all').hide();
				$('.fileOverview .uploadStatus').addClass("notice").removeClass("good").removeClass("bad");
			}

			return result;
		},
		_onDone: function (result, textStatus, jqXHR, options) {
			// Mark form as dirty on completion of successful upload
			if(this.options.changeDetection) {
				this.element.closest('form').trigger('dirty');
			}

			$.blueimpUI.fileupload.prototype._onDone.call(this, result, textStatus, jqXHR, options);
		},
		_onSend: function (e, data) {
			//check the array of existing files to see if we are trying to upload a file that already exists
			var that = this;
			var config = this.options;
			if (config.overwriteWarning && config.replaceFile) {
				$.get(
					config['urlFileExists'],
					{'filename': data.files[0].name},
					function(response, status, xhr) {
						if(response.exists) {
							//display the dialogs with the question to overwrite or not
							data.context.find('.ss-uploadfield-item-status')
								.text(config.errorMessages.overwriteWarning)
								.addClass('ui-state-warning-text');
							data.context.find('.ss-uploadfield-item-progress').hide();
							data.context.find('.ss-uploadfield-item-overwrite').show();
							data.context.find('.ss-uploadfield-item-overwrite-warning').on('click', function(e){
								data.context.find('.ss-uploadfield-item-progress').show();
								data.context.find('.ss-uploadfield-item-overwrite').hide();
								data.context.find('.ss-uploadfield-item-status')
									.removeClass('ui-state-warning-text');
								//upload only if the "overwrite" button is clicked
								$.blueimpUI.fileupload.prototype._onSend.call(that, e, data);

								e.preventDefault(); // Avoid a form submit
								return false;
							});
						} else {    //regular file upload
							return $.blueimpUI.fileupload.prototype._onSend.call(that, e, data);
						}
					}
				);
			} else {
				return $.blueimpUI.fileupload.prototype._onSend.call(that, e, data);
			}
		},
		_onAlways: function (jqXHRorResult, textStatus, jqXHRorError, options) {
			$.blueimpUI.fileupload.prototype._onAlways.call(this, jqXHRorResult, textStatus, jqXHRorError, options);

			if(typeof(jqXHRorError) === 'string') {
				$('.fileOverview .uploadStatus .state').text(ss.i18n._t('AssetUploadField.UploadField.UPLOADFAIL', 'Sorry your upload failed'));
				$('.fileOverview .uploadStatus').addClass("bad").removeClass("good").removeClass("notice");
			} else if (jqXHRorError.status === 200) {
				$('.fileOverview .uploadStatus .state').text(ss.i18n._t('AssetUploadField.FILEUPLOADCOMPLETED', 'File upload completed!'));//.hide();
				$('.ss-uploadfield-item-edit-all').show();
				$('.fileOverview .uploadStatus').addClass("good").removeClass("notice").removeClass("bad");
			}
		},
		_create: function() {
			$.blueimpUI.fileupload.prototype._create.call(this);
			// Ensures that the visibility of the fileupload dialog is set correctly at initialisation
			this._adjustMaxNumberOfFiles(0);
		},
		attach: function(data) {
			if(this.options.changeDetection) {
				this.element.closest('form').trigger('dirty');
			}

			// Handles attachment of already uploaded files, similar to add
			var self = this,
				files = data.files,
				replaceFileID = data.replaceFileID,
				valid = true;

			// If replacing an element (and it exists), adjust max number of files at this point
			var replacedElement = null;
			if(replaceFileID) {
				replacedElement = $(".ss-uploadfield-item[data-fileid='"+replaceFileID+"']");
				if(replacedElement.length === 0) {
					replacedElement = null;
				} else {
					self._adjustMaxNumberOfFiles(1);
				}
			}

			// Validate each file
			$.each(files, function (index, file) {
				self._adjustMaxNumberOfFiles(-1);
				error = self._validate([file]);
				valid = error && valid;
			});
			data.isAdjusted = true;
			data.files.valid = data.isValidated = valid;

			// Generate new file HTMl, and either append or replace (if replacing
			// an already uploaded file).
			data.context = this._renderDownload(files);
			if(replacedElement) {
				replacedElement.replaceWith(data.context);
			} else {
				data.context.appendTo(this._files);
			}
			data.context.data('data', data);
			// Force reflow:
			this._reflow = this._transition && data.context[0].offsetWidth;
			data.context.addClass('in');
		}
	});


	$.entwine('ss', function($) {

		$('div.ss-upload').entwine({

			Config: null,

			onmatch: function() {

				if(this.is('.readonly,.disabled')) return;

				var fileInput = this.find('.ss-uploadfield-fromcomputer-fileinput');
				var dropZone = this.find('.ss-uploadfield-dropzone');
				var config = fileInput.data('config');

				/* Attach classes to dropzone when element can be dropped*/
				$(document).unbind('dragover');
				$(document).bind('dragover', function (e) {
					timeout = window.dropZoneTimeout;
					var $target = $(e.target);
					if (!timeout) {
						dropZone.addClass('active');
					} else {
						clearTimeout(timeout);
					}
					if ($target.closest('.ss-uploadfield-dropzone').length > 0) {
						dropZone.addClass('hover');
					} else {
						dropZone.removeClass('hover');
					}
					window.dropZoneTimeout = setTimeout(function () {
						window.dropZoneTimeout = null;
						dropZone.removeClass('active hover');
					}, 100);
				});

				//disable default behaviour if file dropped in the wrong area
				$(document).bind('drop dragover', function (e){
					e.preventDefault();
				});

				this.setConfig(config);
				this.fileupload($.extend(true,
					{
						formData: function(form) {
							var idVal = $(form).find(':input[name=ID]').val();
							var data = [{name: 'SecurityID', value: $(form).find(':input[name=SecurityID]').val()}];
							if(idVal) data.push({name: 'ID', value: idVal});

							return data;
						},
						errorMessages: {
							// errorMessages for all error codes suggested from the plugin author, some will be overwritten by the config coming from php
							1: ss.i18n._t('UploadField.PHP_MAXFILESIZE'),
							2: ss.i18n._t('UploadField.HTML_MAXFILESIZE'),
							3: ss.i18n._t('UploadField.ONLYPARTIALUPLOADED'),
							4: ss.i18n._t('UploadField.NOFILEUPLOADED'),
							5: ss.i18n._t('UploadField.NOTMPFOLDER'),
							6: ss.i18n._t('UploadField.WRITEFAILED'),
							7: ss.i18n._t('UploadField.STOPEDBYEXTENSION'),
							maxFileSize: ss.i18n._t('UploadField.TOOLARGESHORT'),
							minFileSize: ss.i18n._t('UploadField.TOOSMALL'),
							acceptFileTypes: ss.i18n._t('UploadField.INVALIDEXTENSIONSHORT'),
							maxNumberOfFiles: ss.i18n._t('UploadField.MAXNUMBEROFFILESSHORT'),
							uploadedBytes: ss.i18n._t('UploadField.UPLOADEDBYTES'),
							emptyResult: ss.i18n._t('UploadField.EMPTYRESULT')
						},
						send: function(e, data) {
								if (data.context && data.dataType && data.dataType.substr(0, 6) === 'iframe') {
										// Iframe Transport does not support progress events.
										// In lack of an indeterminate progress bar, we set
										// the progress to 100%, showing the full animated bar:
										data.total = 1;
										data.loaded = 1;
										$(this).data('fileupload').options.progress(e, data);
								}
						},
						progress: function(e, data) {
									if (data.context) {
										var value = parseInt(data.loaded / data.total * 100, 10) + '%';
										data.context.find('.ss-uploadfield-item-status').html((data.total == 1)?ss.i18n._t('UploadField.LOADING'):value);
										data.context.find('.ss-uploadfield-item-progressbarvalue').css('width', value);
									}
							}
					},
					config,
					{
						fileInput: fileInput,
						dropZone: dropZone,
						form: $(fileInput).closest('form'),
						previewAsCanvas: false,
						acceptFileTypes: new RegExp(config.acceptFileTypes, 'i')
					}
				));

				if (this.data('fileupload')._isXHRUpload({multipart: true})) {
					$('.ss-uploadfield-item-uploador').hide().show();
					dropZone.hide().show();
				}


				this._super();
			},
			onunmatch: function() {
				this._super();
			},
			openSelectDialog: function(uploadedFile) {
				// Create dialog and load iframe
				var self = this, config = this.getConfig(), dialogId = 'ss-uploadfield-dialog-' + this.attr('id'), dialog = $('#' + dialogId);
				if(!dialog.length) dialog = $('<div class="ss-uploadfield-dialog" id="' + dialogId + '" />');

				// If user selected 'Choose another file', we need the ID of the file to replace
				var iframeUrl = config['urlSelectDialog'];
				var uploadedFileId = null;
				if (uploadedFile && uploadedFile.attr('data-fileid') > 0){
					uploadedFileId = uploadedFile.attr('data-fileid');
				}

				// Show dialog
				dialog.ssdialog({iframeUrl: iframeUrl, height: 550});

				// TODO Allow single-select
				dialog.find('iframe').bind('load', function(e) {
					var contents = $(this).contents(), gridField = contents.find('.ss-gridfield');
					// TODO Fix jQuery custom event bubbling across iframes on same domain
					// gridField.find('.ss-gridfield-items')).bind('selectablestop', function() {
					// });

					// Remove top margin (easier than including new selectors)
					contents.find('table.ss-gridfield').css('margin-top', 0);

					// Can't use live() in iframes...
					contents.find('input[name=action_doAttach]').unbind('click.openSelectDialog').bind('click.openSelectDialog', function() {
						// TODO Fix entwine method calls across iframe/document boundaries
						var ids = $.map(gridField.find('.ss-gridfield-item.ui-selected'), function(el) {return $(el).data('id');});
						if(ids && ids.length) self.attachFiles(ids, uploadedFileId);

						dialog.ssdialog('close');
						return false;
					});
				});
				dialog.ssdialog('open');
			},
			attachFiles: function(ids, uploadedFileId) {
				var self = this,
					config = this.getConfig(),
					indicator = $('<div class="loader" />'),
					target = (uploadedFileId) ? this.find(".ss-uploadfield-item[data-fileid='"+uploadedFileId+"']") : this.find('.ss-uploadfield-addfile');

				target.children().hide();
				target.append(indicator);

				$.ajax({
					type: "POST",
					url: config['urlAttach'],
					data: {'ids': ids},
					complete: function(xhr, status) {
						target.children().show();
						indicator.remove();
					},
					success: function(data, status, xhr) {
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
			getUploadField: function() {

				return this.parents('div.ss-upload:first');
			}
		});
		$('div.ss-upload .ss-uploadfield-files .ss-uploadfield-item').entwine({
			onadd: function() {
				this._super();
				this.closest('.ss-upload').find('.ss-uploadfield-addfile').addClass('borderTop');
			},
			onremove: function() {
				$('.ss-uploadfield-files:not(:has(.ss-uploadfield-item))').closest('.ss-upload').find('.ss-uploadfield-addfile').removeClass('borderTop');
				this._super();
			}
		});
		$('div.ss-upload .ss-uploadfield-startall').entwine({
			onclick: function(e) {
				this.closest('.ss-upload').find('.ss-uploadfield-item-start button').click();
				e.preventDefault(); // Avoid a form submit
				return false;
			}
		});
		$('div.ss-upload .ss-uploadfield-item-cancelfailed').entwine({
			onclick: function(e) {
				this.closest('.ss-uploadfield-item').remove();
				e.preventDefault(); // Avoid a form submit
				return false;
			}
		});


		$('div.ss-upload .ss-uploadfield-item-remove:not(.ui-state-disabled), .ss-uploadfield-item-delete:not(.ui-state-disabled)').entwine({
			onclick: function(e) {
				var field = this.closest('div.ss-upload'),
					config = field.getConfig('changeDetection'),
					fileupload = field.data('fileupload'),
					item = this.closest('.ss-uploadfield-item'), msg = '';

				if(this.is('.ss-uploadfield-item-delete')) {
					if(confirm(ss.i18n._t('UploadField.ConfirmDelete'))) {
						if(config.changeDetection) {
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
					// Removed files will be applied to object on save
					if(config.changeDetection) {
						this.closest('form').trigger('dirty');
					}

					if (fileupload) {
						fileupload._trigger('destroy', e, {context: item});
					}
				}

				e.preventDefault(); // Avoid a form submit
				return false;
			}
		});

		$('div.ss-upload .ss-uploadfield-item-edit-all').entwine({
			onclick: function(e) {

				if($(this).hasClass('opened')){
					$('.ss-uploadfield-item .ss-uploadfield-item-edit .toggle-details-icon.opened').each(function(i){
						$(this).closest('.ss-uploadfield-item-edit').click();
					});
					$(this).removeClass('opened').find('.toggle-details-icon').removeClass('opened');
				}else{
					$('.ss-uploadfield-item .ss-uploadfield-item-edit .toggle-details-icon').each(function(i){
						if(!$(this).hasClass('opened')){
							$(this).closest('.ss-uploadfield-item-edit').click();
						}
					});
					$(this).addClass('opened').find('.toggle-details-icon').addClass('opened');
				}

				e.preventDefault(); // Avoid a form submit
				return false;
			}
		});
		$( 'div.ss-upload:not(.disabled):not(.readonly) .ss-uploadfield-item-edit').entwine({
			onclick: function(e) {
				var self = this,
					editform = self.closest('.ss-uploadfield-item').find('.ss-uploadfield-item-editform'),
					itemInfo = editform.prev('.ss-uploadfield-item-info'),
					iframe = editform.find('iframe');

				// Ignore clicks while the iframe is loading
				if (iframe.parent().hasClass('loading')) {
					e.preventDefault();
					return false;
				}

				if (iframe.attr('src') == 'about:blank') {
					// Lazy-load the iframe on editform toggle
					iframe.attr('src', iframe.data('src'));

					// Add loading class, disable buttons while loading is in progress
					// (_prepareIframe() handles re-enabling them when appropriate)
					iframe.parent().addClass('loading');
					disabled=this.siblings();
					disabled.addClass('ui-state-disabled');
					disabled.attr('disabled', 'disabled');

					iframe.on('load', function() {
						iframe.parent().removeClass('loading');

						// This ensures we only call _prepareIframe() on load once - otherwise it'll
						// be superfluously called after clicking 'save' in the editform
						if (iframe.data('src')) {
							self._prepareIframe(iframe, editform, itemInfo);
							iframe.data('src', '');
						}
					});
				} else {
					self._prepareIframe(iframe, editform, itemInfo);
				}

				e.preventDefault(); // Avoid a form submit
				return false;
			},
			_prepareIframe: function(iframe, editform, itemInfo) {
				var disabled;

				// Mark the row as changed if any of its form fields are edited
				iframe.contents().ready(function() {
					// Need to use the iframe's own jQuery, as custom event triggers
					// (e.g. from TreeDropdownField) can't be captured by the parent jQuery object.
					var iframe_jQuery = iframe.get(0).contentWindow.jQuery;
					iframe_jQuery(iframe_jQuery.find(':input')).bind('change', function(e){
						editform.removeClass('edited');
						editform.addClass('edited');
					});
				});

				if (editform.hasClass('loading')) {
					// TODO Display loading indication, and register an event to toggle edit form
				} else {
					if(this.hasClass('ss-uploadfield-item-edit')){
						disabled=this.siblings();
					}else{
						disabled=this.find('ss-uploadfield-item-edit').siblings();
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
			fitHeight: function() {
				var iframe = this.find('iframe'),
					contents = iframe.contents().find('body'),
					bodyH = contents.find('form').outerHeight(true), // We set the height to match the form's outer height
					iframeH = bodyH + (iframe.outerHeight(true) - iframe.height()), // content's height + padding on iframe elem
					containerH = iframeH + (this.outerHeight(true) - this.height()); // iframe height + padding on container elem

				/* Set height of body except in IE8. Setting this in IE8 breaks the dropdown */
				if( ! $.browser.msie && $.browser.version.slice(0,3) != "8.0"){
					contents.find('body').css({'height': bodyH});
				}

				iframe.height(iframeH);
				this.animate({height: containerH}, 500);
			},
			toggleEditForm: function() {
				var itemInfo = this.prev('.ss-uploadfield-item-info'), status = itemInfo.find('.ss-uploadfield-item-status');

				var iframe = this.find('iframe').contents(),
					saved = iframe.find('#Form_EditForm_error');

				var text = "";

				if(this.height() === 0) {
					text = ss.i18n._t('UploadField.Editing', "Editing ...");
					this.fitHeight();
					this.addClass('opened');
					itemInfo.find('.toggle-details-icon').addClass('opened');
					status.removeClass('ui-state-success-text').removeClass('ui-state-warning-text');
					iframe.find('#Form_EditForm_action_doEdit').click(function(){
						itemInfo.find('label .name').text(iframe.find('#Name input').val());
					});
					if($('div.ss-upload  .ss-uploadfield-files .ss-uploadfield-item-actions .toggle-details-icon:not(.opened)').index() < 0){
						$('div.ss-upload .ss-uploadfield-item-edit-all').addClass('opened').find('.toggle-details-icon').addClass('opened');
					}

				} else {
					this.animate({height: 0}, 500);
					this.removeClass('opened');
					itemInfo.find('.toggle-details-icon').removeClass('opened');
					$('div.ss-upload .ss-uploadfield-item-edit-all').removeClass('opened').find('.toggle-details-icon').removeClass('opened');
					if(!this.hasClass('edited')){
						text = ss.i18n._t('UploadField.NOCHANGES', 'No Changes');
						status.addClass('ui-state-success-text');
					}else{
						if(saved.hasClass('good')){
							text = ss.i18n._t('UploadField.CHANGESSAVED', 'Changes Saved');
							this.removeClass('edited').parent('.ss-uploadfield-item').removeClass('ui-state-warning');
							status.addClass('ui-state-success-text');
						}else{
							text = ss.i18n._t('UploadField.UNSAVEDCHANGES', 'Unsaved Changes');
							this.parent('.ss-uploadfield-item').addClass('ui-state-warning');
							status.addClass('ui-state-warning-text');
						}
					}
					saved.removeClass('good').hide();
				}
				status.attr('title',text).text(text);
			}
		});
		$('div.ss-upload .ss-uploadfield-fromfiles').entwine({
			onclick: function(e) {
				this.getUploadField().openSelectDialog(this.closest('.ss-uploadfield-item'));
				e.preventDefault(); // Avoid a form submit
				return false;
			}
		});
	});
}(jQuery));
