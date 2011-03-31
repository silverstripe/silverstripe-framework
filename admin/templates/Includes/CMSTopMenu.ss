<div class="logo">
	<a href="http://www.silverstripe.org/" target="_blank">
		SilverStripe <% if CMSVersion %><abbr class="version">$CMSVersion</abbr><% end_if %>
	</a>
</div>
<div class="login-status">
	<% control CurrentMember %>
		<% _t('LOGGEDINAS','Logged in as') %> 
		<strong><% if FirstName && Surname %>$FirstName $Surname<% else_if FirstName %>$FirstName<% else %>$Email<% end_if %></strong>
		<a href="{$AbsoluteBaseURL}admin/myprofile" class="profile-link"><% _t('EDITPROFILE','Profile') %></a>
		<a href="Security/logout" class="logout-link"><% _t('LOGOUT','Log out') %></a>
	<% end_control %>
</div>
<ul id="MainMenu">
<% control MainMenu %>
	<li class="$LinkingMode" id="Menu-$Code">
		<a href="$Link">$Title</a>
		<% if Title == 'Edit Page' %>
		<ul>
			<li><a href="#">Content</a></li>
			<li><a href="#">Settings</a></li>
			<li><a href="#">Reports</a></li>
			<li><a href="#">History</a></li>
		</ul>
		<% end_if %>
	</li>
<% end_control %>
</ul>
