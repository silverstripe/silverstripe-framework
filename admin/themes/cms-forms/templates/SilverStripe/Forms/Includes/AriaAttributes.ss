<% if $Message || $Description %>
	aria-describedby="<% if $Message %>message-$ID<% end_if %> <% if $Description %>describes-$ID<% end_if %>"
<% end_if %>
<% if $Title || $RightTitle %>
	aria-labelledby="<% if $Title %>title-$ID<% end_if %> <% if $RightTitle %>extra-label-$ID<% end_if %>"
<% end_if %>
