/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
    config.skin = 'kama';
    config.theme = 'default';
	config.defaultLanguage = 'en';
	config.resize_enabled = false;
	config.enterMode = CKEDITOR.ENTER_BR;
	config.shiftEnterMode = CKEDITOR.ENTER_P;
	config.FontSize_sizes = '4/xx-small;8/x-small;11/small;14/medium;17/large;20/x-large;24/xx-large';
	config.tabSpaces = 4;
	config.toolbarStartupExpanded = false;
	config.removePlugins = 'elementspath';
	
	config.toolbar_Basic =
		[
			[ 'Source', '-', 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink', 'Anchor', 'Image' ]
		];

	config.toolbar_Full =
		[
			{ name: 'document', items : [ 'Source','-','Save','NewPage','DocProps','Preview','Print','-','Templates' ] },
			{ name: 'clipboard', items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
			{ name: 'editing', items : [ 'Find','Replace','-','SelectAll','-','SpellChecker', 'Scayt' ] },
			{ name: 'forms', items : [ 'Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton',
				'HiddenField' ] },
			'/',
			{ name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
			{ name: 'paragraph', items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','CreateDiv',
				'-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
			{ name: 'links', items : [ 'Link','Unlink','Anchor' ] },
			{ name: 'insert', items : [ 'Image','Flash','Table','HorizontalRule'/*,'Smiley'*/,'SpecialChar','PageBreak','Iframe' ] },
			'/',
			{ name: 'styles', items : [ 'Styles','Format','Font','FontSize' ] },
			{ name: 'colors', items : [ 'TextColor','BGColor' ] },
			{ name: 'tools', items : [ 'Maximize', 'ShowBlocks','-','About' ] }
		];
};