<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
	<head>
		<% base_tag %>
		<title><% _t('Image_iframe.ss.TITLE', 'Image Uploading Iframe') %></title>
		<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7">
	</head>

	<body>
		<div class="mainblock" style="width: 290px;">
			<% if UseSimpleForm %>
			$EditImageSimpleForm
			<% else %>
			$EditImageForm
			<% end_if %>
		</div>
		
		<% if Image.ID %>
		<div class="mainblock" >
			$Image.CMSThumbnail
			<% if DeleteImageForm %>
				$DeleteImageForm
			<% end_if %>
		</div>
		<% end_if %>
		
	</body>

</html>