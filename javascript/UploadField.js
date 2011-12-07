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
			var top = '+=' + (firstNewFile.position().top - parseInt(firstNewFile.css('marginTop')) || 0 - parseInt(firstNewFile.css('borderTopWidth')) || 0);
			firstNewFile.offsetParent().animate({scrollTop: top}, 1000);
			return result;
		}
	});
	$.entwine('ss', function($) {
		$('div.ss-upload').entwine({
			onmatch: function() {
				var fileInput = this.find('input');
				var dropZone = this.find('.ss-uploadfield-dropzone');
				var config = $.parseJSON(fileInput.data('config').replace(/'/g,'"'));
				this.fileupload($.extend(true, 
					{
						errorMessages: {
							// errorMessages for all error codes suggested from the plugin author, some will be overwritten by the config comming from php
							1: ss.i18n._t('UploadField.PHP_MAXFILESIZE', 'File exceeds upload_max_filesize (php.ini directive)'),
							2: ss.i18n._t('UploadField.HTML_MAXFILESIZE', 'File exceeds MAX_FILE_SIZE (HTML form directive)'),
							3: ss.i18n._t('UploadField.ONLYPARTIALUPLOADED', 'File was only partially uploaded'),
							4: ss.i18n._t('UploadField.NOFILEUPLOADED', 'No File was uploaded'),
							5: ss.i18n._t('UploadField.NOTMPFOLDER', 'Missing a temporary folder'),
							6: ss.i18n._t('UploadField.WRITEFAILED', 'Failed to write file to disk'),
							7: ss.i18n._t('UploadField.STOPEDBYEXTENSION', 'File upload stopped by extension'),
							maxFileSize: ss.i18n._t('UploadField.TOOLARGE', 'Filesize is too large'),
							minFileSize: ss.i18n._t('UploadField.TOOSMALL', 'Filesize is too small'),
							acceptFileTypes: ss.i18n._t('UploadField.INVALIDEXTENSION', 'Extension is not allowed'),
							maxNumberOfFiles: ss.i18n._t('UploadField.MAXNUMBEROFFILESSIMPLE', 'Max number of files exceeded'),
							uploadedBytes: ss.i18n._t('UploadField.UPLOADEDBYTES', 'Uploaded bytes exceed file size'),
							emptyResult: ss.i18n._t('UploadField.EMPTYRESULT', 'Empty file upload result')
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
					        	data.context.find('.ss-uploadfield-item-status').html((data.total == 1)?ss.i18n._t('UploadField.LOADING', 'Loading ...'):value);
					            data.context.find('.ss-uploadfield-item-progressbarvalue').css('width', value);
					        }
					    }
					}, 
					config, 
					{
						fileInput: fileInput,
						dropZone: dropZone,
						previewAsCanvas: false,
						acceptFileTypes: new RegExp(config.acceptFileTypes, 'i')
					}
				));
				if (this.data('fileupload')._isXHRUpload({multipart: true})) {
					$('.ss-uploadfield-item-uploador').show();
					dropZone.show(); // drag&drop avaliable
				}
				this._super();
			}
		});
		$('.ss-uploadfield-files .ss-uploadfield-item').entwine({
			onmatch: function() {
				this.closest('.ss-upload').find('.ss-uploadfield-addfile').addClass('borderTop');
			},
			onunmatch: function() {
				$('.ss-uploadfield-files:not(:has(.ss-uploadfield-item))').closest('.ss-upload').find('.ss-uploadfield-addfile').removeClass('borderTop');
			}
		});
		$('.ss-uploadfield-startall').entwine({
			onclick: function(e) {
				this.closest('.ss-upload').find('.ss-uploadfield-item-start button').click();
				return false;
			}
		});
		$('.ss-uploadfield-item-cancelfailed').entwine({
			onclick: function(e) {
				this.closest('.ss-uploadfield-item').remove();
				return false;
			}
		});
		$('.ss-uploadfield-item-remove:not(.ui-state-disabled), .ss-uploadfield-item-delete:not(.ui-state-disabled)').entwine({
			onclick: function(e) {
				var fileupload = this.closest('div.ss-upload').data('fileupload');
				var item = this.closest('.ss-uploadfield-item');
				fileupload._trigger('destroy', e, {
					context: item,
					url: this.data('href'),
	                type: 'get',
	                dataType: fileupload.options.dataType
				});
				return false;
			}
		});
		$('.ss-uploadfield-item-edit').entwine({
			onclick: function(e) {
				var editform = this.closest('.ss-uploadfield-item').find('.ss-uploadfield-item-editform');
				if (editform.hasClass('loading')) {
					alert('TODO: display a nice loading thingy');
				} else {
					this.siblings().toggleClass('ui-state-disabled');
					editform.toggleEditForm();
				}
			}
		});
		$('.ss-uploadfield-item-editform').entwine({
			EditFormVisible: false,
			fitHeight: function() {
				var iframe = this.find('iframe'),
					h = iframe.contents().height() + 'px';
				iframe.css('height', h);
				return h;
			},
			showEditForm: function() {
				return this.stop().animate({height: this.fitHeight()});
			},
			hideEditFormShow: function() {
				return this.stop().animate({height: 0});
			},
			toggleEditForm: function() {
				if (this.getEditFormVisible())
					this.hideEditFormShow();
				else 
					this.showEditForm();
				this.setEditFormVisible(!this.getEditFormVisible());
			}
		});
		$('.ss-uploadfield-item-editform iframe').entwine({
			onmatch: function() {
				this.load(function() {
					$(this).parent().removeClass('loading');
				});
			}
		});
	});
}(jQuery));