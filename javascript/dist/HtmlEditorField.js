(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.HtmlEditorField', ['./jQuery', './i18n'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('./jQuery'), require('./i18n'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery, global.i18n);
		global.ssHtmlEditorField = mod.exports;
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

	var ss = typeof window.ss !== 'undefined' ? window.ss : {};

	ss.editorWrappers = {};
	ss.editorWrappers.tinyMCE = function () {
		var editorID;

		return {
			init: function init(ID) {
				editorID = ID;

				this.create();
			},

			destroy: function destroy() {
				tinymce.EditorManager.execCommand('mceRemoveEditor', false, editorID);
			},

			getInstance: function getInstance() {
				return tinymce.EditorManager.get(editorID);
			},

			onopen: function onopen() {},

			onclose: function onclose() {},

			getConfig: function getConfig() {
				var selector = "#" + editorID,
				    config = (0, _jQuery2.default)(selector).data('config'),
				    self = this;

				config.selector = selector;

				config.setup = function (ed) {
					ed.on('change', function () {
						self.save();
					});
				};
				return config;
			},

			save: function save() {
				var instance = this.getInstance();
				instance.save();

				(0, _jQuery2.default)(instance.getElement()).trigger("change");
			},

			create: function create() {
				var config = this.getConfig();

				if (typeof config.baseURL !== 'undefined') {
					tinymce.EditorManager.baseURL = config.baseURL;
				}
				tinymce.init(config);
			},

			repaint: function repaint() {},

			isDirty: function isDirty() {
				return this.getInstance().isDirty();
			},

			getContent: function getContent() {
				return this.getInstance().getContent();
			},

			getDOM: function getDOM() {
				return this.getInstance().getElement();
			},

			getContainer: function getContainer() {
				return this.getInstance().getContainer();
			},

			getSelectedNode: function getSelectedNode() {
				return this.getInstance().selection.getNode();
			},

			selectNode: function selectNode(node) {
				this.getInstance().selection.select(node);
			},

			setContent: function setContent(html, opts) {
				this.getInstance().setContent(html, opts);
			},

			insertContent: function insertContent(html, opts) {
				this.getInstance().insertContent(html, opts);
			},

			replaceContent: function replaceContent(html, opts) {
				this.getInstance().execCommand('mceReplaceContent', false, html, opts);
			},

			insertLink: function insertLink(attrs, opts) {
				this.getInstance().execCommand("mceInsertLink", false, attrs, opts);
			},

			removeLink: function removeLink() {
				this.getInstance().execCommand('unlink', false);
			},

			cleanLink: function cleanLink(href, node) {
				var settings = this.getConfig,
				    cb = settings['urlconverter_callback'];
				if (cb) href = eval(cb + "(href, node, true);");

				if (href.match(new RegExp('^' + tinyMCE.settings['document_base_url'] + '(.*)$'))) {
					href = RegExp.$1;
				}

				if (href.match(/^javascript:\s*mctmp/)) href = '';

				return href;
			},

			createBookmark: function createBookmark() {
				return this.getInstance().selection.getBookmark();
			},

			moveToBookmark: function moveToBookmark(bookmark) {
				this.getInstance().selection.moveToBookmark(bookmark);
				this.getInstance().focus();
			},

			blur: function blur() {
				this.getInstance().selection.collapse();
			},

			addUndo: function addUndo() {
				this.getInstance().undoManager.add();
			}
		};
	};

	ss.editorWrappers['default'] = ss.editorWrappers.tinyMCE;

	_jQuery2.default.entwine('ss', function ($) {
		$('textarea.htmleditor').entwine({

			Editor: null,

			onadd: function onadd() {
				var edClass = this.data('editor') || 'default',
				    ed = ss.editorWrappers[edClass]();
				this.setEditor(ed);

				ed.init(this.attr('id'));

				this._super();
			},

			onremove: function onremove() {
				this.getEditor().destroy();
				this._super();
			},

			'from .cms-edit-form': {
				onbeforesubmitform: function onbeforesubmitform() {
					this.getEditor().save();
					this._super();
				}
			},

			openLinkDialog: function openLinkDialog() {
				this.openDialog('link');
			},

			openMediaDialog: function openMediaDialog() {
				this.openDialog('media');
			},

			openDialog: function openDialog(type) {
				var capitalize = function capitalize(text) {
					return text.charAt(0).toUpperCase() + text.slice(1).toLowerCase();
				};

				var self = this,
				    url = $('#cms-editor-dialogs').data('url' + capitalize(type) + 'form'),
				    dialog = $('.htmleditorfield-' + type + 'dialog');

				if (dialog.length) {
					dialog.getForm().setElement(this);
					dialog.html('');
					dialog.addClass('loading');
					dialog.open();
				} else {
					dialog = $('<div class="htmleditorfield-dialog htmleditorfield-' + type + 'dialog loading">');
					$('body').append(dialog);
				}

				$.ajax({
					url: url,
					complete: function complete() {
						dialog.removeClass('loading');
					},
					success: function success(html) {
						dialog.html(html);
						dialog.getForm().setElement(self);
						dialog.trigger('ssdialogopen');
					}
				});
			}
		});

		$('.htmleditorfield-dialog').entwine({
			onadd: function onadd() {
				if (!this.is('.ui-dialog-content')) {
					this.ssdialog({
						autoOpen: true,
						buttons: {
							'insert': {
								text: _i18n2.default._t('HtmlEditorField.INSERT', 'Insert'),
								'data-icon': 'accept',
								class: 'ss-ui-action-constructive media-insert',
								click: function click() {
									$(this).find('form').submit();
								}
							}
						}
					});
				}

				this._super();
			},

			getForm: function getForm() {
				return this.find('form');
			},
			open: function open() {
				this.ssdialog('open');
			},
			close: function close() {
				this.ssdialog('close');
			},
			toggle: function toggle(bool) {
				if (this.is(':visible')) this.close();else this.open();
			},
			onscroll: function onscroll() {
				this.animate({
					scrollTop: this.find('form').height()
				}, 500);
			}
		});

		$('form.htmleditorfield-form').entwine({
			Selection: null,

			Bookmark: null,

			Element: null,

			setSelection: function setSelection(node) {
				return this._super($(node));
			},

			onadd: function onadd() {
				var titleEl = this.find(':header:first');
				this.getDialog().attr('title', titleEl.text());

				this._super();
			},
			onremove: function onremove() {
				this.setSelection(null);
				this.setBookmark(null);
				this.setElement(null);

				this._super();
			},

			getDialog: function getDialog() {
				return this.closest('.htmleditorfield-dialog');
			},

			fromDialog: {
				onssdialogopen: function onssdialogopen() {
					var ed = this.getEditor();

					this.setSelection(ed.getSelectedNode());
					this.setBookmark(ed.createBookmark());

					ed.blur();

					this.find(':input:not(:submit)[data-skip-autofocus!="true"]').filter(':visible:enabled').eq(0).focus();

					this.redraw();
					this.updateFromEditor();
				},

				onssdialogclose: function onssdialogclose() {
					var ed = this.getEditor();

					ed.moveToBookmark(this.getBookmark());

					this.setSelection(null);
					this.setBookmark(null);

					this.resetFields();
				}
			},

			getEditor: function getEditor() {
				return this.getElement().getEditor();
			},

			modifySelection: function modifySelection(callback) {
				var ed = this.getEditor();

				ed.moveToBookmark(this.getBookmark());
				callback.call(this, ed);

				this.setSelection(ed.getSelectedNode());
				this.setBookmark(ed.createBookmark());

				ed.blur();
			},

			updateFromEditor: function updateFromEditor() {},
			redraw: function redraw() {},
			resetFields: function resetFields() {
				this.find('.tree-holder').empty();
			}
		});

		$('form.htmleditorfield-linkform').entwine({
			onsubmit: function onsubmit(e) {
				this.insertLink();
				this.getDialog().close();
				return false;
			},
			resetFields: function resetFields() {
				this._super();

				this[0].reset();
			},
			redraw: function redraw() {
				this._super();

				var linkType = this.find(':input[name=LinkType]:checked').val();

				this.addAnchorSelector();

				this.resetFileField();

				this.find('div.content .field').hide();
				this.find('.field[id$="LinkType"]').show();
				this.find('.field[id$="' + linkType + '_Holder"]').show();

				if (linkType == 'internal' || linkType == 'anchor') {
					this.find('.field[id$="Anchor_Holder"]').show();
				}

				if (linkType == 'email') {
					this.find('.field[id$="Subject_Holder"]').show();
				} else {
					this.find('.field[id$="TargetBlank_Holder"]').show();
				}

				if (linkType == 'anchor') {
					this.find('.field[id$="AnchorSelector_Holder"]').show();
				}
				this.find('.field[id$="Description_Holder"]').show();
			},

			getLinkAttributes: function getLinkAttributes() {
				var href,
				    target = null,
				    subject = this.find(':input[name=Subject]').val(),
				    anchor = this.find(':input[name=Anchor]').val();

				if (this.find(':input[name=TargetBlank]').is(':checked')) {
					target = '_blank';
				}

				switch (this.find(':input[name=LinkType]:checked').val()) {
					case 'internal':
						href = '[sitetree_link,id=' + this.find(':input[name=internal]').val() + ']';

						if (anchor) {
							href += '#' + anchor;
						}

						break;

					case 'anchor':
						href = '#' + anchor;
						break;

					case 'file':
						href = '[file_link,id=' + this.find('.ss-uploadfield .ss-uploadfield-item').attr('data-fileid') + ']';
						break;

					case 'email':
						href = 'mailto:' + this.find(':input[name=email]').val();
						if (subject) {
							href += '?subject=' + encodeURIComponent(subject);
						}
						target = null;
						break;

					default:
						href = this.find(':input[name=external]').val();

						if (href.indexOf('://') == -1) href = 'http://' + href;
						break;
				}

				return {
					href: href,
					target: target,
					title: this.find(':input[name=Description]').val()
				};
			},
			insertLink: function insertLink() {
				this.modifySelection(function (ed) {
					ed.insertLink(this.getLinkAttributes());
				});
			},
			removeLink: function removeLink() {
				this.modifySelection(function (ed) {
					ed.removeLink();
				});

				this.resetFileField();
				this.close();
			},

			resetFileField: function resetFileField() {
				var fileField = this.find('.ss-uploadfield[id$="file_Holder"]'),
				    fileUpload = fileField.data('fileupload'),
				    currentItem = fileField.find('.ss-uploadfield-item[data-fileid]');

				if (currentItem.length) {
					fileUpload._trigger('destroy', null, { context: currentItem });
					fileField.find('.ss-uploadfield-addfile').removeClass('borderTop');
				}
			},

			addAnchorSelector: function addAnchorSelector() {
				if (this.find(':input[name=AnchorSelector]').length) return;

				var self = this;
				var anchorSelector = $('<select id="Form_EditorToolbarLinkForm_AnchorSelector" name="AnchorSelector"></select>');
				this.find(':input[name=Anchor]').parent().append(anchorSelector);

				this.updateAnchorSelector();

				anchorSelector.change(function (e) {
					self.find(':input[name="Anchor"]').val($(this).val());
				});
			},

			getAnchors: function getAnchors() {
				var linkType = this.find(':input[name=LinkType]:checked').val();
				var dfdAnchors = $.Deferred();

				switch (linkType) {
					case 'anchor':
						var collectedAnchors = [];
						var ed = this.getEditor();


						if (ed) {
							var raw = ed.getContent().match(/\s+(name|id)\s*=\s*(["'])([^\2\s>]*?)\2|\s+(name|id)\s*=\s*([^"']+)[\s +>]/gim);
							if (raw && raw.length) {
								for (var i = 0; i < raw.length; i++) {
									var indexStart = raw[i].indexOf('id=') == -1 ? 7 : 5;
									collectedAnchors.push(raw[i].substr(indexStart).replace(/"$/, ''));
								}
							}
						}

						dfdAnchors.resolve(collectedAnchors);
						break;

					case 'internal':
						var pageId = this.find(':input[name=internal]').val();

						if (pageId) {
							$.ajax({
								url: $.path.addSearchParams(this.attr('action').replace('LinkForm', 'getanchors'), { 'PageID': parseInt(pageId) }),
								success: function success(body, status, xhr) {
									dfdAnchors.resolve($.parseJSON(body));
								},
								error: function error(xhr, status) {
									dfdAnchors.reject(xhr.responseText);
								}
							});
						} else {
							dfdAnchors.resolve([]);
						}
						break;

					default:
						dfdAnchors.reject(_i18n2.default._t('HtmlEditorField.ANCHORSNOTSUPPORTED', 'Anchors are not supported for this link type.'));
						break;
				}

				return dfdAnchors.promise();
			},

			updateAnchorSelector: function updateAnchorSelector() {
				var self = this;
				var selector = this.find(':input[name=AnchorSelector]');
				var dfdAnchors = this.getAnchors();

				selector.empty();
				selector.append($('<option value="" selected="1">' + _i18n2.default._t('HtmlEditorField.LOOKINGFORANCHORS', 'Looking for anchors...') + '</option>'));

				dfdAnchors.done(function (anchors) {
					selector.empty();
					selector.append($('<option value="" selected="1">' + _i18n2.default._t('HtmlEditorField.SelectAnchor') + '</option>'));

					if (anchors) {
						for (var j = 0; j < anchors.length; j++) {
							selector.append($('<option value="' + anchors[j] + '">' + anchors[j] + '</option>'));
						}
					}
				}).fail(function (message) {
					selector.empty();
					selector.append($('<option value="" selected="1">' + message + '</option>'));
				});

				if ($.browser.msie) selector.hide().show();
			},

			updateFromEditor: function updateFromEditor() {
				var htmlTagPattern = /<\S[^><]*>/g,
				    fieldName,
				    data = this.getCurrentLink();

				if (data) {
					for (fieldName in data) {
						var el = this.find(':input[name=' + fieldName + ']'),
						    selected = data[fieldName];

						if (typeof selected == 'string') selected = selected.replace(htmlTagPattern, '');

						if (el.is(':checkbox')) {
							el.prop('checked', selected).change();
						} else if (el.is(':radio')) {
							el.val([selected]).change();
						} else if (fieldName == 'file') {
							el = this.find(':input[name="' + fieldName + '[Uploads][]"]');

							el = el.parents('.ss-uploadfield');

							(function attach(el, selected) {
								if (!el.getConfig()) {
									setTimeout(function () {
										attach(el, selected);
									}, 50);
								} else {
									el.attachFiles([selected]);
								}
							})(el, selected);
						} else {
							el.val(selected).change();
						}
					}
				}
			},

			getCurrentLink: function getCurrentLink() {
				var selectedEl = this.getSelection(),
				    href = "",
				    target = "",
				    title = "",
				    action = "insert",
				    style_class = "";

				var linkDataSource = null;
				if (selectedEl.length) {
					if (selectedEl.is('a')) {
						linkDataSource = selectedEl;
					} else {
							linkDataSource = selectedEl = selectedEl.parents('a:first');
						}
				}
				if (linkDataSource && linkDataSource.length) this.modifySelection(function (ed) {
					ed.selectNode(linkDataSource[0]);
				});

				if (!linkDataSource.attr('href')) linkDataSource = null;

				if (linkDataSource) {
					href = linkDataSource.attr('href');
					target = linkDataSource.attr('target');
					title = linkDataSource.attr('title');
					style_class = linkDataSource.attr('class');
					href = this.getEditor().cleanLink(href, linkDataSource);
					action = "update";
				}

				if (href.match(/^mailto:(.*)$/)) {
					return {
						LinkType: 'email',
						email: RegExp.$1,
						Description: title
					};
				} else if (href.match(/^(assets\/.*)$/) || href.match(/^\[file_link\s*(?:\s*|%20|,)?id=([0-9]+)\]?(#.*)?$/)) {
					return {
						LinkType: 'file',
						file: RegExp.$1,
						Description: title,
						TargetBlank: target ? true : false
					};
				} else if (href.match(/^#(.*)$/)) {
					return {
						LinkType: 'anchor',
						Anchor: RegExp.$1,
						Description: title,
						TargetBlank: target ? true : false
					};
				} else if (href.match(/^\[sitetree_link(?:\s*|%20|,)?id=([0-9]+)\]?(#.*)?$/i)) {
					return {
						LinkType: 'internal',
						internal: RegExp.$1,
						Anchor: RegExp.$2 ? RegExp.$2.substr(1) : '',
						Description: title,
						TargetBlank: target ? true : false
					};
				} else if (href) {
					return {
						LinkType: 'external',
						external: href,
						Description: title,
						TargetBlank: target ? true : false
					};
				} else {
					return null;
				}
			}
		});

		$('form.htmleditorfield-linkform input[name=LinkType]').entwine({
			onclick: function onclick(e) {
				this.parents('form:first').redraw();
				this._super();
			},
			onchange: function onchange() {
				this.parents('form:first').redraw();

				var linkType = this.parent().find(':checked').val();
				if (linkType === 'anchor' || linkType === 'internal') {
					this.parents('form.htmleditorfield-linkform').updateAnchorSelector();
				}
				this._super();
			}
		});

		$('form.htmleditorfield-linkform input[name=internal]').entwine({
			onvalueupdated: function onvalueupdated() {
				this.parents('form.htmleditorfield-linkform').updateAnchorSelector();
				this._super();
			}
		});

		$('form.htmleditorfield-linkform :submit[name=action_remove]').entwine({
			onclick: function onclick(e) {
				this.parents('form:first').removeLink();
				this._super();
				return false;
			}
		});

		$('form.htmleditorfield-mediaform').entwine({
			toggleCloseButton: function toggleCloseButton() {
				var updateExisting = Boolean(this.find('.ss-htmleditorfield-file').length);
				this.find('.overview .action-delete')[updateExisting ? 'hide' : 'show']();
			},
			onsubmit: function onsubmit() {
				this.modifySelection(function (ed) {
					this.find('.ss-htmleditorfield-file').each(function () {
						$(this).insertHTML(ed);
					});
				});

				this.getDialog().close();
				return false;
			},
			updateFromEditor: function updateFromEditor() {
				var self = this,
				    node = this.getSelection();

				if (node.is('img')) {
					this.showFileView(node.data('url') || node.attr('src')).done(function (filefield) {
						filefield.updateFromNode(node);
						self.toggleCloseButton();
						self.redraw();
					});
				}
				this.redraw();
			},
			redraw: function redraw(updateExisting) {
				this._super();

				var node = this.getSelection(),
				    hasItems = Boolean(this.find('.ss-htmleditorfield-file').length),
				    editingSelected = node.is('img'),
				    insertingURL = this.hasClass('insertingURL'),
				    header = this.find('.header-edit');

				header[hasItems ? 'show' : 'hide']();

				this.closest('ui-dialog').find('ui-dialog-buttonpane .media-insert').button(hasItems ? 'enable' : 'disable').toggleClass('ui-state-disabled', !hasItems);

				this.find('.htmleditorfield-default-panel')[editingSelected || insertingURL ? 'hide' : 'show']();
				this.find('.htmleditorfield-web-panel')[editingSelected || !insertingURL ? 'hide' : 'show']();

				var mediaFormHeading = this.find('.htmleditorfield-mediaform-heading.insert');

				if (editingSelected) {
					mediaFormHeading.hide();
				} else if (insertingURL) {
					mediaFormHeading.show().text(_i18n2.default._t("HtmlEditorField.INSERTURL")).prepend('<button class="back-button font-icon-left-open no-text" title="' + _i18n2.default._t("HtmlEditorField.BACK") + '"></button>');

					this.find('.htmleditorfield-web-panel input.remoteurl').focus();
				} else {
					mediaFormHeading.show().text(_i18n2.default._t("HtmlEditorField.INSERTFROM")).find('.back-button').remove();
				}

				this.find('.htmleditorfield-mediaform-heading.update')[editingSelected ? 'show' : 'hide']();
				this.find('.ss-uploadfield-item-actions')[editingSelected ? 'hide' : 'show']();
				this.find('.ss-uploadfield-item-name')[editingSelected ? 'hide' : 'show']();
				this.find('.ss-uploadfield-item-preview')[editingSelected ? 'hide' : 'show']();
				this.find('.Actions .media-update')[editingSelected ? 'show' : 'hide']();
				this.find('.ss-uploadfield-item-editform').toggleEditForm(editingSelected);
				this.find('.htmleditorfield-from-cms .field.treedropdown').css('left', $('.htmleditorfield-mediaform-heading:visible').outerWidth());
				this.closest('.ui-dialog').addClass('ss-uploadfield-dropzone');
				this.closest('.ui-dialog').find('.ui-dialog-buttonpane .media-insert .ui-button-text').text([editingSelected ? _i18n2.default._t('HtmlEditorField.UPDATE', 'Update') : _i18n2.default._t('HtmlEditorField.INSERT', 'Insert')]);
			},
			resetFields: function resetFields() {
				this.find('.ss-htmleditorfield-file').remove();
				this.find('.ss-gridfield-items .ui-selected').removeClass('ui-selected');
				this.find('li.ss-uploadfield-item').remove();
				this.redraw();

				this._super();
			},
			getFileView: function getFileView(idOrUrl) {
				return this.find('.ss-htmleditorfield-file[data-id=' + idOrUrl + ']');
			},
			showFileView: function showFileView(idOrUrl) {
				var self = this,
				    params = Number(idOrUrl) == idOrUrl ? { ID: idOrUrl } : { FileURL: idOrUrl };

				var item = $('<div class="ss-htmleditorfield-file loading" />');
				this.find('.content-edit').prepend(item);

				var dfr = $.Deferred();

				$.ajax({
					url: $.path.addSearchParams(this.attr('action').replace(/MediaForm/, 'viewfile'), params),
					success: function success(html, status, xhr) {
						var newItem = $(html).filter('.ss-htmleditorfield-file');
						item.replaceWith(newItem);
						self.redraw();
						dfr.resolve(newItem);
					},
					error: function error() {
						item.remove();
						dfr.reject();
					}
				});

				return dfr.promise();
			}
		});

		$('form.htmleditorfield-mediaform div.ss-upload .upload-url').entwine({
			onclick: function onclick() {
				var form = this.closest('form');

				form.addClass('insertingURL');
				form.redraw();
			}
		});

		$('form.htmleditorfield-mediaform .htmleditorfield-mediaform-heading .back-button').entwine({
			onclick: function onclick() {
				var form = this.closest('form');

				form.removeClass('insertingURL');
				form.redraw();
			}
		});

		$('form.htmleditorfield-mediaform .ss-gridfield-items').entwine({
			onselectableselected: function onselectableselected(e, ui) {
				var form = this.closest('form'),
				    item = $(ui.selected);
				if (!item.is('.ss-gridfield-item')) return;
				form.closest('form').showFileView(item.data('id'));
				form.redraw();

				form.parent().trigger('scroll');
			},
			onselectableunselected: function onselectableunselected(e, ui) {
				var form = this.closest('form'),
				    item = $(ui.unselected);
				if (!item.is('.ss-gridfield-item')) return;
				form.getFileView(item.data('id')).remove();
				form.redraw();
			}
		});

		$('form.htmleditorfield-form.htmleditorfield-mediaform div.ss-assetuploadfield').entwine({
			onfileuploadstop: function onfileuploadstop(e) {
				var form = this.closest('form');

				var editFieldIDs = [];
				form.find('div.content-edit').find('div.ss-htmleditorfield-file').each(function () {
					editFieldIDs.push($(this).data('id'));
				});

				var uploadedFiles = $('.ss-uploadfield-files', this).children('.ss-uploadfield-item');
				uploadedFiles.each(function () {
					var uploadedID = $(this).data('fileid');
					if (uploadedID && $.inArray(uploadedID, editFieldIDs) == -1) {
						$(this).remove();
						form.showFileView(uploadedID);
					}
				});

				form.parent().trigger('scroll');

				form.redraw();
			}

		});

		$('form.htmleditorfield-form.htmleditorfield-mediaform input.remoteurl').entwine({
			onadd: function onadd() {
				this._super();
				this.validate();
			},

			onkeyup: function onkeyup() {
				this.validate();
			},

			onchange: function onchange() {
				this.validate();
			},

			getAddButton: function getAddButton() {
				return this.closest('.CompositeField').find('button.add-url');
			},

			validate: function validate() {
				var val = this.val(),
				    orig = val;

				val = $.trim(val);
				val = val.replace(/^https?:\/\//i, '');
				if (orig !== val) this.val(val);

				this.getAddButton().button(!!val ? 'enable' : 'disable');
				return !!val;
			}
		});

		$('form.htmleditorfield-form.htmleditorfield-mediaform .add-url').entwine({
			getURLField: function getURLField() {
				return this.closest('.CompositeField').find('input.remoteurl');
			},

			onclick: function onclick(e) {
				var urlField = this.getURLField(),
				    container = this.closest('.CompositeField'),
				    form = this.closest('form');

				if (urlField.validate()) {
					container.addClass('loading');
					form.showFileView('http://' + urlField.val()).done(function () {
						container.removeClass('loading');

						form.parent().trigger('scroll');
					});
					form.redraw();
				}

				return false;
			}
		});

		$('form.htmleditorfield-mediaform .ss-htmleditorfield-file').entwine({
			getAttributes: function getAttributes() {},

			getExtraData: function getExtraData() {},

			getHTML: function getHTML() {
				return $('<div>').append($('<a/>').attr({ href: this.data('url') }).text(this.find('.name').text())).html();
			},

			insertHTML: function insertHTML(ed) {
				ed.replaceContent(this.getHTML());
			},

			updateFromNode: function updateFromNode(node) {},

			updateDimensions: function updateDimensions(constrainBy, maxW, maxH) {
				var widthEl = this.find(':input[name=Width]'),
				    heightEl = this.find(':input[name=Height]'),
				    w = widthEl.val(),
				    h = heightEl.val(),
				    aspect;

				if (w && h) {
					if (constrainBy) {
						aspect = heightEl.getOrigVal() / widthEl.getOrigVal();

						if (constrainBy == 'Width') {
							if (maxW && w > maxW) w = maxW;
							h = Math.floor(w * aspect);
						} else if (constrainBy == 'Height') {
							if (maxH && h > maxH) h = maxH;
							w = Math.ceil(h / aspect);
						}
					} else {
						if (maxW && w > maxW) w = maxW;
						if (maxH && h > maxH) h = maxH;
					}

					widthEl.val(w);
					heightEl.val(h);
				}
			}
		});

		$('form.htmleditorfield-mediaform .ss-htmleditorfield-file.image').entwine({
			getAttributes: function getAttributes() {
				var width = this.find(':input[name=Width]').val(),
				    height = this.find(':input[name=Height]').val();
				return {
					'src': this.find(':input[name=URL]').val(),
					'alt': this.find(':input[name=AltText]').val(),
					'width': width ? parseInt(width, 10) : null,
					'height': height ? parseInt(height, 10) : null,
					'title': this.find(':input[name=Title]').val(),
					'class': this.find(':input[name=CSSClass]').val(),
					'data-fileid': this.find(':input[name=FileID]').val()
				};
			},
			getExtraData: function getExtraData() {
				return {
					'CaptionText': this.find(':input[name=CaptionText]').val()
				};
			},
			getHTML: function getHTML() {},

			insertHTML: function insertHTML(ed) {
				var form = this.closest('form');
				var node = form.getSelection();
				if (!ed) ed = form.getEditor();

				var attrs = this.getAttributes(),
				    extraData = this.getExtraData();

				var replacee = node && node.is('img') ? node : null;
				if (replacee && replacee.parent().is('.captionImage')) replacee = replacee.parent();

				var img = node && node.is('img') ? node : $('<img />');
				img.attr(attrs);

				var container = img.parent('.captionImage'),
				    caption = container.find('.caption');

				if (extraData.CaptionText) {
					if (!container.length) {
						container = $('<div></div>');
					}

					container.attr('class', 'captionImage ' + attrs['class']).css('width', attrs.width);

					if (!caption.length) {
						caption = $('<p class="caption"></p>').appendTo(container);
					}

					caption.attr('class', 'caption ' + attrs['class']).text(extraData.CaptionText);
				} else {
						container = caption = null;
					}

				var replacer = container ? container : img;

				if (replacee && replacee.not(replacer).length) {
					replacee.replaceWith(replacer);
				}

				if (container) {
					container.prepend(img);
				}

				if (!replacee) {
					ed.repaint();
					ed.insertContent($('<div />').append(replacer).html(), { skip_undo: 1 });
				}

				ed.addUndo();
				ed.repaint();
			},
			updateFromNode: function updateFromNode(node) {
				this.find(':input[name=AltText]').val(node.attr('alt'));
				this.find(':input[name=Title]').val(node.attr('title'));
				this.find(':input[name=CSSClass]').val(node.attr('class'));
				this.find(':input[name=Width]').val(node.width());
				this.find(':input[name=Height]').val(node.height());
				this.find(':input[name=CaptionText]').val(node.siblings('.caption:first').text());
				this.find(':input[name=FileID]').val(node.data('fileid'));
			}
		});

		$('form.htmleditorfield-mediaform .ss-htmleditorfield-file.flash').entwine({
			getAttributes: function getAttributes() {
				var width = this.find(':input[name=Width]').val(),
				    height = this.find(':input[name=Height]').val();
				return {
					'src': this.find(':input[name=URL]').val(),
					'width': width ? parseInt(width, 10) : null,
					'height': height ? parseInt(height, 10) : null,
					'data-fileid': this.find(':input[name=FileID]').val()
				};
			},
			getHTML: function getHTML() {
				var attrs = this.getAttributes();

				var el = tinyMCE.activeEditor.plugins.media.dataToImg({
					'type': 'flash',
					'width': attrs.width,
					'height': attrs.height,
					'params': { 'src': attrs.src },
					'video': { 'sources': [] }
				});

				return $('<div />').append(el).html();
			},
			updateFromNode: function updateFromNode(node) {}
		});

		$('form.htmleditorfield-mediaform .ss-htmleditorfield-file.embed').entwine({
			getAttributes: function getAttributes() {
				var width = this.find(':input[name=Width]').val(),
				    height = this.find(':input[name=Height]').val();
				return {
					'src': this.find('.thumbnail-preview').attr('src'),
					'width': width ? parseInt(width, 10) : null,
					'height': height ? parseInt(height, 10) : null,
					'class': this.find(':input[name=CSSClass]').val(),
					'alt': this.find(':input[name=AltText]').val(),
					'title': this.find(':input[name=Title]').val(),
					'data-fileid': this.find(':input[name=FileID]').val()
				};
			},
			getExtraData: function getExtraData() {
				var width = this.find(':input[name=Width]').val(),
				    height = this.find(':input[name=Height]').val();
				return {
					'CaptionText': this.find(':input[name=CaptionText]').val(),
					'Url': this.find(':input[name=URL]').val(),
					'thumbnail': this.find('.thumbnail-preview').attr('src'),
					'width': width ? parseInt(width, 10) : null,
					'height': height ? parseInt(height, 10) : null,
					'cssclass': this.find(':input[name=CSSClass]').val()
				};
			},
			getHTML: function getHTML() {
				var el,
				    attrs = this.getAttributes(),
				    extraData = this.getExtraData(),
				    imgEl = $('<img />').attr(attrs).addClass('ss-htmleditorfield-file embed');

				$.each(extraData, function (key, value) {
					imgEl.attr('data-' + key, value);
				});

				if (extraData.CaptionText) {
					el = $('<div style="width: ' + attrs['width'] + 'px;" class="captionImage ' + attrs['class'] + '"><p class="caption">' + extraData.CaptionText + '</p></div>').prepend(imgEl);
				} else {
					el = imgEl;
				}
				return $('<div />').append(el).html();
			},
			updateFromNode: function updateFromNode(node) {
				this.find(':input[name=AltText]').val(node.attr('alt'));
				this.find(':input[name=Title]').val(node.attr('title'));
				this.find(':input[name=Width]').val(node.width());
				this.find(':input[name=Height]').val(node.height());
				this.find(':input[name=Title]').val(node.attr('title'));
				this.find(':input[name=CSSClass]').val(node.data('cssclass'));
				this.find(':input[name=FileID]').val(node.data('fileid'));
			}
		});

		$('form.htmleditorfield-mediaform .ss-htmleditorfield-file .dimensions :input').entwine({
			OrigVal: null,
			onmatch: function onmatch() {
				this._super();

				this.setOrigVal(parseInt(this.val(), 10));
			},
			onunmatch: function onunmatch() {
				this._super();
			},
			onfocusout: function onfocusout(e) {
				this.closest('.ss-htmleditorfield-file').updateDimensions(this.attr('name'));
			}
		});

		$('form.htmleditorfield-mediaform .ss-uploadfield-item .ss-uploadfield-item-cancel').entwine({
			onclick: function onclick(e) {
				var form = this.closest('form'),
				    file = this.closest('ss-uploadfield-item');
				form.find('.ss-gridfield-item[data-id=' + file.data('id') + ']').removeClass('ui-selected');
				this.closest('.ss-uploadfield-item').remove();
				form.redraw();
				e.preventDefault();
			}
		});

		$('div.ss-assetuploadfield .ss-uploadfield-item-edit, div.ss-assetuploadfield .ss-uploadfield-item-name').entwine({
			getEditForm: function getEditForm() {
				return this.closest('.ss-uploadfield-item').find('.ss-uploadfield-item-editform');
			},

			fromEditForm: {
				onchange: function onchange(e) {
					var form = $(e.target);
					form.removeClass('edited');
					form.addClass('edited');
				}
			},

			onclick: function onclick(e) {
				var editForm = this.getEditForm();

				if (this.closest('.ss-uploadfield-item').hasClass('ss-htmleditorfield-file')) {
					editForm.parent('ss-uploadfield-item').removeClass('ui-state-warning');

					editForm.toggleEditForm();

					e.preventDefault();

					return false;
				}

				this._super(e);
			}
		});

		$('div.ss-assetuploadfield .ss-uploadfield-item-editform').entwine({
			toggleEditForm: function toggleEditForm(bool) {
				var itemInfo = this.prev('.ss-uploadfield-item-info'),
				    status = itemInfo.find('.ss-uploadfield-item-status');
				var text = "";

				if (bool === true || bool !== false && this.height() === 0) {
					text = _i18n2.default._t('UploadField.Editing', "Editing ...");
					this.height('auto');
					itemInfo.find('.toggle-details-icon').addClass('opened');
					status.removeClass('ui-state-success-text').removeClass('ui-state-warning-text');
				} else {
					this.height(0);
					itemInfo.find('.toggle-details-icon').removeClass('opened');
					if (!this.hasClass('edited')) {
						text = _i18n2.default._t('UploadField.NOCHANGES', 'No Changes');
						status.addClass('ui-state-success-text');
					} else {
						text = _i18n2.default._t('UploadField.CHANGESSAVED', 'Changes Made');
						this.removeClass('edited');
						status.addClass('ui-state-success-text');
					}
				}
				status.attr('title', text).text(text);
			}
		});

		$('form.htmleditorfield-mediaform .field[id$="ParentID_Holder"] .TreeDropdownField').entwine({
			onadd: function onadd() {
				this._super();

				var self = this;
				this.bind('change', function () {
					var fileList = self.closest('form').find('.ss-gridfield');
					fileList.setState('ParentID', self.getValue());
					fileList.reload();
				});
			}
		});
	});
});