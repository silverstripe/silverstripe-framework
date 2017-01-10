<!DOCTYPE html>
<html lang="$Locale.RFC1766">
	<head>
	<% base_tag %>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=720, maximum-scale=1.0" />
	<title>$Title</title>
</head>
<body class="loading cms" data-frameworkpath="$ModulePath(framework)"
	data-member-tempid="$CurrentMember.TempIDHash.ATT"
>
	<% include SilverStripe\\Admin\\CMSLoadingScreen %>

	<div class="cms-container fill-width" data-layout-type="custom">
		$Menu
		$Content
		$PreviewPanel
	</div>

	$EditorToolbar

</body>
</html>
