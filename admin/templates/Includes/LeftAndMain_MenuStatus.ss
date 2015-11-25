<div class="cms-logo">
    <a href="$ApplicationLink" target="_blank" title="$ApplicationName (Version - $CMSVersion)">
		$ApplicationName <% if $CMSVersion %><abbr class="version">$CMSVersion</abbr><% end_if %>
    </a>
    <span><% if $SiteConfig %>$SiteConfig.Title<% else %>$ApplicationName<% end_if %></span>
</div>
<div class="cms-login-status">
	<a href="$LogoutURL" class="logout-link font-icon-logout" title="<%t LeftAndMain_Menu_ss.LOGOUT 'Log out' %>"></a>
	<% with $CurrentMember %>
		<span>
			<%t LeftAndMain_Menu_ss.Hello 'Hi' %>
			<a href="{$AbsoluteBaseURL}admin/myprofile" class="profile-link">
				<% if $FirstName && $Surname %>$FirstName $Surname<% else_if $FirstName %>$FirstName<% else %>$Email<% end_if %>
			</a>
		</span>
	<% end_with %>
</div>