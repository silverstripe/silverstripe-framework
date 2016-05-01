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
