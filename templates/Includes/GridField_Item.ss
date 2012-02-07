<tr class="ss-gridfield-item ss-gridfield-{$EvenOdd} $FirstLast" data-id="$ID">
	<% if $GridField.ExtraColumnsCount %>
		<% control Fields %>
			<td>$Value</td>
		<% end_control %>
		<td colspan="$GridField.ExtraColumnsCount" class="ss-gridfield-last"></td>
	<% else %>
		<% control Fields %>
			<td <% if FirstLast %>class="ss-gridfield-{$FirstLast}"<% end_if %>>$Value</td>
		<% end_control %>
	<% end_if %>
</tr>