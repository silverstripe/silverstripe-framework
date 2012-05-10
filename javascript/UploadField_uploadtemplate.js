window.tmpl.cache['ss-uploadfield-uploadtemplate'] = tmpl(
	'{% for (var i=0, files=o.files, l=files.length, file=files[0]; i<l; file=files[++i]) { %}' +
		'<li class="ss-uploadfield-item template-upload{% if (file.error) { %} ui-state-error{% } %}">' +
			'<div class="ss-uploadfield-item-preview preview"><span></span></div>' +
			'<div class="ss-uploadfield-item-info">' +
				'<label class="ss-uploadfield-item-name">' + 
					'<span class="name">{% if (file.name) { %}{%=file.name%}{% } else { %}' + ss.i18n._t('UploadField.NOFILENAME', 'Untitled') + '{% } %}</span> ' + 
					'{% if (!file.error) { %}' +
						'<div class="ss-uploadfield-item-status">0%</div>' +						
					'{% } else {  %}' +
						'<div class="ss-uploadfield-item-status ui-state-error-text">{%=o.options.errorMessages[file.error].substring(0,25) || file.error.substring(0,25)%}</div>' + 
					'{% } %}' + 
					'<div class="clear"><!-- --></div>' + 
				'</label>' +
				'<div class="ss-uploadfield-item-actions">' + 
					'{% if (!file.error) { %}' +						
						'<div class="ss-uploadfield-item-progress"><div class="ss-uploadfield-item-progressbar"><div class="ss-uploadfield-item-progressbarvalue"></div></div></div>' +
						'{% if (!o.options.autoUpload) { %}' + 
							'<div class="ss-uploadfield-item-start start"><button class="icon icon-16">' + ss.i18n._t('UploadField.START', 'Start') + '</button></div>' + 
						'{% } %}' +
					'{% } %}' + 	
					'<div class="ss-uploadfield-item-cancel cancel"><button class="icon icon-16">' + ss.i18n._t('UploadField.CANCEL', 'Cancel') + '</button></div>' +
				'</div>' +
			'</div>' +
		'</li>' + 
	'{% } %}'
);

