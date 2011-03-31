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
			<div id="logInStatus">
				<a href="$ApplicationLink" title="<% _t('SSWEB','Silverstripe Website') %>">$ApplicationName</a>
				<% if CMSVersion %>-&nbsp;
				<abbr style="border-style: none" title="<% _t('APPVERSIONTEXT1',"This is the") %> $ApplicationName <% _t('APPVERSIONTEXT2',"version that you are currently running, technically it's the CVS branch") %>">$CMSVersion</abbr>
				<% end_if %>
				&nbsp;&nbsp;
				<% control CurrentMember %>
					<% _t('LOGGEDINAS','Logged in as') %> <strong><% if FirstName && Surname %>$FirstName $Surname<% else_if FirstName %>$FirstName<% else %>$Email<% end_if %></strong> | <a href="{$AbsoluteBaseURL}admin/myprofile" id="EditMemberProfile"><% _t('EDITPROFILE','Profile') %></a> | <a href="Security/logout" id="LogoutLink"><% _t('LOGOUT','Log out') %></a>
				<% end_control %>
			</div>

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
