window.tmpl.cache['ss-uploadfield-downloadtemplate'] = tmpl(
	'{% for (var i=0, files=o.files, l=files.length, file=files[0]; i<l; file=files[++i]) { %}' +
		'<li class="ss-uploadfield-item template-download{% if (file.error) { %} ui-state-error{% } %}" data-fileid="{%=file.id%}">' +
			'{% if (file.thumbnail_url) { %}' +
				'<div class="ss-uploadfield-item-preview preview"><span>' +
					'<img src="{%=file.thumbnail_url%}" alt="" />' +
				'</span></div>' +
			'{% } %}' +
			'<div class="ss-uploadfield-item-info">' +
				'{% if (!file.error) { %}' +
					'<input type="hidden" name="{%=file.fieldname%}[Files][]" value="{%=file.id%}" />' +
				'{% } %}' +
				'<label class="ss-uploadfield-item-name">' +
					'<span class="name" title="{%=file.name%}">{%=file.name%}</span> ' +
					'<span class="size">{%=o.formatFileSize(file.size)%}</span>' +
					'{% if (!file.error) { %}' +
						'<div class="ss-uploadfield-item-status ui-state-success-text" title="'+ss.i18n._t('UploadField.Uploaded', 'Uploaded')+'">'+ss.i18n._t('UploadField.Uploaded', 'Uploaded')+'</div>' +
					'{% } else {  %}' +
						'<div class="ss-uploadfield-item-status ui-state-error-text" title="{%=o.options.errorMessages[file.error] || file.error%}">{%=o.options.errorMessages[file.error] || file.error%}</div>' +
					'{% } %}' +
					'<div class="clear"><!-- --></div>' +
				'</label>' +
				'{% if (file.error) { %}' +
					'<div class="ss-uploadfield-item-actions">' +
						'<div class="ss-uploadfield-item-cancel ss-uploadfield-item-cancelfailed delete"><button type="button" class="icon icon-16" data-icon="delete" title="' + ss.i18n._t('UploadField.CANCELREMOVE', 'Cancel/Remove') + '">' + ss.i18n._t('UploadField.CANCELREMOVE', 'Cancel/Remove') + '</button></div>' +
					'</div>' +
				'{% } else { %}' +
					'<div class="ss-uploadfield-item-actions">{% print(file.buttons, true); %}</div>' +
				'{% } %}' +
			'</div>' +
			'{% if (!file.error) { %}' +
				'<div class="ss-uploadfield-item-editform"><iframe frameborder="0" data-src="{%=file.edit_url%}" src="about:blank"></iframe></div>' +
			'{% } %}' +
		'</li>' +
	'{% } %}'
);
