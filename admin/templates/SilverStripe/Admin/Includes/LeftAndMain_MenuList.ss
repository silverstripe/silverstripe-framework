<ul class="cms-menu-list">
	<% loop $MainMenu %>
		<li class="$LinkingMode $FirstLast <% if $LinkingMode == 'link' %><% else %>opened<% end_if %>" id="Menu-$Code" title="$Title.ATT">
			<a href="$Link" $AttributesHTML>
				<% if IconClass %>
					<span class="menu__icon $IconClass"></span>
				<% else %>
					<span class="icon icon-16 icon-{$Icon}">&nbsp;</span>
				<% end_if %>
				<span class="text">$Title</span>
			</a>
		</li>
	<% end_loop %>
</ul>
