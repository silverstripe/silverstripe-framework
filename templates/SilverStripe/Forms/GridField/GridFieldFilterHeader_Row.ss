<% if $Fields %>
    <tr class="grid-field__filter-header grid-field__search-holder--hidden">
	   <% loop $Fields %>
	       <th class="extra">$Field</th>
	   <% end_loop %>
    </tr>
<% end_if %>
