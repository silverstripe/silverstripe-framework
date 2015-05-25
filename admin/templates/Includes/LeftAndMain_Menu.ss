<div class="cms-menu cms-panel cms-panel-layout west" id="cms-menu" data-layout-type="border">
	<div class="cms-logo-header north">
		<div class="cms-logo">
			<a href="$ApplicationLink" target="_blank" title="$ApplicationName (Version - $CMSVersion)">
				$ApplicationName <% if $CMSVersion %><abbr class="version">$CMSVersion</abbr><% end_if %>
			</a>
			<span><% if $SiteConfig %>$SiteConfig.Title<% else %>$ApplicationName<% end_if %></span>
		</div>
	
		<div class="cms-login-status">
			<a href="$LogoutURL" class="logout-link" title="<% _t('LeftAndMain_Menu_ss.LOGOUT','Log out') %>"><% _t('LeftAndMain_Menu_ss.LOGOUT','Log out') %></a>
			<% with $CurrentMember %>
				<span>
					<% _t('LeftAndMain_Menu_ss.Hello','Hi') %>
					<a href="{$AbsoluteBaseURL}admin/myprofile" class="profile-link">
						<% if $FirstName && $Surname %>$FirstName $Surname<% else_if $FirstName %>$FirstName<% else %>$Email<% end_if %>
					</a>
				</span>
			<% end_with %>
		</div>
	</div>
		
	<div class="cms-panel-content center">
		<ul class="cms-menu-list">
		<% loop $MainMenu %>
			<li class="$LinkingMode $FirstLast <% if $LinkingMode == 'link' %><% else %>opened<% end_if %>" id="Menu-$Code" title="$Title.ATT">
				<a href="$Link" $AttributesHTML>
					<span class="icon icon-16 icon-{$Code.LowerCase}">&nbsp;</span>
					<span class="text">$Title</span>
				</a>
			</li>
		<% end_loop %>
		</ul>
	</div>
		
	<div class="cms-panel-toggle south">
		<button class="sticky-toggle" type="button" title="Sticky nav">Sticky nav</button>
		<span class="sticky-status-indicator">auto</span>
		<a class="toggle-expand" href="#"><span>&raquo;</span></a>
		<a class="toggle-collapse" href="#"><span>&laquo;</span></a>
	</div>
</div>
