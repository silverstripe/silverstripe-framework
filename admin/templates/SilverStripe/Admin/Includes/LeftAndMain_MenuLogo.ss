<div class="cms-logo">
    <a href="$ApplicationLink" target="_blank" title="$ApplicationName (Version - $CMSVersion)">
		$ApplicationName <% if $CMSVersion %><abbr class="version">$CMSVersion</abbr><% end_if %>
    </a>
    <span><% if $SiteConfig %>$SiteConfig.Title<% else %>$ApplicationName<% end_if %></span>
</div>
