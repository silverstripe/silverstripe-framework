<ul class="cms-menu-list">
	<% loop $MainMenu %>
		<li class="$LinkingMode $FirstLast <% if $LinkingMode == 'link' %><% else %>opened<% end_if %>" id="Menu-$Code" title="$Title.ATT">
			<a href="$Link" $AttributesHTML>
				<span class="icon icon-16 icon-{$Icon}">&nbsp;</span>
				<span class="text">$Title</span>
			</a>
		</li>
	<% end_loop %>
</ul>
