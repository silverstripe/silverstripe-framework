window.tmpl.cache['ss-uploadfield-downloadtemplate'] = tmpl(
	'{% for (var i=0, files=o.files, l=files.length, file=files[0]; i<l; file=files[++i]) { %}' +
		'<li class="ss-uploadfield-item template-download{% if (file.error) { %} ui-state-error{% } %}" data-fileid="{%=file.id%}">' + 
			'<div class="ss-uploadfield-item-preview preview"><span>' +
				'<img src="{%=file.thumbnail_url%}" alt="" />' +
			'</span></div>' +
			'<div class="ss-uploadfield-item-info">' +
				'<label class="ss-uploadfield-item-name">' + 
					'<span class="name" title="{%=file.name%}">{%=file.name%}</span> ' + 
					'{% if (!file.error) { %}' +
						'<div class="ss-uploadfield-item-status ui-state-success-text" title="'+ss.i18n._t('UploadField.Uploaded', 'Uploaded')+'">'+ss.i18n._t('UploadField.Uploaded', 'Uploaded')+'</div>' +						
					'{% } else {  %}' +
						'<div class="ss-uploadfield-item-status ui-state-error-text" title="{%=o.options.errorMessages[file.error] || file.error%}">{%=o.options.errorMessages[file.error] || file.error%}</div>' + 
					'{% } %}' + 
					'<div class="clear"><!-- --></div>' + 
				'</label>' +
				'{% if (file.error) { %}' +
					'<div class="ss-uploadfield-item-actions">' + 
						'<div class="ss-uploadfield-item-cancel ss-uploadfield-item-cancelfailed"><button class="icon icon-16">' + ss.i18n._t('UploadField.CANCEL', 'Cancel') + '</button></div>' +
					'</div>' +
				'{% } else { %}' +
					'<div class="ss-uploadfield-item-actions">{% print(file.buttons, true); %}</div>' +
				'{% } %}' + 
			'</div>' +
			'{% if (!file.error) { %}' +
				'<div class="ss-uploadfield-item-editform loading"><iframe frameborder="0" src="{%=file.edit_url%}"></iframe></div>' + 
			'{% } %}' + 
		'</li>' + 
	'{% } %}'
);