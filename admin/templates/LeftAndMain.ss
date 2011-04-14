<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-language" content="$i18nLocale" />
<% base_tag %>
<title>$ApplicationName | $SectionTitle</title>
</head>

<body class="loading $CSSClasses">
	
	$CMSLoadingScreen
	
	<div class="cms-container {layout: {type: 'border'}}">

		<div class="cms-menu west">
			$CMSTopMenu
		</div>

		<div class="cms-content center {layout: {type: 'border'}}" id="right">

			<div class="cms-content-header north">
				<h2>Section title</h2>
				<ul>
					<li>Tab 1</li>
					<li>Tab 2</li>
				</ul>
				<div class="cms-content-search"></div>
			</div>

			<div class="cms-content-tools west">
				$Left
			</div>
			
			<div class="cms-content-form center">
				$Right
			</div>

		</div>

	</div>
	
	<div id="contentPanel">
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
