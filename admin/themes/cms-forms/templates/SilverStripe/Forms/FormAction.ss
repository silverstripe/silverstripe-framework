<% if $UseButtonTag %>
	<button $getAttributesHTML('class') class="btn<% if $extraClass %> $extraClass<% end_if %>">
		<% if $ButtonContent %>$ButtonContent<% else %>$Title.XML<% end_if %>
	</button>
<% else %>
	<input $getAttributesHTML('class') class="btn<% if $extraClass %> $extraClass<% end_if %>"/>
<% end_if %>
