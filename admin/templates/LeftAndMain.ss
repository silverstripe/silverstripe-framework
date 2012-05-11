<!DOCTYPE html>
<html>
	<head>
	<% base_tag %>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=720, maximum-scale=1.0" />
	<title>$Title</title>
</head>
<body class="loading cms" lang="$Locale.RFC1766" data-frameworkpath="$ModulePath(framework)">
	<% include CMSLoadingScreen %>
	
	<div class="cms-container center" data-layout-type="border">
		$Menu
		$Content

		<div class="cms-preview east <% if IsPreviewExpanded %>is-expanded<% else %>is-collapsed<% end_if %>" data-layout-type="border">
			<iframe src="about:blank" class="center" name="cms-preview-iframe"></iframe>
			<div class="cms-preview-controls south"></div>
		</div>
	</div>
	
	$EditorToolbar
</body>
</html>
