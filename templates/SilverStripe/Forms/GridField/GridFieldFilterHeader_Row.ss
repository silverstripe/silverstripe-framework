<% if $Fields %>
    <tr class="grid-field__filter-header" style="display:none;">
	   <% loop $Fields %>
	       <th class="extra">$Field</th>
	   <% end_loop %>
    </tr>
<% end_if %>
