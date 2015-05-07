<!DOCTYPE html>
<html>
	<head>
	<% base_tag %>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=720, maximum-scale=1.0" />
	<title>$Title</title>
</head>
<body class="loading cms" lang="$Locale.RFC1766" data-frameworkpath="$ModulePath(framework)"
	data-member-tempid="$CurrentMember.TempIDHash.ATT"
>
	<% include CMSLoadingScreen %>
	
	<div class="cms-container center" data-layout-type="custom">
		$Menu
		$Content

		<div class="cms-preview east" data-layout-type="border">
			<div class="preview-note"><span><!-- --></span><% _t('CMSPageHistoryController_versions_ss.PREVIEW','Website preview') %></div>
			<div class="preview-scroll center">
				<div class="preview-device-outer">
					<div class="preview-device-inner">
						<iframe src="about:blank" class="center" name="cms-preview-iframe"></iframe>
					</div>
				</div>
			</div>
			<div class="cms-content-controls cms-preview-controls south"></div>
		</div>
	</div>
	
	$EditorToolbar
</body>
</html>
