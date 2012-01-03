<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<% base_tag %>
<title>$Title</title>
</head>

<body class="loading cms" lang="$Locale.RFC1766">
	
	<% include CMSLoadingScreen %>
	
	<div class="cms-container center" data-layout="{type: 'border'}">
	
		$Menu

		$Content
		
		<div class="cms-preview east <% if IsPreviewExpanded %>is-expanded<% else %>is-collapsed<% end_if %>" data-layout="{type: 'border'}">
			<iframe src="about:blank" class="center"></iframe>
			<div class="cms-preview-controls south"></div>
		</div>

	</div>
		
	<% cached %>
	<div id="cms-editor-dialogs">
		<% control EditorToolbar %>
			$ImageForm
			$LinkForm
			$FlashForm
		<% end_control %>
	</div>
	<% end_cached %>

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
