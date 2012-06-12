<div $AttributesHTML>
	<ul>
	<% loop Tabs %>
		<li class="$FirstLast $MiddleString $extraClass"><a href="#$id" id="tab-$id">$Title</a></li>
	<% end_loop %>
	</ul>

	<% loop Tabs %>
		<% if Tabs %>
			$FieldHolder
		<% else %>
			<div $AttributesHTML>
				<% loop Fields %>
					$FieldHolder
				<% end_loop %>
			</div>
		<% end_if %>
	<% end_loop %>
</div>
