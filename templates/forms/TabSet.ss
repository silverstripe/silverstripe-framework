<div $AttributesHTML>
	<ul>
	<% loop Tabs %>
		<li class="$FirstLast $MiddleString $extraClass"><a href="#$id" id="tab-$id">$Title</a></li>
	<% end_loop %>
	</ul>

	<% loop Tabs %>
	<div $AttributesHTML>
	<% if Tabs %>
		$FieldHolder
	<% else %>
		<% loop Fields %>
		$FieldHolder
		<% end_loop %>
	<% end_if %>
	</div>
	<% end_loop %>
</div>
