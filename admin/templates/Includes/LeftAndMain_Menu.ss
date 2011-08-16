<div class="cms-menu cms-panel west">	
	<div class="cms-panel-content">

		<div class="cms-header">
			<div class="cms-logo">
				<a href="http://www.silverstripe.org/" target="_blank">
					SilverStripe <% if CMSVersion %><abbr class="version">$CMSVersion</abbr><% end_if %>
				</a>
				<span>$SiteConfig.Title</span>
			</div>
			
			<div class="cms-login-status">
				<a href="Security/logout" class="logout-link" title="<% _t('LOGOUT','Log out') %>"><% _t('LOGOUT','Log out') %></a>
				<% control CurrentMember %>
					<span>
						<% _t('Hello','Hi') %>
						<a href="{$AbsoluteBaseURL}admin/myprofile" class="profile-link">
							<% if FirstName && Surname %>$FirstName $Surname<% else_if FirstName %>$FirstName<% else %>$Email<% end_if %>
						</a>
					</span>
				<% end_control %>
			</div>
		</div>

		<ul class="cms-menu-list">
		<% control MainMenu %>
			<li class="$LinkingMode $FirstLast <% if LinkingMode == 'link' %><% else %>opened<% end_if %>" id="Menu-$Code">
				<a href="$Link">
					<span class="icon icon-16 icon-{$Code.LowerCase}">&nbsp;</span>
					<span class="text">$Title</span>
				</a>
				
				<% if Code == 'CMSMain' %>
					<ul>
						<li class="first <% if Top.class == 'CMSPageEditController' || Top.class == 'CMSMain' %>current<% end_if %>" id="Menu-CMSPageEditController"><a href="admin/page/edit/show/$Top.CurrentPageID">
							<span class="text">Content</span>
						</a></li>
						<li <% if Top.class == 'CMSPageSettingsController' %>class="current"<% end_if %> id="Menu-CMSPageSettingsController"><a href="admin/page/settings/show/$Top.CurrentPageID">
							<span class="text">Settings</span>
						</a></li>
						<li <% if Top.class == 'CMSPageReportsController' %>class="current"<% end_if %> id="Menu-CMSPageReportsController"><a href="admin/page/reports/show/$Top.CurrentPageID">
							<span class="text">Reports</span>
						</a></li>
						<li <% if Top.class == 'CMSPageHistoryController' %>class="current"<% end_if %> id="Menu-CMSPageHistoryController"><a href="admin/page/history/show/$Top.CurrentPageID">
							<span class="text">History</span>
						</a></li>
					</ul>
				<% end_if %>
			</li>
		<% end_control %>
		</ul>
	</div>
</div>