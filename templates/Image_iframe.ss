<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
	<head>
		<% base_tag %>
		<title>Image Uploading Iframe</title>
	</head>

	<body>
		<% if Image.ID %>
		<div class="mainblock" >
			$Image.CMSThumbnail
		</div>
		<% end_if %>
		
		<div class="mainblock" style="width: 240px;">
			<% if UseSimpleForm %>
			$EditImageSimpleForm
			<% else %>
			$EditImageForm
			<% end_if %>
		</div>
		<% if DeleteImageForm %>
		<div class="mainblock" style="width: 150px;">
			$DeleteImageForm
		</div>
		<% end_if %>
		
	</body>

</html>