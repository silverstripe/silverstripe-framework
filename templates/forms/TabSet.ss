<div $AttributesHTML>
	<ul>
	<% control Tabs %>
		<li class="$FirstLast $MiddleString $extraClass"><a href="#$id" id="tab-$id">$Title</a></li>
	<% end_control %>
	</ul>

	<% control Tabs %>
	<div $AttributesHTML>
	<% if Tabs %>
		$FieldHolder
	<% else %>
		<% control Fields %>
		$FieldHolder
		<% end_control %>
	<% end_if %>
	</div>
	<% end_control %>
</div>