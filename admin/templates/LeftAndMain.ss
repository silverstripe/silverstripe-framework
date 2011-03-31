<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-language" content="$i18nLocale" />
<% base_tag %>
<title>$ApplicationName | $SectionTitle</title>
</head>

<body class="stillLoading $CSSClasses">
	<div class="ss-loading-screen">
		<div class="loading-logo">
			<img class="loading-animation" src="sapphire/admin/images/spinner.gif" alt="<% _t('LOADING','Loading...',PR_HIGH) %>" />
			<noscript><p class="nojs-warning"><span class="message notice"><% _t('REQUIREJS','The CMS requires that you have JavaScript enabled.',PR_HIGH) %></span></p></noscript>
		</div>
	</div>
	
	<div class="main-menu">
		$CMSTopMenu
	</div>
	
	<div>
		$Left
	</div>
		
	<div class="right" id="right">
		$Right
	</div>

	<div id="contentPanel">
		<% control EditorToolbar %>
			$ImageForm
			$LinkForm
			$FlashForm
		<% end_control %>
	</div>
	
	<div class="ss-cms-bottom-bar">
		<div class="holder">
			<div id="switchView" class="bottomTabs">
				<% if ShowSwitchView %>
					<div class="blank"> <% _t('VIEWPAGEIN','Page view:') %> </div>
					<span id="SwitchView">$SwitchView</span>
				<% end_if %>
			</div>
		</div>
	</div>
</body>
</html>
