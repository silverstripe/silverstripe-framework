CKEDITOR.plugins.add('ssimage',{
    init:function(a){
	
		a.addCommand('ssimage',{
				exec:function(ed){showSidePanel('ssimage', [ 'sslink', 'ssflash' ], ed);}
		});
		a.addCommand("sslink", {
			exec:function(ed){showSidePanel('sslink', [ 'ssimage', 'ssflash' ], ed);}
		});
		a.addCommand("ssflash", {
			exec:function(ed){showSidePanel('ssflash', [ 'ssimage', 'sslink' ], ed);}
		});
		a.addCommand("ssclosesidepanel", {
			exec:function(ed) {showSidePanel('', [ 'sslink', 'ssimage', 'ssflash' ]);}
		});

        a.ui.addButton('ssimage',{ 
									label:'Добавить изображение..',
									command:'ssimage',
									icon: this.path + 'images/ssimage.png'
								});
        a.ui.addButton('sslink',{
									label:'Добавить ссылку',
									command:'sslink',
									icon: this.path + 'images/sslink.png'
								});
        a.ui.addButton('ssflash',{
									label:'Добавить SWF файл..',
									command:'ssflash',
									icon: this.path + 'images/ssflash.png'
								});

    }
});

/**
* These map the action buttons to the IDs of the forms that they open/close
*/
forms = {
	'sslink' : 'Form_EditorToolbarLinkForm',
	'ssimage' : 'Form_EditorToolbarImageForm',
	'ssflash' : 'Form_EditorToolbarFlashForm'
};

/**
* Show a side panel, hiding others
* If showCommand isn't set, then this will simply hide panels
*/
function showSidePanel(showCommand, hideCommands, ed) {
	ed.ss_focus_bookmark = ed.getSelection();
	hideCommands.each(function(command) { 
		//ed.controlManager.setActive(command,false);
		Element.hide(forms[command]); 
	});

	var showForm = null;
	if(forms[showCommand]) {
		showForm = $(forms[showCommand]);
		showForm.toggle(ed);
	}

	if(!showForm || showForm.style.display == "none") {
		//ed.controlManager.setActive(showCommand, false);
		// Can't use $('contentPanel'), as its in a different window
		window.parent.document.getElementById('contentPanel').style.display = "none";
		// toggle layout panel
		jQuery('body.CMSMain').entwine('ss').getMainLayout().close('east');
	} else {
		//ed.controlManager.setActive(showCommand, true);
		window.parent.document.getElementById('contentPanel').style.display = "block";
		// toggle layout panel
		jQuery('body.CMSMain').entwine('ss').getMainLayout().resizeAll();
		jQuery('body.CMSMain').entwine('ss').getMainLayout().open('east');
	}
}