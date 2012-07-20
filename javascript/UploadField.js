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
			}else{
				$('.fileOverview .uploadStatus .state').text(ss.i18n._t('AssetUploadField.UPLOADINPROGRESS', 'Please wait… upload in progress'));//.show();
				$('.fileOverview button').hide();
			}

			return result;
		},
		_onAlways: function (jqXHRorResult, textStatus, jqXHRorError, options) {
			$.blueimpUI.fileupload.prototype._onAlways.call(this, jqXHRorResult, textStatus, jqXHRorError, options);
			if (this._active === 0) {
				$('.fileOverview .uploadStatus .state').text(ss.i18n._t('AssetUploadField.FILEUPLOADCOMPLETED', 'File Upload Completed!'));//.hide();
				$('.fileOverview button').show();
			}
		}		
	});


	$.entwine('ss', function($) {

		$('div.ss-upload').entwine({

			Config: null,

			onmatch: function() {
			
				if(this.is('.readonly,.disabled')) return;

				var fileInput = this.find('input');
				var dropZone = this.find('.ss-uploadfield-dropzone');
				var config = $.parseJSON(fileInput.data('config').replace(/'/g,'"'));				
				
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
							if(!idVal) {
								idVal = 0;
							}
							return [
								{name: 'SecurityID', value: $(form).find(':input[name=SecurityID]').val()},
								{name: 'ID', value: idVal}
							];
						},
						errorMessages: {
							// errorMessages for all error codes suggested from the plugin author, some will be overwritten by the config comming from php
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
						previewAsCanvas: false,
						acceptFileTypes: new RegExp(config.acceptFileTypes, 'i')
					}
				));
				if (this.data('fileupload')._isXHRUpload({multipart: true})) {
					$('.ss-uploadfield-item-uploador').show();
					dropZone.show(); // drag&drop avaliable
					
				}

				
				this._super();
			},
			onunmatch: function() {
				this._super();
			},
			openSelectDialog: function() {
				// Create dialog and load iframe
				var self = this, config = this.getConfig(), dialogId = 'ss-uploadfield-dialog-' + this.attr('id'), dialog = jQuery('#' + dialogId);
				if(!dialog.length) dialog = jQuery('<div class="ss-uploadfield-dialog" id="' + dialogId + '" />');

				// Show dialog
				dialog.ssdialog({iframeUrl: config['urlSelectDialog'], height: 550});

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
						if(ids && ids.length) self.attachFiles(ids);

						dialog.ssdialog('close');
						return false;
					});
				});
				dialog.ssdialog('open');
			},
			attachFiles: function(ids) {
				var self = this, config = this.getConfig();
				$.post(
					config['urlAttach'], 
					{'ids': ids},
					function(data, status, xhr) {
						var fn = self.fileupload('option', 'downloadTemplate');
						self.find('.ss-uploadfield-files').append(fn({
							files: data,
							formatFileSize: function (bytes) {
								if (typeof bytes !== 'number') return '';
								if (bytes >= 1000000000) return (bytes / 1000000000).toFixed(2) + ' GB';
								if (bytes >= 1000000) return (bytes / 1000000).toFixed(2) + ' MB';
								return (bytes / 1000).toFixed(2) + ' KB';
							},
							options: self.fileupload('option')
						}));
					}
				);
			}
		});
		$('div.ss-upload *').entwine({
			getUploadField: function() {
			
				return this.parents('div.ss-upload:first');
			}
		});
		$('div.ss-upload .ss-uploadfield-files .ss-uploadfield-item').entwine({
			onmatch: function() {
				this._super();
				this.closest('.ss-upload').find('.ss-uploadfield-addfile').addClass('borderTop');
			},
			onunmatch: function() {
				$('.ss-uploadfield-files:not(:has(.ss-uploadfield-item))').closest('.ss-upload').find('.ss-uploadfield-addfile').removeClass('borderTop');
				this._super();
			}
		});
		$('div.ss-upload .ss-uploadfield-startall').entwine({
			onclick: function(e) {
				this.closest('.ss-upload').find('.ss-uploadfield-item-start button').click();
				return false;
			}
		});
		$('div.ss-upload .ss-uploadfield-item-cancelfailed').entwine({
			onclick: function(e) {
				this.closest('.ss-uploadfield-item').remove();
				return false;
			}
		});


		$('div.ss-upload .ss-uploadfield-item-remove:not(.ui-state-disabled), .ss-uploadfield-item-delete:not(.ui-state-disabled)').entwine({
			onclick: function(e) {
				var fileupload = this.closest('div.ss-upload').data('fileupload'), 
					item = this.closest('.ss-uploadfield-item'), msg = '';
				
				if(this.is('.ss-uploadfield-item-delete')) msg = ss.i18n._t('UploadField.ConfirmDelete');
				if(!msg || confirm(msg)) {
					fileupload._trigger('destroy', e, {
						context: item,
						url: this.data('href'),
						type: 'get',
						dataType: fileupload.options.dataType
					});	
				}
				
				return false;
			}
		});

		$('div.ss-upload .fileOverview .ss-uploadfield-item-edit-all').entwine({
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
			} 
		});
		$('div.ss-upload .ss-uploadfield-item-edit, div.ss-upload .ss-uploadfield-item-name').entwine({
			onclick: function(e) {
				var editform = this.closest('.ss-uploadfield-item').find('.ss-uploadfield-item-editform');
				var disabled;
				var iframe = editform.find('iframe');

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
					disabled.toggleClass('ui-state-disabled');
					editform.toggleEditForm();
				}
				e.preventDefault(); // Avoid a form submit
			}
		});



		$('div.ss-upload .ss-uploadfield-item-editform').entwine({
			fitHeight: function() {
				var iframe = this.find('iframe'), padding = 32, parentPadding = 2;
				var h = iframe.contents().find('form').height() + padding;	

				if(this.hasClass('includeParent')){
					padding=0;
					parentPadding=12;
				}		
				
				/* Set height of body except in IE8. Setting this in IE8 breaks the 
				dropdown */
				if(!$.browser.msie && $.browser.version.slice(0,3) != "8.0"){					
					iframe.contents().find('body').css({'height':(h-padding)});	
				}				

				// Set iframe to match its contents height
				iframe.height(h);

				// set container to match the same height
				iframe.parent().height(h+parentPadding);
				iframe.contents().find('body form').css({'width':'98%'});

			},
			toggleEditForm: function() {
				var itemInfo = this.prev('.ss-uploadfield-item-info'), status = itemInfo.find('.ss-uploadfield-item-status');
				var iframe = this.find('iframe').contents(), saved=iframe.find('#Form_EditForm_error');
				var text="";

				if(this.height() === 0) {
					text = ss.i18n._t('UploadField.Editing', "Editing ...");
					this.fitHeight();
					itemInfo.find('.toggle-details-icon').addClass('opened');					
					status.removeClass('ui-state-success-text').removeClass('ui-state-warning-text');
					iframe.find('#Form_EditForm_action_doEdit').click(function(){
						itemInfo.find('label .name').text(iframe.find('#Name input').val());
					});	
					if($('div.ss-upload  .ss-uploadfield-files .ss-uploadfield-item-actions .toggle-details-icon:not(.opened)').index() < 0){
						$('div.ss-upload .fileOverview .ss-uploadfield-item-edit-all').addClass('opened').find('.toggle-details-icon').addClass('opened');
					}

				} else {
					this.height(0);					
					itemInfo.find('.toggle-details-icon').removeClass('opened');
					$('div.ss-upload .fileOverview .ss-uploadfield-item-edit-all').removeClass('opened').find('.toggle-details-icon').removeClass('opened');
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
		$('div.ss-upload .ss-uploadfield-item-editform iframe').entwine({
			onmatch: function() {
				// TODO entwine event binding doesn't work for iframes
				this.load(function() {
					$(this).parent().removeClass('loading');	
				});
				this._super();
			},
			onunmatch: function() {
				this._super();
			}
		});
		$('div.ss-upload .ss-uploadfield-fromfiles').entwine({
			onclick: function(e) {
				e.preventDefault();
				this.getUploadField().openSelectDialog();
			}
		});
	});
}(jQuery));
