<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<% base_tag %>
<title>$Title</title>
</head>

<body class="loading cms" lang="$Locale.RFC1766">
	
	<% include CMSLoadingScreen %>
	
	<div class="cms-container center" data-layout-type="border">
	
		$Menu

		$Content
		
		<div class="cms-preview east <% if IsPreviewExpanded %>is-expanded<% else %>is-collapsed<% end_if %>" data-layout-type="border">
			<iframe src="about:blank" class="center" name="cms-preview-iframe"></iframe>
			<div class="cms-preview-controls south"></div>
		</div>

	</div>
		
	<div id="cms-editor-dialogs">
		<% control EditorToolbar %>
			$MediaForm
			$LinkForm
		<% end_control %>
	</div>

	<!-- <div class="ss-cms-bottom-bar">
			<div class="holder">
				<div id="switchView" class="bottomTabs">
					<% if ShowSwitchView %>
						<div class="blank"> <% _t('VIEWPAGEIN','Page view:') %> </div>
						<span id="SwitchView">$SwitchView</span>
					<% end_if %>
				</div>
			</div>
		</div> -->

</body>
</html>
