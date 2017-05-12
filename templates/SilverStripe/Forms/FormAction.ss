<% if $UseButtonTag %>
	<button $AttributesHTML>
		<% if $ButtonContent %>$ButtonContent<% else %><span>$Title.XML</span><% end_if %>
	</button>
<% else %>
	<input $AttributesHTML />
<% end_if %>
