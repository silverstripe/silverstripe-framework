<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
		<% base_tag %>
		
		<title><% _t('FileIFrameField_iframe.ss.TITLE', 'Image Uploading Iframe') %></title>
	</head>
	
	<body>
		<div class="mainblock editform">
			$EditFileForm
		</div>
		
		<% if AttachedFile %>
			<div class="mainblock">
				$AttachedFile.CMSThumbnail
				
				<% if DeleteFileForm %>
					$DeleteFileForm
				<% end_if %>
			</div>
		<% end_if %>
	</body>
	
</html>
