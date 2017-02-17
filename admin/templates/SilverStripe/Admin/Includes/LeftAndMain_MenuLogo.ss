<div class="cms-logo">
    <a href="$ApplicationLink" class="cms-logo__link" target="_blank" title="$ApplicationName (Version - $CMSVersion)">
		$ApplicationName <% if $CMSVersion %><abbr class="version">$CMSVersion</abbr><% end_if %>
    </a>
    <span><a href="$BaseHref" target="_blank"><% if $SiteConfig %>$SiteConfig.Title<% else %>$ApplicationName<% end_if %></a></span>
</div>
