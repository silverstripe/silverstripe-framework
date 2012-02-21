<div class="cms-menu cms-panel cms-panel-layout west" id="cms-menu" data-layout-type="border">
	<div class="cms-logo-header north">
		<div class="cms-logo">
			<a href="http://www.silverstripe.org/" target="_blank" title="SilverStripe (Version - $CMSVersion)">
				SilverStripe <% if CMSVersion %><abbr class="version">$CMSVersion</abbr><% end_if %>
			</a>
			<span>$SiteConfig.Title</span>
		</div>
	
		<div class="cms-login-status">
			<a href="Security/logout" class="logout-link" title="<% _t('LOGOUT','Log out') %>"><% _t('LOGOUT','Log out') %></a>
			<% control CurrentMember %>
				<span>
					<% _t('Hello','Hi') %>
					<a href="{$AbsoluteBaseURL}admin/settings/myprofile" class="profile-link ss-ui-dialog-link">
						<% if FirstName && Surname %>$FirstName $Surname<% else_if FirstName %>$FirstName<% else %>$Email<% end_if %>
					</a>
				</span>
			<% end_control %>
		</div>
	</div>
		
	<div class="cms-panel-content center">
		<ul class="cms-menu-list">
		<% control MainMenu %>
			<li class="$LinkingMode $FirstLast <% if LinkingMode == 'link' %><% else %>opened<% end_if %>" id="Menu-$Code">
				<a href="$Link">
					<span class="icon icon-16 icon-{$Code.LowerCase}">&nbsp;</span>
					<span class="text">$Title</span>
				</a>
			
				<% if Code == 'CMSMain' %>
					<ul>
						<li class="first <% if Top.class == 'CMSPageEditController' || Top.class == 'CMSMain' %>current<% end_if %>" id="Menu-CMSPageEditController">
							<a href="admin/page/edit/show/$Top.CurrentPageID">
								<span class="text">Content</span>
							</a>
						</li>
						<li <% if Top.class == 'CMSPageSettingsController' %>class="current"<% end_if %> id="Menu-CMSPageSettingsController">
							<a href="admin/page/settings/show/$Top.CurrentPageID">
								<span class="text">Settings</span>
							</a>
						</li>
						<li <% if Top.class == 'CMSPageHistoryController' %>class="current"<% end_if %> id="Menu-CMSPageHistoryController">
							<a href="admin/page/history/show/$Top.CurrentPageID">
								<span class="text">History</span>
							</a>
						</li>
					</ul>
				<% end_if %>

				<% if Code == 'CMSPagesController' %>
					<ul>
						<li class="last <% if Top.class == 'CMSPagesController' %>current<% end_if %>" id="Menu-CMSPagesController">
							<a href="admin/pages/">
								<span class="text">Edit &amp; organize</span>
							</a>
						</li>
						<li class="first <% if Top.class == 'CMSPageAddController' %>current<% end_if %>" id="Menu-CMSPageAddController">
							<a href="admin/page/add/?ParentID=$Top.CurrentPageID">
								<span class="text">Add pages</span>
							</a>
						</li>
					</ul>
				<% end_if %>

				<% if Code == 'AssetAdmin' %>
					<ul>
						<li class="last <% if Top.class == 'AssetAdmin' %>current<% end_if %>" id="Menu-AssetAdmin">
							<a href="admin/assets/">
								<span class="text">Edit &amp; organize</span>
							</a>
						</li>
						<li class="first <% if Top.class == 'CMSFileAddController' %>current<% end_if %>" id="Menu-CMSFileAddController">
							<a href="admin/assets/add">
								<span class="text">Add files</span>
							</a>
						</li>
					</ul>
				<% end_if %>
			</li>
		<% end_control %>
		</ul>
	</div>
		
	<div class="cms-panel-toggle south">
		<a class="toggle-expand" href="#"><span>&raquo;</span></a>
		<a class="toggle-collapse" href="#"><span>&laquo;</span></a>
	</div>
</div>