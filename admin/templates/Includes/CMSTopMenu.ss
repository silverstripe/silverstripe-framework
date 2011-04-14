<div class="cms-logo">
	<a href="http://www.silverstripe.org/" target="_blank">
		SilverStripe <% if CMSVersion %><abbr class="version">$CMSVersion</abbr><% end_if %>
	</a>
</div>
<div class="cms-login-status">
	<a href="Security/logout" class="logout-link"><% _t('LOGOUT','Log out') %></a>
	<% control CurrentMember %>
		<% _t('Hello','Hi') %> 
		<strong>
			<a href="{$AbsoluteBaseURL}admin/myprofile" class="profile-link">
				<% if FirstName && Surname %>$FirstName $Surname<% else_if FirstName %>$FirstName<% else %>$Email<% end_if %>
			</a>
		</strong>
	<% end_control %>
</div>
<ul class="main-menu">
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
