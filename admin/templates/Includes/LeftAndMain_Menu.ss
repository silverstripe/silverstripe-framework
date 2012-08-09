<div class="cms-menu cms-panel cms-panel-layout west" id="cms-menu" data-layout-type="border">
	<div class="cms-logo-header north">
		<div class="cms-logo">
			<a href="http://www.silverstripe.org/" target="_blank" title="SilverStripe (Version - $CMSVersion)">
				SilverStripe <% if CMSVersion %><abbr class="version">$CMSVersion</abbr><% end_if %>
			</a>
			<span><% if SiteConfig %>$SiteConfig.Title<% else %>$ApplicationName<% end_if %></span>
		</div>
	
		<div class="cms-login-status">
			<a href="Security/logout" class="logout-link" title="<% _t('LeftAndMain_Menu.ss.LOGOUT','Log out') %>"><% _t('LeftAndMain_Menu.ss.LOGOUT','Log out') %></a>
			<% with CurrentMember %>
				<span>
					<% _t('LeftAndMain_Menu.ss.Hello','Hi') %>
					<a href="{$AbsoluteBaseURL}admin/myprofile" class="profile-link ss-ui-dialog-link" data-popupclass="edit-profile-popup">
						<% if FirstName && Surname %>$FirstName $Surname<% else_if FirstName %>$FirstName<% else %>$Email<% end_if %>
					</a>
				</span>
			<% end_with %>
		</div>
	</div>
		
	<div class="cms-panel-content center">
		<ul class="cms-menu-list">
		<% loop MainMenu %>
			<li class="$LinkingMode $FirstLast <% if LinkingMode == 'link' %><% else %>opened<% end_if %>" id="Menu-$Code" title="$Title.ATT">
				<a href="$Link" <% if Code == 'Help' %>target="_blank"<% end_if%>>
					<span class="icon icon-16 icon-{$Code.LowerCase}">&nbsp;</span>
					<span class="text">$Title</span>
				</a>
			
				<% if Code == 'AssetAdmin' %>
					<ul>
						<li class="first <% if Top.class == 'AssetAdmin' %>current<% end_if %>" id="Menu-AssetAdmin">
							<a href="admin/assets/">
								<span class="text"><% _t('AssetAdmin.EditOrgMenu', 'Edit &amp; organize') %></span>
							</a>
						</li>
						<li class="last <% if Top.class == 'CMSFileAddController' %>current<% end_if %>" id="Menu-CMSFileAddController">
							<a href="admin/assets/add">
								<span class="text"><% _t('AssetAdmin.ADDFILES', 'Add files') %></span>
							</a>
						</li>
					</ul>
				<% end_if %>
			</li>
		<% end_loop %>
		</ul>
	</div>
		
	<div class="cms-panel-toggle south">
		<a class="toggle-expand" href="#"><span>&raquo;</span></a>
		<a class="toggle-collapse" href="#"><span>&laquo;</span></a>
	</div>
</div>
