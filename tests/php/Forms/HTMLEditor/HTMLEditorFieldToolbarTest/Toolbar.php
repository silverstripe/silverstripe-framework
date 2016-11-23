<?php

namespace SilverStripe\Forms\Tests\HTMLEditor\HTMLEditorFieldToolbarTest;

use SilverStripe\Forms\HTMLEditor\HTMLEditorField_Toolbar;

class Toolbar extends HTMLEditorField_Toolbar
{
	public function viewfile_getLocalFileByID($id)
	{
		return parent::viewfile_getLocalFileByID($id);
	}

	public function viewfile_getRemoteFileByURL($fileUrl)
	{
		return parent::viewfile_getRemoteFileByURL($fileUrl);
	}
}
