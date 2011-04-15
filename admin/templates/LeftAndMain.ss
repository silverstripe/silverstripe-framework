<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-language" content="$i18nLocale" />
<% base_tag %>
<title>$ApplicationName | $SectionTitle</title>
</head>

<body class="loading cms $CSSClasses">
	
	<% include CMSLoadingScreen %>
	
	<div class="cms-container" data-layout="{type: 'border'}">

		$Menu

		$Content

	</div>
	
	<div id="cms-editor-dialogs">
		<% control EditorToolbar %>
			$ImageForm
			$LinkForm
			$FlashForm
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
