<% if $UseButtonTag %>
	<button $AttributesHTML>
		<% if $ButtonContent %>$ButtonContent<% else %>$Title<% end_if %>
	</button>
<% else %>
	<input $AttributesHTML />
<% end_if %>
