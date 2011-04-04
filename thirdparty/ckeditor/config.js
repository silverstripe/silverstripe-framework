/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here. For example:
	baseHref = CKEDITOR_BASEHREF;
	config.language = 'ru';
	config.extraPlugins = 'ssimage,MediaEmbed';
	config.contentsCss = CKEDITOR_CONTENTSCSS;
	config.bodyClass = 'typography';
	config.toolbar = 'Default';

	config.toolbar_Default =
	[
	    ['Source'],
		['Cut','Copy','Paste','PasteFromWord'],
	    ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	    ['NumberedList','BulletedList','Outdent','Indent','Blockquote','RemoveFormat'],
	    ['sslink','Unlink'],
	    ['ssimage','ssflash','MediaEmbed','-','Table','HorizontalRule','SpecialChar'],
	    '/',
	    ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
	    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	    ['Styles','Format','FontSize','TextColor','BackgroundColor'],
	    ['Maximize','FitWindow','ShowBlocks']
	];
};
